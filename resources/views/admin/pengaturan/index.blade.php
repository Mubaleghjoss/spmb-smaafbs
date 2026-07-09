@extends('layouts.admin')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-gear me-2"></i>Pengaturan Sistem</h1>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- GRUP 1: PENGATURAN UMUM --}}
    {{-- ============================================================ --}}
    <div class="d-flex align-items-center mb-3">
        <div class="bg-dark bg-opacity-10 rounded-circle p-2 me-2">
            <i class="bi bi-sliders text-dark"></i>
        </div>
        <h5 class="mb-0 fw-bold">Pengaturan Umum</h5>
    </div>
    <div class="row mb-4">
        {{-- Branding --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                            <i class="bi bi-palette text-primary fs-5"></i>
                        </div>
                        <h6 class="mb-0">Branding</h6>
                    </div>
                    <p class="text-muted small mb-2">Logo, warna, dan identitas institusi.</p>
                    <a href="{{ route('admin.pengaturan.branding') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>
        {{-- Email --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-info bg-opacity-10 p-2 rounded me-2">
                            <i class="bi bi-envelope text-info fs-5"></i>
                        </div>
                        <h6 class="mb-0">Email</h6>
                    </div>
                    <p class="text-muted small mb-2">Konfigurasi SMTP untuk notifikasi.</p>
                    <a href="{{ route('admin.pengaturan.email') }}" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>
        {{-- Syarat Ketentuan --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-secondary bg-opacity-10 p-2 rounded me-2">
                            <i class="bi bi-file-earmark-ruled text-secondary fs-5"></i>
                        </div>
                        <h6 class="mb-0">Syarat & Ketentuan</h6>
                    </div>
                    <p class="text-muted small mb-2">Konten halaman S&K pendaftaran.</p>
                    <a href="{{ route('admin.pengaturan.syarat-ketentuan') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- ============================================================ --}}
    {{-- GRUP 2: PENGATURAN PER TAHAP SPMB --}}
    {{-- ============================================================ --}}
    <div class="d-flex align-items-center mb-3">
        <div class="bg-success bg-opacity-10 rounded-circle p-2 me-2">
            <i class="bi bi-diagram-3 text-success"></i>
        </div>
        <h5 class="mb-0 fw-bold">Pengaturan per Tahap SPMB</h5>
    </div>

    <div class="row mb-4">
        {{-- Tahap 1: Pendaftaran --}}
        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card h-100 border-0 shadow-sm border-start border-3 border-success">
                <div class="card-body p-3">
                    <span class="badge bg-success mb-2">Tahap 1</span>
                    <h6 class="mb-1"><i class="bi bi-person-plus me-1 text-success"></i>Pendaftaran</h6>
                    <p class="text-muted small mb-2">Jadwal buka/tutup, biaya formulir, status pendaftaran.</p>
                    <a href="{{ route('admin.pengaturan.spmb') }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>

        {{-- Tahap 2: Formulir --}}
        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card h-100 border-0 shadow-sm border-start border-3 border-primary">
                <div class="card-body p-3">
                    <span class="badge bg-primary mb-2">Tahap 2</span>
                    <h6 class="mb-1"><i class="bi bi-file-earmark-text me-1 text-primary"></i>Isi Formulir</h6>
                    <p class="text-muted small mb-2">Field formulir peserta otomatis, tidak perlu konfigurasi khusus.</p>
                    <span class="badge bg-light text-muted"><i class="bi bi-check-circle me-1"></i>Otomatis</span>
                </div>
            </div>
        </div>

        {{-- Tahap 3: Pembayaran Formulir --}}
        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card h-100 border-0 shadow-sm border-start border-3" style="border-color:#fd7e14!important">
                <div class="card-body p-3">
                    <span class="badge mb-2" style="background:#fd7e14">Tahap 3</span>
                    <h6 class="mb-1"><i class="bi bi-credit-card me-1" style="color:#fd7e14"></i>Bayar Formulir</h6>
                    <p class="text-muted small mb-2">Rekening bank, nominal pembayaran formulir.</p>
                    <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap3" class="btn btn-sm btn-outline-secondary" style="border-color:#fd7e14;color:#fd7e14">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>

        {{-- Tahap 4: Tes Online --}}
        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card h-100 border-0 shadow-sm border-start border-3 border-warning">
                <div class="card-body p-3">
                    <span class="badge bg-warning text-dark mb-2">Tahap 4</span>
                    <h6 class="mb-1"><i class="bi bi-journal-check me-1 text-warning"></i>Tes Online</h6>
                    <p class="text-muted small mb-2">Durasi default, nilai lulus, pengacakan soal & jawaban.</p>
                    <a href="{{ route('admin.pengaturan.ujian') }}" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>

        {{-- Tahap 5: Wawancara --}}
        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card h-100 border-0 shadow-sm border-start border-3" style="border-color:#6610f2!important">
                <div class="card-body p-3">
                    <span class="badge mb-2" style="background:#6610f2">Tahap 5</span>
                    <h6 class="mb-1"><i class="bi bi-people me-1" style="color:#6610f2"></i>Wawancara & Berkas</h6>
                    <p class="text-muted small mb-2">Pertanyaan wawancara, surat pernyataan, soal pegon.</p>
                    <a href="{{ route('admin.pengaturan.wawancara') }}" class="btn btn-sm btn-outline-secondary" style="border-color:#6610f2;color:#6610f2">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>

        {{-- Tahap 6: Pembayaran Pelunasan --}}
        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card h-100 border-0 shadow-sm border-start border-3 border-info">
                <div class="card-body p-3">
                    <span class="badge bg-info mb-2">Tahap 6</span>
                    <h6 class="mb-1"><i class="bi bi-receipt me-1 text-info"></i>Bayar Pelunasan</h6>
                    <p class="text-muted small mb-2">Template kwitansi, logo, stempel, penandatangan.</p>
                    <a href="{{ route('admin.pengaturan.template-kwitansi') }}" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>

        {{-- Tahap 7: Kelulusan --}}
        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
            <div class="card h-100 border-0 shadow-sm border-start border-3 border-danger">
                <div class="card-body p-3">
                    <span class="badge bg-danger mb-2">Tahap 7</span>
                    <h6 class="mb-1"><i class="bi bi-mortarboard me-1 text-danger"></i>Kelulusan</h6>
                    <p class="text-muted small mb-2">Tampilan surat kelulusan, judul, warna, teks.</p>
                    <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap7" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- ============================================================ --}}
    {{-- GRUP 3: HALAMAN PUBLIK --}}
    {{-- ============================================================ --}}
    <div class="d-flex align-items-center mb-3">
        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
            <i class="bi bi-globe text-primary"></i>
        </div>
        <h5 class="mb-0 fw-bold">Halaman Publik</h5>
    </div>
    <div class="row mb-4">
        {{-- Alur SPMB --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-secondary bg-opacity-10 p-2 rounded me-2">
                            <i class="bi bi-signpost-2 text-secondary fs-5"></i>
                        </div>
                        <h6 class="mb-0">Alur SPMB</h6>
                    </div>
                    <p class="text-muted small mb-2">Deskripsi setiap tahapan di halaman publik.</p>
                    <a href="{{ route('admin.pengaturan.alur-spmb') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>
        {{-- Jadwal SPMB --}}
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-danger bg-opacity-10 p-2 rounded me-2">
                            <i class="bi bi-calendar3 text-danger fs-5"></i>
                        </div>
                        <h6 class="mb-0">Jadwal SPMB</h6>
                    </div>
                    <p class="text-muted small mb-2">Jadwal kegiatan di halaman publik.</p>
                    <a href="{{ route('admin.pengaturan.jadwal') }}" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-gear me-1"></i>Kelola
                    </a>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- ============================================================ --}}
    {{-- GRUP 4: SISTEM & MAINTENANCE --}}
    {{-- ============================================================ --}}
    <div class="d-flex align-items-center mb-3">
        <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-2">
            <i class="bi bi-tools text-warning"></i>
        </div>
        <h5 class="mb-0 fw-bold">Sistem & Maintenance</h5>
    </div>

    <!-- Backup & Restore -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-cloud-download me-1"></i>Backup & Restore</h6>
        </div>
        <div class="card-body">
        <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-3" id="backupTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button">
                        <i class="bi bi-download me-1"></i> Export/Backup
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#import" type="button">
                        <i class="bi bi-upload me-1"></i> Import/Restore
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="sync-tab" data-bs-toggle="tab" data-bs-target="#sync" type="button">
                        <i class="bi bi-arrow-repeat me-1"></i> Sinkronisasi
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="backupTabContent">
                <!-- Export Tab -->
                <div class="tab-pane fade show active" id="export" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-file-earmark-zip text-primary me-2"></i>Download Project (ZIP)</h6>
                                <p class="text-muted small mb-3">Download semua file project termasuk vendor. Tidak perlu composer install di hosting.</p>
                                <a href="{{ route('admin.pengaturan.download-project') }}" class="btn btn-primary btn-sm" onclick="return confirm('Download project akan memakan waktu beberapa saat. Lanjutkan?')">
                                    <i class="bi bi-download me-1"></i> Download Project ZIP
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-database text-success me-2"></i>Download Database (SQL)</h6>
                                <p class="text-muted small mb-3">Download backup database dalam format SQL.</p>
                                <a href="{{ route('admin.pengaturan.download-database') }}" class="btn btn-success btn-sm" onclick="return confirm('Download database akan memakan waktu beberapa saat. Lanjutkan?')">
                                    <i class="bi bi-download me-1"></i> Download Database SQL
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Import Tab -->
                <div class="tab-pane fade" id="import" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-database-up text-info me-2"></i>Import Database (SQL)</h6>
                                <p class="text-muted small mb-3">Restore database dari file SQL backup.</p>
                                <div class="alert alert-warning small py-2">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Data existing akan di-overwrite!
                                </div>
                                <form action="{{ route('admin.pengaturan.import-database') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('PERHATIAN: Data existing akan di-overwrite. Pastikan Anda sudah backup data sebelumnya. Lanjutkan?')">
                                    @csrf
                                    <div class="mb-3">
                                        <input type="file" name="sql_file" class="form-control form-control-sm" accept=".sql" required>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="bi bi-upload me-1"></i> Import Database
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-cloud-upload text-warning me-2"></i>Update Project (ZIP)</h6>
                                <p class="text-muted small mb-3">Update aplikasi dari file ZIP. File .env tidak akan di-overwrite.</p>
                                <div class="alert alert-info small py-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Cache akan di-clear otomatis setelah update.
                                </div>
                                <form action="{{ route('admin.pengaturan.update-project') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Update project akan menimpa file existing (kecuali .env). Lanjutkan?')">
                                    @csrf
                                    <div class="mb-3">
                                        <input type="file" name="zip_file" class="form-control form-control-sm" accept=".zip" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="bi bi-arrow-repeat me-1"></i> Update Project
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Clear Cache -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-trash text-secondary me-2"></i>Clear Cache</h6>
                                <p class="text-muted small mb-0">Hapus cache config, routes, views, dan file cache.</p>
                            </div>
                            <form action="{{ route('admin.pengaturan.clear-cache') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-trash me-1"></i> Clear Cache
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sinkronisasi Tab -->
                <div class="tab-pane fade" id="sync" role="tabpanel">
                    <!-- Konfigurasi Server -->
                    @php
                        $syncUrl = \App\Models\Pengaturan::ambil('sync_server_url', env('SYNC_SERVER_URL', ''));
                        $syncToken = \App\Models\Pengaturan::ambil('sync_token', env('SYNC_TOKEN', ''));
                        $syncSiap = !empty($syncUrl) && !empty($syncToken);
                    @endphp
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><i class="bi bi-gear me-1"></i>Konfigurasi Server Sync</h6>
                                <div id="syncStatus">
                                    @if($syncSiap)
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sinkron sudah siap</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Belum dikonfigurasi</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small mb-1">Server URL</label>
                                    <input type="url" class="form-control form-control-sm" id="syncServerUrl" 
                                           value="{{ $syncUrl }}" placeholder="https://seleksi.smaafbs.sch.id">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Sync Token</label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" class="form-control" id="syncToken" 
                                               value="{{ $syncToken }}" placeholder="Token rahasia">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleTokenVisibility()">
                                            <i class="bi bi-eye" id="tokenEyeIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex gap-1">
                                    <button class="btn btn-sm btn-primary flex-fill" onclick="simpanKonfigSync()">
                                        <i class="bi bi-save me-1"></i>Simpan
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="tesKoneksiSync()" id="btnTesKoneksi">
                                        <i class="bi bi-wifi me-1"></i>Tes
                                    </button>
                                </div>
                            </div>
                            <div id="syncKonfigMessage" class="mt-2" style="display:none"></div>
                        </div>
                    </div>

                    <!-- Sync Actions -->
                    <div class="row mb-4">
                        <!-- Tarik dari Online -->
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 border-primary">
                                <div class="text-center mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px">
                                        <i class="bi bi-cloud-download text-primary fs-3"></i>
                                    </div>
                                </div>
                                <h6 class="text-center"><i class="bi bi-arrow-down-circle me-1"></i>Tarik dari Online → Local</h6>
                                <p class="text-muted small mb-2">Download semua data peserta, hasil tes, bukti bayar, dan file dari server online. <strong>Data local akan ditimpa.</strong></p>
                                <div class="alert alert-warning small py-2 mb-3">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Jika ada data local yang tidak ada di server, akan muncul di riwayat sebagai <strong>konflik</strong> untuk dikonfirmasi.
                                </div>
                                <div class="text-center">
                                    <button type="button" class="btn btn-primary" id="btnTarikOnline" onclick="mulaiSync('tarik')">
                                        <i class="bi bi-cloud-download me-1"></i> Tarik Data dari Online
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Push ke Online -->
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 border-success">
                                <div class="text-center mb-3">
                                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px">
                                        <i class="bi bi-cloud-upload text-success fs-3"></i>
                                    </div>
                                </div>
                                <h6 class="text-center"><i class="bi bi-arrow-up-circle me-1"></i>Push ke Online ← Local</h6>
                                <p class="text-muted small mb-2">Upload semua data peserta, hasil tes, bukti bayar, dan file ke server online. <strong>Data online akan ditimpa.</strong></p>
                                <div class="alert alert-info small py-2 mb-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Data yang sama tidak akan berubah. Hanya data berbeda yang diperbarui. Detail perubahan akan ditampilkan di riwayat.
                                </div>
                                <div class="text-center">
                                    <button type="button" class="btn btn-success" id="btnPushOnline" onclick="mulaiSync('push')">
                                        <i class="bi bi-cloud-upload me-1"></i> Push Data ke Online
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Riwayat Sinkronisasi -->
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Riwayat Sinkronisasi</h6>
                            <button class="btn btn-outline-primary btn-sm" onclick="muatRiwayat()">
                                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                            </button>
                        </div>
                        <div id="riwayatContainer">
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-hourglass-split me-1"></i> Memuat riwayat...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Data Peserta -->
    <div class="card border-danger mb-4 shadow-sm">
        <div class="card-header bg-danger text-white">
            <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Zona Berbahaya</h6>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="bg-danger bg-opacity-10 p-3 rounded me-3">
                    <i class="bi bi-trash3 text-danger fs-4"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">Reset Data Peserta</h6>
                    <p class="text-muted small mb-0">
                        Hapus semua data peserta, formulir, pembayaran, hasil tes, dan file upload. 
                        Auto increment ID akan di-reset ke 1.
                    </p>
                </div>
                <a href="{{ route('admin.pengaturan.reset-data') }}" class="btn btn-outline-danger">
                    <i class="bi bi-trash3 me-1"></i> Reset Data
                </a>
            </div>
        </div>
    </div>
</div>
<!-- Modal Loading Sync -->
<div id="syncLoadingModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; backdrop-filter:blur(4px);">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center;">
        <div class="card shadow-lg border-0" style="min-width:350px; border-radius:16px;">
            <div class="card-body p-4">
                <div class="mb-3">
                    <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <h5 class="mb-2" id="syncLoadingTitle">Sinkronisasi...</h5>
                <div id="syncLoadingSteps" class="text-start">
                    <div class="d-flex align-items-center mb-2" id="step1">
                        <i class="bi bi-circle text-muted me-2" id="step1Icon"></i>
                        <span class="small" id="step1Text">Menghubungi server...</span>
                    </div>
                    <div class="d-flex align-items-center mb-2" id="step2">
                        <i class="bi bi-circle text-muted me-2" id="step2Icon"></i>
                        <span class="small" id="step2Text">Mengekspor data...</span>
                    </div>
                    <div class="d-flex align-items-center mb-2" id="step3">
                        <i class="bi bi-circle text-muted me-2" id="step3Icon"></i>
                        <span class="small" id="step3Text">Menyinkronkan file...</span>
                    </div>
                    <div class="d-flex align-items-center" id="step4">
                        <i class="bi bi-circle text-muted me-2" id="step4Icon"></i>
                        <span class="small" id="step4Text">Menyimpan log...</span>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Mohon tunggu, jangan tutup halaman ini.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konflik -->
<div class="modal fade" id="konflikModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Data Konflik Terdeteksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Berikut data local yang <strong>tidak ada di server online</strong>. Pilih tindakan:
                </p>
                <div id="konflikList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" onclick="resolveKonflikAll('hapus')">
                    <i class="bi bi-trash me-1"></i> Hapus Semua (Ikuti Server)
                </button>
                <button type="button" class="btn btn-success" onclick="resolveKonflikAll('merge')">
                    <i class="bi bi-union me-1"></i> Satukan Semua (Pertahankan Data Local)
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
let currentKonflikLogId = null;
let currentKonflikItems = [];

// ========================================================
// MULAI SYNC (Tarik / Push)
// ========================================================
function mulaiSync(tipe) {
    const konfirmasi = tipe === 'tarik'
        ? 'Semua data local akan DITIMPA dengan data dari server online. Lanjutkan?'
        : 'Semua data di server online akan DITIMPA dengan data local. Lanjutkan?';

    if (!confirm(konfirmasi)) return;

    // Tampilkan loading modal
    const modal = document.getElementById('syncLoadingModal');
    modal.style.display = 'block';

    const title = document.getElementById('syncLoadingTitle');
    title.textContent = tipe === 'tarik' ? 'Menarik Data dari Online...' : 'Push Data ke Online...';

    // Reset steps
    for (let i = 1; i <= 4; i++) {
        document.getElementById('step' + i + 'Icon').className = 'bi bi-circle text-muted me-2';
    }

    // Animate steps
    animateStep(1);
    setTimeout(() => animateStep(2), 1500);
    setTimeout(() => animateStep(3), 4000);

    // Determine URL
    const url = tipe === 'tarik'
        ? '{{ route("admin.pengaturan.sync.tarik") }}'
        : '{{ route("admin.pengaturan.sync.push") }}';

    // Disable buttons
    document.getElementById('btnTarikOnline').disabled = true;
    document.getElementById('btnPushOnline').disabled = true;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        animateStep(4);
        setTimeout(() => {
            modal.style.display = 'none';

            if (data.success) {
                // Cek konflik
                if (data.konflik && data.konflik.length > 0) {
                    tampilkanKonflik(data.konflik, data.log_id);
                    alert('✅ Data berhasil disinkronkan!\n\n⚠️ Ada ' + data.konflik.length + ' data konflik yang perlu diresolvasi.');
                } else {
                    let msg = '✅ ' + (data.message || 'Berhasil!') + '\n\nRingkasan: ' + (data.ringkasan || '-');
                    if (data.debug) {
                        msg += '\n\nDebug: ZIP=' + Math.round((data.debug.zip_size || 0)/1024) + 'KB, HTTP=' + (data.debug.http_status || '-');
                    }
                    alert(msg);
                }
            } else {
                alert('❌ ' + (data.message || data.error || 'Terjadi kesalahan'));
            }

            // Re-enable buttons
            document.getElementById('btnTarikOnline').disabled = false;
            document.getElementById('btnPushOnline').disabled = false;

            muatRiwayat();
        }, 800);
    })
    .catch(error => {
        modal.style.display = 'none';
        alert('❌ Error: ' + error.message);
        document.getElementById('btnTarikOnline').disabled = false;
        document.getElementById('btnPushOnline').disabled = false;
    });
}

function animateStep(step) {
    const icon = document.getElementById('step' + step + 'Icon');
    if (icon) {
        icon.className = 'bi bi-arrow-right-circle-fill text-primary me-2';
        // Mark previous as done
        if (step > 1) {
            const prev = document.getElementById('step' + (step - 1) + 'Icon');
            if (prev) prev.className = 'bi bi-check-circle-fill text-success me-2';
        }
    }
}

// ========================================================
// RIWAYAT SINKRONISASI
// ========================================================
function muatRiwayat() {
    const container = document.getElementById('riwayatContainer');
    container.innerHTML = '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div> Memuat riwayat...</div>';

    fetch('{{ route("admin.pengaturan.sync.riwayat") }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(logs => {
        if (!logs || logs.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada riwayat sinkronisasi</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">';
        html += '<thead class="table-light"><tr><th>Waktu</th><th>Tipe</th><th>Status</th><th>Ringkasan</th><th>Aksi</th></tr></thead><tbody>';

        logs.forEach(log => {
            const tipeBadge = log.tipe === 'tarik'
                ? '<span class="badge bg-primary"><i class="bi bi-arrow-down me-1"></i>Tarik</span>'
                : '<span class="badge bg-success"><i class="bi bi-arrow-up me-1"></i>Push</span>';

            let statusBadge = '';
            if (log.status === 'berhasil') statusBadge = '<span class="badge bg-success">Berhasil</span>';
            else if (log.status === 'gagal') statusBadge = '<span class="badge bg-danger">Gagal</span>';
            else if (log.status === 'konflik') statusBadge = log.konflik_resolved
                ? '<span class="badge bg-info">Konflik (Resolved)</span>'
                : '<span class="badge bg-warning text-dark">Ada Konflik</span>';

            let aksiHtml = '<button class="btn btn-outline-primary btn-sm" onclick="toggleDetail(' + log.id + ')"><i class="bi bi-eye me-1"></i>Detail</button>';

            if (log.status === 'konflik' && !log.konflik_resolved && log.konflik && log.konflik.length > 0) {
                aksiHtml += ' <button class="btn btn-warning btn-sm" onclick=\'tampilkanKonflik(' + JSON.stringify(log.konflik).replace(/'/g, "\\'") + ',' + log.id + ')\'><i class="bi bi-exclamation-triangle me-1"></i>Resolve</button>';
            }

            html += '<tr>';
            html += '<td class="small">' + log.waktu + '</td>';
            html += '<td>' + tipeBadge + '</td>';
            html += '<td>' + statusBadge + '</td>';
            html += '<td class="small">' + (log.ringkasan || '-').substring(0, 80) + '</td>';
            html += '<td>' + aksiHtml + '</td>';
            html += '</tr>';

            // Detail row (hidden)
            html += '<tr id="detail-' + log.id + '" style="display:none"><td colspan="5">';
            html += '<div class="bg-light p-3 rounded small">';

            if (log.perubahan && log.perubahan.length > 0) {
                html += '<strong><i class="bi bi-list-check me-1"></i>Perubahan:</strong><ul class="mb-2">';
                log.perubahan.forEach(p => {
                    const icon = p.aksi === 'tambah' ? '🟢' : (p.aksi === 'hapus' ? '🔴' : '🔵');
                    html += '<li>' + icon + ' ' + (p.keterangan || '-') + '</li>';
                });
                html += '</ul>';
            }

            if (log.konflik && log.konflik.length > 0) {
                html += '<strong><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Konflik:</strong><ul class="mb-0">';
                log.konflik.forEach(k => {
                    html += '<li>⚠️ ' + (k.keterangan || '-') + ' <span class="text-muted">(' + (k.detail || '') + ')</span></li>';
                });
                html += '</ul>';
            }

            if ((!log.perubahan || log.perubahan.length === 0) && (!log.konflik || log.konflik.length === 0)) {
                html += '<span class="text-muted">Tidak ada detail perubahan.</span>';
            }

            html += '</div></td></tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    })
    .catch(err => {
        container.innerHTML = '<div class="alert alert-danger small"><i class="bi bi-x-circle me-1"></i>Gagal memuat riwayat: ' + err.message + '</div>';
    });
}

function toggleDetail(logId) {
    const row = document.getElementById('detail-' + logId);
    if (row) {
        row.style.display = row.style.display === 'none' ? '' : 'none';
    }
}

// ========================================================
// KONFLIK RESOLUTION
// ========================================================
function tampilkanKonflik(konflikData, logId) {
    currentKonflikLogId = logId;
    currentKonflikItems = konflikData;

    const list = document.getElementById('konflikList');
    let html = '';

    konflikData.forEach((item, index) => {
        const icon = item.tipe === 'peserta' ? 'bi-person' : 'bi-file-earmark';
        html += '<div class="border rounded p-2 mb-2 d-flex align-items-center">';
        html += '<i class="bi ' + icon + ' fs-5 me-3 text-warning"></i>';
        html += '<div class="flex-grow-1">';
        html += '<div class="fw-bold small">' + (item.keterangan || '-') + '</div>';
        html += '<div class="text-muted" style="font-size:0.75rem">' + (item.detail || '') + '</div>';
        html += '</div>';
        html += '</div>';
    });

    list.innerHTML = html;
    new bootstrap.Modal(document.getElementById('konflikModal')).show();
}

function resolveKonflikAll(aksi) {
    if (!currentKonflikLogId) return;

    const konfirmasi = aksi === 'hapus'
        ? 'Data local yang tidak ada di server akan DIHAPUS. Lanjutkan?'
        : 'Data local akan disatukan (dipertahankan bersama data server). Lanjutkan?';

    if (!confirm(konfirmasi)) return;

    fetch('{{ route("admin.pengaturan.sync.resolve-konflik") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            log_id: currentKonflikLogId,
            aksi: aksi,
            items: currentKonflikItems,
        }),
    })
    .then(r => r.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('konflikModal')).hide();
        alert(data.success ? ('✅ ' + data.message) : ('❌ ' + data.message));
        muatRiwayat();
    })
    .catch(err => {
        alert('❌ Error: ' + err.message);
    });
}

// Load riwayat saat tab sync diklik
document.getElementById('sync-tab')?.addEventListener('shown.bs.tab', muatRiwayat);
// Also load on page if sync tab is already active
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#sync') {
        document.getElementById('sync-tab')?.click();
    }
});

// ========================================================
// KONFIGURASI SYNC
// ========================================================
function simpanKonfigSync() {
    const url = document.getElementById('syncServerUrl').value.trim();
    const token = document.getElementById('syncToken').value.trim();
    const msgBox = document.getElementById('syncKonfigMessage');

    if (!url || !token) {
        msgBox.style.display = 'block';
        msgBox.innerHTML = '<div class="alert alert-warning small py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>URL dan Token wajib diisi.</div>';
        return;
    }

    if (token.length < 6) {
        msgBox.style.display = 'block';
        msgBox.innerHTML = '<div class="alert alert-warning small py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Token minimal 6 karakter.</div>';
        return;
    }

    msgBox.style.display = 'block';
    msgBox.innerHTML = '<div class="alert alert-info small py-2 mb-0"><div class="spinner-border spinner-border-sm me-1"></div> Menyimpan...</div>';

    fetch('{{ route("admin.pengaturan.sync.simpan-konfig") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ sync_server_url: url, sync_token: token }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            msgBox.innerHTML = '<div class="alert alert-success small py-2 mb-0"><i class="bi bi-check-circle me-1"></i>' + data.message + '</div>';
            if (data.server_url) {
                document.getElementById('syncServerUrl').value = data.server_url;
            }
            // Update status badge
            document.getElementById('syncStatus').innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sinkron sudah siap</span>';
        } else {
            msgBox.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle me-1"></i>' + (data.message || 'Gagal menyimpan') + '</div>';
        }
        setTimeout(() => { msgBox.style.display = 'none'; }, 3000);
    })
    .catch(err => {
        msgBox.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle me-1"></i>Error: ' + err.message + '</div>';
    });
}

