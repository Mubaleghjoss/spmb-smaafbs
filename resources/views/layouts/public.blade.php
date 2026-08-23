<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPMB') - {{ $branding['nama_institusi'] ?? 'SPMB' }}</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('meta_description', 'Penerimaan Murid Baru ' . ($branding['nama_institusi'] ?? 'SPMB') . ' Tahun Ajaran ' . ($branding['tahun_ajaran'] ?? date('Y')))">
    <meta name="keywords" content="SPMB, Penerimaan Murid Baru, {{ $branding['nama_institusi'] ?? '' }}, Pendaftaran Sekolah">
    <meta name="author" content="{{ $branding['nama_institusi'] ?? 'SPMB' }}">
    
    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', 'Penerimaan Murid Baru - ' . ($branding['nama_institusi'] ?? 'SPMB'))">
    <meta property="og:description" content="@yield('og_description', 'Daftar sekarang! Penerimaan Murid Baru ' . ($branding['nama_institusi'] ?? 'SPMB') . ' Tahun Ajaran ' . ($branding['tahun_ajaran'] ?? date('Y')) . '. Mencetak generasi Qurani yang berakhlak mulia dan berprestasi.')">
    @if(!empty($branding['logo']))
    <meta property="og:image" content="{{ url('storage/' . $branding['logo']) }}">
    @else
    <meta property="og:image" content="{{ asset('images/og-default.png') }}">
    @endif
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="{{ $branding['nama_institusi'] ?? 'SPMB' }}">
    <meta property="og:locale" content="id_ID">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'Penerimaan Murid Baru - ' . ($branding['nama_institusi'] ?? 'SPMB'))">
    <meta name="twitter:description" content="@yield('og_description', 'Daftar sekarang! Penerimaan Murid Baru ' . ($branding['nama_institusi'] ?? 'SPMB') . ' Tahun Ajaran ' . ($branding['tahun_ajaran'] ?? date('Y')))">
    @if(!empty($branding['logo']))
    <meta name="twitter:image" content="{{ url('storage/' . $branding['logo']) }}">
    @endif
    
    @if(!empty($branding['favicon']))
    <link rel="icon" href="{{ asset('storage/' . $branding['favicon']) }}" type="image/x-icon">
    @endif
    
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @include('partials.pwa-head')

    {{-- Font premium (gaya "Taste", tema hijau) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --primary-color: {{ $branding['warna_primer'] ?? '#1a5f2a' }};
            --secondary-color: {{ $branding['warna_sekunder'] ?? '#2e8b57' }};
            --accent-color: #ffc107;
            /* Tema hijau (design system publik) */
            --tk-ease: cubic-bezier(0.22, 1, 0.36, 1);
            --tk-ink: #10241a;
            --tk-muted: #5b6b63;
            --tk-cream: #f6f8f5;
            --tk-green: var(--secondary-color);
            --tk-green-deep: var(--primary-color);
            --tk-green-soft: rgba(46,139,87,.10);
            --tk-green-ring: rgba(46,139,87,.16);
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--tk-ink);
        }
        h1, h2, h3, .display-5, .display-6, .tk-serif {
            font-family: 'Fraunces', Georgia, serif; letter-spacing: -0.01em;
        }

        /* ===== Navbar ===== */
        .navbar { backdrop-filter: saturate(1.1); }
        .navbar-brand img { height: 50px; }
        .navbar .nav-link { font-weight: 600; color: #33463d; border-radius: 999px; padding: .4rem .85rem !important; transition: color .2s var(--tk-ease), background .2s var(--tk-ease); }
        .navbar .nav-link:hover { color: var(--tk-green-deep); background: var(--tk-green-soft); }
        .navbar .nav-link.active { color: #fff; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }

        /* ===== Buttons (pil + ikon lingkaran) ===== */
        .btn { border-radius: 999px; font-weight: 600; }
        .btn-success { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border: 0; box-shadow: 0 14px 30px -14px rgba(16,36,26,.55); }
        .btn-success:hover, .btn-success:focus { filter: brightness(1.05); background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        .btn-outline-success { border-color: var(--secondary-color); color: var(--tk-green-deep); }
        .btn-outline-success:hover { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-color: transparent; }
        .btn-warning { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border: 0; color: #fff; box-shadow: 0 14px 30px -14px rgba(16,36,26,.55); }
        .btn-warning:hover { color: #fff; filter: brightness(1.05); background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }

        .tk-eyebrow {
            display: inline-flex; align-items: center; gap: .5rem;
            font-size: .7rem; font-weight: 700; letter-spacing: .18em; text-transform: uppercase;
            padding: .35rem .85rem; border-radius: 999px;
            background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.28); color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .tk-eyebrow.dark { background: var(--tk-green-soft); border-color: var(--tk-green-ring); color: var(--tk-green-deep); }
        .tk-eyebrow .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--secondary-color); }
        .tk-eyebrow:not(.dark) .dot { background: #fff; }

        .tk-btn {
            display: inline-flex; align-items: center; gap: .6rem;
            font-weight: 600; text-decoration: none; border: 0; cursor: pointer;
            padding: .8rem 1.1rem .8rem 1.4rem; border-radius: 999px;
            transition: transform .25s var(--tk-ease), box-shadow .25s var(--tk-ease), filter .25s var(--tk-ease);
        }
        .tk-btn .tk-btn-ico { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; transition: transform .35s var(--tk-ease); }
        .tk-btn:hover { transform: translateY(-2px); filter: brightness(1.03); }
        .tk-btn:hover .tk-btn-ico { transform: translate(3px, -1px); }
        .tk-btn:active { transform: scale(.985); }
        .tk-btn-primary { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: #fff; box-shadow: 0 18px 40px -16px rgba(16,36,26,.6); }
        .tk-btn-primary .tk-btn-ico { background: rgba(255,255,255,.18); }
        .tk-btn-ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.3); }
        .tk-btn-ghost .tk-btn-ico { background: rgba(255,255,255,.16); }
        .tk-btn-soft { background: var(--tk-green-soft); color: var(--tk-green-deep); }
        .tk-btn-soft .tk-btn-ico { background: var(--tk-green-ring); }

        /* ===== Section & typography helpers ===== */
        .tk-section { padding: clamp(3rem, 7vw, 6rem) 0; }
        .tk-section.cream { background: var(--tk-cream); }
        .tk-h2 { font-weight: 700; font-size: clamp(1.7rem, 3.6vw, 2.6rem); }
        .tk-lead { color: var(--tk-muted); font-size: 1.02rem; max-width: 44rem; margin-inline: auto; }

        /* ===== Hero hijau ===== */
        .tk-hero {
            position: relative; overflow: hidden; color: #fff;
            background:
                radial-gradient(60% 60% at 82% 12%, rgba(255,255,255,.16), transparent 55%),
                radial-gradient(50% 55% at 12% 88%, rgba(255,255,255,.10), transparent 55%),
                linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: clamp(3rem, 7vw, 6rem) 0;
        }
        .tk-hero::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.5'/%3E%3C/svg%3E");
            opacity: .035;
        }
        .tk-hero .container { position: relative; z-index: 1; }

        /* ===== Double-bezel card ===== */
        .tk-card {
            height: 100%; border-radius: 1.5rem; padding: .45rem;
            background: #fff; border: 1px solid rgba(16,36,26,.06);
            box-shadow: 0 24px 50px -34px rgba(16,36,26,.28);
            transition: transform .4s var(--tk-ease), box-shadow .4s var(--tk-ease);
        }
        .tk-card:hover { transform: translateY(-5px); box-shadow: 0 34px 66px -30px rgba(16,36,26,.34); }
        .tk-card .core {
            height: 100%; border-radius: 1.1rem; padding: 1.5rem;
            background: linear-gradient(180deg, #fff, #fbfdfb);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.9);
        }
        .tk-ico-tile {
            width: 58px; height: 58px; border-radius: 1rem;
            display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem;
            color: var(--secondary-color);
            background: radial-gradient(120% 120% at 30% 20%, var(--tk-green-ring), var(--tk-green-soft));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
        }

        /* Accordion halus */
        .accordion-item { border: 1px solid rgba(16,36,26,.06); border-radius: 1rem !important; overflow: hidden; box-shadow: 0 18px 40px -34px rgba(16,36,26,.3); }
        .accordion-button { font-weight: 600; }
        .accordion-button:not(.collapsed) { background: var(--tk-green-soft); color: var(--tk-ink); box-shadow: none; }
        .accordion-button:focus { box-shadow: none; }

        /* Reveal animation */
        .reveal { opacity: 0; transform: translateY(26px); filter: blur(6px); transition: opacity .8s var(--tk-ease), transform .8s var(--tk-ease), filter .8s var(--tk-ease); }
        .reveal.is-visible { opacity: 1; transform: none; filter: none; }
        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; filter: none; transition: none; }
            .tk-card, .tk-btn { transition: none; }
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 80px 0;
        }

        .tahapan-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .tahapan-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .tahapan-number {
            width: 50px; height: 50px;
            background: var(--primary-color); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; font-weight: bold;
        }

        /* ===== Footer hijau ===== */
        .footer {
            background: linear-gradient(160deg, #0f2318, #14311f);
            color: #cfe0d6;
        }
        .footer a.text-light:hover { color: #fff !important; text-decoration: underline; }

        .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { background-color: var(--secondary-color); border-color: var(--secondary-color); }

        /* ===== Dropdown Login: cegah terpotong di HP ===== */
        @media (max-width: 991.98px) {
            /* Di layar kecil navbar collapse; dropdown tampil menyatu (bukan floating) agar tidak terpotong */
            .navbar .dropdown-menu {
                position: static !important;
                float: none;
                width: 100%;
                margin-top: .35rem;
                border: 1px solid rgba(16,36,26,.10);
                box-shadow: none;
                border-radius: .75rem;
            }
            .navbar .navbar-nav ~ .d-flex,
            .navbar .d-flex.gap-2 { flex-direction: column; align-items: stretch; width: 100%; gap: .5rem !important; }
            .navbar .d-flex .dropdown { width: 100%; }
            .navbar .d-flex .dropdown > .btn { width: 100%; }
            .navbar .d-flex > .btn,
            .navbar .d-flex form,
            .navbar .d-flex form > .btn { width: 100%; }
            .navbar .dropdown-item { white-space: normal; }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('beranda') }}">
                @if(!empty($branding['logo']))
                <img src="{{ asset('storage/' . $branding['logo']) }}" alt="Logo" class="me-2" style="height: 50px;">
                @endif
                <span class="d-flex flex-column lh-1">
                    <span class="fw-bold" style="color: {{ $branding['warna_primer'] ?? '#1a5f2a' }}; font-family:'Fraunces',serif; font-size:1.15rem;">{{ $branding['nama_singkat'] ?? 'SPMB' }} SMA AFBS</span>
                    <small class="text-muted" style="font-size:.68rem; letter-spacing:.03em;">Seleksi Penerimaan Murid Baru</small>
                </span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('beranda') ? 'active' : '' }}" href="{{ route('beranda') }}">
                            <i class="bi bi-house-door me-1"></i>Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('alur-spmb') ? 'active' : '' }}" href="{{ route('alur-spmb') }}">
                            <i class="bi bi-signpost-split me-1"></i>Alur SPMB
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('jadwal') ? 'active' : '' }}" href="{{ route('jadwal') }}">
                            <i class="bi bi-calendar-event me-1"></i>Jadwal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('kontak') ? 'active' : '' }}" href="{{ route('kontak') }}">
                            <i class="bi bi-envelope me-1"></i>Kontak
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cek-status') ? 'active' : '' }}" href="{{ route('cek-status') }}">
                            <i class="bi bi-search me-1"></i>Cek Status
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex gap-2">
                    @auth('pengguna')
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-success">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right me-1"></i>Keluar
                            </button>
                        </form>
                    @else
                        <!-- Dropdown Login -->
                        <div class="dropdown">
                            <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>Login
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('peserta.login') }}">
                                        <i class="bi bi-person me-2"></i>Login Peserta SPMB
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('login.token') }}">
                                        <i class="bi bi-play-circle me-2"></i>Langsung Ujian (Token)
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('login') }}">
                                        <i class="bi bi-shield-lock me-2"></i>Login Admin/Operator
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <a href="{{ route('daftar') }}" class="btn btn-success">
                            <i class="bi bi-pencil-square me-1"></i>Daftar SPMB
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages (melayang, satu tempat) -->
    @include('partials.flash')

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="footer py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-3">{{ $branding['nama_institusi'] ?? 'SPMB' }}</h5>
                    <p class="small">Mencetak generasi Qurani yang berakhlak mulia, berprestasi, dan siap menghadapi tantangan masa depan.</p>
                    @if(!empty($branding['website']))
                    <p class="small"><a href="{{ $branding['website'] }}" class="text-light" target="_blank">{{ $branding['website'] }}</a></p>
                    @endif
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-3">Kontak</h5>
                    <ul class="list-unstyled small">
                        @if(!empty($branding['alamat']))
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>{{ $branding['alamat'] }}</li>
                        @endif
                        @if(!empty($branding['telepon']))
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i>{{ $branding['telepon'] }}</li>
                        @endif
                        @if(!empty($branding['email']))
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i>{{ $branding['email'] }}</li>
                        @endif
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-3">Link Cepat</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('alur-spmb') }}" class="text-decoration-none text-light"><i class="bi bi-signpost-split me-2"></i>Alur SPMB</a></li>
                        <li class="mb-2"><a href="{{ route('jadwal') }}" class="text-decoration-none text-light"><i class="bi bi-calendar-event me-2"></i>Jadwal</a></li>
                        <li class="mb-2"><a href="{{ route('kontak') }}" class="text-decoration-none text-light"><i class="bi bi-envelope me-2"></i>Kontak</a></li>
                        <li class="mb-2"><a href="{{ route('peserta.login') }}" class="text-decoration-none text-light"><i class="bi bi-box-arrow-in-right me-2"></i>Login Peserta</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center small">
                <p class="mb-0">&copy; {{ date('Y') }} {{ $branding['nama_institusi'] ?? 'SPMB' }}. All rights reserved.</p>
                @if(!empty($branding['tahun_ajaran']))
                <p class="mb-0 mt-1">Tahun Ajaran {{ $branding['tahun_ajaran'] }}</p>
                @endif
            </div>
        </div>
    </footer>

    @stack('scripts')

    @include('partials.pwa-scripts')

    {{-- Tombol WhatsApp mengambang --}}
    @php
        $waFloat = app(\App\Services\PengaturanService::class)->ambil('whatsapp_spmb', '');
    @endphp
    @if(!empty($waFloat))
    <a href="https://wa.me/62{{ ltrim($waFloat, '0') }}" target="_blank" rel="noopener"
       class="float-wa" aria-label="Hubungi via WhatsApp" title="Hubungi Tim SPMB">
        <i class="bi bi-whatsapp"></i>
    </a>
    <style>
        .float-wa{position:fixed;right:18px;bottom:18px;z-index:1030;width:56px;height:56px;border-radius:50%;
            background:#25d366;color:#fff;display:flex;align-items:center;justify-content:center;
            box-shadow:0 6px 18px rgba(0,0,0,.25);font-size:1.6rem;text-decoration:none;transition:transform .2s ease}
        .float-wa:hover{transform:scale(1.08);color:#fff}
    </style>
    @endif
</body>
</html>
