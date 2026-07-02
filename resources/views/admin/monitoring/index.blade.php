@extends('layouts.admin')

@section('title', 'Monitoring SPMB')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-graph-up me-2"></i>Monitoring SPMB</h4>
        <div>
            <a href="{{ route('admin.monitoring.peserta') }}" class="btn btn-primary">
                <i class="bi bi-people me-2"></i>Daftar Peserta
            </a>
            <a href="{{ route('admin.monitoring.ekspor') }}" class="btn btn-success">
                <i class="bi bi-download me-2"></i>Ekspor CSV
            </a>
        </div>
    </div>

    {{-- Statistik Ringkas --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-25 p-3 me-3">
                            <i class="bi bi-people text-primary fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['total_peserta'] }}</h3>
                            <small class="text-muted">Total Peserta</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['peserta_per_tahap']['selesai'] ?? 0 }}</h3>
                            <small class="text-muted">Diterima</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-25 p-3 me-3">
                            <i class="bi bi-calendar-day text-info fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['peserta_baru_hari_ini'] }}</h3>
                            <small class="text-muted">Baru Hari Ini</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3">
                            <i class="bi bi-calendar-week text-warning fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['peserta_baru_minggu_ini'] }}</h3>
                            <small class="text-muted">Minggu Ini</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart Peserta per Tahap --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Distribusi Peserta per Tahap</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @for($i = 1; $i <= 7; $i++)
                <div class="col">
                    <div class="text-center mb-2">
                        <small class="text-muted d-block">Tahap {{ $i }}</small>
                        <strong>{{ $statistik['peserta_per_tahap'][$i] ?? 0 }}</strong>
                    </div>
                    <div class="progress" style="height: 100px;">
                        <div class="progress-bar bg-{{ $i == 7 ? 'success' : 'primary' }}" 
                             role="progressbar" 
                             style="height: {{ $statistik['persentase_per_tahap'][$i] ?? 0 }}%; width: 100%;"
                             aria-valuenow="{{ $statistik['persentase_per_tahap'][$i] ?? 0 }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <small class="text-muted d-block text-center mt-1">{{ $statistik['persentase_per_tahap'][$i] ?? 0 }}%</small>
                </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Detail per Tahap --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Detail per Tahap</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tahap</th>
                            <th>Nama Tahap</th>
                            <th>Jumlah Peserta</th>
                            <th>Persentase</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 7; $i++)
                        <tr>
                            <td><span class="badge bg-{{ $i == 7 ? 'success' : 'primary' }}">{{ $i }}</span></td>
                            <td>{{ $statistik['tahap_labels'][$i] }}</td>
                            <td>{{ $statistik['peserta_per_tahap'][$i] ?? 0 }}</td>
                            <td>
                                <div class="progress" style="width: 100px; height: 8px;">
                                    <div class="progress-bar" style="width: {{ $statistik['persentase_per_tahap'][$i] ?? 0 }}%"></div>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('admin.monitoring.peserta', ['tahap' => $i]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
