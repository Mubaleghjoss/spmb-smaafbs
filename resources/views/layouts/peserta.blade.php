<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ $branding['nama_singkat'] ?? 'SPMB' }} {{ $branding['nama_institusi'] ?? '' }}</title>
    @if(!empty($branding['favicon']))
    <link rel="icon" href="{{ asset('storage/' . $branding['favicon']) }}" type="image/x-icon">
    @endif
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="{{ route('peserta.dashboard') }}">
                <i class="bi bi-mortarboard me-2"></i>{{ $branding['nama_singkat'] ?? 'SPMB' }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('peserta.dashboard') ? 'active' : '' }}" href="{{ route('peserta.dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    @php
                        $pesertaNav = \App\Models\Peserta::with('tahapanSpmb')->find(session('peserta_id'));
                    @endphp
                    @if($pesertaNav && $pesertaNav->tahapanSpmb?->tahap_4_selesai)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('peserta.wawancara.*') ? 'active' : '' }}" href="{{ route('peserta.wawancara.info') }}">
                            <i class="bi bi-people me-1"></i>Wawancara
                        </a>
                    </li>
                    @endif
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="bi bi-person-circle me-1"></i>{{ session('peserta_nama') }}
                    </span>
                    <form action="{{ route('peserta.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-0 rounded-0" role="alert">
        <div class="container">{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show m-0 rounded-0" role="alert">
        <div class="container">{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Main Content -->
    <main>@yield('content')</main>

    @stack('scripts')
</body>
</html>
