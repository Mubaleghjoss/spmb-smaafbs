@extends('layouts.admin')

@section('title', 'Monitor: ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Monitor: {{ $tes->nama }}</h1>
            <small class="text-muted">
                Durasi: {{ $tes->durasi_menit }} menit | Soal: {{ $tes->soal()->count() }}
            </small>
        </div>
        <div>
            <a href="{{ route('admin.monitoring-ujian.riwayat', $tes) }}" class="btn btn-outline-secondary">
                <i class="bi bi-clock-history"></i> Riwayat
            </a>
            <a href="{{ route('admin.hasil.show', $tes) }}" class="btn btn-outline-info">
                <i class="bi bi-clipboard-data"></i> Hasil
            </a>
            <a href="{{ route('admin.monitoring-ujian.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistik -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0" id="peserta-online">{{ $pesertaOnline->count() }}</h3>
                    <small>Online</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $statistik['total_peserta'] }}</h3>
                    <small>Total Selesai</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ number_format($statistik['rata_rata'], 1) }}</h3>
                    <small>Rata-rata</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $statistik['nilai_tertinggi'] }}</h3>
                    <small>Tertinggi</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $statistik['lulus'] }}</h3>
                    <small>Lulus</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $statistik['tidak_lulus'] }}</h3>
                    <small>Tidak Lulus</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Peserta Online -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-broadcast text-success"></i> Peserta Sedang Mengerjakan
                <span class="badge bg-success ms-2">{{ $pesertaOnline->count() }}</span>
            </h6>
            <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            @if($pesertaOnline->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Tidak ada peserta yang sedang mengerjakan.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover" id="tabel-peserta">
                        <thead>
                            <tr>
                                <th>Peserta</th>
                                <th class="text-center">Progres</th>
                                <th class="text-center">Soal</th>
                                <th class="text-center">Waktu Mulai</th>
                                <th class="text-center">Waktu Tersisa</th>
                                <th>IP Address</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pesertaOnline as $data)
                                <tr>
                                    <td>
                                        <strong>{{ $data['peserta']->nama }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $data['peserta']->nomor_pendaftaran ?? '-' }}</small>
                                    </td>
                                    <td class="text-center" style="width: 150px;">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $data['progres'] >= 80 ? 'bg-success' : ($data['progres'] >= 50 ? 'bg-info' : 'bg-warning') }}" 
                                                 style="width: {{ $data['progres'] }}%">
                                                {{ $data['progres'] }}%
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ $data['dijawab'] }}/{{ $data['total_soal'] }} dijawab</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $data['soal_saat_ini'] }}/{{ $data['total_soal'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <small>{{ $data['waktu_mulai'] ? \Carbon\Carbon::parse($data['waktu_mulai'])->format('H:i') : '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $waktu = $data['waktu_tersisa'];
                                            $menit = floor($waktu / 60);
                                            $detik = $waktu % 60;
                                        @endphp
                                        <span class="badge {{ $menit < 5 ? 'bg-danger' : ($menit < 15 ? 'bg-warning text-dark' : 'bg-success') }}">
                                            {{ sprintf('%02d:%02d', $menit, $detik) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $data['ip_address'] ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#modalPerpanjang{{ $data['sesi_id'] }}">
                                                <i class="bi bi-clock-history me-1"></i> Tambah Waktu
                                            </button>
                                            <form method="POST" action="{{ route('admin.monitoring-ujian.paksa-selesai', $data['sesi_id']) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning w-100" onclick="return confirm('Paksa selesaikan sesi {{ $data['peserta']->nama }}?')">
                                                    <i class="bi bi-stop-circle me-1"></i> Paksa Selesai
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.monitoring-ujian.batalkan', $data['sesi_id']) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Batalkan sesi {{ $data['peserta']->nama }}? Data jawaban akan hilang.')">
                                                    <i class="bi bi-x-circle me-1"></i> Batalkan
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Modal Perpanjang Waktu -->
                                        <div class="modal fade" id="modalPerpanjang{{ $data['sesi_id'] }}" tabindex="-1">
                                            <div class="modal-dialog modal-sm">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('admin.monitoring-ujian.perpanjang', $data['sesi_id']) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h6 class="modal-title">Perpanjang Waktu</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small">Peserta: <strong>{{ $data['peserta']->nama }}</strong></p>
                                                            <p class="small text-muted">Waktu tersisa: {{ sprintf('%02d:%02d', $menit, $detik) }}</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Tambah Waktu (menit)</label>
                                                                <input type="number" name="menit" class="form-control" value="15" min="1" max="120" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                <i class="bi bi-clock-history me-1"></i> Perpanjang
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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

@push('scripts')
<script>
// Auto refresh setiap 15 detik
setTimeout(function() {
    location.reload();
}, 15000);
</script>
@endpush
@endsection

