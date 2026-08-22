@extends('layouts.public')

@section('title', 'Beranda')

@push('styles')
{{-- Beranda-specific (design-system 'tk' & tema hijau kini di layout public) --}}
<style>
    .tk-hero h1 { font-weight: 700; font-size: clamp(2.1rem, 5.2vw, 3.6rem); line-height: 1.05; }
    .tk-hero .tk-sub { font-size: 1.05rem; line-height: 1.7; color: rgba(255,255,255,.9); }

    /* plate kaca hero */
    .tk-hero-plate {
        border-radius: 2rem; padding: .5rem;
        background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.22);
        box-shadow: 0 40px 80px -40px rgba(0,0,0,.5);
    }
    .tk-hero-plate .inner {
        border-radius: calc(2rem - .5rem); overflow: hidden;
        background: rgba(255,255,255,.06);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.18);
        aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center;
    }
    .tk-hero-plate .inner img { width: 100%; height: 100%; object-fit: cover; }
    .tk-hero-plate .inner .ico { font-size: clamp(4rem, 12vw, 7rem); color: rgba(255,255,255,.85); }

    .tk-stat { text-align: center; }
    .tk-stat .num { font-family: 'Fraunces', serif; font-weight: 700; font-size: clamp(1.9rem, 4vw, 2.6rem); color: var(--secondary-color); line-height: 1; }
    .tk-stat .lbl { color: var(--tk-muted); font-size: .86rem; margin-top: .4rem; }

    .tk-step .core { text-align: center; }
    .tk-step .no {
        width: 44px; height: 44px; border-radius: 50%; margin: 0 auto .75rem;
        display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        box-shadow: 0 10px 20px -8px rgba(16,36,26,.5);
    }

    .tk-cta {
        border-radius: 2rem; overflow: hidden; position: relative; color: #fff;
        background:
            radial-gradient(60% 120% at 85% 10%, rgba(255,255,255,.16), transparent 50%),
            linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        box-shadow: 0 40px 80px -40px rgba(16,36,26,.6);
    }

    .testi-avatar { width: 56px; height: 56px; object-fit: cover; }
</style>
@endpush

@section('content')

{{-- ============ 1. HERO ============ --}}
<section class="tk-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                @if(!empty($beranda['hero_badge']))
                <span class="tk-eyebrow mb-3"><span class="dot"></span>{{ $beranda['hero_badge'] }}</span>
                @endif
                <h1 class="mb-3">{{ $beranda['hero_judul'] }}</h1>
                <p class="tk-sub mb-1 fw-semibold">{{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}</p>
                <p class="tk-sub mb-4" style="opacity:.8">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
                @if(!empty($beranda['hero_subjudul']))
                <p class="tk-sub mb-4">{!! nl2br(e($beranda['hero_subjudul'])) !!}</p>
                @endif
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('daftar') }}" class="tk-btn tk-btn-primary">
                        {{ $beranda['hero_tombol1_teks'] ?: 'Daftar Sekarang' }}
                        <span class="tk-btn-ico"><i class="bi bi-arrow-up-right"></i></span>
                    </a>
                    <a href="{{ route('alur-spmb') }}" class="tk-btn tk-btn-ghost">
                        {{ $beranda['hero_tombol2_teks'] ?: 'Lihat Alur SPMB' }}
                        <span class="tk-btn-ico"><i class="bi bi-arrow-right"></i></span>
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="tk-hero-plate mx-auto reveal" style="max-width: 380px;">
                    <div class="inner">
                        @if(!empty($beranda['hero_gambar']))
                            <img src="{{ Storage::url($beranda['hero_gambar']) }}" alt="Ilustrasi">
                        @elseif(!empty($branding['logo']))
                            <img src="{{ Storage::url($branding['logo']) }}" alt="Logo" style="object-fit:contain; padding:2rem;">
                        @else
                            <span class="ico"><i class="bi bi-mortarboard-fill"></i></span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ 2. STATISTIK ============ --}}
