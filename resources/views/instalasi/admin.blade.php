@extends('instalasi.layout')

@section('content')
<h5 class="mb-4"><i class="bi bi-person-gear"></i> Pengaturan Admin & Institusi</h5>

@if($errors->any())
<div class="alert alert-danger">
    @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
    @endforeach
</div>
@endif

<form action="{{ route('instalasi.simpan-admin') }}" method="POST">
    @csrf
    <div class="card mb-4">
        <div class="card-header bg-light">
            <i class="bi bi-building"></i> Informasi Institusi
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nama Institusi/Sekolah</label>
                <input type="text" name="nama_institusi" class="form-control" 
                       value="{{ old('nama_institusi', 'SMA Al-Furqon') }}" required>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <i class="bi bi-shield-lock"></i> Akun Administrator
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="admin_nama" class="form-control" 
                       value="{{ old('admin_nama') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="admin_email" class="form-control" 
                       value="{{ old('admin_email') }}" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="admin_password" class="form-control" required minlength="8">
                    <small class="text-muted">Minimal 8 karakter</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="admin_password_confirmation" class="form-control" required>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('instalasi.database') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <button type="submit" class="btn btn-primary">
            Lanjutkan <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>
@endsection
