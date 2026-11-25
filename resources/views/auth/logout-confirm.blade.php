@extends('layouts.app')

@section('title', 'Konfirmasi Keluar — Portal SSO')
@section('page_title', 'Konfirmasi Keluar')

@section('content')
<div class="ta-card" style="max-width: 480px; margin: 2rem auto; text-align: center;">
    <div style="display:flex; justify-content:center; margin-bottom:1rem;">
        <span class="ta-kpi-icon icon-amber" style="width:56px; height:56px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </span>
    </div>
    <h2 class="ta-card-title" style="margin-bottom:0.4rem;">Keluar dari Semua Aplikasi?</h2>
    <p class="ta-card-sub" style="margin-bottom:1.5rem;">
        Tindakan ini akan mengakhiri sesi SSO Anda di Portal dan seluruh aplikasi klien yang sedang terbuka.
    </p>
    <div style="display:flex; gap:0.75rem; justify-content:center;">
        <a href="{{ route('launcher') }}" class="ta-btn ta-btn-outline">Kembali ke Launcher</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="ta-btn ta-btn-danger">Ya, Keluar Semua</button>
        </form>
    </div>
</div>
@endsection
