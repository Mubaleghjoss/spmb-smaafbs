@extends('layouts.admin')

@section('title', 'Impor Soal')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Impor Soal</h1>
        <a href="{{ route('admin.soal.index') }}" class="btn btn-outline-secondary">
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
            <strong>Beberapa soal gagal diimpor:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('errors_impor') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Impor dari Excel -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-excel text-success"></i> Impor dari Excel
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Upload file Excel (.xlsx, .xls) dengan format yang sesuai template.
                    </p>

                    <form action="{{ route('admin.soal.impor.excel') }}" method="POST" enctype="multipart/form-data">
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
                                <i class="bi bi-upload"></i> Impor Excel
                            </button>
                            <a href="{{ route('admin.soal.template') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Impor dari Word -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-word text-primary"></i> Impor dari Word
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Upload file Word (.docx, .doc) dengan format soal standar.
                    </p>

                    <form action="{{ route('admin.soal.impor.word') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">File Word <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                   accept=".docx,.doc" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maksimal 5MB</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Topik</label>
                            <select name="topik_id" class="form-select">
                                <option value="">-- Pilih Topik --</option>
                                @foreach($topik as $t)
                                    <option value="{{ $t->id }}">{{ $t->nama }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Semua soal akan masuk ke topik ini</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Impor Word
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan Format -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Panduan Format</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Format Excel</h6>
                    <p class="small text-muted">Kolom yang diperlukan:</p>
                    <ol class="small">
                        <li>Pertanyaan (wajib)</li>
                        <li>Tipe (pilihan_ganda/jawaban_ganda/esai/benar_salah)</li>
                        <li>Topik</li>
                        <li>Bobot</li>
                        <li>Jawaban A (wajib)</li>
                        <li>Jawaban B (wajib)</li>
                        <li>Jawaban C</li>
                        <li>Jawaban D</li>
                        <li>Jawaban Benar (A/B/C/D, wajib)</li>
                        <li>Pembahasan</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6>Format Word</h6>
                    <p class="small text-muted">Contoh format soal:</p>
                    <pre class="bg-light p-3 rounded small">1. Apa ibu kota Indonesia?
A. Jakarta
B. Bandung
C. Surabaya
D. Medan
Jawaban: A

2. Siapa presiden pertama Indonesia?
A. Soekarno
B. Soeharto
C. Habibie
D. Megawati
Jawaban: A</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
