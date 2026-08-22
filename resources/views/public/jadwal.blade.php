@extends('layouts.public')

@section('title', 'Jadwal SPMB')

@push('styles')
<style>
    .timeline { position: relative; padding-left: 2.5rem; }
    .timeline::before {
        content: ''; position: absolute; left: .85rem; top: .25rem; bottom: .25rem;
        width: 3px; background: linear-gradient(var(--primary-color), var(--secondary-color));
        border-radius: 3px;
    }
    .timeline-item { position: relative; margin-bottom: 1.75rem; }
    .timeline-dot {
        position: absolute; left: -2.5rem; top: .1rem;
        width: 1.9rem; height: 1.9rem; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .9rem; box-shadow: 0 0 0 4px #fff;
    }
    .timeline-card { border-radius: .9rem; }
</style>
@endpush

@section('content')
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h1 class="fw-bold">Jadwal SPMB</h1>
            <p class="text-muted lead mb-0">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? (date('Y') . '/' . (date('Y') + 1)) }}</p>
            @if(!empty($gelombangTerpilih))
                <p class="text-success fw-semibold mt-1 mb-0">
                    <i class="bi bi-layers-half me-1"></i>{{ $gelombangTerpilih->nama }}
                </p>
            @endif
        </div>

        {{-- Pemilih gelombang (jika ada >1) --}}
        @if(!empty($daftarGelombang) && $daftarGelombang->count() > 1)
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
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
                    <div class="timeline-item">
                        <span class="timeline-dot {{ $dotClass }}">
                            <i class="bi bi-{{ $item['icon'] ?? 'calendar' }}"></i>
                        </span>
                        <div class="card timeline-card border-0 shadow-sm">
                            <div class="card-body py-3">
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
                    <a href="{{ route('daftar') }}" class="btn btn-success btn-lg">
                        <i class="bi bi-pencil-square me-2"></i>Daftar Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
