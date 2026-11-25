@extends('layouts.app')

@section('title', 'Dashboard & Launcher — Portal SSO')
@section('page_title', 'Dashboard & Launcher')

@section('content')
{{-- TailAdmin KPI Metric Row --}}
<div class="ta-kpi-grid">
    <div class="ta-kpi-card">
        <span class="ta-kpi-icon icon-indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
            </svg>
        </span>
        <div>
            <div class="ta-kpi-value">{{ $apps->count() }}</div>
            <div class="ta-kpi-label">Aplikasi Tersedia</div>
        </div>
    </div>

    <div class="ta-kpi-card">
        <span class="ta-kpi-icon icon-emerald">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3l7 3v5c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-3z"/><path d="M9.5 12l2 2 3.5-4"/>
            </svg>
        </span>
        <div>
            <div class="ta-kpi-value">{{ $totalLevels }}</div>
            <div class="ta-kpi-label">Total Peran Aktif</div>
        </div>
    </div>

    <div class="ta-kpi-card">
        <span class="ta-kpi-icon icon-sky">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
            </svg>
        </span>
        <div>
            <div class="ta-kpi-value">{{ $activeAppSessions }}</div>
            <div class="ta-kpi-label">Sesi Aplikasi Terbuka</div>
        </div>
    </div>

    <div class="ta-kpi-card">
        <span class="ta-kpi-icon icon-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            </svg>
        </span>
        <div>
            <div class="ta-kpi-value" style="font-size:1.1rem;">{{ $ssoSession->dibuat_pada->format('H:i') }}</div>
            <div class="ta-kpi-label">Login Sejak ({{ $ssoSession->dibuat_pada->format('d M') }})</div>
        </div>
    </div>
</div>

{{-- Icon-Centric App Launcher --}}
<div class="ta-card">
    <div class="ta-card-header">
        <div>
            <h2 class="ta-card-title">Launcher Aplikasi</h2>
            <p class="ta-card-sub">Klik ikon aplikasi untuk masuk otomatis tanpa login ulang.</p>
        </div>
    </div>

    @if($apps->isEmpty())
        <p style="color: var(--ta-muted); font-size:0.9rem;">Belum ada aplikasi yang dapat Anda akses.</p>
    @else
        <div class="ta-launcher-grid">
            @foreach($apps as $item)
                @php
                    $href = $item->app->kode === config('sso.admin_app_code')
                        ? route('admin.access.index')
                        : route('open', $item->app->kode);
                @endphp
                <a href="{{ $href }}" class="ta-app-tile" title="Buka {{ $item->app->nama }}">
                    <x-app-icon :name="$item->app->ikon" :kode="$item->app->kode" />
                    <div class="ta-app-name">{{ $item->app->nama }}</div>
                    <div class="ta-app-desc">{{ $item->app->deskripsi ?: $item->app->kode }}</div>
                    <div class="ta-app-roles">
                        @foreach($item->levels as $lvl)
                            <span class="ta-badge ta-badge-success">{{ $lvl }}</span>
                        @endforeach
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

{{-- Recent User Activity --}}
<div class="ta-card">
    <div class="ta-card-header">
        <div>
            <h2 class="ta-card-title">Aktivitas Terakhir Anda</h2>
            <p class="ta-card-sub">5 catatan keamanan terbaru untuk akun Anda.</p>
        </div>
    </div>
    <div class="ta-table-wrap">
        <table class="ta-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Aksi</th>
                    <th>Aplikasi</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            @forelse($recentLogs as $log)
                <tr>
                    <td>{{ $log->waktu->format('d M Y H:i:s') }}</td>
                    <td><span class="ta-badge">{{ $log->aksi }}</span></td>
                    <td>{{ $log->app?->nama ?? 'Portal SSO' }}</td>
                    <td><code>{{ $log->ip ?? '-' }}</code></td>
                </tr>
            @empty
                <tr><td colspan="4" style="color: var(--ta-muted);">Belum ada catatan aktivitas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
