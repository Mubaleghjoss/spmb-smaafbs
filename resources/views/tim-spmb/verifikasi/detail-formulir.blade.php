@extends('layouts.tim-spmb')

@section('title', 'Detail Formulir')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Formulir</h1>
        <a href="{{ route('tim-spmb.verifikasi.formulir') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Data Peserta</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted" width="40%">Nama Lengkap</td>
                                    <td>{{ $formulir->nama_lengkap }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tempat Lahir</td>
                                    <td>{{ $formulir->tempat_lahir ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Lahir</td>
                                    <td>{{ $formulir->tanggal_lahir ? \Carbon\Carbon::parse($formulir->tanggal_lahir)->format('d/m/Y') : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jenis Kelamin</td>
                                    <td>{{ $formulir->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Agama</td>
                                    <td>{{ $formulir->agama ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted" width="40%">Asal Sekolah</td>
                                    <td>{{ $formulir->asal_sekolah ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">NISN</td>
                                    <td>{{ $formulir->nisn ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Alamat</td>
                                    <td>{{ $formulir->alamat ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">No. HP</td>
                                    <td>{{ $formulir->no_hp ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Data Orang Tua</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Ayah</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">Nama</td>
                                    <td>{{ $formulir->nama_ayah ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pekerjaan</td>
                                    <td>{{ $formulir->pekerjaan_ayah ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">No. HP</td>
                                    <td>{{ $formulir->no_hp_ayah ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Ibu</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">Nama</td>
                                    <td>{{ $formulir->nama_ibu ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pekerjaan</td>
                                    <td>{{ $formulir->pekerjaan_ibu ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">No. HP</td>
                                    <td>{{ $formulir->no_hp_ibu ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Status</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="badge bg-warning fs-6">Menunggu Verifikasi</span>
                    </div>
                    <p class="text-muted small">Diajukan: {{ $formulir->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Aksi</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('tim-spmb.verifikasi.formulir.terima', $formulir) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Terima formulir ini?')">
                            <i class="bi bi-check-circle me-1"></i> Terima Formulir
                        </button>
                    </form>

                    <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#tolakModal">
                        <i class="bi bi-x-circle me-1"></i> Tolak Formulir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tolak -->
<div class="modal fade" id="tolakModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tim-spmb.verifikasi.formulir.tolak', $formulir) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Formulir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan</label>
                        <textarea name="alasan" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
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
@endsection
