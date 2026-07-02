@extends('layouts.auth')

@section('title', 'Login Ujian')
@section('subtitle', 'Masuk Langsung ke Ujian dengan Token')

@section('content')
<form method="POST" action="{{ route('login.token.proses') }}">
    @csrf
    
    <div class="mb-3">
        <label for="nomor_pendaftaran" class="form-label">Nomor Pendaftaran</label>
        <input type="text" 
               class="form-control @error('nomor_pendaftaran') is-invalid @enderror" 
               id="nomor_pendaftaran" 
               name="nomor_pendaftaran" 
               value="{{ old('nomor_pendaftaran') }}"
               placeholder="SPMB-2025-00001"
               required 
               autofocus>
        @error('nomor_pendaftaran')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="mb-4">
        <label for="token" class="form-label">Token Ujian</label>
        <input type="text" 
               class="form-control text-center fw-bold @error('token') is-invalid @enderror" 
               id="token" 
               name="token" 
               value="{{ old('token') }}"
               placeholder="XXXXXXXX"
               minlength="6"
               maxlength="20"
               style="font-size: 1.2rem; letter-spacing: 3px; text-transform: uppercase;"
               required>
        @error('token')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Masukkan token yang diberikan panitia</div>
    </div>
    
    <button type="submit" class="btn btn-success w-100">
        <i class="bi bi-play-circle me-2"></i>Mulai Ujian
    </button>
</form>

<hr class="my-4">

<div class="text-center">
    <p class="text-muted small mb-3">Pilihan login lainnya:</p>
    <div class="d-flex flex-column gap-2">
        <a href="{{ route('peserta.login') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-person me-1"></i>Login Peserta SPMB
        </a>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-shield-lock me-1"></i>Login Admin/Operator
        </a>
    </div>
</div>
@endsection
