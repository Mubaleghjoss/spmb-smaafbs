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

    @include('partials.pwa-head')

    <style>
        /* ===== BOTTOM NAV (MOBILE) — Peserta ===== */
        .mobile-bottom-nav { display: none; }
        @media (max-width: 768px) {
            .navbar .navbar-nav { display: none; } /* sembunyikan menu atas di HP, ganti bottom-nav */
            .mobile-bottom-nav {
                display: grid;
                grid-template-columns: repeat(var(--mbn-cols, 4), 1fr);
                position: fixed; left: 0; right: 0; bottom: 0;
                height: 62px; background: #fff;
                border-top: 1px solid #e5e7eb;
                box-shadow: 0 -4px 16px rgba(15,23,42,.08);
                z-index: 1050; padding-bottom: env(safe-area-inset-bottom, 0);
            }
            .mobile-bottom-nav a, .mobile-bottom-nav button {
                border: 0; background: none;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                gap: 2px; min-width: 0; padding: 4px 2px;
                color: #64748b; font-size: .64rem; font-weight: 600;
                text-decoration: none; position: relative;
            }
            .mobile-bottom-nav a i, .mobile-bottom-nav button i { font-size: 1.25rem; }
            .mobile-bottom-nav a.active, .mobile-bottom-nav a.active i { color: #198754; }
            .mobile-bottom-nav .mbn-label { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            body { padding-bottom: 74px; }
        }
        .mbn-sheet-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 1060; display: none; }
        .mbn-sheet-backdrop.show { display: block; }
        .mbn-sheet {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 1061; background: #fff;
            border-radius: 18px 18px 0 0; padding: 16px 16px calc(16px + env(safe-area-inset-bottom,0));
            transform: translateY(100%); transition: transform .25s ease; max-height: 80vh; overflow-y: auto;
        }
        .mbn-sheet.show { transform: translateY(0); }
        .mbn-grip { width: 44px; height: 5px; border-radius: 999px; background: #cbd5e1; margin: 0 auto 12px; }
        .mbn-sheet-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .mbn-sheet-item {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            padding: 12px 6px; border: 1px solid #e5e7eb; border-radius: 14px;
            color: #334155; text-decoration: none; font-size: .72rem; font-weight: 600; text-align: center;
        }
        .mbn-sheet-item i { font-size: 1.35rem; color: #198754; }
        .mbn-sheet-item.active { border-color: #198754; background: #f0fdf4; color: #146c43; }
    </style>
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

    {{-- ===== BOTTOM NAV (MOBILE) — Peserta ===== --}}
    @php
        $mbnP = \App\Models\Peserta::with('tahapanSpmb')->find(session('peserta_id'));
        $tes4 = $mbnP && ($mbnP->tahapanSpmb?->tahap_4_selesai);
        $mbnMain = [
            ['route' => 'peserta.dashboard', 'match' => 'peserta.dashboard', 'icon' => 'speedometer2', 'label' => 'Dashboard'],
            ['route' => 'peserta.formulir.isi', 'match' => 'peserta.formulir.*', 'icon' => 'file-earmark-text', 'label' => 'Formulir'],
        ];
        if ($tes4) {
            $mbnMain[] = ['route' => 'peserta.wawancara.info', 'match' => 'peserta.wawancara.*', 'icon' => 'people', 'label' => 'Wawancara'];
        }
        $mbnCols = count($mbnMain) + 1;
    @endphp
    <nav class="mobile-bottom-nav" aria-label="Navigasi bawah" style="--mbn-cols: {{ $mbnCols }}">
        @foreach($mbnMain as $it)
            @php
                $adaRoute = \Illuminate\Support\Facades\Route::has($it['route']);
            @endphp
            @if($adaRoute)
            <a href="{{ route($it['route']) }}" class="{{ request()->routeIs($it['match']) ? 'active' : '' }}">
                <i class="bi bi-{{ $it['icon'] }}"></i>
                <span class="mbn-label">{{ $it['label'] }}</span>
            </a>
            @endif
        @endforeach
        <button type="button" onclick="mbnToggleSheet(true)" aria-label="Menu lainnya">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            <span class="mbn-label">Lainnya</span>
        </button>
    </nav>

    <div class="mbn-sheet-backdrop" id="mbnBackdrop" onclick="mbnToggleSheet(false)"></div>
    <div class="mbn-sheet" id="mbnSheet" role="dialog" aria-modal="true" aria-label="Menu lainnya">
        <div class="mbn-grip"></div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Menu Lainnya</h6>
            <button type="button" class="btn btn-sm btn-light" onclick="mbnToggleSheet(false)"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mbn-sheet-grid">
            <a href="{{ route('peserta.dashboard') }}" class="mbn-sheet-item {{ request()->routeIs('peserta.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>
            @if(\Illuminate\Support\Facades\Route::has('peserta.formulir.isi'))
            <a href="{{ route('peserta.formulir.isi') }}" class="mbn-sheet-item {{ request()->routeIs('peserta.formulir.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i><span>Formulir</span>
            </a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('peserta.pembayaran.formulir'))
            <a href="{{ route('peserta.pembayaran.formulir') }}" class="mbn-sheet-item {{ request()->routeIs('peserta.pembayaran.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card"></i><span>Pembayaran</span>
            </a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('ujian.index'))
            <a href="{{ route('ujian.index') }}" class="mbn-sheet-item">
                <i class="bi bi-laptop"></i><span>Tes Online</span>
            </a>
            @endif
            @if($tes4 && \Illuminate\Support\Facades\Route::has('peserta.wawancara.info'))
            <a href="{{ route('peserta.wawancara.info') }}" class="mbn-sheet-item {{ request()->routeIs('peserta.wawancara.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i><span>Wawancara</span>
            </a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('peserta.konfirmasi-diterima'))
            <a href="{{ route('peserta.konfirmasi-diterima') }}" class="mbn-sheet-item">
                <i class="bi bi-mortarboard"></i><span>Kelulusan</span>
            </a>
            @endif
        </div>
        <form action="{{ route('peserta.logout') }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="w-100 btn btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Keluar</button>
        </form>
    </div>

    @stack('scripts')

    <script>
        function mbnToggleSheet(open) {
            var sheet = document.getElementById('mbnSheet');
            var bd = document.getElementById('mbnBackdrop');
            if (!sheet || !bd) return;
            if (open) { bd.classList.add('show'); requestAnimationFrame(function(){ sheet.classList.add('show'); }); document.body.style.overflow = 'hidden'; }
            else { sheet.classList.remove('show'); bd.classList.remove('show'); document.body.style.overflow = ''; }
        }
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') mbnToggleSheet(false); });
    </script>

    @include('partials.pwa-scripts')
</body>
</html>
