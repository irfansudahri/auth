<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\SsoSession;
use App\Services\SiakadAuthService;
use App\Services\SsoService;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly SiakadAuthService $siakadAuth,
        private readonly SsoService $ssoService,
        private readonly TenantService $tenantService,
    ) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        $sessionId = $request->session()->get('sso_session_id');
        if ($sessionId && ($ssoSession = SsoSession::find($sessionId)) && $ssoSession->isValid()) {
            return redirect()->route('launcher');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_key' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $username = trim($validated['username']);
        $ip = $request->ip() ?? '0.0.0.0';
        $defaultTenant = config('sso.multi_tenant.default_tenant', 'default');
        $requestedTenant = trim((string) ($validated['tenant_key'] ?? ''));

        $ipKey = 'sso-login:ip:'.$ip;
        $userKey = 'sso-login:user:'.($requestedTenant ?: $defaultTenant).':'.strtolower($username);
        $maxUser = config('sso.login.max_attempt_user', 5);
        $maxIp = config('sso.login.max_attempt_ip', 20);
        $lockSeconds = config('sso.login.lock_minutes', 15) * 60;

        if (RateLimiter::tooManyAttempts($userKey, $maxUser) || RateLimiter::tooManyAttempts($ipKey, $maxIp)) {
            AuditLog::record('LOGIN_RATE_LIMITED', null, null, ['username' => $username], $ip, $requestedTenant ?: $defaultTenant);

            return back()
                ->withInput(['username' => $username])
                ->withErrors(['username' => 'Terlalu banyak percobaan login. Coba lagi beberapa menit lagi.']);
        }

        $tenantKey = $requestedTenant ?: $defaultTenant;
        $user = null;
        $passwordOk = false;

        if ($requestedTenant === '' && $this->tenantService->isEnabled() && config('sso.siakad.driver') === 'database') {
            $campuses = $this->tenantService->listCampuses();
            foreach ($campuses as $campus) {
                try {
                    $candidate = $this->siakadAuth->findByUsername($username, $campus->tenant_key);
                } catch (\Throwable) {
                    continue;
                }
                if ($candidate && $candidate->aktif && $this->siakadAuth->verifyPassword($validated['password'], $candidate->password)) {
                    $user = $candidate;
                    $tenantKey = $campus->tenant_key;
                    $passwordOk = true;
                    break;
                }
            }
            if (! $user) {
                // Timing-safe dummy check
                $this->siakadAuth->verifyPassword($validated['password'], null);
            }
        } else {
            $user = $this->siakadAuth->findByUsername($username, $tenantKey);
            $passwordOk = $this->siakadAuth->verifyPassword($validated['password'], $user?->password);
        }

        if (! $user || ! $user->aktif || ! $passwordOk) {
            RateLimiter::hit($userKey, $lockSeconds);
            RateLimiter::hit($ipKey, $lockSeconds);

            LoginAttempt::create([
                'tenant_key' => $tenantKey,
                'username' => $username,
                'ip' => $ip,
                'berhasil' => false,
                'waktu' => now(),
            ]);

            AuditLog::record('LOGIN_GAGAL', $user?->id, null, ['username' => $username], $ip, $tenantKey);

            return back()
                ->withInput(['username' => $username])
                ->withErrors(['username' => 'Username atau password salah.']);
        }

        $apps = $this->ssoService->getUserAppsWithLevels((int) $user->id, $tenantKey);
        if ($apps->isEmpty()) {
            AuditLog::record('LOGIN_DITOLAK_NO_ACCESS', (int) $user->id, null, ['username' => $username], $ip, $tenantKey);

            return back()
                ->withInput(['username' => $username, 'tenant_key' => $tenantKey])
                ->withErrors(['username' => 'Anda belum memiliki akses aplikasi. Hubungi administrator.']);
        }

        RateLimiter::clear($userKey);

        LoginAttempt::create([
            'tenant_key' => $tenantKey,
            'username' => $username,
            'ip' => $ip,
            'berhasil' => true,
            'waktu' => now(),
        ]);

        $request->session()->regenerate();

        $ssoSession = SsoSession::create([
            'id' => bin2hex(random_bytes(32)),
            'tenant_key' => $tenantKey,
            'siakad_user_id' => (int) $user->id,
            'username' => $user->username,
            'nama' => $user->nama,
            'ip' => $ip,
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'dibuat_pada' => now(),
            'aktivitas_terakhir' => now(),
        ]);

        $request->session()->put('sso_session_id', $ssoSession->id);

        AuditLog::record('LOGIN_OK', (int) $user->id, null, ['sso_session_id' => $ssoSession->id], $ip, $tenantKey);

        return redirect()->intended(route('launcher'));
    }

    public function logoutPrompt(Request $request): View|RedirectResponse
    {
        $sessionId = (string) $request->session()->get('sso_session_id', '');
        $sidHint = (string) $request->query('sid', '');

        if ($sessionId !== '' && $sidHint !== '' && hash_equals($sessionId, $sidHint)) {
            return $this->logout($request);
        }

        $ssoSession = $sessionId !== '' ? SsoSession::find($sessionId) : null;
        if (! $ssoSession || ! $ssoSession->isValid()) {
            return redirect()->route('login');
        }

        return view('auth.logout-confirm', [
            'ssoSession' => $ssoSession,
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $sessionId = $request->session()->get('sso_session_id');
        if ($sessionId && ($ssoSession = SsoSession::find($sessionId))) {
            $ssoSession->revoke();
            $this->ssoService->sendBackchannelLogout($ssoSession);
            AuditLog::record('LOGOUT', $ssoSession->siakad_user_id, null, ['sso_session_id' => $ssoSession->id], $request->ip());
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar dari semua aplikasi.');
    }
}
