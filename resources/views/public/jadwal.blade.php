@extends('layouts.public')

@section('title', 'Jadwal SPMB')

@push('styles')
<style>
    .timeline { position: relative; padding-left: 2.75rem; }
    .timeline::before {
        content: ''; position: absolute; left: .95rem; top: .25rem; bottom: .25rem;
        width: 3px; background: linear-gradient(var(--primary-color), rgba(46,139,87,.2));
        border-radius: 3px;
    }
    .timeline-item { position: relative; margin-bottom: 1.6rem; }
    .timeline-dot {
        position: absolute; left: -2.75rem; top: .35rem;
        width: 2rem; height: 2rem; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .9rem; box-shadow: 0 0 0 4px #fff, 0 8px 18px -6px rgba(16,36,26,.5);
    }
    .timeline-card { padding: .4rem; }
    .timeline-card .core {
        border-radius: .95rem; padding: 1rem 1.15rem;
        background: linear-gradient(180deg, #fff, #fbfdfb);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.9);
    }
</style>
@endpush

@section('content')
<section class="tk-hero">
    <div class="container text-center">
        <span class="tk-eyebrow mb-3"><span class="dot"></span>Jadwal SPMB</span>
        <h1 class="fw-bold display-6 mb-1">Jadwal SPMB</h1>
        <p class="tk-sub mb-0" style="opacity:.9">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? (date('Y') . '/' . (date('Y') + 1)) }}</p>
        @if(!empty($gelombangTerpilih))
            <p class="fw-semibold mt-2 mb-0"><i class="bi bi-layers-half me-1"></i>{{ $gelombangTerpilih->nama }}</p>
        @endif
    </div>
</section>

<section class="tk-section cream">
    <div class="container">
        {{-- Pemilih gelombang (jika ada >1) --}}
        @if(!empty($daftarGelombang) && $daftarGelombang->count() > 1)
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4 reveal">
            @foreach($daftarGelombang as $g)
                @php $st = $g->statusPendaftaran(); @endphp
                <a href="{{ route('jadwal', ['gelombang' => $g->id]) }}"
                   class="btn btn-sm {{ (optional($gelombangTerpilih)->id === $g->id) ? 'btn-success' : 'btn-outline-success' }}">
                    {{ $g->nama }}
                    <span class="badge bg-{{ $st['class'] }} ms-1">{{ $st['label'] }}</span>
                </a>
            @endforeach
        </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="timeline">
                    @foreach($jadwal as $item)
                    @php
                        $status = $item['status'] ?? 'info';
                        $dotClass = match($status) {
                            'dibuka' => 'bg-success',
                            'akan_datang' => 'bg-secondary',
                            'selesai' => 'bg-dark',
                            'persiapan' => 'bg-warning',
                            default => 'bg-info'
                        };
                        $badgeClass = match($status) {
                            'dibuka' => 'bg-success',
                            'akan_datang' => 'bg-secondary',
                            'selesai' => 'bg-dark',
                            'persiapan' => 'bg-warning text-dark',
                            default => 'bg-info'
                        };
                    @endphp
                    <div class="timeline-item reveal">
                        <span class="timeline-dot {{ $dotClass }}">
                            <i class="bi bi-{{ $item['icon'] ?? 'calendar' }}"></i>
                        </span>
                        <div class="tk-card timeline-card">
                            <div class="core">
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                    <div>
                                        <h6 class="fw-semibold mb-1">{{ $item['kegiatan'] }}</h6>
                                        <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ $item['tanggal'] }}</div>
                                        @if(!empty($item['catatan']))
                                            <div class="small text-secondary mt-1"><i class="bi bi-pin-angle me-1"></i>{{ $item['catatan'] }}</div>
                                        @endif
                                    </div>
                                    <span class="badge {{ $badgeClass }}">{{ $item['keterangan'] ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if(!empty($catatan))
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Catatan:</strong> {{ $catatan }}
                </div>
                @endif

                <div class="text-center mt-4">
                    <a href="{{ route('daftar') }}" class="tk-btn tk-btn-primary">
                        Daftar Sekarang
                        <span class="tk-btn-ico"><i class="bi bi-arrow-up-right"></i></span>
                    </a>
                </div>
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
