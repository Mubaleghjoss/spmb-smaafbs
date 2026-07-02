@extends('layouts.auth')

@section('title', 'Login Admin')
@section('subtitle', 'Login Administrator / Operator')

@section('content')
<form method="POST" action="{{ route('login.proses') }}" x-data="{ loading: false }" @submit="loading = true">
    @csrf
    
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" 
               class="form-control @error('email') is-invalid @enderror" 
               id="email" 
               name="email" 
               value="{{ old('email') }}"
               placeholder="admin@example.com"
               required 
               autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="input-group" x-data="{ show: false }">
            <input :type="show ? 'text' : 'password'" 
                   class="form-control @error('password') is-invalid @enderror" 
                   id="password" 
                   name="password"
                   placeholder="••••••••"
                   required>
            <button class="btn btn-outline-secondary" type="button" @click="show = !show">
                <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
            </button>
        </div>
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Ingat saya</label>
    </div>
    
    <button type="submit" class="btn btn-success w-100" :disabled="loading">
        <span x-show="!loading">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
        </span>
        <span x-show="loading">
            <span class="spinner-border spinner-border-sm me-2"></span>Memproses...
        </span>
    </button>
</form>

<hr class="my-4">

<div class="text-center">
    <p class="text-muted small mb-2">Login sebagai peserta ujian?</p>
    <a href="{{ route('login.token') }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-key me-1"></i>Login dengan Token
    </a>
</div>
@endsection
