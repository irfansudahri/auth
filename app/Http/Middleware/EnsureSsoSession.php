<?php

namespace App\Http\Middleware;

use App\Models\SsoSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = $request->session()->get('sso_session_id');
        if (! $sessionId) {
            return $this->redirectToLogin($request);
        }

        /** @var SsoSession|null $ssoSession */
        $ssoSession = SsoSession::find($sessionId);
        if (! $ssoSession || ! $ssoSession->isValid()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->redirectToLogin($request, 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        // Throttle DB heartbeat update to once every 60 seconds
        if ($ssoSession->aktivitas_terakhir->diffInSeconds(now()) >= 60) {
            $ssoSession->update(['aktivitas_terakhir' => now()]);
        }

        $request->attributes->set('sso_session', $ssoSession);

        return $next($request);
    }

    private function redirectToLogin(Request $request, ?string $message = null): Response
    {
        if ($request->isMethod('GET') && ! $request->is('logout')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        $redirect = redirect()->route('login');

        return $message ? $redirect->with('status', $message) : $redirect;
    }
}
