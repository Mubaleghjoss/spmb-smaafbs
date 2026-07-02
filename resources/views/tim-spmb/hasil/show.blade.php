@extends('layouts.tim-spmb')

@section('title', 'Hasil ' . $tes->nama)

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $tes->nama }}</h1>
            <small class="text-muted">Hasil Ujian</small>
        </div>
        <a href="{{ route('tim-spmb.hasil.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <!-- Statistik -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $statistik['total_peserta'] }}</h4>
                    <small>Total Peserta</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($statistik['rata_rata'], 1) }}</h4>
                    <small>Rata-rata</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($statistik['tertinggi'], 1) }}</h4>
                    <small>Tertinggi</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($statistik['terendah'], 1) }}</h4>
                    <small>Terendah</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $statistik['lulus'] }}</h4>
                    <small>Lulus</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $statistik['total_peserta'] - $statistik['lulus'] }}</h4>
                    <small>Tidak Lulus</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama atau nomor pendaftaran..." value="{{ request('cari') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Hasil -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Rank</th>
                            <th>Peserta</th>
                            <th>Nilai</th>
                            <th>Benar/Salah</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sesi as $index => $s)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $index < 3 ? 'warning' : 'secondary' }}">
                                    #{{ $sesi->firstItem() + $index }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $s->peserta->nama }}</strong><br>
                                <small class="text-muted">{{ $s->peserta->nomor_pendaftaran }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $s->nilai >= 70 ? 'success' : ($s->nilai >= 50 ? 'warning' : 'danger') }} fs-6">
                                    {{ number_format($s->nilai, 1) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-success">{{ $s->jawaban_benar ?? 0 }}</span> / 
                                <span class="text-danger">{{ $s->jawaban_salah ?? 0 }}</span>
                            </td>
                            <td>
                                @if($s->lulus)
                                    <span class="badge bg-success">Lulus</span>
                                @else
                                    <span class="badge bg-danger">Tidak Lulus</span>
                                @endif
                            </td>
                            <td>{{ $s->selesai_pada?->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada hasil
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sesi->hasPages())
        <div class="card-footer">
            {{ $sesi->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
