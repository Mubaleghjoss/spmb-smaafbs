@extends('layouts.tim-spmb')

@section('title', 'Verifikasi Hasil Tes')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Verifikasi Hasil Tes</h1>
        <a href="{{ route('tim-spmb.verifikasi.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Peserta</th>
                            <th>Tes</th>
                            <th>Nilai</th>
                            <th>Status</th>
                            <th>Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sesi as $s)
                        <tr>
                            <td>
                                <strong>{{ $s->peserta->nama }}</strong><br>
                                <small class="text-muted">{{ $s->peserta->nomor_pendaftaran }}</small>
                            </td>
                            <td>{{ $s->tes->nama }}</td>
                            <td>
                                <span class="badge bg-{{ $s->nilai >= 70 ? 'success' : ($s->nilai >= 50 ? 'warning' : 'danger') }} fs-6">
                                    {{ number_format($s->nilai, 1) }}
                                </span>
                            </td>
                            <td>
                                @if($s->lulus)
                                    <span class="badge bg-success">Lulus</span>
                                @else
                                    <span class="badge bg-danger">Tidak Lulus</span>
                                @endif
                            </td>
                            <td>{{ $s->selesai_pada?->format('d/m/Y H:i') }}</td>
                            <td>
                                <form action="{{ route('tim-spmb.verifikasi.hasil-tes.loloskan', $s) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Loloskan peserta ini ke tahap wawancara?')">
                                        <i class="bi bi-check"></i> Loloskan
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                                Tidak ada hasil tes yang perlu diverifikasi
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
