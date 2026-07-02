@extends('layouts.admin')

@section('title', 'Log Perubahan Tahapan')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i>Log Perubahan Tahapan</h4>
        <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>

    {{-- Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Filter Tahap</label>
                    <select name="tahap" class="form-select">
                        <option value="">Semua Tahap</option>
                        @for($i = 1; $i <= 7; $i++)
                            <option value="{{ $i }}" {{ ($filter['tahap'] ?? '') == $i ? 'selected' : '' }}>Tahap {{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="tanggal_dari" class="form-control" value="{{ $filter['tanggal_dari'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="tanggal_sampai" class="form-control" value="{{ $filter['tanggal_sampai'] ?? '' }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.monitoring.log') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Tidak ada log perubahan</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Peserta</th>
                                <th>Tahap</th>
                                <th>Aksi</th>
                                <th>Status</th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <code>{{ $log->peserta->nomor_pendaftaran }}</code><br>
                                    <small class="text-muted">{{ $log->peserta->nama }}</small>
                                </td>
                                <td><span class="badge bg-primary">Tahap {{ $log->tahap }}</span></td>
                                <td>{{ ucfirst(str_replace('_', ' ', $log->aksi)) }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->status_lama ? 'success' : 'secondary' }}">{{ $log->status_lama ? 'Selesai' : 'Belum' }}</span>
                                    <i class="bi bi-arrow-right mx-1"></i>
                                    <span class="badge bg-{{ $log->status_baru ? 'success' : 'secondary' }}">{{ $log->status_baru ? 'Selesai' : 'Belum' }}</span>
                                </td>
                                <td>{{ $log->admin?->nama ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="p-3">
                    {{ $logs->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
