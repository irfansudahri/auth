<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\SsoSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppManagementController extends Controller
{
    public function index(Request $request): View
    {
        $apps = App::orderBy('nama')->get();

        return view('admin.apps', [
            'ssoSession' => $request->attributes->get('sso_session'),
            'apps' => $apps,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:apps,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:200'],
            'ikon' => ['nullable', 'string', 'max:50'],
            'base_url' => ['required', 'url', 'max:255'],
            'redirect_uri' => ['required', 'url', 'max:255'],
            'backchannel_logout_uri' => ['nullable', 'url', 'max:255'],
            'ip_allowlist' => ['nullable', 'string', 'max:500'],
        ]);

        $clientId = 'app-'.Str::slug($validated['kode']).'-'.bin2hex(random_bytes(4));
        $plainSecret = bin2hex(random_bytes(32));
        $plainHmac = bin2hex(random_bytes(32));

        $app = App::create([
            'kode' => strtolower($validated['kode']),
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'ikon' => $validated['ikon'] ?? 'cog',
            'base_url' => rtrim($validated['base_url'], '/'),
            'redirect_uri' => $validated['redirect_uri'],
            'backchannel_logout_uri' => $validated['backchannel_logout_uri'] ?? null,
            'client_id' => $clientId,
            'client_secret_hash' => password_hash($plainSecret, PASSWORD_BCRYPT),
            'hmac_secret_encrypted' => $plainHmac,
            'ip_allowlist' => $validated['ip_allowlist'] ?? null,
            'aktif' => true,
        ]);

        AuditLog::record('APP_DIBUAT', $ssoSession->siakad_user_id, $app->id, [
            'kode' => $app->kode,
            'client_id' => $clientId,
        ], $request->ip());

        return redirect()->route('admin.apps.index')->with('new_credentials', [
            'nama' => $app->nama,
            'client_id' => $clientId,
            'client_secret' => $plainSecret,
            'hmac_secret' => $plainHmac,
        ]);
    }

    public function toggle(Request $request, App $app): RedirectResponse
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');

        $before = $app->aktif;
        $app->update(['aktif' => ! $before]);

        AuditLog::record('APP_DIUBAH', $ssoSession->siakad_user_id, $app->id, [
            'kode' => $app->kode,
            'aktif_sebelum' => $before,
            'aktif_sesudah' => $app->aktif,
        ], $request->ip());

        return back()->with('status', "Status aplikasi {$app->nama} diperbarui.");
    }

    public function rotateSecret(Request $request, App $app): RedirectResponse
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');

        $plainSecret = bin2hex(random_bytes(32));
        $plainHmac = bin2hex(random_bytes(32));

        $app->update([
            'client_secret_hash' => password_hash($plainSecret, PASSWORD_BCRYPT),
            'hmac_secret_encrypted' => $plainHmac,
        ]);

        AuditLog::record('SECRET_DIROTASI', $ssoSession->siakad_user_id, $app->id, [
            'kode' => $app->kode,
        ], $request->ip());

        return redirect()->route('admin.apps.index')->with('new_credentials', [
            'nama' => $app->nama.' (Rotasi Secret)',
            'client_id' => $app->client_id,
            'client_secret' => $plainSecret,
            'hmac_secret' => $plainHmac,
        ]);
    }
}
