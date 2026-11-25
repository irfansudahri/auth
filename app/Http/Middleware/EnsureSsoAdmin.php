<?php

namespace App\Http\Middleware;

use App\Models\SsoSession;
use App\Services\SsoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoAdmin
{
    public function __construct(private readonly SsoService $ssoService) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var SsoSession|null $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');
        if (! $ssoSession || ! $this->ssoService->isAdminUser($ssoSession->siakad_user_id, $ssoSession->tenant_key ?? 'default')) {
            abort(403, 'Anda tidak memiliki hak akses administrator SSO.');
        }

        return $next($request);
    }
}
