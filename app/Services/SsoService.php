<?php

namespace App\Services;

use App\Models\App;
use App\Models\AuditLog;
use App\Models\SsoCode;
use App\Models\SsoSession;
use App\Models\UserAppLevel;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SsoService
{
    public function getActiveApps(): Collection
    {
        return Cache::remember('sso:active_apps', 300, function () {
            return App::where('aktif', true)->orderBy('nama')->get();
        });
    }

    public function getUserAppsWithLevels(int $siakadUserId, string $tenantKey = 'default'): Collection
    {
        $levels = UserAppLevel::with('app')
            ->where('tenant_key', $tenantKey)
            ->where('siakad_user_id', $siakadUserId)
            ->activeAndValid()
            ->whereHas('app', fn ($q) => $q->where('aktif', true))
            ->get();

        return $levels->groupBy('app_id')->map(function (Collection $group) {
            /** @var App $app */
            $app = $group->first()->app;

            return (object) [
                'app' => $app,
                'levels' => $group->pluck('level')->values()->all(),
            ];
        })->values();
    }

    public function getUserLevelsForApp(int $siakadUserId, int $appId, string $tenantKey = 'default'): array
    {
        return UserAppLevel::where('tenant_key', $tenantKey)
            ->where('siakad_user_id', $siakadUserId)
            ->where('app_id', $appId)
            ->activeAndValid()
            ->pluck('level')
            ->values()
            ->all();
    }

    public function isAdminUser(int $siakadUserId, string $tenantKey = 'default'): bool
    {
        $adminApp = App::where('kode', config('sso.admin_app_code', 'auth'))
            ->where('aktif', true)
            ->first();

        if (! $adminApp) {
            return false;
        }

        $levels = $this->getUserLevelsForApp($siakadUserId, $adminApp->id, $tenantKey);

        return count(array_intersect($levels, config('sso.admin_levels', ['superadmin', 'admin']))) > 0;
    }

    public function issueAuthorizationCode(
        SsoSession $session,
        App $app,
        string $redirectUri,
        string $state,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = null
    ): string {
        $plainCode = bin2hex(random_bytes(32));

        SsoCode::create([
            'code_hash' => hash('sha256', $plainCode),
            'tenant_key' => $session->tenant_key ?? 'default',
            'siakad_user_id' => $session->siakad_user_id,
            'app_id' => $app->id,
            'sso_session_id' => $session->id,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'expired_at' => now()->addSeconds(config('sso.code_ttl', 60)),
        ]);

        return $plainCode;
    }

    /**
     * Atomically exchange an authorization code for user identity and app-scoped levels.
     *
     * @throws RuntimeException
     */
    public function exchangeCode(
        App $app,
        string $plainCode,
        string $redirectUri,
        ?string $codeVerifier = null,
        ?string $ip = null
    ): array {
        $codeHash = hash('sha256', $plainCode);

        $result = DB::transaction(function () use ($app, $codeHash, $redirectUri, $codeVerifier) {
            /** @var SsoCode|null $ssoCode */
            $ssoCode = SsoCode::where('code_hash', $codeHash)
                ->lockForUpdate()
                ->first();

            if (! $ssoCode) {
                return ['error' => 'Invalid authorization code.', 'aksi' => 'CODE_DITOLAK', 'user_id' => null, 'detail' => ['reason' => 'not_found']];
            }

            if ($ssoCode->used_at !== null) {
                return [
                    'error' => 'Authorization code has already been used.',
                    'aksi' => 'CODE_REPLAY_DETECTED',
                    'user_id' => $ssoCode->siakad_user_id,
                    'revoke_session_id' => $ssoCode->sso_session_id,
                    'detail' => ['sso_session_id' => $ssoCode->sso_session_id],
                ];
            }

            if ($ssoCode->app_id !== $app->id || ! hash_equals($ssoCode->redirect_uri, $redirectUri)) {
                return ['error' => 'Authorization code mismatch.', 'aksi' => 'CODE_DITOLAK', 'user_id' => $ssoCode->siakad_user_id, 'detail' => ['reason' => 'client_or_redirect_mismatch']];
            }

            if ($ssoCode->expired_at->isPast()) {
                return ['error' => 'Authorization code expired.', 'aksi' => 'CODE_DITOLAK', 'user_id' => $ssoCode->siakad_user_id, 'detail' => ['reason' => 'expired']];
            }

            if ($ssoCode->code_challenge !== null) {
                if (! $this->verifyPkce($ssoCode->code_challenge, $ssoCode->code_challenge_method, $codeVerifier)) {
                    return ['error' => 'PKCE verification failed.', 'aksi' => 'CODE_DITOLAK', 'user_id' => $ssoCode->siakad_user_id, 'detail' => ['reason' => 'pkce_failed']];
                }
            }

            $ssoCode->update(['used_at' => now()]);

            /** @var SsoSession|null $session */
            $session = $ssoCode->ssoSession;
            if (! $session || ! $session->isValid()) {
                return ['error' => 'SSO session is no longer active.', 'aksi' => 'CODE_DITOLAK', 'user_id' => $ssoCode->siakad_user_id, 'detail' => ['reason' => 'session_inactive']];
            }

            $tenantKey = $session->tenant_key ?? 'default';
            $levels = $this->getUserLevelsForApp($ssoCode->siakad_user_id, $app->id, $tenantKey);
            if ($levels === []) {
                return ['error' => 'User has no active access to this application.', 'aksi' => 'CODE_DITOLAK', 'user_id' => $ssoCode->siakad_user_id, 'detail' => ['reason' => 'no_access']];
            }

            return [
                'ok' => true,
                'payload' => [
                    'tenant_key' => $tenantKey,
                    'siakad_user_id' => $ssoCode->siakad_user_id,
                    'username' => $session->username,
                    'nama' => $session->nama,
                    'levels' => $levels,
                    'sso_session_id' => $session->id,
                    'expires_in' => max(0, config('sso.session_max_ttl', 28800) - (int) $session->dibuat_pada->diffInSeconds(now())),
                ],
            ];
        });

        if (! ($result['ok'] ?? false)) {
            if (! empty($result['revoke_session_id'])) {
                SsoSession::where('id', $result['revoke_session_id'])->update(['dicabut_pada' => now()]);
            }

            AuditLog::record($result['aksi'], $result['user_id'], $app->id, $result['detail'], $ip);

            throw new RuntimeException($result['error']);
        }

        $payload = $result['payload'];
        AuditLog::record('CODE_DITUKAR', $payload['siakad_user_id'], $app->id, [
            'levels_count' => count($payload['levels']),
        ], $ip);

        return $payload;
    }

    /**
     * Send concurrent HMAC-signed back-channel logout requests to all opened client apps.
     */
    public function sendBackchannelLogout(SsoSession $session): void
    {
        $appSessions = $session->appSessions()->with('app')->get()->filter(function ($item) {
            return $item->app
                && $item->app->aktif
                && ! empty($item->app->backchannel_logout_uri)
                && ! empty($item->app->hmac_secret_encrypted);
        });

        if ($appSessions->isEmpty()) {
            return;
        }

        $timeout = config('sso.backchannel.timeout_seconds', 3);
        $timestamp = now()->timestamp;

        Http::pool(function (Pool $pool) use ($appSessions, $timeout, $timestamp) {
            foreach ($appSessions as $appSession) {
                $payload = json_encode([
                    'sid' => $appSession->sid_aplikasi,
                    'waktu' => $timestamp,
                    'jti' => bin2hex(random_bytes(16)),
                ], JSON_THROW_ON_ERROR);

                $signature = hash_hmac('sha256', $payload, $appSession->app->hmac_secret_encrypted);

                $pool->as((string) $appSession->id)
                    ->timeout($timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Signature' => $signature,
                        'X-Timestamp' => (string) $timestamp,
                    ])
                    ->withBody($payload, 'application/json')
                    ->post($appSession->app->backchannel_logout_uri);
            }
        });
    }

    /**
     * Validate a relative return_to path to prevent open redirect vulnerabilities.
     */
    public function sanitizeReturnTo(?string $returnTo): ?string
    {
        if ($returnTo === null || $returnTo === '') {
            return null;
        }

        // Must start with a single '/' and not '//' or '/\'
        if (! str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//') || str_starts_with($returnTo, '/\\')) {
            return null;
        }

        return $returnTo;
    }

    private function verifyPkce(string $challenge, ?string $method, ?string $verifier): bool
    {
        if ($verifier === null || $verifier === '') {
            return false;
        }

        if ($method === 'S256') {
            $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

            return hash_equals($challenge, $computed);
        }

        return false;
    }
}
