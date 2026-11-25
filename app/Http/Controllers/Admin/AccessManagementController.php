<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\SsoSession;
use App\Models\UserAppLevel;
use App\Services\SiakadAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessManagementController extends Controller
{
    public function __construct(private readonly SiakadAuthService $siakadAuth) {}

    public function index(Request $request): View
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');
        $tenantKey = $request->query('tenant_key', $ssoSession->tenant_key ?? 'default');

        $query = trim((string) $request->query('q', ''));
        $selectedUserId = $request->query('user_id') ? (int) $request->query('user_id') : null;

        $users = $this->siakadAuth->searchUsers($query, 20, $tenantKey);
        $selectedUser = $selectedUserId ? $this->siakadAuth->findById($selectedUserId, $tenantKey) : $users->first();

        $apps = App::where('aktif', true)->orderBy('nama')->get();
        $userLevels = $selectedUser
            ? UserAppLevel::with('app')->where('tenant_key', $tenantKey)->where('siakad_user_id', $selectedUser->id)->orderBy('app_id')->get()
            : collect();

        return view('admin.access', [
            'ssoSession' => $ssoSession,
            'tenantKey' => $tenantKey,
            'query' => $query,
            'users' => $users,
            'selectedUser' => $selectedUser,
            'apps' => $apps,
            'userLevels' => $userLevels,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');

        $validated = $request->validate([
            'tenant_key' => ['nullable', 'string', 'max:100'],
            'siakad_user_id' => ['required', 'integer', 'min:1'],
            'app_id' => ['required', 'integer', 'exists:apps,id'],
            'level' => ['required', 'string', 'max:50'],
            'berlaku_sampai' => ['nullable', 'date'],
        ]);

        $tenantKey = trim((string) ($validated['tenant_key'] ?? '')) ?: ($ssoSession->tenant_key ?? 'default');
        $levelName = strtolower(trim($validated['level']));

        $existing = UserAppLevel::where('tenant_key', $tenantKey)
            ->where('siakad_user_id', $validated['siakad_user_id'])
            ->where('app_id', $validated['app_id'])
            ->where('level', $levelName)
            ->first();

        $before = $existing?->only(['aktif', 'berlaku_sampai']);

        $record = UserAppLevel::updateOrCreate(
            [
                'tenant_key' => $tenantKey,
                'siakad_user_id' => $validated['siakad_user_id'],
                'app_id' => $validated['app_id'],
                'level' => $levelName,
            ],
            [
                'aktif' => true,
                'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
                'dibuat_oleh' => $ssoSession->siakad_user_id,
            ]
        );

        AuditLog::record('HAK_DIUBAH', $ssoSession->siakad_user_id, (int) $validated['app_id'], [
            'target_user_id' => (int) $validated['siakad_user_id'],
            'level' => $levelName,
            'sebelum' => $before,
            'sesudah' => $record->only(['aktif', 'berlaku_sampai']),
        ], $request->ip());

        return redirect()
            ->route('admin.access.index', ['user_id' => $validated['siakad_user_id']])
            ->with('status', "Hak akses '{$levelName}' berhasil disimpan.");
    }

    public function toggle(Request $request, UserAppLevel $userAppLevel): RedirectResponse
    {
        /** @var SsoSession $ssoSession */
        $ssoSession = $request->attributes->get('sso_session');

        $before = $userAppLevel->aktif;
        $userAppLevel->update(['aktif' => ! $before]);

        AuditLog::record('HAK_DIUBAH', $ssoSession->siakad_user_id, $userAppLevel->app_id, [
            'target_user_id' => $userAppLevel->siakad_user_id,
            'level' => $userAppLevel->level,
            'sebelum' => ['aktif' => $before],
            'sesudah' => ['aktif' => $userAppLevel->aktif],
        ], $request->ip());

        return back()->with('status', 'Status hak akses diperbarui.');
    }

    public function auditLogs(Request $request): View
    {
        $aksi = trim((string) $request->query('aksi', ''));

        $logs = AuditLog::with('app')
            ->when($aksi !== '', fn ($q) => $q->where('aksi', $aksi))
            ->orderByDesc('waktu')
            ->limit(100)
            ->get();

        $failedAttempts = LoginAttempt::where('berhasil', false)
            ->orderByDesc('waktu')
            ->limit(30)
            ->get();

        $aksiTypes = AuditLog::select('aksi')->distinct()->orderBy('aksi')->pluck('aksi');

        return view('admin.audit', [
            'ssoSession' => $request->attributes->get('sso_session'),
            'logs' => $logs,
            'failedAttempts' => $failedAttempts,
            'aksi' => $aksi,
            'aksiTypes' => $aksiTypes,
        ]);
    }
}
