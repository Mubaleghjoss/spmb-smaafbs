@extends('layouts.public')

@section('title', 'Beranda')

@push('styles')
<style>
    .reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
    .reveal.is-visible { opacity: 1; transform: none; }
    .hero-overlay {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        position: relative; overflow: hidden;
    }
    .hero-overlay::after {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(circle at 80% 20%, rgba(255,255,255,.12), transparent 45%);
    }
    .stat-card { border-radius: 1rem; }
    .stat-num { font-size: 2.4rem; font-weight: 800; line-height: 1; }
    .feature-icon {
        width: 64px; height: 64px; border-radius: 1rem;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(46,139,87,.1);
    }
    .program-card { transition: transform .3s ease, box-shadow .3s ease; }
    .program-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(0,0,0,.12); }
    .float-wa {
        position: fixed; right: 18px; bottom: 18px; z-index: 1030;
        width: 56px; height: 56px; border-radius: 50%;
        background: #25d366; color: #fff; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 6px 18px rgba(0,0,0,.25); font-size: 1.6rem; text-decoration: none;
        transition: transform .2s ease;
    }
    .float-wa:hover { transform: scale(1.08); color:#fff; }
    .testi-card { border-radius: 1rem; }
    .testi-avatar { width: 56px; height: 56px; object-fit: cover; }
    .hero-illust-fallback {
        width: 260px; height: 260px; max-width: 80vw; max-height: 80vw;
        border-radius: 2rem;
        background: rgba(255,255,255,.14);
        border: 2px dashed rgba(255,255,255,.4);
        color: rgba(255,255,255,.85);
        font-size: 7rem;
    }
</style>
@endpush

@section('content')

{{-- ============ 1. HERO ============ --}}
<section class="hero-overlay text-white py-5">
    <div class="container position-relative" style="z-index:1">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                @if(!empty($beranda['hero_badge']))
                <span class="badge bg-warning text-dark mb-3 px-3 py-2">{{ $beranda['hero_badge'] }}</span>
                @endif
                <h1 class="display-5 fw-bold mb-3">{{ $beranda['hero_judul'] }}</h1>
                <p class="lead mb-2">{{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}</p>
                <p class="mb-4 opacity-75">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
                @if(!empty($beranda['hero_subjudul']))
                <p class="mb-4">{{ $beranda['hero_subjudul'] }}</p>
                @endif
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('daftar') }}" class="btn btn-warning btn-lg">
                        <i class="bi bi-pencil-square me-2"></i>{{ $beranda['hero_tombol1_teks'] ?: 'Daftar Sekarang' }}
                    </a>
                    <a href="{{ route('alur-spmb') }}" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-info-circle me-2"></i>{{ $beranda['hero_tombol2_teks'] ?: 'Lihat Alur SPMB' }}
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                @if(!empty($beranda['hero_gambar']))
                    <img src="{{ Storage::url($beranda['hero_gambar']) }}" alt="Ilustrasi" class="img-fluid rounded-4 shadow-lg" style="max-height: 380px;">
                @elseif(!empty($branding['logo']))
                    <img src="{{ Storage::url($branding['logo']) }}" alt="Logo" class="img-fluid" style="max-height: 240px;">
                @else
                    {{-- Placeholder ikon open-source (Bootstrap Icons, MIT) saat belum ada gambar --}}
                    <div class="hero-illust-fallback mx-auto d-flex align-items-center justify-content-center">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ============ 2. STATISTIK ============ --}}
@if($beranda['statistik_aktif'] && !empty($beranda['statistik']))
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-3 text-center">
            @foreach($beranda['statistik'] as $stat)
            @php
                $angka = strtolower(trim($stat['angka'] ?? ''));
                $nilai = $angka === 'auto' ? number_format($totalPendaftar ?? 0, 0, ',', '.') : ($stat['angka'] ?? '');
            @endphp
            <div class="col-6 col-md-3 reveal">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body py-4">
                        <i class="bi bi-{{ $stat['icon'] ?: 'star-fill' }} text-success fs-3 mb-2 d-block"></i>
                        <div class="stat-num text-success">{{ $nilai }}{{ $stat['suffix'] ?? '' }}</div>
                        <div class="text-muted small mt-1">{{ $stat['label'] ?? '' }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ 3. KEUNGGULAN ============ --}}
