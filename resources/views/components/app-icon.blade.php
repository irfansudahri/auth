@props(['name' => 'cog', 'kode' => ''])

@php
    $key = $name ?: $kode;
    $colorClass = match($key) {
        'shield', 'auth' => 'icon-indigo',
        'academic-cap', 'obe', 'siam' => 'icon-violet',
        'book-open', 'kelas' => 'icon-emerald',
        'users', 'simpeg', 'simpati' => 'icon-sky',
        'banknotes', 'sikeu' => 'icon-amber',
        'briefcase', 'alumni' => 'icon-rose',
        'document-text', 'skpi' => 'icon-indigo',
        'clipboard-check', 'skp' => 'icon-amber',
        'chart-bar', 'akreditasi' => 'icon-emerald',
        default => 'icon-indigo',
    };
@endphp

<div {{ $attributes->merge(['class' => "ta-app-icon-wrap {$colorClass}"]) }}>
    @switch($key)
        @case('shield')
        @case('auth')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3l7 3v5c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-3z"/>
                <path d="M9.5 12l2 2 3.5-4"/>
            </svg>
            @break
        @case('academic-cap')
        @case('obe')
        @case('siam')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10L12 5 2 10l10 5 10-5z"/>
                <path d="M6 12v5c3 2 9 2 12 0v-5"/>
                <path d="M22 10v6"/>
            </svg>
            @break
        @case('book-open')
        @case('kelas')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 5a2 2 0 012-2h6a2 2 0 012 2v14a2 2 0 00-2-2H4a2 2 0 01-2-2V5z"/>
                <path d="M22 5a2 2 0 00-2-2h-6a2 2 0 00-2 2v14a2 2 0 012-2h6a2 2 0 002-2V5z"/>
            </svg>
            @break
        @case('users')
        @case('simpeg')
        @case('simpati')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="7" r="4"/>
                <path d="M2 21v-2a4 4 0 014-4h6a4 4 0 014 4v2"/>
                <path d="M16 3.13a4 4 0 010 7.75"/>
                <path d="M22 21v-2a4 4 0 00-3-3.87"/>
            </svg>
            @break
        @case('banknotes')
        @case('sikeu')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="6" width="20" height="12" rx="2"/>
                <circle cx="12" cy="12" r="2.5"/>
                <path d="M6 12h.01M18 12h.01"/>
            </svg>
            @break
        @case('briefcase')
        @case('alumni')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                <path d="M2 12h20"/>
            </svg>
            @break
        @case('clipboard-check')
        @case('skp')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="4" width="14" height="17" rx="2"/>
                <path d="M9 4V2h6v2"/>
                <path d="M9 13l2 2 4-4"/>
            </svg>
            @break
        @case('document-text')
        @case('skpi')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <path d="M14 2v6h6M8 13h8M8 17h5"/>
            </svg>
            @break
        @case('chart-bar')
        @case('akreditasi')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v18h18"/>
                <rect x="7" y="10" width="3" height="7" rx="1"/>
                <rect x="12" y="6" width="3" height="11" rx="1"/>
                <rect x="17" y="13" width="3" height="4" rx="1"/>
            </svg>
            @break
        @default
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                <rect x="14" y="14" width="7" height="7" rx="1.5"/>
            </svg>
    @endswitch
</div>
