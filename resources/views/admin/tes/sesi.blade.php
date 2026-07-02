@extends('layouts.admin')

@section('title', 'Daftar Sesi - ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Daftar Sesi Peserta</h1>
            <p class="text-muted mb-0">{{ $tes->nama }}</p>
        </div>
        <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <!-- Filter Status -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.tes.sesi', [$tes, 'status' => 'semua']) }}" 
                   class="btn {{ $status === 'semua' ? 'btn-primary' : 'btn-outline-primary' }}">
                    Semua
                </a>
                <a href="{{ route('admin.tes.sesi', [$tes, 'status' => 'berlangsung']) }}" 
                   class="btn {{ $status === 'berlangsung' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="bi bi-play-circle me-1"></i> Sedang Ujian
                </a>
                <a href="{{ route('admin.tes.sesi', [$tes, 'status' => 'selesai']) }}" 
                   class="btn {{ $status === 'selesai' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    <i class="bi bi-check-circle me-1"></i> Selesai
                </a>
            </div>
        </div>
    </div>

    <!-- Daftar Sesi -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Peserta</th>
                            <th>No. Pendaftaran</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Nilai</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sesiList as $sesi)
                            <tr>
                                <td>
                                    <strong>{{ $sesi->peserta->nama }}</strong>
                                </td>
                                <td>{{ $sesi->peserta->nomor_pendaftaran }}</td>
                                <td class="text-center">
                                    @if($sesi->status === 'berlangsung')
                                        <span class="badge bg-success">Sedang Ujian</span>
                                    @elseif($sesi->status === 'selesai')
                                        <span class="badge bg-secondary">Selesai</span>
                                    @else
                                        <span class="badge bg-warning">{{ ucfirst($sesi->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($sesi->status === 'selesai' && $sesi->nilai !== null)
                                        <span class="{{ $sesi->nilai >= $tes->nilai_lulus ? 'text-success' : 'text-danger' }} fw-bold">
                                            {{ number_format($sesi->nilai, 1) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $sesi->waktu_mulai?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>{{ $sesi->waktu_selesai?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    @if($sesi->status === 'selesai')
                                        <a href="{{ route('admin.hasil.detail-peserta', [$tes, $sesi]) }}" 
                                           class="btn btn-sm btn-info text-white">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Belum ada sesi peserta
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sesiList->hasPages())
            <div class="card-footer">
                {{ $sesiList->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
