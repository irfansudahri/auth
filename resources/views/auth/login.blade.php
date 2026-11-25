@extends('layouts.app')

@section('title', 'Login — Portal SSO')

@section('content')
<div class="ta-login-wrap">
    <div class="ta-login-card">
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem;">
            <span class="ta-brand-mark" style="width:42px; height:42px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3l7 3v5c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-3z"/>
                    <path d="M9.5 12l2 2 3.5-4"/>
                </svg>
            </span>
            <div>
                <h1 style="font-size:1.3rem; font-weight:700; color:var(--ta-text);">Portal SSO</h1>
                <p style="font-size:0.8rem; color:var(--ta-muted);">Satu pintu masuk aplikasi akademik</p>
            </div>
        </div>

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

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label for="username" class="ta-label">Username Siakad</label>
                <input type="text" id="username" name="username" class="ta-input" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus autocomplete="username">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label for="password" class="ta-label">Password</label>
                <input type="password" id="password" name="password" class="ta-input" placeholder="Masukkan password" required autocomplete="current-password">
            </div>
            <button type="submit" class="ta-btn ta-btn-primary" style="width: 100%; padding: 0.7rem;">
                Masuk ke Portal
            </button>
        </form>
    </div>
</div>
@endsection
