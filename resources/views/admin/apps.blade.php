@extends('layouts.app')

@section('title', 'Kelola Aplikasi — Admin SSO')
@section('page_title', 'Manajemen Aplikasi Klien')

@section('content')
@if(session('new_credentials'))
    @php($cred = session('new_credentials'))
    <div class="ta-alert ta-alert-warning">
        <strong>Kredensial Baru untuk {{ $cred['nama'] }} (Tampil SEKALI saja — simpan ke <code>.env</code> aplikasi!):</strong>
        <pre style="margin-top: 0.5rem; background: #fff; padding: 0.75rem; border-radius: 0.35rem; overflow-x: auto; font-size: 0.8rem;">SSO_CLIENT_ID={{ $cred['client_id'] }}
SSO_CLIENT_SECRET={{ $cred['client_secret'] }}
SSO_HMAC_KEY={{ $cred['hmac_secret'] }}</pre>
    </div>
@endif

<div class="ta-card">
    <div class="ta-card-header">
        <div>
            <h2 class="ta-card-title">Daftarkan Aplikasi Baru</h2>
            <p class="ta-card-sub">Generate <code>client_id</code>, <code>client_secret</code>, dan <code>hmac_secret</code> untuk aplikasi klien.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.apps.store') }}" class="ta-form-grid">
        @csrf
        <div>
            <label for="kode" class="ta-label">Kode Unik</label>
            <input type="text" id="kode" name="kode" class="ta-input" placeholder="mis. simpeg" required>
        </div>
        <div>
            <label for="nama" class="ta-label">Nama Aplikasi</label>
            <input type="text" id="nama" name="nama" class="ta-input" placeholder="Sistem Kepegawaian" required>
        </div>
        <div>
            <label for="ikon" class="ta-label">Ikon Launcher</label>
            <select id="ikon" name="ikon" class="ta-input">
                <option value="academic-cap">Akademik / SIAM / OBE (Topi Toga)</option>
                <option value="book-open">Kelas / E-Learning (Buku)</option>
                <option value="clipboard-check">SKP / Kinerja (Clipboard)</option>
                <option value="document-text">SKPI / Persuratan (Dokumen)</option>
                <option value="briefcase">Alumni / Karir (Tas Kerja)</option>
                <option value="chart-bar">Akreditasi / Pelaporan (Grafik)</option>
                <option value="users">SIMPATI / Kepegawaian (Pengguna)</option>
                <option value="banknotes">Keuangan / Sikeu (Uang)</option>
                <option value="shield">Keamanan / Admin (Perisai)</option>
                <option value="cog">Lainnya (Grid)</option>
            </select>
        </div>
        <div>
            <label for="deskripsi" class="ta-label">Deskripsi Singkat</label>
            <input type="text" id="deskripsi" name="deskripsi" class="ta-input" placeholder="Opsional">
        </div>
        <div>
            <label for="base_url" class="ta-label">Base URL</label>
            <input type="url" id="base_url" name="base_url" class="ta-input" placeholder="https://simpeg.domain.com" required>
        </div>
        <div>
            <label for="redirect_uri" class="ta-label">Redirect URI (Persis)</label>
            <input type="url" id="redirect_uri" name="redirect_uri" class="ta-input" placeholder="https://simpeg.domain.com/sso/callback" required>
        </div>
        <div>
            <label for="backchannel_logout_uri" class="ta-label">Back-channel Logout URI</label>
            <input type="url" id="backchannel_logout_uri" name="backchannel_logout_uri" class="ta-input" placeholder="https://simpeg.domain.com/sso/backchannel-logout">
        </div>
        <div>
            <label for="ip_allowlist" class="ta-label">IP Allowlist (opsional)</label>
            <input type="text" id="ip_allowlist" name="ip_allowlist" class="ta-input" placeholder="127.0.0.1, 10.0.0.0/24">
        </div>
        <div style="display:flex; align-items:flex-end;">
            <button type="submit" class="ta-btn ta-btn-primary" style="width:100%;">Simpan &amp; Generate Secret</button>
        </div>
    </form>
</div>

<div class="ta-card">
    <div class="ta-card-header">
        <div>
            <h2 class="ta-card-title">Daftar Aplikasi Klien SSO</h2>
            <p class="ta-card-sub">Total {{ $apps->count() }} aplikasi terdaftar. Pengelolaan aktif/nonaktif dikelola terpisah di aplikasi Tenant.</p>
        </div>
    </div>
    <div class="ta-table-wrap">
        <table class="ta-table">
            <thead>
                <tr>
                    <th>Ikon &amp; Aplikasi</th>
                    <th>Kode</th>
                    <th>Client ID</th>
                    <th>Redirect URI</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @foreach($apps as $app)
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.65rem;">
                            <x-app-icon :name="$app->ikon" :kode="$app->kode" style="width:36px; height:36px; border-radius:0.5rem; margin-bottom:0;" />
                            <div>
                                <div style="font-weight:600;">{{ $app->nama }}</div>
                                <div style="font-size:0.75rem; color:var(--ta-muted);">{{ $app->base_url }}</div>
                            </div>
                        </div>
                    </td>
                    <td><code>{{ $app->kode }}</code></td>
                    <td><code>{{ $app->client_id }}</code></td>
                    <td style="font-size: 0.78rem;">{{ $app->redirect_uri }}</td>
                    <td>
                        <span class="ta-badge {{ $app->aktif ? 'ta-badge-success' : 'ta-badge-danger' }}">
                            {{ $app->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.apps.rotate', $app) }}">
                            @csrf
                            <button type="submit" class="ta-btn ta-btn-danger ta-btn-sm">Rotasi Secret</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
