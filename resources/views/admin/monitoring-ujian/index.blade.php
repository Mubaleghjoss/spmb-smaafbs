@extends('layouts.admin')

@section('title', 'Monitoring Ujian')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0">Monitoring Ujian</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring-ujian.semua-peserta') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-people-fill me-1"></i>Semua Peserta
            </a>
            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Statistik Dashboard -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Tes Aktif</h6>
                            <h2 class="mb-0">{{ $statistik['tes_aktif'] }}</h2>
                        </div>
                        <i class="bi bi-play-circle display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Peserta Online</h6>
                            <h2 class="mb-0">{{ $statistik['peserta_online'] }}</h2>
                        </div>
                        <i class="bi bi-people display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Sesi Hari Ini</h6>
                            <h2 class="mb-0">{{ $statistik['sesi_hari_ini'] }}</h2>
                        </div>
                        <i class="bi bi-calendar-check display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Rata-rata Nilai</h6>
                            <h2 class="mb-0">{{ number_format($statistik['rata_rata_nilai_hari_ini'], 1) }}</h2>
                        </div>
                        <i class="bi bi-graph-up display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Tes Aktif -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-broadcast"></i> Tes Sedang Berlangsung</h6>
                </div>
                <div class="card-body">
                    @if($tesAktif->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="mt-3 text-muted">Tidak ada tes yang sedang berlangsung.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Tes</th>
                                        <th class="text-center">Online</th>
                                        <th class="text-center">Selesai</th>
                                        <th class="text-center">Soal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tesAktif as $tes)
                                        <tr>
                                            <td>
                                                <strong>{{ $tes->nama }}</strong>
                                                <br>
                                                <small class="text-muted">Durasi: {{ $tes->durasi_menit }} menit</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success fs-6">{{ $tes->peserta_online }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">{{ $tes->peserta_selesai }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $tes->soal_count ?? $tes->soal()->count() }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.monitoring-ujian.show', $tes) }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> Monitor
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Ringkasan Tes</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Total Tes</span>
                        <span class="badge bg-primary">{{ $statistik['total_tes'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Draft</span>
                        <span class="badge bg-secondary">{{ $statistik['tes_draft'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Aktif</span>
                        <span class="badge bg-success">{{ $statistik['tes_aktif'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Selesai</span>
                        <span class="badge bg-info">{{ $statistik['tes_selesai'] }}</span>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Aktivitas Hari Ini</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Sesi Dimulai</span>
                        <span class="badge bg-primary">{{ $statistik['sesi_hari_ini'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Sesi Selesai</span>
                        <span class="badge bg-success">{{ $statistik['sesi_selesai_hari_ini'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Aktivitas -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Aktivitas Per Jam (Hari Ini)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            @foreach($grafikAktivitas as $data)
                                <th class="text-center" style="font-size: 0.75rem;">{{ $data['jam'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($grafikAktivitas as $data)
                                <td class="text-center">
                                    @if($data['sesi_mulai'] > 0)
                                        <span class="badge bg-primary">{{ $data['sesi_mulai'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            <small class="text-muted">Angka menunjukkan jumlah sesi yang dimulai per jam</small>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto refresh setiap 30 detik
setTimeout(function() {
    location.reload();
}, 30000);
</script>
@endpush
@endsection
