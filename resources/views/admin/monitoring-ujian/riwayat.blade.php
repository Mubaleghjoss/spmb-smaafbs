@extends('layouts.admin')

@section('title', 'Riwayat Sesi: ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Riwayat Sesi</h1>
            <small class="text-muted">{{ $tes->nama }}</small>
        </div>
        <div>
            <a href="{{ route('admin.monitoring-ujian.show', $tes) }}" class="btn btn-outline-primary">
                <i class="bi bi-broadcast"></i> Monitor Live
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

    <div class="card">
        <div class="card-header">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="berlangsung" {{ request('status') === 'berlangsung' ? 'selected' : '' }}>Berlangsung</option>
                        <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="timeout" {{ request('status') === 'timeout' ? 'selected' : '' }}>Timeout</option>
                        <option value="dibatalkan" {{ request('status') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ request('tanggal') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if(request()->hasAny(['status', 'tanggal']))
                        <a href="{{ route('admin.monitoring-ujian.riwayat', $tes) }}" class="btn btn-sm btn-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-body">
            @if($riwayat->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Tidak ada riwayat sesi.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Peserta</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                                <th>Durasi</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($riwayat as $sesi)
                                <tr>
                                    <td>
                                        <strong>{{ $sesi->peserta->nama }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $sesi->peserta->nomor_pendaftaran ?? '-' }}</small>
                                    </td>
                                    <td>{{ $sesi->waktu_mulai->format('d/m/Y H:i:s') }}</td>
                                    <td>{{ $sesi->waktu_selesai?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                    <td>
                                        @if($sesi->waktu_selesai)
                                            {{ $sesi->durasi_menit_bulat }} menit
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($sesi->nilai !== null)
                                            <span class="badge {{ $sesi->nilai >= $tes->nilai_lulus ? 'bg-success' : 'bg-danger' }} fs-6">
                                                {{ number_format($sesi->nilai, 1) }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @switch($sesi->status)
                                            @case('berlangsung')
                                                <span class="badge bg-primary">Berlangsung</span>
                                                @break
                                            @case('selesai')
                                                <span class="badge bg-success">Selesai</span>
                                                @break
                                            @case('timeout')
                                                <span class="badge bg-warning text-dark">Timeout</span>
                                                @break
                                            @case('dibatalkan')
                                                <span class="badge bg-danger">Dibatalkan</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($sesi->status === 'berlangsung')
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" action="{{ route('admin.monitoring-ujian.paksa-selesai', $sesi) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Paksa selesaikan?')" title="Paksa Selesai">
                                                        <i class="bi bi-stop-circle"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.monitoring-ujian.reset', $sesi) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-info" onclick="return confirm('Reset sesi ini?')" title="Reset">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <a href="{{ route('admin.hasil.detail-peserta', [$tes, $sesi]) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif
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
@endsection
