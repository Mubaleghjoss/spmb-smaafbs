@extends('layouts.admin')

@section('title', 'Pengaturan Branding')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pengaturan Branding</h1>
        <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pengaturan.branding.simpan') }}" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Identitas Institusi</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nama Institusi <span class="text-danger">*</span></label>
                                <input type="text" name="nama_institusi" class="form-control @error('nama_institusi') is-invalid @enderror" 
                                       value="{{ old('nama_institusi', $branding['nama_institusi']) }}" required>
                                @error('nama_institusi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nama Singkat <span class="text-danger">*</span></label>
                                <input type="text" name="nama_singkat" class="form-control @error('nama_singkat') is-invalid @enderror" 
                                       value="{{ old('nama_singkat', $branding['nama_singkat']) }}" required>
                                @error('nama_singkat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2">{{ old('alamat', $branding['alamat']) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="telepon" class="form-control" value="{{ old('telepon', $branding['telepon']) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $branding['email']) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" value="{{ old('website', $branding['website']) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahun Ajaran</label>
                                <input type="text" class="form-control" value="{{ $branding['tahun_ajaran'] }}" readonly>
                                <div class="form-text">
                                    Diatur dari
                                    <a href="{{ route('admin.pengaturan.spmb.periode') }}">Tahun Ajaran & Gelombang</a>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Warna Tema</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Warna Primer</label>
                                <div class="input-group">
                                    <input type="color" name="warna_primer" class="form-control form-control-color" 
                                           value="{{ old('warna_primer', $branding['warna_primer']) }}">
                                    <input type="text" class="form-control" value="{{ $branding['warna_primer'] }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Warna Sekunder</label>
                                <div class="input-group">
                                    <input type="color" name="warna_sekunder" class="form-control form-control-color" 
                                           value="{{ old('warna_sekunder', $branding['warna_sekunder']) }}">
                                    <input type="text" class="form-control" value="{{ $branding['warna_sekunder'] }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-card-text me-2"></i>Teks Halaman Publik</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Teks Hero (Halaman Beranda)</label>
                            <textarea name="teks_hero" class="form-control" rows="3" placeholder="Teks yang muncul di bagian hero halaman beranda">{{ old('teks_hero', $branding['teks_hero'] ?? '') }}</textarea>
                            <small class="text-muted">Teks ajakan di halaman utama, contoh: "Bergabunglah bersama kami untuk menjadi generasi Qurani..."</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teks CTA (Call to Action)</label>
                            <textarea name="teks_cta" class="form-control" rows="2" placeholder="Teks ajakan untuk mendaftar">{{ old('teks_cta', $branding['teks_cta'] ?? '') }}</textarea>
                            <small class="text-muted">Teks di bagian bawah halaman beranda, contoh: "Daftarkan diri Anda sekarang..."</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teks Halaman Alur SPMB</label>
                            <textarea name="teks_alur_spmb" class="form-control" rows="2" placeholder="Teks deskripsi di halaman alur SPMB">{{ old('teks_alur_spmb', $branding['teks_alur_spmb'] ?? '') }}</textarea>
                            <small class="text-muted">Teks di halaman alur SPMB, contoh: "Ikuti setiap tahapan untuk menjadi bagian dari keluarga besar..."</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Logo</h6>
                    </div>
                    <div class="card-body text-center">
                        @if($branding['logo'])
                            <img src="{{ Storage::url($branding['logo']) }}" alt="Logo" class="img-fluid mb-3" style="max-height: 150px;">
                        @else
                            <div class="bg-light p-4 mb-3 rounded">
                                <i class="bi bi-image display-4 text-muted"></i>
                                <p class="text-muted small mb-0">Belum ada logo</p>
                            </div>
                        @endif
                        <input type="file" name="logo" class="form-control form-control-sm" accept="image/png,image/jpeg">
                        <small class="text-muted">PNG/JPG, maks 2MB</small>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Favicon</h6>
                    </div>
                    <div class="card-body text-center">
                        @if($branding['favicon'])
                            <img src="{{ Storage::url($branding['favicon']) }}" alt="Favicon" class="mb-3" style="max-height: 64px;">
                        @else
                            <div class="bg-light p-3 mb-3 rounded d-inline-block">
                                <i class="bi bi-app display-6 text-muted"></i>
                            </div>
                        @endif
                        <input type="file" name="favicon" class="form-control form-control-sm" accept="image/png,image/x-icon">
                        <small class="text-muted">PNG/ICO, maks 512KB</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
