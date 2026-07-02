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
    
    <style>
        :root {
            --primary-color: {{ $branding['warna_primer'] ?? '#1a5f2a' }};
            --secondary-color: {{ $branding['warna_sekunder'] ?? '#2e8b57' }};
            --accent-color: #ffc107;
        }
        
        .navbar-brand img {
            height: 50px;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 80px 0;
        }
        
        .tahapan-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .tahapan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .tahapan-number {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .footer {
            background: #1a1a1a;
            color: #ccc;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-warning {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: #000;
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
                <span class="fw-bold" style="color: {{ $branding['warna_primer'] ?? '#1a5f2a' }}">{{ $branding['nama_singkat'] ?? 'SPMB' }}</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('beranda') ? 'active' : '' }}" href="{{ route('beranda') }}">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('alur-spmb') ? 'active' : '' }}" href="{{ route('alur-spmb') }}">Alur SPMB</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('jadwal') ? 'active' : '' }}" href="{{ route('jadwal') }}">Jadwal</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('kontak') ? 'active' : '' }}" href="{{ route('kontak') }}">Kontak</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cek-status') ? 'active' : '' }}" href="{{ route('cek-status') }}">
                            <i class="bi bi-search me-1"></i>Cek Status
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex gap-2">
                    @auth('pengguna')
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-success">Dashboard</a>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">Keluar</button>
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
                        <a href="{{ route('daftar') }}" class="btn btn-success">Daftar SPMB</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-0 rounded-0" role="alert">
        <div class="container">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show m-0 rounded-0" role="alert">
        <div class="container">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif

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
                        <li class="mb-2"><a href="{{ route('alur-spmb') }}" class="text-decoration-none text-light">Alur SPMB</a></li>
                        <li class="mb-2"><a href="{{ route('jadwal') }}" class="text-decoration-none text-light">Jadwal</a></li>
                        <li class="mb-2"><a href="{{ route('kontak') }}" class="text-decoration-none text-light">Kontak</a></li>
                        <li class="mb-2"><a href="{{ route('peserta.login') }}" class="text-decoration-none text-light">Login Peserta</a></li>
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
</body>
</html>
