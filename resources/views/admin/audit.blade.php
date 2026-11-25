@extends('layouts.app')

@section('title', 'Audit Log — Admin SSO')
@section('page_title', 'Audit Log & Keamanan Login')

@section('content')
<div class="ta-card">
    <div class="ta-card-header">
        <div>
            <h2 class="ta-card-title">Percobaan Login Gagal Terbaru</h2>
            <p class="ta-card-sub">Pantau percobaan login mencurigakan dan potensi brute-force.</p>
        </div>
    </div>
    <div class="ta-table-wrap">
        <table class="ta-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Username</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            @forelse($failedAttempts as $attempt)
                <tr>
                    <td>{{ $attempt->waktu->format('Y-m-d H:i:s') }}</td>
                    <td><code>{{ $attempt->username }}</code></td>
                    <td>{{ $attempt->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="color: var(--ta-muted);">Tidak ada percobaan login gagal.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ta-card">
    <div class="ta-card-header">
        <div>
            <h2 class="ta-card-title">Riwayat Audit</h2>
            <p class="ta-card-sub">100 kejadian keamanan terbaru.</p>
        </div>
        <form method="GET" action="{{ route('admin.audit.index') }}" style="display:flex; gap:0.5rem;">
            <select name="aksi" class="ta-input" style="width:auto;">
                <option value="">Semua Aksi</option>
                @foreach($aksiTypes as $type)
                    <option value="{{ $type }}" {{ $aksi === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
            <button type="submit" class="ta-btn ta-btn-outline ta-btn-sm">Filter</button>
        </form>
    </div>

    <div class="ta-table-wrap">
        <table class="ta-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Aksi</th>
                    <th>User ID</th>
                    <th>Aplikasi</th>
                    <th>IP</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
            @foreach($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $log->waktu->format('Y-m-d H:i:s') }}</td>
                    <td><span class="ta-badge">{{ $log->aksi }}</span></td>
                    <td>{{ $log->siakad_user_id ?? '-' }}</td>
                    <td>{{ $log->app?->kode ?? '-' }}</td>
                    <td>{{ $log->ip ?? '-' }}</td>
                    <td style="font-size: 0.78rem;"><code>{{ json_encode($log->detail) }}</code></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
