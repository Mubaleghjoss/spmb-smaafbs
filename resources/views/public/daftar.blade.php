@extends('layouts.public')

@section('title', 'Pendaftaran SPMB')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Pendaftaran SPMB</h2>
                            <p class="text-muted">{{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}</p>
                            <p class="text-muted small">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
                        </div>
                        
                        @if(!$pendaftaranDibuka)
                        {{-- Tampilkan pesan jika pendaftaran belum dibuka --}}
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="bi bi-calendar-x text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="text-muted mb-3">Mohon Maaf</h4>
                            <p class="text-muted mb-4">{{ $pesanTutup }}</p>
                            
                            @if(!empty($spmb['tanggal_buka']) && \Carbon\Carbon::now() < \Carbon\Carbon::parse($spmb['tanggal_buka']))
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Pendaftaran akan dibuka: <strong>{{ \Carbon\Carbon::parse($spmb['tanggal_buka'])->translatedFormat('d F Y') }}</strong>
                            </div>
                            @endif
                            
                            <div class="mt-4">
                                <a href="{{ route('beranda') }}" class="btn btn-outline-success me-2">
                                    <i class="bi bi-house me-1"></i>Kembali ke Beranda
                                </a>
                                <a href="{{ route('peserta.login') }}" class="btn btn-success">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login Peserta
                                </a>
                            </div>
                            
                            @if(!empty($spmb['whatsapp_spmb']))
                            <div class="mt-4">
                                <p class="text-muted small mb-2">Ada pertanyaan?</p>
                                <a href="https://wa.me/62{{ ltrim($spmb['whatsapp_spmb'], '0') }}" target="_blank" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-whatsapp me-1"></i>Hubungi Tim SPMB
                                </a>
                            </div>
                            @endif
                        </div>
                        @else
                        {{-- Form pendaftaran --}}
                        <form method="POST" action="{{ route('daftar.proses') }}" x-data="formDaftar()">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('nama') is-invalid @enderror" 
                                       id="nama" 
                                       name="nama" 
                                       value="{{ old('nama') }}"
                                       placeholder="Masukkan nama lengkap sesuai akta"
                                       required>
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="telepon" class="form-label">No HP / WhatsApp <span class="text-danger">*</span></label>
                                <input type="tel" 
                                       class="form-control @error('telepon') is-invalid @enderror" 
                                       id="telepon" 
                                       name="telepon" 
                                       value="{{ old('telepon') }}"
                                       placeholder="08xxxxxxxxxx"
                                       required>
                                @error('telepon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">No HP akan digunakan untuk login dan notifikasi WhatsApp</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="asal_sekolah" class="form-label">Asal Sekolah <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('asal_sekolah') is-invalid @enderror" 
                                       id="asal_sekolah" 
                                       name="asal_sekolah" 
                                       value="{{ old('asal_sekolah') }}"
                                       placeholder="Nama SMP/MTs asal"
                                       required>
                                @error('asal_sekolah')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input :type="showPassword ? 'text' : 'password'" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="Minimal 8 karakter"
                                           minlength="8"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" @click="showPassword = !showPassword">
                                        <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input :type="showPassword ? 'text' : 'password'" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation"
                                       placeholder="Ulangi password"
                                       required>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="setuju" name="setuju" required>
                                    <label class="form-check-label small" for="setuju">
                                        Saya menyetujui <a href="#" class="text-success" data-bs-toggle="modal" data-bs-target="#modalSyaratKetentuan">syarat dan ketentuan</a> pendaftaran {{ $branding['nama_singkat'] ?? 'SPMB' }} {{ $branding['nama_institusi'] ?? '' }}
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 btn-lg" :disabled="loading">
                                <span x-show="!loading">
                                    <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                                </span>
                                <span x-show="loading">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Memproses...
                                </span>
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="text-muted small mb-2">Sudah punya akun?</p>
                            <a href="{{ route('peserta.login') }}" class="btn btn-outline-success">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login Peserta
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modal Syarat dan Ketentuan --}}
<div class="modal fade" id="modalSyaratKetentuan" tabindex="-1" aria-labelledby="modalSyaratKetentuanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalSyaratKetentuanLabel">
                    <i class="bi bi-file-earmark-text me-2"></i>Syarat dan Ketentuan SPMB
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-success">{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</h4>
                    <p class="text-muted">Seleksi Penerimaan Murid Baru (SPMB)</p>
                    <p class="text-muted small">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Bismillahirrahmanirrahim</strong><br>
                    Dengan mendaftar di SPMB {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}, calon peserta didik dan orang tua/wali menyatakan telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan berikut.
                </div>
                
                @foreach($syaratKetentuan ?? [] as $bagian)
                <h6 class="fw-bold text-success mt-4 mb-3">
                    <i class="{{ $bagian['ikon'] ?? 'bi-circle' }} me-2"></i>{{ $bagian['judul'] }}
                </h6>
                {!! $bagian['konten'] !!}
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="bi bi-check-circle me-2"></i>Saya Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function formDaftar() {
    return {
        showPassword: false,
        loading: false
    }
}
</script>
@endpush
@endsection
