@extends('layouts.tim-spmb')

@section('title', 'Verifikasi SPMB')

@section('content')
<div class="container-fluid p-4">
    <h1 class="h3 mb-4">Verifikasi SPMB</h1>

    <div class="row">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Pembayaran Formulir</h6>
                            <h2 class="mb-0 text-warning">{{ $stats['pembayaran_formulir'] }}</h2>
                        </div>
                        <i class="bi bi-credit-card text-warning fs-1 opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('tim-spmb.verifikasi.pembayaran-formulir') }}" class="btn btn-warning btn-sm w-100">
                        <i class="bi bi-arrow-right me-1"></i> Verifikasi
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Formulir</h6>
                            <h2 class="mb-0 text-info">{{ $stats['formulir'] }}</h2>
                        </div>
                        <i class="bi bi-file-text text-info fs-1 opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('tim-spmb.verifikasi.formulir') }}" class="btn btn-info btn-sm w-100">
                        <i class="bi bi-arrow-right me-1"></i> Verifikasi
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Hasil Tes</h6>
                            <h2 class="mb-0 text-primary">{{ $stats['hasil_tes'] }}</h2>
                        </div>
                        <i class="bi bi-journal-check text-primary fs-1 opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('tim-spmb.verifikasi.hasil-tes') }}" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-arrow-right me-1"></i> Verifikasi
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Pelunasan</h6>
                            <h2 class="mb-0 text-success">{{ $stats['pelunasan'] }}</h2>
                        </div>
                        <i class="bi bi-cash-stack text-success fs-1 opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('tim-spmb.verifikasi.pelunasan') }}" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-arrow-right me-1"></i> Verifikasi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
