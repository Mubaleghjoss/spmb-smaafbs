@extends('layouts.auth')

@section('title', 'Login Peserta')
@section('subtitle', 'Login Peserta SPMB')

@php
    $pengaturanService = app(\App\Services\PengaturanService::class);
    $kontakTimSpmb = $pengaturanService->ambilKontakTimSpmb();
    $kontakPertama = $kontakTimSpmb[0] ?? null;
@endphp

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('peserta.login.proses') }}" x-data="{ loading: false, showPassword: false }" @submit="loading = true">
    @csrf
    
    <div class="mb-3">
        <label for="telepon" class="form-label">No HP / WhatsApp</label>
        <input type="tel" 
               class="form-control @error('telepon') is-invalid @enderror" 
               id="telepon" 
               name="telepon" 
               value="{{ old('telepon') }}"
               placeholder="08xxxxxxxxxx"
               required 
               autofocus>
        @error('telepon')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
            <input :type="showPassword ? 'text' : 'password'" 
                   class="form-control @error('password') is-invalid @enderror" 
                   id="password" 
                   name="password"
                   placeholder="••••••••"
                   required>
            <button class="btn btn-outline-secondary" type="button" @click="showPassword = !showPassword">
                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
            </button>
        </div>
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    
    @if($kontakPertama)
    <div class="mb-4 text-end">
        <a href="#" data-bs-toggle="modal" data-bs-target="#modalLupaPassword" class="text-decoration-none small">
            <i class="bi bi-whatsapp me-1"></i>Lupa Password?
        </a>
    </div>
    @endif
    
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
    <p class="text-muted small mb-3">Pilihan lainnya:</p>
    <div class="d-flex flex-column gap-2">
        <a href="{{ route('daftar') }}" class="btn btn-outline-success">
            <i class="bi bi-person-plus me-1"></i>Daftar Sekarang
        </a>
        <a href="{{ route('login.token') }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-play-circle me-1"></i>Langsung Ujian (Token)
        </a>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-shield-lock me-1"></i>Login Admin/Operator
        </a>
    </div>
</div>

{{-- Modal Lupa Password --}}
@if($kontakPertama)
<div class="modal fade" id="modalLupaPassword" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-whatsapp me-2"></i>Lupa Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Masukkan No HP/WhatsApp yang Anda daftarkan saat pendaftaran SPMB:</p>
                <div class="mb-3">
                    <label for="noHpLupaPassword" class="form-label">No HP / WhatsApp Terdaftar</label>
                    <input type="tel" class="form-control" id="noHpLupaPassword" placeholder="08xxxxxxxxxx">
                    <div class="form-text">Nomor ini akan dikirim ke Tim SPMB untuk verifikasi.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btnKirimWaLupaPassword">
                    <i class="bi bi-whatsapp me-1"></i>Hubungi Tim SPMB
                </button>
            </div>
        </div>
    </div>
</div>

@php
    $waNumber = $kontakPertama['whatsapp'];
    // Format nomor: hapus karakter non-digit, lalu pastikan diawali 62
    $waNumber = preg_replace('/[^0-9]/', '', $waNumber);
    if (str_starts_with($waNumber, '0')) {
        $waNumber = '62' . substr($waNumber, 1);
    } elseif (!str_starts_with($waNumber, '62')) {
        $waNumber = '62' . $waNumber;
    }
@endphp

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnKirimWaLupaPassword').addEventListener('click', function() {
        const noHp = document.getElementById('noHpLupaPassword').value.trim();
        if (!noHp) {
            alert('Silakan masukkan No HP/WhatsApp yang terdaftar.');
            return;
        }
        
        const pesan = `Assalamu'alaikum, saya lupa password akun SPMB saya.\n\nNo HP terdaftar: ${noHp}\n\nMohon bantuannya untuk reset password. Terima kasih.`;
        const waUrl = `https://wa.me/{{ $waNumber }}?text=${encodeURIComponent(pesan)}`;
        
        window.open(waUrl, '_blank');
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('modalLupaPassword')).hide();
    });
});
</script>
@endif
@endsection
