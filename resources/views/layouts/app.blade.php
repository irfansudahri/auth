<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal SSO')</title>
    <link rel="stylesheet" href="{{ asset('css/tailadmin.css') }}?v={{ filemtime(public_path('css/tailadmin.css')) }}">
</head>
<body>
@if(isset($ssoSession))
<div class="ta-shell" id="taShell">
    <div class="ta-backdrop" id="taBackdrop"></div>
    <aside class="ta-sidebar" id="taSidebar">
        <div class="ta-sidebar-brand" style="justify-content: space-between;">
            <a href="{{ route('launcher') }}" style="display:flex; align-items:center; gap:0.75rem;">
                <span class="ta-brand-mark">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l7 3v5c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-3z"/>
                        <path d="M9.5 12l2 2 3.5-4"/>
                    </svg>
                </span>
                <span>
                    <span class="ta-brand-title">Portal SSO</span>
                    <span class="ta-brand-sub">Single Sign-On</span>
                </span>
            </a>
            <button type="button" id="taCloseBtn" aria-label="Tutup Menu" style="background:transparent; border:none; color:var(--ta-sidebar-muted); cursor:pointer; padding:0.25rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="ta-nav">
            <div class="ta-nav-label">Menu Utama</div>
            <a href="{{ route('launcher') }}" class="ta-nav-link {{ request()->routeIs('launcher') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                <span>Dashboard &amp; Launcher</span>
            </a>

            @if(app(\App\Services\SsoService::class)->isAdminUser($ssoSession->siakad_user_id, $ssoSession->tenant_key ?? 'default'))
                <div class="ta-nav-label">Administrasi SSO</div>
                <a href="{{ route('admin.access.index') }}" class="ta-nav-link {{ request()->routeIs('admin.access.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        <path d="M19 8v6M22 11h-6"/>
                    </svg>
                    <span>Hak Akses</span>
                </a>
                <a href="{{ route('admin.apps.index') }}" class="ta-nav-link {{ request()->routeIs('admin.apps.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
                    </svg>
                    <span>Aplikasi Klien</span>
                </a>
                <a href="{{ route('admin.audit.index') }}" class="ta-nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/>
                    </svg>
                    <span>Audit Log</span>
                </a>
            @endif
        </nav>

        <div class="ta-sidebar-footer">
            <div>Sesi aktif: <code>{{ substr($ssoSession->id, 0, 8) }}...</code></div>
            <div style="margin-top: 0.2rem;">IP: {{ $ssoSession->ip }}</div>
        </div>
    </aside>

    <div class="ta-main">
        <header class="ta-header">
            <div class="ta-header-left">
                <button type="button" class="ta-menu-btn" id="taToggleBtn" aria-label="Toggle Menu" title="Buka/Tutup Menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M3 12h18M3 18h18"/>
                    </svg>
                </button>
                <span class="ta-page-title">@yield('page_title', 'Dashboard')</span>
            </div>

            <div class="ta-header-right">
                <div class="ta-user-chip">
                    <span class="ta-avatar">{{ strtoupper(substr($ssoSession->nama, 0, 2)) }}</span>
                    <div>
                        <div class="ta-user-name">{{ $ssoSession->nama }}</div>
                        <div class="ta-user-meta">{{ $ssoSession->username }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="ta-btn ta-btn-outline ta-btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </header>

        <main class="ta-content">
            @if(session('status'))
                <div class="ta-alert ta-alert-success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="ta-alert ta-alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const shell = document.getElementById('taShell');
    const toggleBtn = document.getElementById('taToggleBtn');
    const closeBtn = document.getElementById('taCloseBtn');
    const backdrop = document.getElementById('taBackdrop');
    if (!shell) return;

    function setSidebar(open) {
        shell.classList.toggle('sidebar-open', open);
    }

    toggleBtn?.addEventListener('click', function () {
        setSidebar(!shell.classList.contains('sidebar-open'));
    });
    closeBtn?.addEventListener('click', function () {
        setSidebar(false);
    });
    backdrop?.addEventListener('click', function () {
        setSidebar(false);
    });
});
</script>
@else
    @yield('content')
@endif
</body>
</html>
