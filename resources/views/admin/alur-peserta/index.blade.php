@extends('layouts.admin')

@section('title', 'Alur Peserta')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-signpost-split me-2"></i>Alur Peserta SPMB</h1>
            <p class="text-muted mb-0">Pipeline tracking — lihat posisi semua peserta di setiap tahap</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.alur-peserta.ekspor', ['tahap' => $filterTahap]) }}" class="btn btn-action-export px-3 py-2">
                <i class="bi bi-download me-1"></i>Ekspor CSV
            </a>
            <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-check-circle me-1"></i>Lulus: {{ $lulus }}</span>
            <span class="badge bg-danger fs-6 px-3 py-2"><i class="bi bi-x-circle me-1"></i>Tidak Lulus: {{ $tidakLulus }}</span>
        </div>
    </div>

    {{-- Pipeline Cards --}}
    <div class="pipeline-scroll mb-4">
        <div class="pipeline-track">
            @for($i = 1; $i <= 7; $i++)
            @php
                $isActive = $filterTahap == $i;
                $pct = $total > 0 ? round(($perTahap[$i] / $total) * 100) : 0;
            @endphp
            <a href="{{ route('admin.alur-peserta.index', ['tahap' => $i, 'cari' => $filterCari]) }}" 
               class="pipeline-card {{ $isActive ? 'active' : '' }}" 
               style="--card-color: {{ $tahapColors[$i] }}">
                <div class="pipeline-step">Tahap {{ $i }}</div>
                <div class="pipeline-icon">
                    <i class="bi {{ $tahapIcons[$i] }}"></i>
                </div>
                <div class="pipeline-label">{{ $tahapLabels[$i] }}</div>
                <div class="pipeline-count">{{ $perTahap[$i] }}</div>
                <div class="pipeline-bar">
                    <div class="pipeline-bar-fill" style="width: {{ $pct }}%"></div>
                </div>
                <div class="pipeline-pct">{{ $pct }}%</div>
            </a>
            @if($i < 7)
            <div class="pipeline-arrow">
                <i class="bi bi-chevron-right"></i>
            </div>
            @endif
            @endfor
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <i class="bi bi-search text-muted"></i>
                    <input type="text" name="cari" class="form-control form-control-sm" 
                           placeholder="Cari nama atau no. pendaftaran..." value="{{ $filterCari }}"
                           style="max-width: 300px;">
                </div>
                @if($filterTahap)
                    <input type="hidden" name="tahap" value="{{ $filterTahap }}">
                @endif
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Cari
                </button>
                @if($filterTahap || $filterCari)
                <a href="{{ route('admin.alur-peserta.index') }}" class="btn btn-sm btn-action-reset">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabel Peserta --}}
    <div class="card">
        <div class="card-header bg-white d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
            <h5 class="mb-0">
                @if($filterTahap)
                    <span class="badge rounded-pill me-2" style="background: {{ $tahapColors[$filterTahap] }}">
                        Tahap {{ $filterTahap }}
                    </span>
                    {{ $tahapLabels[$filterTahap] }}
                @else
                    Semua Peserta
                @endif
                <small class="text-muted fw-normal">({{ $peserta->total() }})</small>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th class="d-none d-md-table-cell">Asal Sekolah</th>
                            <th>Tahap</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th style="width:80px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peserta as $index => $p)
                        @php
                            $tahap = $p->tahapanSpmb?->tahap_saat_ini ?? 1;
                            $progres = $p->tahapanSpmb?->persentase_progres ?? 0;
                            $statusKelulusan = $p->tahapanSpmb?->status_kelulusan;
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $peserta->firstItem() + $index }}</td>
                            <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                            <td>{{ $p->nama }}</td>
                            <td class="d-none d-md-table-cell text-muted">{{ $p->formulirSpmb?->asal_sekolah ?? '-' }}</td>
                            <td>
                                <span class="badge rounded-pill" style="background: {{ $tahapColors[$tahap] ?? '#6b7280' }}">
                                    {{ $tahap }} — {{ $tahapLabels[$tahap] ?? '?' }}
                                </span>
                            </td>
                            <td style="min-width: 120px">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: {{ $progres }}%; background: {{ $tahapColors[$tahap] ?? '#6b7280' }}"
                                             aria-valuenow="{{ $progres }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted" style="min-width: 30px">{{ $progres }}%</small>
                                </div>
                            </td>
                            <td>
                                @if($statusKelulusan === 'lulus')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Lulus</span>
                                @elseif($statusKelulusan === 'tidak_lulus')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Tidak Lulus</span>
                                @elseif($tahap >= 7)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>
                                @else
                                    <span class="badge bg-secondary">Proses</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.peserta.show', $p) }}" class="btn btn-sm btn-action-view" title="Detail">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                @if($filterTahap)
                                    Tidak ada peserta di tahap ini
                                @else
                                    Belum ada peserta terdaftar
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($peserta->hasPages())
        <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <small class="text-muted">{{ $peserta->firstItem() }}–{{ $peserta->lastItem() }} dari {{ $peserta->total() }}</small>
            <div class="d-flex gap-2">
                @if($peserta->previousPageUrl())
                    <a href="{{ $peserta->previousPageUrl() }}" class="btn btn-sm btn-action-back">
                        <i class="bi bi-chevron-left"></i> Prev
                    </a>
                @endif
                @if($peserta->nextPageUrl())
                    <a href="{{ $peserta->nextPageUrl() }}" class="btn btn-sm btn-action-next">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<style>
    .pipeline-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 8px;
    }

    .pipeline-track {
        display: flex;
        align-items: center;
        gap: 4px;
        min-width: max-content;
    }

    .pipeline-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        padding: 16px 18px;
        border-radius: 12px;
        background: white;
        border: 2px solid #e5e7eb;
        text-decoration: none;
        color: #111827;
        transition: all 0.25s;
        min-width: 110px;
        text-align: center;
        position: relative;
    }

    .pipeline-card:hover {
        border-color: var(--card-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        color: #111827;
    }

        .pipeline-card.active {
            border-color: var(--card-color);
            background: var(--card-color);
            color: #fff;
            box-shadow: 0 4px 16px color-mix(in srgb, var(--card-color) 20%, transparent);
        }

        .pipeline-card.active .pipeline-step,
        .pipeline-card.active .pipeline-count,
        .pipeline-card.active .pipeline-pct {
            color: #fff;
        }

        .pipeline-card.active .pipeline-icon {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        .pipeline-card.active .pipeline-bar {
            background: rgba(255,255,255,0.28);
        }

        .pipeline-card.active .pipeline-bar-fill {
            background: #fff;
        }

    .pipeline-step {
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--card-color);
    }

    .pipeline-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: color-mix(in srgb, var(--card-color) 12%, white);
        color: var(--card-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .pipeline-label {
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.2;
    }

    .pipeline-count {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--card-color);
    }

    .pipeline-bar {
        width: 100%;
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
    }

    .pipeline-bar-fill {
        height: 100%;
        background: var(--card-color);
        border-radius: 2px;
        transition: width 0.5s ease;
    }

    .pipeline-pct {
        font-size: 0.65rem;
        color: #9ca3af;
    }

    .pipeline-arrow {
        color: #d1d5db;
        font-size: 1rem;
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .pipeline-card {
            min-width: 90px;
            padding: 10px 12px;
        }
        .pipeline-icon {
            width: 32px; height: 32px;
            font-size: 1rem;
        }
        .pipeline-count {
            font-size: 1.1rem;
        }
    }
</style>
@endsection
