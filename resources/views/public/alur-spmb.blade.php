@extends('layouts.public')

@section('title', 'Alur SPMB')

@push('styles')
<style>
    /* ===== Alur SPMB — Timeline responsif (HP & desktop) ===== */
    .alur-hero {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: #fff;
    }
    .alur-timeline {
        position: relative;
        max-width: 820px;
        margin: 0 auto;
    }
    /* garis vertikal timeline */
    .alur-timeline::before {
        content: '';
        position: absolute;
        top: 8px;
        bottom: 8px;
        left: 27px;                 /* sejajar tengah badge nomor (HP) */
        width: 3px;
        background: linear-gradient(var(--primary-color), rgba(46,139,87,.15));
        border-radius: 3px;
    }
    .alur-item {
        position: relative;
        padding-left: 72px;         /* ruang untuk badge nomor */
        margin-bottom: 1.25rem;
    }
    .alur-item:last-child { margin-bottom: 0; }
    .alur-num {
        position: absolute;
        left: 0;
        top: 0;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        box-shadow: 0 4px 12px rgba(46,139,87,.35);
        z-index: 1;
    }
    .alur-num .bi { font-size: 1.35rem; }
    .alur-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #eef2f7;
        box-shadow: 0 2px 10px rgba(15,23,42,.05);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .alur-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(15,23,42,.10);
    }
    .alur-card .card-body { padding: 1.1rem 1.25rem; }
    .alur-step-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--primary-color);
    }
    .alur-title { font-weight: 700; font-size: 1.08rem; line-height: 1.3; }
    .alur-detail li {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        font-size: .9rem;
        color: #475569;
        margin-bottom: .35rem;
    }
    .alur-detail li .bi { color: #22c55e; flex-shrink: 0; margin-top: .15rem; }
    /* badge sorotan untuk langkah 1 (paling penting) */
    .alur-item.is-first .alur-card {
        border-color: rgba(46,139,87,.35);
        background: linear-gradient(180deg, rgba(46,139,87,.05), #fff);
    }
    .alur-badge-mudah {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .7rem; font-weight: 700; color: #146c43;
        background: #d1fae5; border: 1px solid #a7f3d0;
        padding: .2rem .5rem; border-radius: 999px;
    }

    /* Desktop: layout zig-zag dua kolom */
    @media (min-width: 768px) {
        .alur-timeline::before { left: 50%; transform: translateX(-50%); }
        .alur-item {
            width: 50%;
            padding-left: 0;
            padding-right: 48px;
            margin-left: 0;
        }
        .alur-item:nth-child(even) {
            margin-left: 50%;
            padding-right: 0;
            padding-left: 48px;
        }
        .alur-num {
            left: auto;
            right: -28px;
            top: 12px;
        }
        .alur-item:nth-child(even) .alur-num { right: auto; left: -28px; }
    }
</style>
@endpush

@section('content')
{{-- HERO --}}
<section class="alur-hero py-5">
    <div class="container text-center">
        <span class="badge bg-white text-success mb-3 px-3 py-2 rounded-pill">
            <i class="bi bi-signpost-split me-1"></i>Alur Pendaftaran
        </span>
        <h1 class="fw-bold display-6 mb-2">Alur SPMB</h1>
        <p class="lead mb-0 opacity-90">
            {{ $branding['teks_alur_spmb'] ?? 'Ikuti setiap tahapan untuk menjadi bagian dari keluarga besar' }}
            {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}
        </p>
        <p class="small opacity-75 mt-1">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
    </div>
</section>

{{-- TIMELINE --}}
<section class="py-5 bg-light">
    <div class="container">
        <div class="alur-timeline">
            @foreach($alurSpmb as $index => $item)
            <div class="alur-item {{ $index === 0 ? 'is-first' : '' }}">
                <div class="alur-num">
                    @if(!empty($item['icon']))
                        <i class="bi bi-{{ $item['icon'] }}"></i>
                    @else
                        {{ $item['nomor'] ?? ($index + 1) }}
                    @endif
                </div>
                <div class="alur-card">
                    <div class="card-body">
                        <div class="alur-step-label mb-1">Langkah {{ $item['nomor'] ?? ($index + 1) }}</div>
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <span class="alur-title">{{ $item['judul'] }}</span>
                            @if($index === 0)
                                <span class="alur-badge-mudah"><i class="bi bi-lightning-charge-fill"></i>Cukup 1 langkah</span>
                            @endif
                        </div>
                        <p class="text-muted mb-2" style="font-size:.92rem;">{{ $item['deskripsi'] }}</p>
                        @if(!empty($item['detail']))
                        <ul class="list-unstyled alur-detail mb-0">
                            @foreach($item['detail'] as $detail)
                            <li><i class="bi bi-check-circle-fill"></i><span>{{ $detail }}</span></li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="text-center mt-5">
            <a href="{{ route('daftar') }}" class="btn btn-success btn-lg px-4 shadow-sm">
                <i class="bi bi-pencil-square me-2"></i>Mulai Pendaftaran Sekarang
            </a>
            <p class="text-muted small mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>Daftar = langsung isi biodata, akun otomatis dibuat, dan masuk dashboard.
            </p>
        </div>
    </div>
</section>
@endsection
