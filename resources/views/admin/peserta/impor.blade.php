@extends('layouts.admin')

@section('title', 'Impor Peserta')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Impor Peserta</h1>
        <a href="{{ route('admin.peserta.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('errors_impor'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Beberapa peserta gagal diimpor:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('errors_impor') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-excel text-success"></i> Impor dari Excel
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Upload file Excel (.xlsx, .xls) dengan format yang sesuai template.
                    </p>

                    <form action="{{ route('admin.peserta.impor.proses') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">File Excel <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                   accept=".xlsx,.xls" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maksimal 5MB</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload"></i> Impor Peserta
                            </button>
                            <a href="{{ route('admin.peserta.template') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Panduan Format</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Kolom yang diperlukan:</p>
                    <ol class="small">
                        <li>Nama (wajib)</li>
                        <li>Email (wajib, harus unik)</li>
                        <li>Telepon</li>
                        <li>Alamat</li>
                        <li>Asal Sekolah</li>
                    </ol>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle"></i> 
                        Password default untuk semua peserta yang diimpor adalah <code>password123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
