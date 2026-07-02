@extends('layouts.public')

@section('title', 'Kontak')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Hubungi Kami</h1>
            <p class="text-muted lead">Ada pertanyaan? Jangan ragu untuk menghubungi {{ $branding['nama_institusi'] ?? 'kami' }}</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-geo-alt text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h5>Alamat</h5>
                        <p class="text-muted mb-0">{!! nl2br(e($branding['alamat'] ?? '-')) !!}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-telephone text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h5>Telepon</h5>
                        <p class="text-muted mb-0">{{ $branding['telepon'] ?? '-' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-envelope text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h5>Email</h5>
                        <p class="text-muted mb-0">{{ $branding['email'] ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($branding['website']))
        <div class="text-center mt-4">
            <a href="{{ $branding['website'] }}" target="_blank" class="btn btn-outline-success">
                <i class="bi bi-globe me-2"></i>{{ $branding['website'] }}
            </a>
        </div>
        
    </div>
    <div class="container mt-5">
        <div class="card border-0 shadow-sm">
                        <!-- Elfsight Instagram Feed | Untitled Instagram Feed -->
            <script src="https://elfsightcdn.com/platform.js" async></script>
            <div class="elfsight-app-3286249f-fe0e-4a0a-a5d2-0a225fd6b889" data-elfsight-app-lazy></div>
        </div>
    </div>
    <div class="container mt-5">
        <div class="card border-0 shadow-sm">
                    <!-- Elfsight Facebook Feed | Untitled Facebook Feed -->
            <script src="https://elfsightcdn.com/platform.js" async></script>
            <div class="elfsight-app-72b4180c-929c-4c3d-a833-9197d075ca21" data-elfsight-app-lazy></div>
    
        </div>
    </div>
    <div class="container mt-5">
        <div class="card border-0 shadow-sm">
                    <!-- Elfsight TikTok Feed | Untitled TikTok Feed -->
            <script src="https://elfsightcdn.com/platform.js" async></script>
            <div class="elfsight-app-5b9c5718-7dcf-44e0-857b-7c3e4ae8f350" data-elfsight-app-lazy></div>
        </div>
    </div>
    <div class="container mt-5">
                <div class="card border-0 shadow-sm">
                            <!-- Elfsight YouTube Gallery | Untitled YouTube Gallery -->
                <script src="https://elfsightcdn.com/platform.js" async></script>
                <div class="elfsight-app-9ad46348-6b62-4fdc-94f6-46d56f96b736" data-elfsight-app-lazy></div>
    </div>
    @endif    
    </div>
</section>
@endsection

