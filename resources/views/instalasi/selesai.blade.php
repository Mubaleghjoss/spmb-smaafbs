@extends('instalasi.layout')

@section('content')
<div class="text-center py-4">
    <div class="mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 80px;"></i>
    </div>
    <h4 class="text-success mb-3">Instalasi Berhasil!</h4>
    <p class="text-muted mb-4">
        Aplikasi SPMB Al-Furqon telah berhasil diinstal dan siap digunakan.
    </p>

    <div class="card bg-light mb-4">
        <div class="card-body text-start">
            <h6><i class="bi bi-info-circle"></i> Langkah Selanjutnya:</h6>
            <ol class="mb-0">
                <li>Login ke panel admin dengan akun yang telah dibuat</li>
                <li>Lengkapi pengaturan institusi di menu Pengaturan</li>
                <li>Upload logo dan atur branding</li>
                <li>Konfigurasi jadwal SPMB</li>
                <li>Tambahkan operator jika diperlukan</li>
            </ol>
        </div>
    </div>

    <div class="d-grid gap-2">
        <a href="{{ url('/login') }}" class="btn btn-primary btn-lg">
            <i class="bi bi-box-arrow-in-right"></i> Login ke Admin Panel
        </a>
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
            <i class="bi bi-house"></i> Ke Halaman Utama
        </a>
    </div>
</div>
@endsection