function tesKoneksiSync() {
    const btn = document.getElementById('btnTesKoneksi');
    const msgBox = document.getElementById('syncKonfigMessage');
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';

    msgBox.style.display = 'block';
    msgBox.innerHTML = '<div class="alert alert-info small py-2 mb-0"><div class="spinner-border spinner-border-sm me-1"></div> Menghubungi server online...</div>';

    fetch('{{ route("admin.pengaturan.sync.tes-koneksi") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-wifi me-1"></i>Tes';
        if (data.success) {
            msgBox.innerHTML = '<div class="alert alert-success small py-2 mb-0"><i class="bi bi-check-circle me-1"></i>' + data.message + '</div>';
            if (data.server_url) {
                document.getElementById('syncServerUrl').value = data.server_url;
            }
            document.getElementById('syncStatus').innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sinkron sudah siap</span>';
        } else {
            msgBox.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle me-1"></i>' + data.message + '</div>';
        }
        setTimeout(() => { msgBox.style.display = 'none'; }, 5000);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-wifi me-1"></i>Tes';
        msgBox.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle me-1"></i>Error: ' + err.message + '</div>';
    });
}

function toggleTokenVisibility() {
    const input = document.getElementById('syncToken');
    const icon = document.getElementById('tokenEyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
@endpush

