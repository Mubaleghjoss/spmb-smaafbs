@extends('layouts.admin')

@section('title', 'Reset Data Peserta')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Reset Data Peserta</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.pengaturan.index') }}">Pengaturan</a></li>
                    <li class="breadcrumb-item active">Reset Data</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <!-- Warning Card -->
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>PERINGATAN - ZONA BERBAHAYA</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger mb-3">
                        <h6 class="alert-heading"><i class="bi bi-trash3 me-2"></i>Fitur ini akan menghapus SEMUA data peserta!</h6>
                        <hr>
                        <p class="mb-0">Data yang akan dihapus:</p>
                        <ul class="mb-0 mt-2">
                            <li>Semua data peserta dan akun login</li>
                            <li>Semua formulir pendaftaran</li>
                            <li>Semua data pembayaran dan bukti transfer</li>
                            <li>Semua hasil tes dan jawaban</li>
                            <li>Semua data wawancara</li>
                            <li>Semua file upload (foto, berkas, bukti pembayaran)</li>
                            <li>Semua log tahapan SPMB</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Catatan:</strong> Auto increment ID akan di-reset ke 1. Peserta baru akan mendapat nomor pendaftaran dari awal.
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-shield-check me-2"></i>
                        <strong>Data yang TIDAK dihapus:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Data admin/operator</li>
                            <li>Bank soal dan topik</li>
                            <li>Konfigurasi tes</li>
                            <li>Pengaturan sistem</li>
                            <li>Grup peserta (struktur saja)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Form Reset -->
            <div class="card border-danger">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Konfirmasi Reset Data</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.pengaturan.proses-reset-data') }}" method="POST" id="formReset">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label">Ketik <strong class="text-danger">RESET</strong> untuk konfirmasi:</label>
                            <input type="text" name="konfirmasi" class="form-control @error('konfirmasi') is-invalid @enderror" 
                                   placeholder="Ketik RESET" autocomplete="off" required>
                            @error('konfirmasi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="checkBackup" required>
                            <label class="form-check-label" for="checkBackup">
                                Saya sudah melakukan backup database sebelum reset
                            </label>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="checkUnderstand" required>
                            <label class="form-check-label" for="checkUnderstand">
                                Saya mengerti bahwa data yang dihapus <strong class="text-danger">TIDAK BISA</strong> dikembalikan
                            </label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger" id="btnReset" disabled>
                                <i class="bi bi-trash3 me-1"></i> Reset Semua Data Peserta
                            </button>
                            <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Statistik Data -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Data yang Akan Dihapus</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td><i class="bi bi-people text-primary me-2"></i>Peserta</td>
                                <td class="text-end"><span class="badge bg-primary">{{ number_format($stats['peserta']) }}</span></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-file-text text-info me-2"></i>Formulir</td>
                                <td class="text-end"><span class="badge bg-info">{{ number_format($stats['formulir']) }}</span></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-list-check text-success me-2"></i>Tahapan SPMB</td>
                                <td class="text-end"><span class="badge bg-success">{{ number_format($stats['tahapan']) }}</span></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-credit-card text-warning me-2"></i>Pembayaran</td>
                                <td class="text-end"><span class="badge bg-warning">{{ number_format($stats['pembayaran']) }}</span></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-journal-check text-danger me-2"></i>Sesi Tes</td>
                                <td class="text-end"><span class="badge bg-danger">{{ number_format($stats['sesi_tes']) }}</span></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-check2-square text-secondary me-2"></i>Jawaban</td>
                                <td class="text-end"><span class="badge bg-secondary">{{ number_format($stats['jawaban']) }}</span></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-chat-dots text-dark me-2"></i>Wawancara</td>
                                <td class="text-end"><span class="badge bg-dark">{{ number_format($stats['wawancara']) }}</span></td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-clock-history text-muted me-2"></i>Log Tahapan</td>
                                <td class="text-end"><span class="badge bg-secondary">{{ number_format($stats['log_tahapan']) }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Sebelum Reset</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Pastikan Anda sudah backup data sebelum melakukan reset:</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.pengaturan.download-database') }}" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-database-down me-1"></i> Backup Database
                        </a>
                        <a href="{{ route('admin.pengaturan.download-project') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-file-earmark-zip me-1"></i> Backup Project
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputKonfirmasi = document.querySelector('input[name="konfirmasi"]');
    const checkBackup = document.getElementById('checkBackup');
    const checkUnderstand = document.getElementById('checkUnderstand');
    const btnReset = document.getElementById('btnReset');
    const formReset = document.getElementById('formReset');

    function updateButtonState() {
        const isValid = inputKonfirmasi.value === 'RESET' && checkBackup.checked && checkUnderstand.checked;
        btnReset.disabled = !isValid;
    }

    inputKonfirmasi.addEventListener('input', updateButtonState);
    checkBackup.addEventListener('change', updateButtonState);
    checkUnderstand.addEventListener('change', updateButtonState);

    formReset.addEventListener('submit', function(e) {
        if (!confirm('PERINGATAN TERAKHIR!\n\nAnda akan menghapus SEMUA data peserta.\nTindakan ini TIDAK BISA dibatalkan!\n\nYakin ingin melanjutkan?')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
@endsection
