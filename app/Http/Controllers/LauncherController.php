<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AuditLog;
use App\Models\SsoSession;
use App\Services\SsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LauncherController extends Controller
{
    public function __construct(private readonly SsoService $ssoService) {}

    public function index(Request $request): View
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');
        $apps = $this->ssoService->getUserAppsWithLevels($ssoSession->siakad_user_id);
        $isAdmin = $this->ssoService->isAdminUser($ssoSession->siakad_user_id);

        $totalLevels = $apps->sum(fn ($item) => count($item->levels));
        $activeAppSessions = $ssoSession->appSessions()->count();
        $recentLogs = AuditLog::with('app')
            ->where('siakad_user_id', $ssoSession->siakad_user_id)
            ->orderByDesc('waktu')
            ->limit(5)
            ->get();

        return view('launcher.index', [
            'ssoSession' => $ssoSession,
            'apps' => $apps,
            'isAdmin' => $isAdmin,
            'totalLevels' => $totalLevels,
            'activeAppSessions' => $activeAppSessions,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function open(Request $request, string $kode): RedirectResponse
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');

        /** @var App|null $app */
        $app = App::where('kode', $kode)->where('aktif', true)->first();
        if (! $app) {
            abort(404, 'Aplikasi tidak ditemukan atau tidak aktif.');
        }

        $levels = $this->ssoService->getUserLevelsForApp($ssoSession->siakad_user_id, $app->id);
        if ($levels === []) {
            abort(403, 'Anda tidak memiliki akses ke aplikasi ini.');
        }

        // Redirect to client app's /sso/login so the client generates its own CSRF state
        $target = rtrim($app->base_url, '/').'/sso/login';
        $returnTo = $this->ssoService->sanitizeReturnTo($request->query('return_to'));
        if ($returnTo !== null) {
            $target .= '?return_to='.urlencode($returnTo);
        }

        return redirect()->away($target);
    }

    public function authorizeApp(Request $request): RedirectResponse
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');

        $clientId = (string) $request->query('client_id', '');
        $redirectUri = (string) $request->query('redirect_uri', '');
        $state = (string) $request->query('state', '');
        $codeChallenge = $request->query('code_challenge');
        $codeChallengeMethod = $request->query('code_challenge_method');

        /** @var App|null $app */
        $app = App::where('client_id', $clientId)->where('aktif', true)->first();

        if (! $app || $redirectUri === '' || ! hash_equals($app->redirect_uri, $redirectUri)) {
            AuditLog::record('CODE_DITOLAK', $ssoSession->siakad_user_id, $app?->id, [
                'reason' => 'invalid_client_or_redirect_uri',
                'client_id' => $clientId,
            ], $request->ip());

            abort(400, 'Permintaan otorisasi tidak valid (client_id atau redirect_uri tidak cocok).');
        }

        if ($state === '' || strlen($state) < 8 || strlen($state) > 128) {
            abort(400, 'Parameter state wajib diisi (minimal 8 karakter).');
        }

        if ($codeChallenge !== null && $codeChallengeMethod !== 'S256') {
            abort(400, 'Metode PKCE yang didukung hanya S256.');
        }

        $levels = $this->ssoService->getUserLevelsForApp($ssoSession->siakad_user_id, $app->id);
        if ($levels === []) {
            AuditLog::record('CODE_DITOLAK', $ssoSession->siakad_user_id, $app->id, [
                'reason' => 'no_app_access',
            ], $request->ip());

            abort(403, 'Anda tidak memiliki akses ke aplikasi ini.');
        }

        $code = $this->ssoService->issueAuthorizationCode(
            $ssoSession,
            $app,
            $redirectUri,
            $state,
            is_string($codeChallenge) ? $codeChallenge : null,
            is_string($codeChallengeMethod) ? $codeChallengeMethod : null,
        );

        AuditLog::record('BUKA_APP', $ssoSession->siakad_user_id, $app->id, [
            'kode' => $app->kode,
        ], $request->ip());

        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return redirect()->away($redirectUri.$separator.http_build_query([
            'code' => $code,
            'state' => $state,
        ]));
    }
}
