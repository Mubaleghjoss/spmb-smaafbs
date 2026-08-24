<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - {{ $branding['nama_singkat'] ?? 'SPMB' }} {{ $branding['nama_institusi'] ?? '' }}</title>
    @if(!empty($branding['favicon']))
    <link rel="icon" href="{{ asset('storage/' . $branding['favicon']) }}" type="image/x-icon">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;450;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @include('partials.tk-backend')
    <style>
        :root {
            --sidebar-width: 260px;
            --topbar-height: 56px;
            --sidebar-bg: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            --sidebar-hover: rgba(255,255,255,0.14);
            --sidebar-active: linear-gradient(135deg, #10b981, #059669);
            --sidebar-text: rgba(255,255,255,0.86);
            --sidebar-text-active: #fff;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        html { overflow-x: hidden; }

        /* ===== SIDEBAR ===== */
        .admin-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: var(--sidebar-bg);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            transition: transform var(--transition);
            border-right: 1px solid rgba(255,255,255,0.06);
        }

        .admin-sidebar.collapsed {
            transform: translateX(-100%);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            min-height: var(--topbar-height);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .sidebar-brand-icon {
            width: 34px; height: 34px;
            background: var(--sidebar-active);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .sidebar-brand-text {
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.2;
        }

        .sidebar-brand-text small {
            display: block;
            font-size: 0.7rem;
            font-weight: 400;
            color: var(--sidebar-text);
        }

        /* Close button inside sidebar */
        .sidebar-close {
            background: none;
            border: none;
            color: var(--sidebar-text);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }

        .sidebar-close:hover {
            background: var(--sidebar-hover);
            color: white;
        }

        /* Navigation */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        .nav-section-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,0.58);
            padding: 14px 12px 6px;
        }

        .sidebar-nav .nav-link {
            color: var(--sidebar-text);
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid transparent;
            font-size: 0.875rem;
            font-weight: 450;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .sidebar-nav .nav-link:hover {
            background: var(--sidebar-hover);
            border-color: rgba(255,255,255,0.10);
            color: var(--sidebar-text-active);
        }

        .sidebar-nav .nav-link.active {
            background: var(--sidebar-active);
            color: var(--sidebar-text-active);
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .sidebar-nav .nav-link i {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
            color: #6ee7b7;
        }

        .sidebar-nav .nav-link.active i {
            color: #fff;
        }

        /* Badge antrean di menu sidebar */
        .sidebar-nav .nav-link .queue-badge {
            margin-left: auto;
            background: #ef4444;
            color: #fff;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.05rem 0.45rem;
            min-width: 20px;
            text-align: center;
            line-height: 1.4;
            box-shadow: 0 2px 6px rgba(239,68,68,.5);
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
        }

        .sidebar-user:hover {
            background: var(--sidebar-hover);
            color: white;
        }

        .sidebar-user-avatar {
            width: 34px; height: 34px;
            background: rgba(255,255,255,0.12);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #10b981;
            flex-shrink: 0;
        }

        .sidebar-user-info {
            flex: 1;
            min-width: 0;
        }

        .sidebar-user-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-role {
            font-size: 0.7rem;
            color: var(--sidebar-text);
        }

        /* ===== OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1035;
        }

        .sidebar-overlay.show { display: block; }

        /* ===== TOP BAR ===== */
        .admin-topbar {
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .topbar-toggle {
            background: none;
            border: 1px solid #e5e7eb;
            color: #374151;
            width: 38px; height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.1rem;
        }

        .topbar-toggle:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        /* ===== MAIN CONTENT ===== */
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f3f4f6;
            transition: margin-left var(--transition);
        }

        .admin-main.expanded {
            margin-left: 0;
        }

        .admin-main .content-area {
            padding: 24px;
        }

        /* ===== MOBILE ===== */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 280px;
                transform: translateX(-100%);
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0 !important;
                width: 100%;
                max-width: 100vw;
            }

            .admin-main .content-area {
                padding: 12px;
            }

            .container-fluid {
                padding-left: 12px;
                padding-right: 12px;
            }

            .table-responsive {
                font-size: 0.8rem;
            }
        }

        /* Tombol hapus jawaban */
        .btn-hapus-jawaban { padding: 0.375rem 0.75rem; }

        .admin-main .btn[class*="btn-outline-"] {
            background-color: #fff;
            border-width: 1.5px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .admin-main .btn-outline-primary { background-color: #ecfdf5; border-color: #10b981; color: #047857; }
        .admin-main .btn-outline-success { background-color: #f0fdf4; border-color: #22c55e; color: #15803d; }
        .admin-main .btn-outline-secondary { background-color: #f8fafc; border-color: #64748b; color: #334155; }
        .admin-main .btn-outline-info { background-color: #ecfeff; border-color: #06b6d4; color: #0e7490; }
        .admin-main .btn-outline-warning { background-color: #fffbeb; border-color: #f59e0b; color: #92400e; }
        .admin-main .btn-outline-danger { background-color: #fef2f2; border-color: #ef4444; color: #b91c1c; }
        .admin-main .btn-outline-dark { background-color: #f8fafc; border-color: #0f172a; color: #0f172a; }

        .admin-main .btn[class*="btn-outline-"]:hover {
            color: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.16);
            transform: translateY(-1px);
        }

        .admin-main .btn-action-view,
        .admin-main .btn-action-edit,
        .admin-main .btn-action-save,
        .admin-main .btn-action-export,
        .admin-main .btn-action-print,
        .admin-main .btn-action-back,
        .admin-main .btn-action-reset,
        .admin-main .btn-action-next {
            color: #fff !important;
            border: 0;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.18);
        }

        .admin-main .btn-action-view { background: #2563eb; }
        .admin-main .btn-action-edit { background: #7c3aed; }
        .admin-main .btn-action-save { background: #059669; }
        .admin-main .btn-action-export { background: #15803d; }
        .admin-main .btn-action-print { background: #0891b2; }
        .admin-main .btn-action-back { background: #475569; }
        .admin-main .btn-action-reset { background: #b45309; }
        .admin-main .btn-action-next { background: #0f766e; }

        .admin-main .btn-action-view:hover { background: #1d4ed8; }
        .admin-main .btn-action-edit:hover { background: #6d28d9; }
        .admin-main .btn-action-save:hover { background: #047857; }
        .admin-main .btn-action-export:hover { background: #166534; }
        .admin-main .btn-action-print:hover { background: #0e7490; }
        .admin-main .btn-action-back:hover { background: #334155; }
        .admin-main .btn-action-reset:hover { background: #92400e; }
        .admin-main .btn-action-next:hover { background: #115e59; }

        .admin-main .btn-action-view i,
        .admin-main .btn-action-edit i,
        .admin-main .btn-action-save i,
        .admin-main .btn-action-export i,
        .admin-main .btn-action-print i,
        .admin-main .btn-action-back i,
        .admin-main .btn-action-reset i,
        .admin-main .btn-action-next i {
            color: inherit;
        }

        .admin-main .btn-warning,
        .admin-main .badge.bg-warning {
            color: #111827 !important;
        }

        /* Pagination styling */
        .pagination { margin-bottom: 0; font-size: 0.875rem; }
        .pagination .page-link { padding: 0.375rem 0.75rem; font-size: 0.875rem; }
        nav[role="navigation"] { font-size: 0.875rem; }

        /* ===== BOTTOM NAV (MOBILE) ===== */
        .mobile-bottom-nav { display: none; }
        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                position: fixed;
                left: 0; right: 0; bottom: 0;
                height: 62px;
                background: #ffffff;
                border-top: 1px solid #e5e7eb;
                box-shadow: 0 -4px 16px rgba(15,23,42,.08);
                z-index: 1050;
                padding-bottom: env(safe-area-inset-bottom, 0);
            }
            .mobile-bottom-nav a, .mobile-bottom-nav button {
                border: 0; background: none;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                gap: 2px; min-width: 0; padding: 4px 2px;
                color: #64748b; font-size: .64rem; font-weight: 600;
                text-decoration: none; position: relative;
                transition: color .15s;
            }
            .mobile-bottom-nav a i, .mobile-bottom-nav button i { font-size: 1.25rem; }
            .mobile-bottom-nav a.active { color: #059669; }
            .mobile-bottom-nav a.active i { color: #059669; }
            .mobile-bottom-nav .mbn-label { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .mobile-bottom-nav .mbn-badge {
                position: absolute; top: 6px; right: 50%; transform: translateX(18px);
                background: #ef4444; color: #fff; border-radius: 999px;
                font-size: .6rem; font-weight: 800; line-height: 1;
                min-width: 16px; padding: 2px 4px; text-align: center;
            }
            /* beri ruang agar konten tidak tertutup bottom-nav */
            .admin-main { padding-bottom: 74px !important; }
        }

        /* Sheet menu "Lainnya" (mobile) */
        .mbn-sheet-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 1060; display: none; }
        .mbn-sheet-backdrop.show { display: block; }
        .mbn-sheet {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 1061;
            background: #fff; border-radius: 18px 18px 0 0;
            padding: 16px 16px calc(16px + env(safe-area-inset-bottom, 0));
            transform: translateY(100%); transition: transform .25s ease;
            max-height: 80vh; overflow-y: auto;
        }
        .mbn-sheet.show { transform: translateY(0); }
        .mbn-sheet .mbn-grip { width: 44px; height: 5px; border-radius: 999px; background: #cbd5e1; margin: 0 auto 12px; }
        .mbn-sheet-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .mbn-sheet-item {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            padding: 12px 6px; border: 1px solid #e5e7eb; border-radius: 14px;
            color: #334155; text-decoration: none; font-size: .72rem; font-weight: 600; text-align: center;
            position: relative;
        }
        .mbn-sheet-item i { font-size: 1.35rem; color: #059669; }
        .mbn-sheet-item.active { border-color: #10b981; background: #f0fdf4; color: #047857; }
        .mbn-sheet-item .mbn-badge { position: absolute; top: 6px; right: 8px; transform: none; }
    </style>
</head>
<body>
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="admin-sidebar" id="sidebar">
        <!-- Header with brand + close button -->
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="sidebar-brand-text">
                    {{ $branding['nama_singkat'] ?? 'SPMB' }}
                    <small>{{ $branding['nama_institusi'] ?? 'Admin Panel' }}</small>
                </div>
            </div>
            <button class="sidebar-close" id="sidebarClose" title="Tutup Menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        @php $pengguna = auth('pengguna')->user(); @endphp

        <!-- Navigation -->
        <div class="sidebar-nav">
            @php
                $navAntre = 0;
                try {
                    $navStat = app(\App\Services\VerifikasiSpmbService::class)->ambilStatistik();
                    $navAntre = ($navStat['pembayaran_menunggu'] ?? 0)
                        + ($navStat['formulir_menunggu'] ?? 0)
                        + ($navStat['hasil_tes_menunggu'] ?? 0)
                        + ($navStat['pelunasan_menunggu'] ?? 0);
                } catch (\Throwable $e) { $navAntre = 0; }
            @endphp
            <div class="nav-section-label">Menu Utama</div>

            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i>Dashboard
            </a>

            <a class="nav-link {{ request()->routeIs('admin.alur-tahap') ? 'active' : '' }}" href="{{ route('admin.alur-tahap') }}">
                <i class="bi bi-signpost-2-fill"></i>Alur &amp; Tahap
            </a>

            @if($pengguna->bisaAkses('pengaturan'))
            <a class="nav-link {{ request()->routeIs('admin.alur-jadwal.*') ? 'active' : '' }}" href="{{ route('admin.alur-jadwal.index') }}">
                <i class="bi bi-calendar-week-fill"></i>Alur &amp; Jadwal
            </a>
            @endif

            <a class="nav-link {{ request()->routeIs('admin.alur-peserta.*') ? 'active' : '' }}" href="{{ route('admin.alur-peserta.index') }}">
                <i class="bi bi-people-fill"></i>Alur Peserta
            </a>

            @if($pengguna->bisaAkses('peserta'))
            <a class="nav-link {{ request()->routeIs('admin.peserta.*') ? 'active' : '' }}" href="{{ route('admin.peserta.index') }}">
                <i class="bi bi-person-vcard-fill"></i>Data Peserta
            </a>
            @endif

            @if($pengguna->bisaAkses('verifikasi'))
            <a class="nav-link {{ request()->routeIs('admin.verifikasi.*') ? 'active' : '' }}" href="{{ route('admin.verifikasi.index') }}">
                <i class="bi bi-clipboard-check-fill"></i>Verifikasi SPMB
                @if($navAntre > 0)<span class="queue-badge">{{ $navAntre > 99 ? '99+' : $navAntre }}</span>@endif
            </a>
            @endif




            <div class="nav-section-label">Ujian</div>

            @if($pengguna->bisaAkses('tes'))
            <a class="nav-link {{ request()->routeIs('admin.tes.*') ? 'active' : '' }}" href="{{ route('admin.tes.index') }}">
                <i class="bi bi-journal-text"></i>Tes
            </a>
            @endif

            @if($pengguna->bisaAkses('soal'))
            <a class="nav-link {{ request()->routeIs('admin.soal.*') ? 'active' : '' }}" href="{{ route('admin.soal.index') }}">
                <i class="bi bi-question-diamond-fill"></i>Bank Soal
            </a>
            @endif

            @if($pengguna->bisaAkses('monitoring_ujian'))
            <a class="nav-link {{ request()->routeIs('admin.monitoring-ujian.*') ? 'active' : '' }}" href="{{ route('admin.monitoring-ujian.index') }}">
                <i class="bi bi-display-fill"></i>Monitoring Ujian
            </a>
            @endif

            @if($pengguna->bisaAkses('hasil'))
            <a class="nav-link {{ request()->routeIs('admin.hasil.*') ? 'active' : '' }}" href="{{ route('admin.hasil.index') }}">
                <i class="bi bi-bar-chart-fill"></i>Hasil Ujian
            </a>
            @endif

            <div class="nav-section-label">Sistem</div>

            @if($pengguna->bisaAkses('pengaturan'))
            <a class="nav-link {{ request()->routeIs('admin.pengaturan.*') ? 'active' : '' }}" href="{{ route('admin.pengaturan.index') }}">
                <i class="bi bi-gear-fill"></i>Pengaturan
            </a>
            @endif

            @if($pengguna->bisaAkses('pengguna'))
            <a class="nav-link {{ request()->routeIs('admin.pengguna.*') ? 'active' : '' }}" href="{{ route('admin.pengguna.index') }}">
                <i class="bi bi-person-fill-gear"></i>Pengguna
            </a>
            @endif

            @if($pengguna->bisaAkses('log_aktivitas'))
            <a class="nav-link {{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}" href="{{ route('admin.log-aktivitas.index') }}">
                <i class="bi bi-clock-history"></i>Log Aktivitas
            </a>
            @endif
        </div>

        <!-- Footer / User -->
        <div class="sidebar-footer">
            <div class="dropdown">
                <button class="sidebar-user" data-bs-toggle="dropdown">
                    <div class="sidebar-user-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name">{{ auth('pengguna')->user()->nama ?? 'Admin' }}</div>
                        <div class="sidebar-user-role">{{ ucfirst(str_replace('_', ' ', auth('pengguna')->user()->peran ?? 'admin')) }}</div>
                    </div>
                    <i class="bi bi-three-dots-vertical" style="color: var(--sidebar-text); font-size: 0.85rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark mb-1" style="min-width: 220px;">
                    <li class="px-3 py-1">
                        <small class="text-muted"><i class="bi bi-shield-check me-1"></i>{{ ucfirst(str_replace('_', ' ', auth('pengguna')->user()->peran ?? 'admin')) }}</small>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="admin-main" id="mainContent">
        <!-- Top Bar -->
        <div class="admin-topbar">
            <button class="topbar-toggle" id="sidebarToggle" title="Toggle Menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="ms-3">
                <small class="text-muted fw-medium">@yield('title', 'Dashboard')</small>
            </div>

            {{-- ===== SWITCHER PERIODE AKTIF (Tahun Ajaran) ===== --}}
            @isset($periodePilihan)
            <div class="ms-auto dropdown periode-switcher">
                <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-2"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false"
                        title="Periode data yang sedang ditampilkan">
                    <i class="bi bi-calendar3-range"></i>
                    <span class="d-none d-sm-inline text-muted small">Periode:</span>
                    <span class="fw-semibold">{{ $periodeAktifLabel ?? 'Semua Periode' }}</span>
                    @if(!empty($periodeAktifSemua))
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Agregat</span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 240px;">
                    <li><h6 class="dropdown-header"><i class="bi bi-funnel me-1"></i>Tampilkan data periode</h6></li>
                    @foreach($periodePilihan as $ta)
                    <li>
                        <form action="{{ route('admin.periode-aktif.ganti') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="tahun_ajaran_id" value="{{ $ta->id }}">
                            <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between {{ (empty($periodeAktifSemua) && (int)($periodeAktifId ?? 0) === (int)$ta->id) ? 'active' : '' }}">
                                <span>
                                    <i class="bi bi-calendar-event me-2"></i>{{ $ta->nama }}
                                </span>
                                @if($ta->default)
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle ms-2">Aktif</span>
                                @elseif(!$ta->aktif)
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border ms-2">Arsip</span>
                                @endif
                            </button>
                        </form>
                    </li>
                    @endforeach
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('admin.periode-aktif.ganti') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="tahun_ajaran_id" value="semua">
                            <button type="submit" class="dropdown-item d-flex align-items-center {{ !empty($periodeAktifSemua) ? 'active' : '' }}">
                                <i class="bi bi-collection me-2"></i>Semua Periode <span class="text-muted small ms-1">(total)</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            @endisset
        </div>

        <!-- Content -->
        <div class="content-area">
            @include('partials.flash')

            @yield('content')
        </div>
    </div>

    {{-- ===== BOTTOM NAV (MOBILE) ===== --}}
    @php
        $mbnItems = [
            ['route' => 'admin.dashboard',       'match' => 'admin.dashboard',      'icon' => 'grid-1x2-fill',      'label' => 'Dashboard', 'akses' => null],
            ['route' => 'admin.alur-tahap',      'match' => 'admin.alur-tahap',     'icon' => 'signpost-2-fill',    'label' => 'Alur',      'akses' => null],
            ['route' => 'admin.verifikasi.index','match' => 'admin.verifikasi.*',   'icon' => 'clipboard-check-fill','label' => 'Verifikasi','akses' => 'verifikasi', 'badge' => $navAntre ?? 0],
            ['route' => 'admin.peserta.index',   'match' => 'admin.peserta.*',      'icon' => 'person-vcard-fill',  'label' => 'Peserta',   'akses' => 'peserta'],
        ];
        // Menu "Lainnya" (sheet)
        $mbnMore = [
            ['route' => 'admin.alur-peserta.index','match' => 'admin.alur-peserta.*','icon' => 'people-fill',          'label' => 'Alur Peserta', 'akses' => null],
            ['route' => 'admin.tes.index',         'match' => 'admin.tes.*',         'icon' => 'journal-text',         'label' => 'Tes',          'akses' => 'tes'],
            ['route' => 'admin.soal.index',        'match' => 'admin.soal.*',        'icon' => 'question-diamond-fill','label' => 'Bank Soal',    'akses' => 'soal'],
            ['route' => 'admin.monitoring-ujian.index','match' => 'admin.monitoring-ujian.*','icon' => 'display-fill','label' => 'Monitoring','akses' => 'monitoring_ujian'],
            ['route' => 'admin.hasil.index',       'match' => 'admin.hasil.*',       'icon' => 'bar-chart-fill',       'label' => 'Hasil Ujian',  'akses' => 'hasil'],
            ['route' => 'admin.pengaturan.index',  'match' => 'admin.pengaturan.*',  'icon' => 'gear-fill',            'label' => 'Pengaturan',   'akses' => 'pengaturan'],
            ['route' => 'admin.pengguna.index',    'match' => 'admin.pengguna.*',    'icon' => 'person-fill-gear',     'label' => 'Pengguna',     'akses' => 'pengguna'],
            ['route' => 'admin.log-aktivitas.index','match' => 'admin.log-aktivitas.*','icon' => 'clock-history',       'label' => 'Log Aktivitas','akses' => 'log_aktivitas'],
        ];
        $bolehTampil = fn($akses) => $akses === null || (isset($pengguna) && $pengguna->bisaAkses($akses));
    @endphp

    <nav class="mobile-bottom-nav" aria-label="Navigasi bawah">
        @foreach($mbnItems as $it)
            @if($bolehTampil($it['akses']))
            <a href="{{ route($it['route']) }}" class="{{ request()->routeIs($it['match']) ? 'active' : '' }}">
                <i class="bi bi-{{ $it['icon'] }}"></i>
                <span class="mbn-label">{{ $it['label'] }}</span>
                @if(!empty($it['badge']) && $it['badge'] > 0)
                    <span class="mbn-badge">{{ $it['badge'] > 99 ? '99+' : $it['badge'] }}</span>
                @endif
            </a>
            @endif
        @endforeach
        <button type="button" onclick="mbnToggleSheet(true)" aria-label="Menu lainnya">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            <span class="mbn-label">Lainnya</span>
        </button>
    </nav>

    {{-- Sheet menu lainnya --}}
    <div class="mbn-sheet-backdrop" id="mbnBackdrop" onclick="mbnToggleSheet(false)"></div>
    <div class="mbn-sheet" id="mbnSheet" role="dialog" aria-modal="true" aria-label="Menu lainnya">
        <div class="mbn-grip"></div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Menu Lainnya</h6>
            <button type="button" class="btn btn-sm btn-light" onclick="mbnToggleSheet(false)"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mbn-sheet-grid">
            @foreach($mbnMore as $it)
                @if($bolehTampil($it['akses']))
                <a href="{{ route($it['route']) }}" class="mbn-sheet-item {{ request()->routeIs($it['match']) ? 'active' : '' }}">
                    <i class="bi bi-{{ $it['icon'] }}"></i>
                    <span>{{ $it['label'] }}</span>
                </a>
                @endif
            @endforeach
        </div>
        <form action="{{ route('logout') }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="w-100 btn btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Keluar</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleBtn = document.getElementById('sidebarToggle');
            const closeBtn = document.getElementById('sidebarClose');
            const overlay = document.getElementById('sidebarOverlay');
            const isMobile = () => window.innerWidth <= 768;

            // Load saved state (desktop only)
            if (!isMobile()) {
                const saved = localStorage.getItem('sidebarCollapsed');
                if (saved === 'true') {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            }

            function openSidebar() {
                if (isMobile()) {
                    sidebar.classList.add('show');
                    overlay.classList.add('show');
                    document.body.style.overflow = 'hidden';
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    localStorage.setItem('sidebarCollapsed', 'false');
                }
            }

            function closeSidebar() {
                if (isMobile()) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                } else {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                    localStorage.setItem('sidebarCollapsed', 'true');
                }
            }

            // Toggle button on top bar → open sidebar
            toggleBtn.addEventListener('click', function() {
                if (sidebar.classList.contains('collapsed') || (isMobile() && !sidebar.classList.contains('show'))) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            });

            // Close button inside sidebar
            closeBtn.addEventListener('click', closeSidebar);

            // Overlay click (mobile)
            overlay.addEventListener('click', closeSidebar);

            // Handle resize
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                    const saved = localStorage.getItem('sidebarCollapsed');
                    if (saved === 'true') {
                        sidebar.classList.add('collapsed');
                        mainContent.classList.add('expanded');
                    } else {
                        sidebar.classList.remove('collapsed');
                        mainContent.classList.remove('expanded');
                    }
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    mainContent.classList.remove('expanded');
                }
            });

            // Auto-close sidebar on nav click (mobile)
            sidebar.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (isMobile()) closeSidebar();
                });
            });
        });
    </script>

    <script>
        // Bottom-nav sheet "Lainnya"
        function mbnToggleSheet(open) {
            var sheet = document.getElementById('mbnSheet');
            var bd = document.getElementById('mbnBackdrop');
            if (!sheet || !bd) return;
            if (open) { bd.classList.add('show'); requestAnimationFrame(function(){ sheet.classList.add('show'); }); document.body.style.overflow = 'hidden'; }
            else { sheet.classList.remove('show'); bd.classList.remove('show'); document.body.style.overflow = ''; }
        }
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') mbnToggleSheet(false); });
    </script>

    @stack('scripts')
</body>
</html>
