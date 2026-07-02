@extends('layouts.admin')

@section('title', 'Monitoring Semua Peserta')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0"><i class="bi bi-people-fill me-2"></i>Monitoring Semua Peserta</h1>
        <a href="{{ route('admin.monitoring-ujian.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width:44px;height:44px;">
                        <i class="bi bi-people text-primary fs-5"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['totalPeserta'] }}</h4>
                    <small class="text-muted">Total Peserta</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width:44px;height:44px;">
                        <i class="bi bi-pencil-square text-info fs-5"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['sudahTes'] }}</h4>
                    <small class="text-muted">Sudah Tes</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width:44px;height:44px;">
                        <i class="bi bi-hourglass-split text-warning fs-5"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['sedangTes'] }}</h4>
                    <small class="text-muted">Sedang Tes</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width:44px;height:44px;">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <h4 class="mb-0">{{ $stats['selesaiTes'] }}</h4>
                    <small class="text-muted">Selesai Tes</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter & Search --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1">Cari Peserta</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="cari" class="form-control" placeholder="Nama atau No. Pendaftaran..." value="{{ request('cari') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Status Tes</label>
                    <select name="status_tes" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="belum_tes" {{ request('status_tes') === 'belum_tes' ? 'selected' : '' }}>Belum Tes</option>
                        <option value="berlangsung" {{ request('status_tes') === 'berlangsung' ? 'selected' : '' }}>Sedang Tes</option>
                        <option value="selesai" {{ request('status_tes') === 'selesai' ? 'selected' : '' }}>Sudah Selesai</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('admin.monitoring-ujian.semua-peserta') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Peserta List --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Peserta ({{ $pesertaList->total() }})</h5>
            <small class="text-muted">Klik baris untuk melihat detail tes</small>
        </div>
        <div class="card-body p-0">
            @forelse($pesertaList as $index => $peserta)
            @php
                $sesiList = $sesiTesAll->get($peserta->id, collect());
                $sesiTesIds = $sesiList->pluck('tes_id')->toArray();
                $belumDikerjakan = $semuaTes->filter(fn($t) => !in_array($t->id, $sesiTesIds));
            @endphp
            <div class="border-bottom">
                {{-- Peserta header row (clickable) --}}
                <div class="d-flex justify-content-between align-items-center p-3 bg-hover-light"
                     style="cursor:pointer;"
                     data-bs-toggle="collapse"
                     data-bs-target="#peserta-detail-{{ $peserta->id }}"
                     aria-expanded="false">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-chevron-right small text-muted toggle-icon" id="icon-{{ $peserta->id }}"></i>
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:36px;height:36px;font-size:0.8rem;font-weight:600;color:var(--bs-primary)">
                            {{ strtoupper(substr($peserta->nama, 0, 2)) }}
                        </div>
                        <div>
                            <span class="fw-semibold">{{ $peserta->nama }}</span>
                            <span class="text-muted small ms-2">{{ $peserta->nomor_pendaftaran }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap align-items-center">
                        @if($peserta->sesi_berlangsung > 0)
                            <span class="badge bg-warning text-dark"><i class="bi bi-play-circle me-1"></i>Sedang Tes</span>
                        @endif
                        @if($peserta->sesi_selesai > 0)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>{{ $peserta->sesi_selesai }} Selesai</span>
                        @endif
                        @if($peserta->total_sesi === 0)
                            <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Belum Tes</span>
                        @endif
                        <span class="badge bg-primary bg-opacity-75">Tahap {{ $peserta->tahap_saat_ini }}</span>
                        <span class="badge bg-light text-dark border">{{ $semuaTes->count() }} Tes</span>
                    </div>
                </div>

                {{-- Collapsible detail --}}
                <div class="collapse" id="peserta-detail-{{ $peserta->id }}">
                    <div class="px-3 pb-3">

                        {{-- Tes yang sudah dikerjakan --}}
                        @if($sesiList->isNotEmpty())
                        <div class="mb-3">
                            <h6 class="small fw-bold text-success mb-2"><i class="bi bi-check-all me-1"></i>Tes yang Sudah Dikerjakan ({{ $sesiList->count() }})</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Tes</th>
                                            <th class="text-center" style="width:80px;">Status</th>
                                            <th class="text-center" style="width:60px;">Nilai</th>
                                            <th style="width:130px;">Waktu</th>
                                            <th class="text-center" style="width:200px;">Aksi Admin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sesiList as $sesi)
                                        <tr>
                                            <td>
                                                <span class="fw-medium">{{ $sesi->tes->nama ?? 'Tes Dihapus' }}</span>
                                                @if($sesi->tes)
                                                <br><small class="text-muted">{{ $sesi->tes->durasi_menit }}m • KKM: {{ $sesi->tes->nilai_lulus }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">
                                                @switch($sesi->status)
                                                    @case('berlangsung')
                                                        <span class="badge bg-warning text-dark">Berlangsung</span>
                                                        @break
                                                    @case('selesai')
                                                        <span class="badge bg-success">Selesai</span>
                                                        @break
                                                    @case('timeout')
                                                        <span class="badge bg-danger">Timeout</span>
                                                        @break
                                                    @case('dibatalkan')
                                                        <span class="badge bg-dark">Dibatalkan</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $sesi->status }}</span>
                                                @endswitch
                                            </td>
                                            <td class="text-center align-middle fw-bold {{ $sesi->nilai !== null ? ($sesi->tes && $sesi->nilai >= $sesi->tes->nilai_lulus ? 'text-success' : 'text-danger') : '' }}">
                                                {{ $sesi->nilai !== null ? number_format($sesi->nilai, 1) : '-' }}
                                            </td>
                                            <td class="align-middle">
                                                @if($sesi->waktu_mulai)
                                                    {{ $sesi->waktu_mulai->format('d/m/Y H:i') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">
                                                @if($sesi->status === 'berlangsung')
                                                    {{-- Paksa Selesai --}}
                                                    <form action="{{ route('admin.monitoring-ujian.paksa-selesai', $sesi) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Paksa selesaikan sesi tes {{ $peserta->nama }}?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm py-0 px-2" title="Paksa Selesai">
                                                            <i class="bi bi-check-square me-1"></i>Selesai
                                                        </button>
                                                    </form>

                                                    {{-- Tambah Waktu --}}
                                                    <button class="btn btn-primary btn-sm py-0 px-2" title="Tambah Waktu"
                                                            data-bs-toggle="modal" data-bs-target="#modalTambahWaktu{{ $sesi->id }}">
                                                        <i class="bi bi-clock-history me-1"></i>+Waktu
                                                    </button>

                                                    {{-- Batalkan --}}
                                                    <form action="{{ route('admin.monitoring-ujian.batalkan', $sesi) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Batalkan & reset tes {{ $peserta->nama }}? Semua jawaban akan dihapus!')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger btn-sm py-0 px-2" title="Batalkan Tes (Reset)">
                                                            <i class="bi bi-x-circle me-1"></i>Batal
                                                        </button>
                                                    </form>

                                                    {{-- Modal Tambah Waktu --}}
                                                    <div class="modal fade" id="modalTambahWaktu{{ $sesi->id }}" tabindex="-1">
                                                        <div class="modal-dialog modal-sm">
                                                            <div class="modal-content">
                                                                <form action="{{ route('admin.monitoring-ujian.perpanjang', $sesi) }}" method="POST">
                                                                    @csrf
                                                                    <div class="modal-header py-2">
                                                                        <h6 class="modal-title">Tambah Waktu</h6>
                                                                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p class="small text-muted mb-2">Peserta: <strong>{{ $peserta->nama }}</strong></p>
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number" name="menit" class="form-control" min="1" max="120" value="15" required>
                                                                            <span class="input-group-text">menit</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer py-2">
                                                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                                                            <i class="bi bi-clock-history me-1"></i>Perpanjang
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                @elseif(in_array($sesi->status, ['selesai', 'timeout']))
                                                    {{-- Reset Sesi --}}
                                                    <form action="{{ route('admin.monitoring-ujian.reset', $sesi) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Reset tes {{ $peserta->nama }}? Semua jawaban & waktu direset ulang!')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning btn-sm py-0 px-2" title="Reset Ulang">
                                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                                        </button>
                                                    </form>

                                                    {{-- Loloskan (Verifikasi) --}}
                                                    @if(!$sesi->status_verifikasi_tes || $sesi->status_verifikasi_tes === 'menunggu')
                                                    <form action="{{ route('admin.verifikasi.hasil-tes.loloskan', $sesi) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Loloskan tes {{ $peserta->nama }}?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm py-0 px-2" title="Loloskan Tes">
                                                            <i class="bi bi-check-circle me-1"></i>Loloskan
                                                        </button>
                                                    </form>

                                                    {{-- Ulangi Tes --}}
                                                    <form action="{{ route('admin.verifikasi.hasil-tes.tolak', $sesi) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Hapus sesi & ijinkan {{ $peserta->nama }} mengulang tes ini?')">
                                                        @csrf
                                                        <input type="hidden" name="alasan" value="Diulangi dari monitoring ujian">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Ulangi Tes">
                                                            <i class="bi bi-arrow-repeat me-1"></i>Ulangi
                                                        </button>
                                                    </form>
                                                    @else
                                                    <span class="badge bg-success py-1 px-2">
                                                        <i class="bi bi-check me-1"></i>Diloloskan
                                                    </span>
                                                    @endif
                                                @elseif($sesi->status === 'dibatalkan')
                                                    {{-- Paksa Selesai (dari dibatalkan) --}}
                                                    <form action="{{ route('admin.monitoring-ujian.paksa-selesai', $sesi) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Paksa selesaikan sesi tes {{ $peserta->nama }} yang dibatalkan?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm py-0 px-2" title="Paksa Selesai">
                                                            <i class="bi bi-check-square me-1"></i>Selesai
                                                        </button>
                                                    </form>

                                                    {{-- Reset Sesi (dari dibatalkan) --}}
                                                    <form action="{{ route('admin.monitoring-ujian.reset', $sesi) }}" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Reset tes {{ $peserta->nama }}? Semua jawaban & waktu direset ulang!')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning btn-sm py-0 px-2" title="Reset Ulang">
                                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        {{-- Tes yang belum dikerjakan --}}
                        @if($belumDikerjakan->isNotEmpty())
                        <div>
                            <h6 class="small fw-bold text-warning mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Tes Belum Dikerjakan ({{ $belumDikerjakan->count() }})</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Tes</th>
                                            <th class="text-center" style="width:80px;">Durasi</th>
                                            <th class="text-center" style="width:80px;">KKM</th>
                                            <th class="text-center" style="width:80px;">Status</th>
                                            <th class="text-center" style="width:200px;">Aksi Admin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($belumDikerjakan as $tes)
                                        <tr>
                                            <td><span class="fw-medium">{{ $tes->nama }}</span></td>
                                            <td class="text-center align-middle">{{ $tes->durasi_menit }} menit</td>
                                            <td class="text-center align-middle">{{ $tes->nilai_lulus }}</td>
                                            <td class="text-center align-middle">
                                                @if(in_array($tes->status, ['aktif', 'berlangsung']))
                                                    <span class="badge bg-info">{{ ucfirst($tes->status) }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($tes->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">
                                                <form action="{{ route('admin.monitoring-ujian.paksa-selesai-tanpa-sesi') }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Paksa selesaikan tes \'{{ $tes->nama }}\' untuk {{ $peserta->nama }}? Sesi tes akan dibuat otomatis dengan nilai KKM.')">
                                                    @csrf
                                                    <input type="hidden" name="peserta_id" value="{{ $peserta->id }}">
                                                    <input type="hidden" name="tes_id" value="{{ $tes->id }}">
                                                    <button type="submit" class="btn btn-outline-success btn-sm py-0 px-2">
                                                        <i class="bi bi-check-square me-1"></i>Paksa Selesai
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        {{-- Link ke detail peserta --}}
                        <div class="mt-2 text-end">
                            <a href="{{ route('admin.peserta.show', $peserta) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person me-1"></i>Lihat Detail Peserta
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <p class="mt-3 text-muted">Tidak ada peserta ditemukan.</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($pesertaList->hasPages())
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Menampilkan {{ $pesertaList->firstItem() }}-{{ $pesertaList->lastItem() }} dari {{ $pesertaList->total() }}</small>
                <div class="d-flex gap-2">
                    @if($pesertaList->onFirstPage())
                        <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-chevron-left"></i></button>
                    @else
                        <a href="{{ $pesertaList->previousPageUrl() }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-left"></i></a>
                    @endif
                    @if($pesertaList->hasMorePages())
                        <a href="{{ $pesertaList->nextPageUrl() }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-chevron-right"></i></a>
                    @else
                        <button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-chevron-right"></i></button>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Legend --}}
    <div class="card border-0 shadow-sm mt-3 mb-4">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan Aksi:</strong>
                <span class="ms-2"><i class="bi bi-check-square text-success"></i> Paksa Selesai</span>
                <span class="ms-2"><i class="bi bi-clock-history text-primary"></i> Tambah Waktu</span>
                <span class="ms-2"><i class="bi bi-x-circle text-danger"></i> Batalkan (Reset Jawaban)</span>
                <span class="ms-2"><i class="bi bi-arrow-counterclockwise text-warning"></i> Reset Ulang</span>
            </small>
        </div>
    </div>
</div>

<style>
    .bg-hover-light:hover {
        background-color: rgba(0,0,0,.03);
    }
    .toggle-icon {
        transition: transform 0.2s ease;
    }
    .collapse.show + .d-flex .toggle-icon,
    [aria-expanded="true"] .toggle-icon {
        transform: rotate(90deg);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Rotate toggle icons on collapse show/hide
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(el) {
        const target = document.querySelector(el.getAttribute('data-bs-target'));
        if (!target) return;

        target.addEventListener('show.bs.collapse', function() {
            el.querySelector('.toggle-icon').style.transform = 'rotate(90deg)';
        });
        target.addEventListener('hide.bs.collapse', function() {
            el.querySelector('.toggle-icon').style.transform = 'rotate(0deg)';
        });
    });
});
</script>
@endsection
