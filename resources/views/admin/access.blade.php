@extends('layouts.app')

@section('title', 'Kelola Hak Akses — Admin SSO')
@section('page_title', 'Manajemen Hak Akses Pengguna')

@section('content')
<div class="ta-card">
    <div class="ta-card-header">
        <div>
            <h2 class="ta-card-title">Cari Pengguna Siakad</h2>
            <p class="ta-card-sub">Data pengguna diambil secara read-only dari database Siakad.</p>
        </div>
    </div>
    <form method="GET" action="{{ route('admin.access.index') }}" style="display:flex; gap:0.75rem;">
        <input type="text" name="q" value="{{ $query }}" class="ta-input" placeholder="Cari username / nama dari Siakad...">
        <button type="submit" class="ta-btn ta-btn-primary">Cari</button>
    </form>

    @if($users->isNotEmpty())
        <div style="margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
            @foreach($users as $u)
                <a href="{{ route('admin.access.index', ['q' => $query, 'user_id' => $u->id]) }}"
                   class="ta-btn ta-btn-sm {{ $selectedUser && $selectedUser->id === $u->id ? 'ta-btn-primary' : 'ta-btn-outline' }}">
                    {{ $u->nama }} ({{ $u->username }})
                </a>
            @endforeach
        </div>
    @endif
</div>

@if($selectedUser)
<div class="ta-card">
    <div class="ta-card-header">
        <div>
            <h2 class="ta-card-title">{{ $selectedUser->nama }}</h2>
            <p class="ta-card-sub">Username: <code>{{ $selectedUser->username }}</code> &bull; Siakad ID: <code>{{ $selectedUser->id }}</code></p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.access.store') }}" class="ta-form-grid" style="margin-bottom: 1.5rem;">
        @csrf
        <input type="hidden" name="siakad_user_id" value="{{ $selectedUser->id }}">
        <div>
            <label for="app_id" class="ta-label">Aplikasi</label>
            <select id="app_id" name="app_id" class="ta-input" required>
                @foreach($apps as $app)
                    <option value="{{ $app->id }}">{{ $app->nama }} ({{ $app->kode }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="level" class="ta-label">Level / Peran</label>
            <input type="text" id="level" name="level" class="ta-input" placeholder="mis. dosen, kaprodi, admin" required>
        </div>
        <div>
            <label for="berlaku_sampai" class="ta-label">Berlaku Sampai (Opsional)</label>
            <input type="date" id="berlaku_sampai" name="berlaku_sampai" class="ta-input">
        </div>
        <div style="display:flex; align-items:flex-end;">
            <button type="submit" class="ta-btn ta-btn-primary" style="width:100%;">Tambah / Aktifkan Level</button>
        </div>
    </form>

    <div class="ta-table-wrap">
        <table class="ta-table">
            <thead>
                <tr>
                    <th>Aplikasi</th>
                    <th>Level</th>
                    <th>Berlaku Sampai</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($userLevels as $ul)
                <tr>
                    <td>{{ $ul->app->nama }} (<code>{{ $ul->app->kode }}</code>)</td>
                    <td><span class="ta-badge">{{ $ul->level }}</span></td>
                    <td>{{ $ul->berlaku_sampai ? $ul->berlaku_sampai->format('Y-m-d') : 'Permanen' }}</td>
                    <td>
                        <span class="ta-badge {{ $ul->aktif ? 'ta-badge-success' : 'ta-badge-danger' }}">
                            {{ $ul->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.access.toggle', $ul) }}">
                            @csrf
                            <button type="submit" class="ta-btn ta-btn-outline ta-btn-sm">{{ $ul->aktif ? 'Cabut' : 'Aktifkan' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="color: var(--ta-muted);">Belum ada hak akses untuk pengguna ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
