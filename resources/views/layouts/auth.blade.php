<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - {{ $branding['nama_institusi'] ?? 'SPMB' }}</title>
    @if(!empty($branding['favicon']))
    <link rel="icon" href="{{ asset('storage/' . $branding['favicon']) }}" type="image/x-icon">
    @endif
    
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a5f2a 0%, #2e8b57 100%);
        }
        
        .auth-card {
            max-width: 420px;
            width: 100%;
        }
        
        .auth-logo {
            width: 80px;
            height: 80px;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-4">
    <div class="auth-card">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="auth-logo mb-3" onerror="this.style.display='none'">
                    <h4 class="fw-bold text-success">{{ $branding['nama_institusi'] ?? 'SPMB' }}</h4>
                    <p class="text-muted small">@yield('subtitle', 'Sistem Penerimaan Murid Baru')</p>
                </div>
                
                @yield('content')
                
                <div class="text-center mt-4">
                    <a href="{{ route('beranda') }}" class="text-decoration-none small">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
        
        <p class="text-center text-white-50 small mt-3">
            &copy; {{ date('Y') }} {{ $branding['nama_institusi'] ?? 'SPMB' }}
        </p>
    </div>
</body>
</html>
