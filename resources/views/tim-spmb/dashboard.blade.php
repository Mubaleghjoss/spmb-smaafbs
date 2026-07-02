@extends('layouts.tim-spmb')

@section('title', 'Dashboard Tim SPMB')

@section('content')
<div class="container-fluid p-4">
    <h1 class="h3 mb-4">Dashboard Tim SPMB</h1>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Total Peserta</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_peserta']) }}</h2>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('tim-spmb.peserta.index') }}" class="text-white small">Lihat semua <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark opacity-75">Verifikasi Pembayaran</h6>
                            <h2 class="mb-0">{{ number_format($stats['menunggu_verifikasi_pembayaran']) }}</h2>
                        </div>
                        <i class="bi bi-credit-card fs-1 opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('tim-spmb.verifikasi.pembayaran-formulir') }}" class="text-dark small">Verifikasi <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Verifikasi Formulir</h6>
                            <h2 class="mb-0">{{ number_format($stats['menunggu_verifikasi_formulir']) }}</h2>
                        </div>
                        <i class="bi bi-file-text fs-1 opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('tim-spmb.verifikasi.formulir') }}" class="text-white small">Verifikasi <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Sudah Tes</h6>
                            <h2 class="mb-0">{{ number_format($stats['sudah_tes']) }}</h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('tim-spmb.hasil.index') }}" class="text-white small">Lihat hasil <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Aksi Cepat</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('tim-spmb.peserta.create') }}" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus me-2"></i>Tambah Peserta Baru
                        </a>
                        <a href="{{ route('tim-spmb.verifikasi.index') }}" class="btn btn-outline-success">
                            <i class="bi bi-clipboard-check me-2"></i>Verifikasi SPMB
                        </a>
                        <a href="{{ route('tim-spmb.hasil.index') }}" class="btn btn-outline-info">
                            <i class="bi bi-bar-chart me-2"></i>Lihat Hasil Ujian
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Akun</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">Nama</td>
                            <td>{{ auth('pengguna')->user()->nama }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>{{ auth('pengguna')->user()->email }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Role</td>
                            <td><span class="badge bg-success">Tim SPMB</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