@if($beranda['statistik_aktif'] && !empty($beranda['statistik']))
<section class="tk-section cream">
    <div class="container">
        <div class="row g-3 g-md-4">
            @foreach($beranda['statistik'] as $stat)
            @php
                $angka = strtolower(trim($stat['angka'] ?? ''));
                $nilai = $angka === 'auto' ? number_format($totalPendaftar ?? 0, 0, ',', '.') : ($stat['angka'] ?? '');
            @endphp
            <div class="col-6 col-md-3 reveal">
                <div class="tk-card">
                    <div class="core tk-stat">
                        <i class="bi bi-{{ $stat['icon'] ?: 'star-fill' }} mb-2 d-block" style="font-size:1.4rem;color:var(--secondary-color)"></i>
                        <div class="num">{{ $nilai }}{{ $stat['suffix'] ?? '' }}</div>
                        <div class="lbl">{{ $stat['label'] ?? '' }}</div>
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
<section class="tk-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="tk-eyebrow dark mb-3"><span class="dot"></span>Kenapa Kami</span>
            <h2 class="tk-h2">{{ $beranda['keunggulan_judul'] }}</h2>
            @if(!empty($beranda['keunggulan_subjudul']))<p class="tk-lead mt-2">{{ $beranda['keunggulan_subjudul'] }}</p>@endif
        </div>
        <div class="row g-4">
            @foreach($beranda['keunggulan'] as $item)
            <div class="col-md-4 reveal">
                <div class="tk-card">
                    <div class="core">
                        <div class="tk-ico-tile mb-3"><i class="bi bi-{{ $item['icon'] ?: 'check-circle' }}"></i></div>
                        <h5 class="fw-bold mb-2" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ $item['judul'] }}</h5>
                        <p class="text-muted mb-0">{{ $item['deskripsi'] }}</p>
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
<section class="tk-section cream">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="tk-eyebrow dark mb-3"><span class="dot"></span>Program</span>
            <h2 class="tk-h2">{{ $beranda['program_judul'] }}</h2>
            @if(!empty($beranda['program_subjudul']))<p class="tk-lead mt-2">{{ $beranda['program_subjudul'] }}</p>@endif
        </div>
        <div class="row g-4">
            @foreach($beranda['program'] as $item)
            <div class="col-6 col-md-3 reveal">
                <div class="tk-card">
                    <div class="core text-center">
                        <div class="tk-ico-tile mx-auto mb-3"><i class="bi bi-{{ $item['icon'] ?: 'star' }}"></i></div>
                        <h6 class="fw-bold mb-1">{{ $item['judul'] }}</h6>
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
<section class="tk-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="tk-eyebrow dark mb-3"><span class="dot"></span>Alur</span>
            <h2 class="tk-h2">{{ count($alurSpmb) }} Tahapan SPMB</h2>
            <p class="tk-lead mt-2">{{ $branding['teks_alur_spmb'] ?? 'Ikuti setiap tahapan untuk menjadi bagian dari keluarga besar' }} {{ $branding['nama_institusi'] ?? 'SMA Al Furqon' }}</p>
        </div>
        <div class="row g-3 justify-content-center">
            @foreach($alurSpmb as $index => $item)
            <div class="col-6 col-md-3 col-lg reveal">
                <div class="tk-card tk-step">
                    <div class="core">
                        <div class="no">{{ $item['nomor'] ?? ($index + 1) }}</div>
                        <i class="bi bi-{{ $item['icon'] ?? 'circle' }} mb-2 d-block" style="font-size:1.35rem;color:var(--secondary-color)"></i>
                        <p class="small mb-0 fw-semibold">{{ $item['judul'] ?? '' }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-5">
            <a href="{{ route('alur-spmb') }}" class="tk-btn tk-btn-primary" style="color:#1a1300;">
                Lihat Detail Alur
                <span class="tk-btn-ico"><i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
    </div>
</section>
@endif

{{-- ============ 6. FAQ ============ --}}
@if(!empty($beranda['faq']))
<section class="tk-section cream">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="tk-eyebrow dark mb-3"><span class="dot"></span>FAQ</span>
            <h2 class="tk-h2">{{ $beranda['faq_judul'] }}</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    @foreach($beranda['faq'] as $i => $item)
                    <div class="accordion-item mb-3 reveal">
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

{{-- ============ 7. TESTIMONI ============ --}}
@if(!empty($beranda['testimoni']))
<section class="tk-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="tk-eyebrow dark mb-3"><span class="dot"></span>Testimoni</span>
            <h2 class="tk-h2">{{ $beranda['testimoni_judul'] }}</h2>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach($beranda['testimoni'] as $t)
            <div class="col-md-6 col-lg-5 reveal">
                <div class="tk-card">
                    <div class="core">
                        <i class="bi bi-quote fs-1 lh-1" style="color:var(--secondary-color)"></i>
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

{{-- ============ PETA ============ --}}
@if(!empty($beranda['maps_embed']))
<section class="tk-section cream">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <span class="tk-eyebrow dark mb-3"><span class="dot"></span>Lokasi</span>
            <h2 class="tk-h2">Lokasi Kami</h2>
        </div>
        <div class="reveal" style="border-radius:1.5rem;padding:.45rem;background:#fff;border:1px solid rgba(16,36,26,.06);box-shadow:0 24px 50px -34px rgba(16,36,26,.28);">
            <div class="ratio ratio-21x9" style="border-radius:1.1rem;overflow:hidden;">
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
    </div>
</section>
@endif

{{-- ============ CTA ============ --}}
<section class="tk-section">
    <div class="container">
        <div class="tk-cta reveal">
            <div class="p-5 text-center">
                <span class="tk-eyebrow mb-3"><span class="dot"></span>Ayo Bergabung</span>
                <h3 class="fw-bold mb-3" style="font-family:'Fraunces',serif;font-size:clamp(1.6rem,3.4vw,2.4rem);">Siap Bergabung?</h3>
                <p class="mb-4 mx-auto" style="max-width:40rem;opacity:.92;">{{ $branding['teks_cta'] ?? 'Daftarkan diri Anda sekarang dan mulai perjalanan menuju masa depan yang cerah' }} bersama {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}.</p>
                <a href="{{ route('daftar') }}" class="tk-btn tk-btn-primary">
                    Daftar Sekarang
                    <span class="tk-btn-ico"><i class="bi bi-arrow-up-right"></i></span>
                </a>
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
    }, { threshold: 0.08 });
    els.forEach(e => obs.observe(e));
});
</script>
@endpush
