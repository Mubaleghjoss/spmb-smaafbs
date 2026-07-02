@extends('instalasi.layout')

@section('content')
<h5 class="mb-4"><i class="bi bi-database"></i> Konfigurasi Database</h5>

@if($errors->any())
<div class="alert alert-danger">
    @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
    @endforeach
</div>
@endif

<form action="{{ route('instalasi.simpan-database') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-8 mb-3">
            <label class="form-label">Host Database</label>
            <input type="text" name="db_host" class="form-control" value="{{ old('db_host', 'localhost') }}" required>
            <small class="text-muted">Biasanya: localhost atau 127.0.0.1</small>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Port</label>
            <input type="number" name="db_port" class="form-control" value="{{ old('db_port', '3306') }}" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Nama Database</label>
        <input type="text" name="db_database" class="form-control" value="{{ old('db_database') }}" required>
        <small class="text-muted">Database harus sudah dibuat di cPanel/phpMyAdmin</small>
    </div>
    <div class="mb-3">
        <label class="form-label">Username Database</label>
        <input type="text" name="db_username" class="form-control" value="{{ old('db_username') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Password Database</label>
        <input type="password" name="db_password" class="form-control" value="{{ old('db_password') }}">
    </div>

    <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('instalasi.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <button type="submit" class="btn btn-primary">
            Test & Lanjutkan <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>
@endsection
