@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold mb-3">Penerimaan Murid Baru</h1>
                <h2 class="h4 mb-4">{{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}</h2>
                <p class="lead mb-4">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
                <p class="mb-4">{{ $branding['teks_hero'] ?? 'Bergabunglah bersama kami untuk menjadi generasi Qurani yang berakhlak mulia, berprestasi, dan siap menghadapi tantangan masa depan.' }}</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('daftar') }}" class="btn btn-warning btn-lg">
                        <i class="bi bi-pencil-square me-2"></i>Daftar Sekarang
                    </a>
                    <a href="{{ route('alur-spmb') }}" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-info-circle me-2"></i>Lihat Alur SPMB
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="{{ asset('images/hero-illustration.png') }}" alt="Ilustrasi" class="img-fluid" style="max-height: 400px;" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</section>

<!-- Info Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-calendar-check text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="card-title">Pendaftaran Online</h5>
                        <p class="card-text text-muted">Daftar kapan saja dan di mana saja melalui sistem online kami yang mudah digunakan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-laptop text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="card-title">Tes Online</h5>
                        <p class="card-text text-muted">Ikuti tes seleksi secara online dengan sistem CBT yang aman dan terpercaya.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-shield-check text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="card-title">Proses Transparan</h5>
                        <p class="card-text text-muted">Pantau status pendaftaran Anda secara real-time melalui dashboard peserta.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tahapan Preview -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">7 Tahapan SPMB</h2>
            <p class="text-muted">{{ $branding['teks_alur_spmb'] ?? 'Ikuti setiap tahapan untuk menjadi bagian dari keluarga besar' }} {{ $branding['nama_institusi'] ?? 'SMA Al Furqon' }}</p>
        </div>
        
        <div class="row g-3 justify-content-center">
            @php
                $tahapan = [
                    ['icon' => 'person-plus', 'label' => 'Buat Akun'],
                    ['icon' => 'file-earmark-text', 'label' => 'Isi Formulir'],
                    ['icon' => 'credit-card', 'label' => 'Bayar Formulir'],
                    ['icon' => 'laptop', 'label' => 'Tes Online'],
                    ['icon' => 'people', 'label' => 'Wawancara'],
                    ['icon' => 'cash-stack', 'label' => 'Pelunasan'],
                    ['icon' => 'info-circle', 'label' => 'Info Kelulusan'],
                ];
            @endphp
            
            @foreach($tahapan as $index => $item)
            <div class="col-6 col-md-3 col-lg">
                <div class="card border-0 shadow-sm h-100 tahapan-card">
                    <div class="card-body text-center py-4">
                        <div class="tahapan-number mx-auto mb-3">{{ $index + 1 }}</div>
                        <i class="bi bi-{{ $item['icon'] }} text-success mb-2" style="font-size: 1.5rem;"></i>
                        <p class="small mb-0 fw-medium">{{ $item['label'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <div class="text-center mt-4">
            <a href="{{ route('alur-spmb') }}" class="btn btn-success">
                Lihat Detail Alur <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5">
    <div class="container">
        <div class="card border-0 bg-success text-white">
            <div class="card-body p-5 text-center">
                <h3 class="fw-bold mb-3">Siap Bergabung?</h3>
                <p class="mb-4">{{ $branding['teks_cta'] ?? 'Daftarkan diri Anda sekarang dan mulai perjalanan menuju masa depan yang cerah' }} bersama {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}.</p>
                <a href="{{ route('daftar') }}" class="btn btn-warning btn-lg">
                    <i class="bi bi-pencil-square me-2"></i>Daftar Sekarang
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
