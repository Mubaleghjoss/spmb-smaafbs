{{-- Meta & registrasi PWA (dipakai di semua layout) --}}
<meta name="theme-color" content="{{ $branding['warna_primer'] ?? '#1a5f2a' }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ $branding['nama_singkat'] ?? 'SPMB' }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
