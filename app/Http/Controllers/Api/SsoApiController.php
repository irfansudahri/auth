<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppSession;
use App\Models\SsoSession;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SsoApiController extends Controller
{
    public function __construct(private readonly SsoService $ssoService) {}

    public function verify(Request $request): JsonResponse
    {
        $app = $this->authenticateClient($request);
        if ($app instanceof JsonResponse) {
            return $app;
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'code_verifier' => ['nullable', 'string'],
        ]);

        try {
            $payload = $this->ssoService->exchangeCode(
                $app,
                $validated['code'],
                $validated['redirect_uri'],
                $validated['code_verifier'] ?? null,
                $request->ip()
            );

            return response()->json($payload);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function introspect(Request $request): JsonResponse
    {
        $app = $this->authenticateClient($request);
        if ($app instanceof JsonResponse) {
            return $app;
        }

        $validated = $request->validate([
            'sso_session_id' => ['required', 'string', 'size:64'],
        ]);

        /** @var SsoSession|null $ssoSession */
        $ssoSession = SsoSession::find($validated['sso_session_id']);
        if (! $ssoSession || ! $ssoSession->isValid()) {
            return response()->json([
                'aktif' => false,
                'levels' => [],
            ]);
        }

        $tenantKey = $ssoSession->tenant_key ?? 'default';
        $levels = $this->ssoService->getUserLevelsForApp($ssoSession->siakad_user_id, $app->id, $tenantKey);

        return response()->json([
            'aktif' => $levels !== [],
            'tenant_key' => $tenantKey,
            'levels' => $levels,
        ]);
    }

    public function registerSession(Request $request): JsonResponse
    {
        $app = $this->authenticateClient($request);
        if ($app instanceof JsonResponse) {
            return $app;
        }

        $validated = $request->validate([
            'sso_session_id' => ['required', 'string', 'size:64'],
            'sid_aplikasi' => ['required', 'string', 'max:128'],
        ]);

        /** @var SsoSession|null $ssoSession */
        $ssoSession = SsoSession::find($validated['sso_session_id']);
        if (! $ssoSession || ! $ssoSession->isValid()) {
            return response()->json(['error' => 'SSO session is not active.'], 401);
        }

        AppSession::firstOrCreate([
            'sso_session_id' => $ssoSession->id,
            'app_id' => $app->id,
            'sid_aplikasi' => $validated['sid_aplikasi'],
        ], [
            'dibuat_pada' => now(),
        ]);

        return response()->json(['registered' => true]);
    }

    private function authenticateClient(Request $request): App|JsonResponse
    {
        $clientId = (string) $request->input('client_id', '');
        $clientSecret = (string) $request->input('client_secret', '');

        if ($clientId === '' || $clientSecret === '') {
            return response()->json(['error' => 'Missing client credentials.'], 401);
        }

        /** @var App|null $app */
        $app = App::where('client_id', $clientId)->where('aktif', true)->first();
        if (! $app) {
            return response()->json(['error' => 'Invalid client_id.'], 401);
        }

        // Check IP allowlist before expensive password_verify()
        if (! $app->isIpAllowed((string) $request->ip())) {
            return response()->json(['error' => 'Client IP is not allowlisted.'], 403);
        }

        if (! $app->verifySecret($clientSecret)) {
            return response()->json(['error' => 'Invalid client_secret.'], 401);
        }

        return $app;
    }
}