@if(!empty($beranda['keunggulan']))
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">{{ $beranda['keunggulan_judul'] }}</h2>
            @if(!empty($beranda['keunggulan_subjudul']))<p class="text-muted">{{ $beranda['keunggulan_subjudul'] }}</p>@endif
        </div>
        <div class="row g-4">
            @foreach($beranda['keunggulan'] as $item)
            <div class="col-md-4 reveal">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon mb-3"><i class="bi bi-{{ $item['icon'] ?: 'check-circle' }} text-success fs-3"></i></div>
                        <h5 class="card-title">{{ $item['judul'] }}</h5>
                        <p class="card-text text-muted">{{ $item['deskripsi'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ 4. PROGRAM UNGGULAN ============ --}}
@if(!empty($beranda['program']))
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">{{ $beranda['program_judul'] }}</h2>
            @if(!empty($beranda['program_subjudul']))<p class="text-muted">{{ $beranda['program_subjudul'] }}</p>@endif
        </div>
        <div class="row g-4">
            @foreach($beranda['program'] as $item)
            <div class="col-6 col-md-3 reveal">
                <div class="card program-card h-100 border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <i class="bi bi-{{ $item['icon'] ?: 'star' }} text-success" style="font-size:2.2rem"></i>
                        <h6 class="fw-bold mt-3">{{ $item['judul'] }}</h6>
                        <p class="small text-muted mb-0">{{ $item['deskripsi'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ 5. TAHAPAN PREVIEW ============ --}}
@if($beranda['tahapan_aktif'])
@php $alurSpmb = app(\App\Services\PengaturanService::class)->ambilAlurSpmb(); @endphp
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">{{ count($alurSpmb) }} Tahapan SPMB</h2>
            <p class="text-muted">{{ $branding['teks_alur_spmb'] ?? 'Ikuti setiap tahapan untuk menjadi bagian dari keluarga besar' }} {{ $branding['nama_institusi'] ?? 'SMA Al Furqon' }}</p>
        </div>
        <div class="row g-3 justify-content-center">
            @foreach($alurSpmb as $index => $item)
            <div class="col-6 col-md-3 col-lg reveal">
                <div class="card border-0 shadow-sm h-100 tahapan-card">
                    <div class="card-body text-center py-4">
                        <div class="tahapan-number mx-auto mb-3">{{ $item['nomor'] ?? ($index + 1) }}</div>
                        <i class="bi bi-{{ $item['icon'] ?? 'circle' }} text-success mb-2" style="font-size: 1.5rem;"></i>
                        <p class="small mb-0 fw-medium">{{ $item['judul'] ?? '' }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('alur-spmb') }}" class="btn btn-success">Lihat Detail Alur <i class="bi bi-arrow-right ms-2"></i></a>
        </div>
    </div>
</section>
@endif

{{-- ============ 6. FAQ ============ --}}
@if(!empty($beranda['faq']))
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">{{ $beranda['faq_judul'] }}</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    @foreach($beranda['faq'] as $i => $item)
                    <div class="accordion-item border-0 shadow-sm mb-2 rounded overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}">
                                {{ $item['tanya'] }}
                            </button>
                        </h2>
                        <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">{{ $item['jawab'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============ 7. TESTIMONI + PETA ============ --}}
@if(!empty($beranda['testimoni']))
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">{{ $beranda['testimoni_judul'] }}</h2>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach($beranda['testimoni'] as $t)
            <div class="col-md-6 col-lg-5 reveal">
                <div class="card testi-card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <i class="bi bi-quote text-success fs-1 lh-1"></i>
                        <p class="mb-4">{{ $t['isi'] }}</p>
                        <div class="d-flex align-items-center gap-3">
                            @if(!empty($t['foto']))
                                <img src="{{ Storage::url($t['foto']) }}" alt="{{ $t['nama'] }}" class="rounded-circle testi-avatar" onerror="this.style.display='none'">
                            @else
                                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center testi-avatar">
                                    <i class="bi bi-person-fill text-success fs-4"></i>
                                </div>
                            @endif
                            <div>
                                <div class="fw-semibold">{{ $t['nama'] }}</div>
                                <div class="small text-muted">{{ $t['peran'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!empty($beranda['maps_embed']))
<section class="pb-5">
    <div class="container">
        <div class="text-center mb-4"><h2 class="fw-bold">Lokasi Kami</h2></div>
        <div class="ratio ratio-21x9 rounded-4 overflow-hidden shadow-sm">
            @php
                $embed = trim($beranda['maps_embed']);
                $isIframe = \Illuminate\Support\Str::startsWith($embed, '<iframe');
            @endphp
            @if($isIframe)
                {!! $embed !!}
            @else
                <iframe src="{{ $embed }}" style="border:0" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            @endif
        </div>
    </div>
</section>
@endif

{{-- ============ CTA ============ --}}
<section class="py-5">
    <div class="container">
        <div class="card border-0 bg-success text-white">
            <div class="card-body p-5 text-center">
                <h3 class="fw-bold mb-3">Siap Bergabung?</h3>
                <p class="mb-4">{{ $branding['teks_cta'] ?? 'Daftarkan diri Anda sekarang dan mulai perjalanan menuju masa depan yang cerah' }} bersama {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}.</p>
                <a href="{{ route('daftar') }}" class="btn btn-warning btn-lg"><i class="bi bi-pencil-square me-2"></i>Daftar Sekarang</a>
            </div>
        </div>
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
    }, { threshold: 0.12 });
    els.forEach(e => obs.observe(e));
});
</script>
@endpush
