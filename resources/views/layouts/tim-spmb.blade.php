<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tim SPMB') - {{ $branding['nama_singkat'] ?? 'SPMB' }}</title>
    @if(!empty($branding['favicon']))
    <link rel="icon" href="{{ asset('storage/' . $branding['favicon']) }}" type="image/x-icon">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 0px;
            --transition-speed: 0.3s;
        }
        
        .sidebar { 
            width: var(--sidebar-width); 
            min-height: 100vh; 
            transition: transform var(--transition-speed) ease, width var(--transition-speed) ease;
        }
        
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        .main-content { 
            margin-left: var(--sidebar-width); 
            min-height: 100vh; 
            transition: margin-left var(--transition-speed) ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .sidebar-toggle {
            position: fixed;
            top: 10px;
            left: 260px;
            z-index: 1050;
            background: #198754;
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: left var(--transition-speed) ease;
        }
        
        .sidebar-toggle:hover {
            background: #157347;
        }
        
        .sidebar-toggle.collapsed {
            left: 10px;
        }
        
        .sidebar-toggle i {
            transition: transform var(--transition-speed) ease;
        }
        
        .sidebar-toggle.collapsed i {
            transform: rotate(180deg);
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .sidebar { 
                width: var(--sidebar-width); 
                min-height: 100vh;
                position: fixed;
                z-index: 1045;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                left: 10px;
                top: 10px;
            }
            
            .sidebar-toggle.show {
                left: 260px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Menu">
        <i class="bi bi-chevron-left"></i>
    </button>
    
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-success text-white p-3 position-fixed" id="sidebar">
            <div class="mb-4">
                <h5 class="text-white"><i class="bi bi-mortarboard me-2"></i>{{ $branding['nama_singkat'] ?? 'SPMB' }}</h5>
                <small class="text-white-50">Tim SPMB Panel</small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white {{ request()->routeIs('tim-spmb.dashboard') ? 'active bg-dark rounded' : '' }}" href="{{ route('tim-spmb.dashboard') }}">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tim-spmb.peserta.*') ? 'text-white active bg-dark rounded' : 'text-white-50' }}" href="{{ route('tim-spmb.peserta.index') }}">
                        <i class="bi bi-people me-2"></i>Peserta
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tim-spmb.verifikasi.*') ? 'text-white active bg-dark rounded' : 'text-white-50' }}" href="{{ route('tim-spmb.verifikasi.index') }}">
                        <i class="bi bi-clipboard-check me-2"></i>Verifikasi SPMB
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tim-spmb.hasil.*') ? 'text-white active bg-dark rounded' : 'text-white-50' }}" href="{{ route('tim-spmb.hasil.index') }}">
                        <i class="bi bi-bar-chart me-2"></i>Hasil Ujian
                    </a>
                </li>
            </ul>
            <hr class="border-light">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-2"></i>
                    <span>{{ auth('pengguna')->user()->nama ?? 'Tim SPMB' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content bg-light flex-grow-1" id="mainContent">
            @if(session('success') || session('sukses'))
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') ?? session('sukses') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleBtn = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');
            const isMobile = () => window.innerWidth <= 768;
            
            const savedState = localStorage.getItem('sidebarCollapsed');
            
            if (!isMobile() && savedState === 'true') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                toggleBtn.classList.add('collapsed');
            }
            
            toggleBtn.addEventListener('click', function() {
                if (isMobile()) {
                    sidebar.classList.toggle('show');
                    toggleBtn.classList.toggle('show');
                    overlay.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    toggleBtn.classList.toggle('collapsed');
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                toggleBtn.classList.remove('show');
                overlay.classList.remove('show');
            });
            
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    sidebar.classList.remove('show');
                    toggleBtn.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
