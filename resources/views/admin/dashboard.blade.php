@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h4 class="fw-bold">Dashboard</h4>
            <p class="text-muted">Selamat datang, {{ auth('pengguna')->user()->nama }}</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="bi bi-people text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $stats['total_peserta'] }}</h3>
                            <p class="text-muted mb-0 small">Total Peserta</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="bi bi-person-plus text-success" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $stats['peserta_baru'] }}</h3>
                            <p class="text-muted mb-0 small">Peserta Hari Ini</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="bi bi-file-earmark-text text-warning" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $stats['total_tes'] }}</h3>
                            <p class="text-muted mb-0 small">Total Tes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="bi bi-question-circle text-info" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $stats['total_soal'] }}</h3>
                            <p class="text-muted mb-0 small">Bank Soal</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Menu Cepat</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="{{ route('admin.peserta.index') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-people d-block mb-2" style="font-size: 1.5rem;"></i>
                        Kelola Peserta
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-clipboard-check d-block mb-2" style="font-size: 1.5rem;"></i>
                        Verifikasi SPMB
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.tes.index') }}" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-file-earmark-text d-block mb-2" style="font-size: 1.5rem;"></i>
                        Kelola Tes
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.soal.index') }}" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-question-circle d-block mb-2" style="font-size: 1.5rem;"></i>
                        Bank Soal
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Row 2 -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="{{ route('admin.alur-peserta.index') }}" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-signpost-split d-block mb-2" style="font-size: 1.5rem;"></i>
                        Alur Peserta
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.monitoring-ujian.index') }}" class="btn btn-outline-dark w-100 py-3">
                        <i class="bi bi-display d-block mb-2" style="font-size: 1.5rem;"></i>
                        Monitoring Ujian
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.hasil.index') }}" class="btn btn-outline-danger w-100 py-3">
                        <i class="bi bi-bar-chart d-block mb-2" style="font-size: 1.5rem;"></i>
                        Hasil Ujian
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-gear d-block mb-2" style="font-size: 1.5rem;"></i>
                        Pengaturan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
