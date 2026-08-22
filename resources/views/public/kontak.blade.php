@extends('layouts.public')

@section('title', 'Kontak')

@section('content')
<section class="tk-hero">
    <div class="container text-center">
        <span class="tk-eyebrow mb-3"><span class="dot"></span>Kontak</span>
        <h1 class="fw-bold display-6 mb-2">Hubungi Kami</h1>
        <p class="tk-sub mb-0" style="opacity:.92">Ada pertanyaan? Jangan ragu untuk menghubungi {{ $branding['nama_institusi'] ?? 'kami' }}</p>
    </div>
</section>

<section class="tk-section cream">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 reveal">
                <div class="tk-card">
                    <div class="core text-center">
                        <div class="tk-ico-tile mx-auto mb-3"><i class="bi bi-geo-alt"></i></div>
                        <h5 class="fw-bold" style="font-family:'Plus Jakarta Sans',sans-serif;">Alamat</h5>
                        <p class="text-muted mb-0">{!! nl2br(e($branding['alamat'] ?? '-')) !!}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 reveal">
                <div class="tk-card">
                    <div class="core text-center">
                        <div class="tk-ico-tile mx-auto mb-3"><i class="bi bi-telephone"></i></div>
                        <h5 class="fw-bold" style="font-family:'Plus Jakarta Sans',sans-serif;">Telepon</h5>
                        <p class="text-muted mb-0">{{ $branding['telepon'] ?? '-' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 reveal">
                <div class="tk-card">
                    <div class="core text-center">
                        <div class="tk-ico-tile mx-auto mb-3"><i class="bi bi-envelope"></i></div>
                        <h5 class="fw-bold" style="font-family:'Plus Jakarta Sans',sans-serif;">Email</h5>
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
        <div class="tk-card"><div class="core p-2">
                        <!-- Elfsight Instagram Feed | Untitled Instagram Feed -->
            <script src="https://elfsightcdn.com/platform.js" async></script>
            <div class="elfsight-app-3286249f-fe0e-4a0a-a5d2-0a225fd6b889" data-elfsight-app-lazy></div>
        </div></div>
    </div>
    <div class="container mt-4">
        <div class="tk-card"><div class="core p-2">
                    <!-- Elfsight Facebook Feed | Untitled Facebook Feed -->
            <script src="https://elfsightcdn.com/platform.js" async></script>
            <div class="elfsight-app-72b4180c-929c-4c3d-a833-9197d075ca21" data-elfsight-app-lazy></div>
        </div></div>
    </div>
    <div class="container mt-4">
        <div class="tk-card"><div class="core p-2">
                    <!-- Elfsight TikTok Feed | Untitled TikTok Feed -->
            <script src="https://elfsightcdn.com/platform.js" async></script>
            <div class="elfsight-app-5b9c5718-7dcf-44e0-857b-7c3e4ae8f350" data-elfsight-app-lazy></div>
        </div></div>
    </div>
    <div class="container mt-4">
                <div class="tk-card"><div class="core p-2">
                            <!-- Elfsight YouTube Gallery | Untitled YouTube Gallery -->
                <script src="https://elfsightcdn.com/platform.js" async></script>
                <div class="elfsight-app-9ad46348-6b62-4fdc-94f6-46d56f96b736" data-elfsight-app-lazy></div>
    </div></div>
    @endif    
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('is-visible')); return; }
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('is-visible'); obs.unobserve(en.target); } });
    }, { threshold: 0.08 });
    els.forEach(e => obs.observe(e));
});
</script>
@endpush

