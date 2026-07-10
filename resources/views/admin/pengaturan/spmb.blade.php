@extends('layouts.admin')

@section('title', 'Pengaturan SPMB')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-sliders me-2"></i>Pengaturan SPMB</h1>
        <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses') || session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('sukses') ?? session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="fw-bold mb-1">Pengaturan belum dapat disimpan:</div>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Tab Navigation --}}
    <ul class="nav nav-pills nav-fill mb-4 bg-white rounded shadow-sm p-2" id="spmbTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-tahap1" data-bs-toggle="pill" data-bs-target="#pane-tahap1" type="button">
                <span class="badge bg-success rounded-circle me-1">1</span> Pendaftaran
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-tahap2" data-bs-toggle="pill" data-bs-target="#pane-tahap2" type="button">
                <span class="badge bg-primary rounded-circle me-1">2</span> Formulir
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-tahap3" data-bs-toggle="pill" data-bs-target="#pane-tahap3" type="button">
                <span class="badge rounded-circle me-1" style="background:#fd7e14">3</span> Pembayaran
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-tahap6" data-bs-toggle="pill" data-bs-target="#pane-tahap6" type="button">
                <span class="badge bg-info rounded-circle me-1">6</span> Pelunasan
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-tahap7" data-bs-toggle="pill" data-bs-target="#pane-tahap7" type="button">
                <span class="badge bg-danger rounded-circle me-1">7</span> Kelulusan
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-timeline" data-bs-toggle="pill" data-bs-target="#pane-timeline" type="button">
                <i class="bi bi-calendar-range me-1"></i> Timeline
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-kontak" data-bs-toggle="pill" data-bs-target="#pane-kontak" type="button">
                <i class="bi bi-whatsapp me-1"></i> Kontak
            </button>
        </li>
    </ul>

    <form method="POST" action="{{ route('admin.pengaturan.spmb.simpan') }}" enctype="multipart/form-data">
        @csrf

        <div class="tab-content" id="spmbTabContent">

            {{-- ======================================== --}}
            {{-- TAB: TAHAP 1 - PENDAFTARAN --}}
            {{-- ======================================== --}}
            <div class="tab-pane fade show active" id="pane-tahap1" role="tabpanel">
                <div class="alert alert-info border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <strong><i class="bi bi-calendar3 me-2"></i>Jadwal /daftar memakai Tahun Ajaran & Gelombang</strong>
                        <div class="small mt-1">Peserta hanya melihat tahun dan gelombang yang aktif serta sedang berada dalam tanggal/jam buka-tutup.</div>
                    </div>
                    <a href="{{ route('admin.pengaturan.spmb.periode') }}" class="btn btn-outline-primary">
                        <i class="bi bi-sliders me-1"></i>Kelola Periode
                    </a>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-power me-2"></i>Gerbang Pendaftaran</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="form-check form-switch me-2">
                                            <input type="checkbox" name="pendaftaran_buka" class="form-check-input" id="pendaftaranBuka"
                                                   {{ $spmb['pendaftaran_buka'] ? 'checked' : '' }}
                                                   onchange="togglePendaftaran(this)">
                                            <label class="form-check-label fw-semibold" for="pendaftaranBuka">Pendaftaran Dibuka</label>
                                        </div>
                                        <span id="statusPendaftaran" class="badge {{ $spmb['pendaftaran_buka'] ? 'bg-success' : 'bg-danger' }}">
                                            <i class="bi bi-{{ $spmb['pendaftaran_buka'] ? 'check-circle' : 'x-circle' }} me-1"></i>
                                            {{ $spmb['pendaftaran_buka'] ? 'DIBUKA' : 'DITUTUP' }}
                                        </span>
                                    </div>
                                    <small class="text-muted mt-2 d-block">Jika toggle ini ditutup, halaman /daftar ditutup meskipun ada gelombang yang aktif.</small>
                                </div>
                                <div class="alert alert-light border mb-0 small">
                                    Jadwal rinci pendaftaran tidak lagi diatur ganda di sini. Gunakan <strong>Kelola Periode</strong> untuk menentukan tahun ajaran, gelombang, tanggal, dan jam yang tampil di /daftar.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                                <h6 class="mb-0"><i class="bi bi-calendar-range me-2 text-success"></i>Periode yang Dipakai /daftar</h6>
                                <a href="{{ route('admin.pengaturan.spmb.periode') }}" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-pencil-square me-1"></i>Atur Jadwal
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tahun</th>
                                                <th>Gelombang</th>
                                                <th>Jadwal</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($periodePendaftaran as $tahun)
                                                @forelse($tahun->gelombangPendaftaran as $gelombang)
                                                    @php($statusGelombang = $gelombang->statusPendaftaran())
                                                    <tr>
                                                        <td>
                                                            <span class="fw-semibold">{{ $tahun->nama }}</span>
                                                            @if($tahun->default)
                                                                <span class="badge bg-primary ms-1">Default</span>
                                                            @endif
                                                            @unless($tahun->aktif)
                                                                <span class="badge bg-secondary ms-1">Nonaktif</span>
                                                            @endunless
                                                        </td>
                                                        <td>{{ $gelombang->nama }}</td>
                                                        <td class="small">{{ $gelombang->labelPeriodePendaftaran() }}</td>
                                                        <td><span class="badge bg-{{ $statusGelombang['class'] }}">{{ $statusGelombang['label'] }}</span></td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>{{ $tahun->nama }}</td>
                                                        <td colspan="3" class="text-muted">Belum ada gelombang.</td>
                                                    </tr>
                                                @endforelse
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">Belum ada tahun ajaran.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-success bg-opacity-75 text-white">
                                <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Biaya Formulir</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Biaya Formulir Pendaftaran</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="biaya_formulir" class="form-control"
                                               value="{{ old('biaya_formulir', $spmb['biaya_formulir']) }}" min="0">
                                    </div>
                                    <small class="text-muted">Biaya yang dibayar peserta saat mendaftar (Tahap 3)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================================== --}}
            {{-- TAB: TAHAP 2 - ISI FORMULIR --}}
            {{-- ======================================== --}}
            <div class="tab-pane fade" id="pane-tahap2" role="tabpanel">
                @include('admin.pengaturan.partials.jadwal-tahap', [
                    'fieldPrefix' => 'tahap_2',
                    'jadwalTahap' => $tahapan['tahap_2'],
                    'statusJadwal' => $statusTahapan[2],
                    'judulJadwal' => 'Jadwal Isi Formulir SPMB',
                    'deskripsiJadwal' => 'Tahap 2 dapat diatur terpisah dari periode pendaftaran akun baru.',
                    'warnaJadwal' => 'primary',
                ])
            </div>

            {{-- ======================================== --}}
            {{-- TAB: TAHAP 3 - PEMBAYARAN FORMULIR --}}
            {{-- ======================================== --}}
            <div class="tab-pane fade" id="pane-tahap3" role="tabpanel">
                @include('admin.pengaturan.partials.jadwal-tahap', [
                    'fieldPrefix' => 'tahap_3',
                    'jadwalTahap' => $tahapan['tahap_3'],
                    'statusJadwal' => $statusTahapan[3],
                    'judulJadwal' => 'Jadwal Pembayaran Formulir',
                    'deskripsiJadwal' => 'Atur kapan peserta dapat mengunggah bukti pembayaran formulir.',
                    'warnaJadwal' => 'warning',
                ])
                <div class="row">
                    <div class="col-md-6 mx-auto">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header text-white" style="background:#fd7e14">
                                <h6 class="mb-0"><i class="bi bi-bank me-2"></i>Rekening Pembayaran</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Rekening yang ditampilkan ke peserta untuk pembayaran formulir dan pelunasan.</p>
                                <div class="mb-3">
                                    <label class="form-label">Nama Bank</label>
                                    <input type="text" name="rekening_bank" class="form-control" 
                                           value="{{ old('rekening_bank', $spmb['rekening_bank']) }}" placeholder="BSI">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nomor Rekening</label>
                                    <input type="text" name="nomor_rekening" class="form-control" 
                                           value="{{ old('nomor_rekening', $spmb['nomor_rekening']) }}" placeholder="7227212335">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Atas Nama</label>
                                    <input type="text" name="nama_rekening" class="form-control" 
                                           value="{{ old('nama_rekening', $spmb['nama_rekening']) }}" placeholder="Yayasan Al Furqon">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================================== --}}
            {{-- TAB: TAHAP 6 - PELUNASAN --}}
            {{-- ======================================== --}}
            <div class="tab-pane fade" id="pane-tahap6" role="tabpanel">
                @include('admin.pengaturan.partials.jadwal-tahap', [
                    'fieldPrefix' => 'tahap_6',
                    'jadwalTahap' => $tahapan['tahap_6'],
                    'statusJadwal' => $statusTahapan[6],
                    'judulJadwal' => 'Jadwal Pembayaran Pelunasan',
                    'deskripsiJadwal' => 'Atur kapan peserta yang lolos wawancara dapat mengunggah bukti pelunasan.',
                    'warnaJadwal' => 'info',
                ])
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Biaya Pelunasan</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Biaya Pelunasan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="biaya_pelunasan" class="form-control" 
                                               value="{{ old('biaya_pelunasan', $spmb['biaya_pelunasan']) }}" min="0">
                                    </div>
                                    <small class="text-muted">Pembayaran setelah peserta dinyatakan lulus wawancara</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-info bg-opacity-75 text-white">
                                <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Template Kwitansi</h6>
                            </div>
                            <div class="card-body text-center">
                                <p class="text-muted small mb-3">Atur tampilan kwitansi pembayaran: logo, stempel, penandatangan.</p>
                                <a href="{{ route('admin.pengaturan.template-kwitansi') }}" class="btn btn-outline-info">
                                    <i class="bi bi-gear me-1"></i>Kelola Template Kwitansi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================================== --}}
            {{-- TAB: TAHAP 7 - KELULUSAN --}}
            {{-- ======================================== --}}
            <div class="tab-pane fade" id="pane-tahap7" role="tabpanel">
                <div class="row">
                    <div class="col-12">
                        @include('admin.pengaturan.partials.jadwal-tahap', [
                            'fieldPrefix' => 'tahap_7',
                            'jadwalTahap' => $tahapan['tahap_7'],
                            'statusJadwal' => $statusTahapan[7],
                            'judulJadwal' => 'Jadwal Pengumuman Kelulusan',
                            'deskripsiJadwal' => 'Atur tanggal dan jam hasil kelulusan dapat dilihat peserta.',
                            'warnaJadwal' => 'danger',
                        ])
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 border-0 shadow-sm border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Tampilan Jika LULUS</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Judul</label>
                                    @php $pengaturanKelulusan = app(\App\Services\PengaturanService::class)->ambilPengaturanKelulusan(); @endphp
                                    <input type="text" name="kelulusan_judul_lulus" class="form-control" 
                                           value="{{ $pengaturanKelulusan['judul_lulus'] ?? 'Selamat Bergabung!' }}" placeholder="Selamat Bergabung!">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Warna</label>
                                    <input type="color" name="kelulusan_warna_lulus" class="form-control form-control-color w-100" 
                                           value="{{ $pengaturanKelulusan['warna_lulus'] ?? '#198754' }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Teks Keterangan</label>
                                    <textarea name="kelulusan_teks_lulus" class="form-control" rows="2" placeholder="Anda resmi diterima sebagai peserta didik baru">{{ $pengaturanKelulusan['teks_lulus'] ?? 'Anda resmi diterima sebagai peserta didik baru' }}</textarea>
                                    <div class="form-text">Nama institusi dan tahun ajaran akan ditambahkan otomatis</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Informasi Penting Setelah Lulus</label>
                                    <textarea name="tahap_7[keterangan_lulus]" class="form-control" rows="3" placeholder="Informasi tambahan yang ditampilkan kepada peserta yang LULUS...">{{ old('tahap_7.keterangan_lulus', $tahapan['tahap_7']['keterangan_lulus'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4 border-0 shadow-sm border-danger">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="bi bi-x-circle me-2"></i>Tampilan Jika TIDAK LULUS</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Judul</label>
                                    <input type="text" name="kelulusan_judul_tidak_lulus" class="form-control" 
                                           value="{{ $pengaturanKelulusan['judul_tidak_lulus'] ?? 'Pengumuman Kelulusan' }}" placeholder="Pengumuman Kelulusan">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Warna</label>
                                    <input type="color" name="kelulusan_warna_tidak_lulus" class="form-control form-control-color w-100" 
                                           value="{{ $pengaturanKelulusan['warna_tidak_lulus'] ?? '#dc3545' }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Teks Keterangan</label>
                                    <textarea name="kelulusan_teks_tidak_lulus" class="form-control" rows="2" placeholder="Maaf, Anda belum diqodar menjadi peserta didik">{{ $pengaturanKelulusan['teks_tidak_lulus'] ?? 'Maaf, Anda belum diqodar menjadi peserta didik' }}</textarea>
                                    <div class="form-text">Nama institusi dan tahun ajaran akan ditambahkan otomatis</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Informasi Jika Tidak Lulus</label>
                                    <textarea name="tahap_7[keterangan_tidak_lulus]" class="form-control" rows="3" placeholder="Informasi tambahan yang ditampilkan kepada peserta yang TIDAK LULUS...">{{ old('tahap_7.keterangan_tidak_lulus', $tahapan['tahap_7']['keterangan_tidak_lulus'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-dark text-white">
                                <h6 class="mb-0"><i class="bi bi-file-earmark-pdf me-2"></i>SK Kelulusan per Gelombang</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Tambahkan gelombang dan upload SK yang sesuai. Saat meluluskan peserta, admin dapat memilih SK gelombang mana yang dipakai.</p>

                                <div id="skGelombangContainer">
                                    @foreach($skGelombang ?? [] as $index => $sk)
                                    <div class="sk-gelombang-item border rounded p-3 mb-3">
                                        <input type="hidden" name="sk_gelombang_existing[{{ $index }}][id]" value="{{ $sk['id'] }}">
                                        <input type="hidden" name="sk_gelombang_existing[{{ $index }}][file]" value="{{ $sk['file'] }}">
                                        <input type="hidden" name="sk_gelombang_existing[{{ $index }}][uploaded_at]" value="{{ $sk['uploaded_at'] ?? '' }}">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label small">Nama Gelombang</label>
                                                <input type="text" name="sk_gelombang_existing[{{ $index }}][nama]" class="form-control form-control-sm" value="{{ $sk['nama'] }}" placeholder="Gelombang 1">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Ganti File SK</label>
                                                <input type="file" name="sk_gelombang_existing[{{ $index }}][file_upload]" class="form-control form-control-sm" accept=".pdf,image/*">
                                            </div>
                                            <div class="col-md-2">
                                                <a href="{{ Storage::url($sk['file']) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                </a>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-check">
                                                    <input type="checkbox" name="sk_gelombang_existing[{{ $index }}][hapus]" value="1" class="form-check-input" id="hapusSk{{ $index }}">
                                                    <label for="hapusSk{{ $index }}" class="form-check-label small text-danger">Hapus</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-outline-dark btn-sm" onclick="tambahSkGelombang()">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Gelombang SK
                                </button>
                                <div class="form-text mt-2">Format file: PDF, JPG, PNG. Maksimal 5MB per file.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="bi bi-person-check me-2"></i>Verifikasi Kelulusan</h6>
                            </div>
                            <div class="card-body text-center">
                                <p class="text-muted small mb-3">Kelola kelulusan dan verifikasi peserta diterima.</p>
                                <a href="{{ route('admin.verifikasi.kelulusan') }}" class="btn btn-outline-success">
                                    <i class="bi bi-person-check me-2"></i>Verifikasi Kelulusan Peserta
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================================== --}}
            {{-- TAB: TIMELINE TAHAPAN --}}
            {{-- ======================================== --}}
            <div class="tab-pane fade" id="pane-timeline" role="tabpanel">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-calendar-range me-2"></i>Pengaturan Waktu Tahapan</h6>
                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#bulkActionPanel">
                            <i class="bi bi-lightning-charge me-1"></i>Bulk Action
                        </button>
                    </div>

                    {{-- Bulk Action Panel --}}
                    <div class="collapse" id="bulkActionPanel">
                        <div class="card-body bg-primary bg-opacity-10 border-bottom">
                            <h6 class="mb-3"><i class="bi bi-lightning-charge me-1"></i>Atur Tanggal Massal</h6>
                            <p class="text-muted small mb-3">Pilih tahapan yang ingin diubah, lalu masukkan tanggal buka dan tutup.</p>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tanggal Buka</label>
                                    <input type="date" id="bulkTanggalBuka" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tanggal Tutup</label>
                                    <input type="date" id="bulkTanggalTutup" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="applyBulkDates()">
                                        <i class="bi bi-check2-all me-1"></i>Terapkan ke Tahap Terpilih
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllTahap()">
                                    <i class="bi bi-check-all me-1"></i>Pilih Semua
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAllTahap()">
                                    <i class="bi bi-x-lg me-1"></i>Batal Pilih
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 40px;">
                                            <input type="checkbox" class="form-check-input" id="checkAllTahap" onclick="toggleAllTahap(this)" title="Pilih Semua">
                                        </th>
                                        <th>Tahap</th>
                                        <th style="width: 160px;">Tanggal Buka</th>
                                        <th style="width: 160px;">Tanggal Tutup</th>
                                        <th style="width: 100px;">Status</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $tahapanList = [
                                            2 => ['label' => 'Isi Formulir SPMB', 'icon' => 'file-earmark-text', 'color' => 'info'],
                                            3 => ['label' => 'Upload Bukti Bayar Formulir', 'icon' => 'credit-card', 'color' => 'warning'],
                                            4 => ['label' => 'Tes Online', 'icon' => 'laptop', 'color' => 'danger'],
                                            5 => ['label' => 'Wawancara & Verifikasi', 'icon' => 'people', 'color' => 'purple'],
                                            6 => ['label' => 'Upload Bukti Bayar Pertama', 'icon' => 'wallet2', 'color' => 'success'],
                                            7 => ['label' => 'Resmi Peserta Didik', 'icon' => 'mortarboard', 'color' => 'primary'],
                                        ];
                                    @endphp
                                    @foreach($tahapanList as $num => $info)
                                    @php
                                        $buka = $tahapan['tahap_'.$num]['tanggal_buka'] ?? '';
                                        $tutup = $tahapan['tahap_'.$num]['tanggal_tutup'] ?? '';
                                        $mulaiTahap = $buka ? \Carbon\Carbon::parse($buka . ' ' . (($tahapan['tahap_'.$num]['waktu_mulai'] ?? '') ?: '00:00')) : null;
                                        $selesaiTahap = $tutup ? \Carbon\Carbon::parse($tutup . ' ' . (($tahapan['tahap_'.$num]['waktu_selesai'] ?? '') ?: '23:59')) : null;
                                        $now = now();
                                        $isAktif = ($mulaiTahap || $selesaiTahap) && (!$mulaiTahap || $now->gte($mulaiTahap)) && (!$selesaiTahap || $now->lte($selesaiTahap));
                                        $isBelum = $mulaiTahap && $now->lt($mulaiTahap);
                                        $isLewat = $selesaiTahap && $now->gt($selesaiTahap);
                                        $dibukaTahap = filter_var($tahapan['tahap_'.$num]['dibuka'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                        $dibukaTahap = $dibukaTahap ?? true;
                                        $hasExtra = in_array($num, [2, 3, 4, 5, 6, 7]);
                                    @endphp
                                    <tr class="{{ $isAktif ? 'table-success' : '' }}">
                                        <td class="ps-3">
                                            @if(in_array($num, [2, 3, 4, 6, 7]))
                                                <input type="checkbox" class="form-check-input" disabled title="Jadwal diatur dari menu khusus tahap">
                                            @else
                                            <input type="checkbox" class="form-check-input tahap-check" data-tahap="{{ $num }}">
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-{{ $info['color'] }} rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">{{ $num }}</span>
                                                <div>
                                                    <span class="fw-semibold"><i class="bi bi-{{ $info['icon'] }} me-1"></i>{{ $info['label'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if(in_array($num, [2, 3, 6]))
                                                <span class="text-muted small">{{ $buka ? \Carbon\Carbon::parse($buka)->translatedFormat('d M Y') : 'Atur di tab tahap' }}</span>
                                            @elseif($num == 4)
                                                <span class="text-muted small">{{ $buka ? \Carbon\Carbon::parse($buka)->translatedFormat('d M Y') : 'Atur di menu Ujian' }}</span>
                                            @elseif($num == 7)
                                                <span class="text-muted small">{{ $buka ? \Carbon\Carbon::parse($buka)->translatedFormat('d M Y') : 'Atur di tab Kelulusan' }}</span>
                                            @else
                                            <input type="date" name="tahap_{{ $num }}[tanggal_buka]" id="tahap{{ $num }}_buka"
                                                   class="form-control form-control-sm" value="{{ $buka }}">
                                            @endif
                                        </td>
                                        <td>
                                            @if(in_array($num, [2, 3, 6]))
                                                <span class="text-muted small">{{ $tutup ? \Carbon\Carbon::parse($tutup)->translatedFormat('d M Y') : 'Atur di tab tahap' }}</span>
                                            @elseif($num == 4)
                                                <span class="text-muted small">{{ $tutup ? \Carbon\Carbon::parse($tutup)->translatedFormat('d M Y') : 'Atur di menu Ujian' }}</span>
                                            @elseif($num == 7)
                                                <span class="text-muted small">{{ $tutup ? \Carbon\Carbon::parse($tutup)->translatedFormat('d M Y') : '-' }}</span>
                                            @else
                                            <input type="date" name="tahap_{{ $num }}[tanggal_tutup]" id="tahap{{ $num }}_tutup"
                                                   class="form-control form-control-sm" value="{{ $tutup }}">
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$dibukaTahap)
                                                <span class="badge bg-danger"><i class="bi bi-lock me-1"></i>Ditutup</span>
                                            @elseif($num == 2 && $dibukaTahap && !$mulaiTahap && !$selesaiTahap)
                                                <span class="badge bg-success"><i class="bi bi-broadcast me-1"></i>Aktif</span>
                                            @elseif($isAktif)
                                                <span class="badge bg-success"><i class="bi bi-broadcast me-1"></i>Aktif</span>
                                            @elseif($isBelum)
                                                <span class="badge bg-info"><i class="bi bi-clock me-1"></i>Belum</span>
                                            @elseif($isLewat)
                                                <span class="badge bg-secondary"><i class="bi bi-check me-1"></i>Selesai</span>
                                            @else
                                                <span class="badge bg-light text-muted">Belum diatur</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($hasExtra)
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" 
                                                    data-bs-toggle="collapse" data-bs-target="#extraTahap{{ $num }}" title="Detail">
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @if(in_array($num, [2, 3]))
                                    <tr class="collapse" id="extraTahap{{ $num }}">
                                        <td></td>
                                        <td colspan="5">
                                            @if($num == 2)
                                            <div class="p-3 bg-light rounded">
                                                <p class="text-muted small mb-2">Jadwal isi formulir sekarang berdiri sendiri dan tidak mengikuti jadwal pendaftaran akun.</p>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="new bootstrap.Tab(document.getElementById('tab-tahap2')).show()">
                                                    <i class="bi bi-arrow-right-circle me-1"></i>Buka Tab Formulir
                                                </button>
                                            </div>
                                            @else
                                            <div class="p-3 bg-light rounded">
                                                <p class="text-muted small mb-2">Jadwal pembayaran formulir diatur bersama rekening pada tab Pembayaran.</p>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="new bootstrap.Tab(document.getElementById('tab-tahap3')).show()">
                                                    <i class="bi bi-arrow-right-circle me-1"></i>Buka Tab Pembayaran
                                                </button>
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @elseif($num == 4)
                                    <tr class="collapse" id="extraTahap4">
                                        <td></td>
                                        <td colspan="5">
                                            <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center gap-3">
                                                <span class="text-muted small">Tahap 4 menggunakan jadwal global Tes Online.</span>
                                                <a href="{{ route('admin.pengaturan.ujian') }}" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka Pengaturan Ujian
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @elseif($num == 5)
                                    <tr class="collapse" id="extraTahap5">
                                        <td></td>
                                        <td colspan="5">
                                            <div class="p-2 bg-light rounded">
                                                <div class="row g-2">
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Waktu Mulai</label>
                                                        <input type="time" name="tahap_5[waktu_mulai]" class="form-control form-control-sm" 
                                                               value="{{ $tahapan['tahap_5']['waktu_mulai'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Waktu Selesai</label>
                                                        <input type="time" name="tahap_5[waktu_selesai]" class="form-control form-control-sm" 
                                                               value="{{ $tahapan['tahap_5']['waktu_selesai'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small">Lokasi Wawancara</label>
                                                        <input type="text" name="tahap_5[lokasi]" class="form-control form-control-sm" 
                                                               value="{{ $tahapan['tahap_5']['lokasi'] ?? '' }}" placeholder="Contoh: Ruang Aula SMA Al Furqon">
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <label class="form-label small">Keterangan</label>
                                                    <textarea name="tahap_5[keterangan]" class="form-control form-control-sm" rows="2" placeholder="Keterangan...">{{ $tahapan['tahap_5']['keterangan'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @elseif($num == 6)
                                    <tr class="collapse" id="extraTahap6">
                                        <td></td>
                                        <td colspan="5">
                                            <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center gap-3">
                                                <span class="text-muted small">Jadwal pelunasan diatur bersama nominal pembayaran pada tab Pelunasan.</span>
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="new bootstrap.Tab(document.getElementById('tab-tahap6')).show()">
                                                    <i class="bi bi-arrow-right-circle me-1"></i>Buka Tab Pelunasan
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @elseif($num == 7)
                                    <tr class="collapse" id="extraTahap7">
                                        <td></td>
                                        <td colspan="5">
                                            <div class="p-2 bg-light rounded">
                                                <p class="small text-muted mb-2">Jadwal pengumuman dan keterangan hasil kelulusan dipusatkan di tab Kelulusan agar sama dengan halaman peserta.</p>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="new bootstrap.Tab(document.getElementById('tab-tahap7')).show()">
                                                    <i class="bi bi-arrow-right-circle me-1"></i>Buka Tab Kelulusan
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================================== --}}
            {{-- TAB: KONTAK TIM SPMB --}}
            {{-- ======================================== --}}
            <div class="tab-pane fade" id="pane-kontak" role="tabpanel">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-whatsapp me-2"></i>Kontak Tim SPMB</h6>
                                <button type="button" class="btn btn-sm btn-success" onclick="tambahKontak()">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah
                                </button>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Daftar kontak Tim SPMB yang dapat dihubungi peserta untuk bantuan.</p>
                                
                                <div id="kontakContainer">
                                    @php
                                        $kontakTim = json_decode($spmb['kontak_tim_spmb'] ?? '[]', true) ?: [];
                                        if (empty($kontakTim) && !empty($spmb['whatsapp_spmb'])) {
                                            $kontakTim = [['nama' => 'Tim SPMB', 'whatsapp' => $spmb['whatsapp_spmb']]];
                                        }
                                    @endphp
                                    
                                    @forelse($kontakTim as $index => $kontak)
                                    <div class="kontak-item border rounded p-3 mb-2" data-index="{{ $index }}">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-5">
                                                <label class="form-label small">Nama</label>
                                                <input type="text" name="kontak_tim[{{ $index }}][nama]" class="form-control form-control-sm" 
                                                       value="{{ $kontak['nama'] ?? '' }}" placeholder="Nama Tim/PIC">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small">No. WhatsApp</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">+62</span>
                                                    <input type="text" name="kontak_tim[{{ $index }}][whatsapp]" class="form-control" 
                                                           value="{{ $kontak['whatsapp'] ?? '' }}" placeholder="81234567890">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="hapusKontak(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center text-muted py-3" id="emptyKontak">
                                        <i class="bi bi-person-plus" style="font-size: 2rem;"></i>
                                        <p class="mb-0 mt-2">Belum ada kontak. Klik "Tambah" untuk menambahkan.</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end tab-content --}}

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-check-lg me-1"></i> Simpan Semua Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// === Deep link ke tab dari URL hash ===
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab) {
        const tabBtn = document.getElementById('tab-' + tab);
        if (tabBtn) {
            const bsTab = new bootstrap.Tab(tabBtn);
            bsTab.show();
        }
    }
});

function togglePendaftaran(checkbox) {
    const status = checkbox.checked;
    const badge = document.getElementById('statusPendaftaran');
    
    checkbox.disabled = true;
    badge.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Memproses...';
    badge.className = 'badge bg-secondary';
    
    fetch('{{ route("admin.pengaturan.spmb.toggle-pendaftaran") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        checkbox.disabled = false;
        
        if (data.sukses) {
            if (data.status) {
                badge.innerHTML = '<i class="bi bi-check-circle me-1"></i>DIBUKA';
                badge.className = 'badge bg-success';
            } else {
                badge.innerHTML = '<i class="bi bi-x-circle me-1"></i>DITUTUP';
                badge.className = 'badge bg-danger';
            }
            showToast(data.pesan, data.status ? 'success' : 'warning');
        } else {
            checkbox.checked = !status;
            badge.innerHTML = status ? '<i class="bi bi-x-circle me-1"></i>DITUTUP' : '<i class="bi bi-check-circle me-1"></i>DIBUKA';
            badge.className = status ? 'badge bg-danger' : 'badge bg-success';
            showToast('Gagal mengubah status pendaftaran', 'danger');
        }
    })
    .catch(error => {
        checkbox.disabled = false;
        checkbox.checked = !status;
        badge.innerHTML = status ? '<i class="bi bi-x-circle me-1"></i>DITUTUP' : '<i class="bi bi-check-circle me-1"></i>DIBUKA';
        badge.className = status ? 'badge bg-danger' : 'badge bg-success';
        showToast('Terjadi kesalahan', 'danger');
        console.error('Error:', error);
    });
}

// === Bulk Action Tahapan ===
function toggleAllTahap(masterCheckbox) {
    document.querySelectorAll('.tahap-check').forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
}

function selectAllTahap() {
    document.querySelectorAll('.tahap-check').forEach(cb => cb.checked = true);
    document.getElementById('checkAllTahap').checked = true;
}

function deselectAllTahap() {
    document.querySelectorAll('.tahap-check').forEach(cb => cb.checked = false);
    document.getElementById('checkAllTahap').checked = false;
}

function applyBulkDates() {
    const buka = document.getElementById('bulkTanggalBuka').value;
    const tutup = document.getElementById('bulkTanggalTutup').value;
    
    if (!buka && !tutup) {
        alert('Silakan isi minimal salah satu tanggal (buka atau tutup).');
        return;
    }
    
    const checked = document.querySelectorAll('.tahap-check:checked');
    if (checked.length === 0) {
        alert('Pilih minimal satu tahapan terlebih dahulu.');
        return;
    }
    
    let applied = 0;
    checked.forEach(cb => {
        const tahap = cb.dataset.tahap;
        if (buka) document.getElementById('tahap' + tahap + '_buka').value = buka;
        if (tutup) document.getElementById('tahap' + tahap + '_tutup').value = tutup;
        applied++;
    });
    
    showToast(`Tanggal berhasil diterapkan ke ${applied} tahapan. Klik "Simpan" untuk menyimpan.`, 'success');
}

let kontakIndex = {{ count($kontakTim) }};
let skGelombangBaruIndex = 0;

function tambahSkGelombang() {
    const container = document.getElementById('skGelombangContainer');
    const index = skGelombangBaruIndex++;
    const html = `
        <div class="sk-gelombang-item border rounded p-3 mb-3 bg-light">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Nama Gelombang</label>
                    <input type="text" name="sk_gelombang_baru[${index}][nama]" class="form-control form-control-sm" placeholder="Gelombang 1">
                </div>
                <div class="col-md-5">
                    <label class="form-label small">File SK</label>
                    <input type="file" name="sk_gelombang_baru[${index}][file]" class="form-control form-control-sm" accept=".pdf,image/*">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.sk-gelombang-item').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function tambahKontak() {
    const container = document.getElementById('kontakContainer');
    const emptyMsg = document.getElementById('emptyKontak');
    if (emptyMsg) emptyMsg.remove();
    
    const html = `
        <div class="kontak-item border rounded p-3 mb-2" data-index="${kontakIndex}">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Nama</label>
                    <input type="text" name="kontak_tim[${kontakIndex}][nama]" class="form-control form-control-sm" placeholder="Nama Tim/PIC">
                </div>
                <div class="col-md-5">
                    <label class="form-label small">No. WhatsApp</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">+62</span>
                        <input type="text" name="kontak_tim[${kontakIndex}][whatsapp]" class="form-control" placeholder="81234567890">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="hapusKontak(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    kontakIndex++;
}

function hapusKontak(btn) {
    const item = btn.closest('.kontak-item');
    item.remove();
    
    const container = document.getElementById('kontakContainer');
    if (container.querySelectorAll('.kontak-item').length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-3" id="emptyKontak">
                <i class="bi bi-person-plus" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">Belum ada kontak. Klik "Tambah" untuk menambahkan.</p>
            </div>
        `;
    }
}

function showToast(message, type) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }
    
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning' : 'bg-danger');
    const textClass = type === 'warning' ? 'text-dark' : 'text-white';
    
    const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass} ${textClass}" role="alert">
            <div class="toast-body d-flex align-items-center">
                <i class="bi bi-${type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'x-circle')} me-2"></i>
                ${message}
                <button type="button" class="btn-close ${type !== 'warning' ? 'btn-close-white' : ''} ms-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    
    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}
</script>
@endpush
