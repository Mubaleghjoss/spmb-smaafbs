@extends('layouts.tim-spmb')

@section('title', 'Verifikasi Pelunasan')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Verifikasi Pelunasan</h1>
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
                            <th>Jumlah</th>
                            <th>Bukti</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pembayaran as $p)
                        <tr>
                            <td>
                                <strong>{{ $p->peserta->nama }}</strong><br>
                                <small class="text-muted">{{ $p->peserta->nomor_pendaftaran }}</small>
                            </td>
                            <td>Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                            <td>
                                @if($p->bukti)
                                    <a href="{{ Storage::url($p->bukti) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-image"></i> Lihat
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <form action="{{ route('tim-spmb.verifikasi.pelunasan.terima', $p) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Terima pelunasan ini?')">
                                        <i class="bi bi-check"></i> Terima
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#tolakModal{{ $p->id }}">
                                    <i class="bi bi-x"></i> Tolak
                                </button>

                                <!-- Modal Tolak -->
                                <div class="modal fade" id="tolakModal{{ $p->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('tim-spmb.verifikasi.pelunasan.tolak', $p) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Tolak Pelunasan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Alasan Penolakan</label>
                                                        <textarea name="alasan" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Tolak</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                                Tidak ada pelunasan yang perlu diverifikasi
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($pembayaran->hasPages())
        <div class="card-footer">
            {{ $pembayaran->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
