@extends('layouts.admin')

@section('title', 'Log Aktivitas')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-clock-history me-2"></i>Log Aktivitas</h1>
            <p class="text-muted mb-0 small">Rekaman setiap aksi Tim SPMB yang mengubah data</p>
        </div>
        <a href="{{ route('admin.log-aktivitas.ekspor', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i>Ekspor CSV
        </a>
    </div>

    {{-- Statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Hari Ini</div>
                    <div class="h4 mb-0">{{ number_format($statistik['hari_ini']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">7 Hari Terakhir</div>
                    <div class="h4 mb-0">{{ number_format($statistik['tujuh_hari']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Total Tercatat</div>
                    <div class="h4 mb-0">{{ number_format($statistik['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <div class="text-muted small">Teraktif (30 hari)</div>
                    <div class="fw-semibold text-truncate">{{ $statistik['teraktif'] ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.log-aktivitas.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3 col-6">
                    <label class="form-label small mb-1">Kategori</label>
                    <select name="kategori" class="form-select form-select-sm">
                        <option value="">Semua kategori</option>
                        @foreach($daftarKategori as $kode => $label)
                            <option value="{{ $kode }}" @selected(($filter['kategori'] ?? '') === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small mb-1">Pengguna</label>
                    <select name="pengguna_id" class="form-select form-select-sm">
                        <option value="">Semua pengguna</option>
                        @foreach($daftarPengguna as $u)
                            <option value="{{ $u->id }}" @selected((string)($filter['pengguna_id'] ?? '') === (string)$u->id)>{{ $u->nama }} ({{ $u->peran }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small mb-1">Dari Tanggal</label>
                    <input type="date" name="tanggal_dari" class="form-control form-control-sm" value="{{ $filter['tanggal_dari'] ?? '' }}">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small mb-1">Sampai Tanggal</label>
                    <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ $filter['tanggal_sampai'] ?? '' }}">
                </div>
                <div class="col-md-2 col-12">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="text" name="cari" class="form-control form-control-sm" value="{{ $filter['cari'] ?? '' }}" placeholder="Nama / keterangan">
                </div>
                <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Terapkan
                    </button>
                    <a href="{{ route('admin.log-aktivitas.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel (layar sedang ke atas) --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:140px">Waktu</th>
                        <th style="min-width:140px">Pengguna</th>
                        <th style="min-width:110px">Kategori</th>
                        <th>Keterangan</th>
                        <th style="min-width:120px">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($log as $l)
                    <tr>
                        <td class="text-nowrap small">
                            {{ $l->created_at?->format('d/m/Y') }}<br>
                            <span class="text-muted">{{ $l->created_at?->format('H:i:s') }}</span>
                        </td>
                        <td class="small">
                            <div class="fw-semibold text-break">{{ $l->nama_pengguna }}</div>
                            @if($l->peran)<span class="badge bg-light text-muted">{{ $l->peran }}</span>@endif
                            @if($l->pengguna_id === null)
                                <span class="badge bg-secondary" title="Akun sudah dihapus">akun dihapus</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $l->kategori_warna }}">
                                <i class="bi bi-{{ $l->kategori_ikon }} me-1"></i>{{ $l->kategori_label }}
                            </span>
                        </td>
                        <td class="small">
                            <div class="text-break">{{ $l->keterangan }}</div>
                            @if($l->subjek_label)
                                <div class="text-muted text-break">
                                    <i class="bi bi-arrow-return-right me-1"></i>{{ $l->subjek_label }}
                                    @unless($l->subjek_masih_ada)
                                        <span class="badge bg-light text-danger">data dihapus</span>
                                    @endunless
                                </div>
                            @endif
                            @if($l->data)
                                <details class="mt-1">
                                    <summary class="text-muted" style="cursor:pointer;font-size:.75rem">Detail</summary>
                                    <pre class="mb-0 mt-1 p-2 bg-light rounded" style="font-size:.72rem;white-space:pre-wrap;word-break:break-word">{{ json_encode($l->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                            <div class="text-muted" style="font-size:.7rem"><code>{{ $l->aksi }}</code></div>
                        </td>
                        <td class="small text-muted">{{ $l->ip ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Belum ada aktivitas tercatat
                            @if(array_filter($filter))
                                dengan filter ini
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Kartu (HP) --}}
        <div class="d-md-none">
            @forelse($log as $l)
            <div class="border-bottom p-3">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                    <span class="badge bg-{{ $l->kategori_warna }}">
                        <i class="bi bi-{{ $l->kategori_ikon }} me-1"></i>{{ $l->kategori_label }}
                    </span>
                    <small class="text-muted text-nowrap">{{ $l->created_at?->format('d/m H:i') }}</small>
                </div>
                <div class="small text-break">{{ $l->keterangan }}</div>
                @if($l->subjek_label)
                    <div class="small text-muted text-break">
                        <i class="bi bi-arrow-return-right me-1"></i>{{ $l->subjek_label }}
                    </div>
                @endif
                <div class="small text-muted mt-1">
                    <i class="bi bi-person me-1"></i>{{ $l->nama_pengguna }}
                    @if($l->peran)<span class="text-muted">({{ $l->peran }})</span>@endif
                </div>
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>Belum ada aktivitas tercatat
            </div>
            @endforelse
        </div>

        @if($log->hasPages())
        <div class="card-footer bg-white">
            {{ $log->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
