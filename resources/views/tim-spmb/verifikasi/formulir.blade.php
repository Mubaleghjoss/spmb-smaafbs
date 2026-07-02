@extends('layouts.tim-spmb')

@section('title', 'Verifikasi Formulir')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Verifikasi Formulir</h1>
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
                            <th>Nama Lengkap</th>
                            <th>Asal Sekolah</th>
                            <th>Diajukan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($formulir as $f)
                        <tr>
                            <td>
                                <code>{{ $f->peserta->nomor_pendaftaran }}</code>
                            </td>
                            <td>{{ $f->nama_lengkap }}</td>
                            <td>{{ $f->asal_sekolah ?? '-' }}</td>
                            <td>{{ $f->updated_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('tim-spmb.verifikasi.formulir.detail', $f) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                                Tidak ada formulir yang perlu diverifikasi
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($formulir->hasPages())
        <div class="card-footer">
            {{ $formulir->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
