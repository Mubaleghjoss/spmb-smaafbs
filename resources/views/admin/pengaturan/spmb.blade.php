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
                        <div class="small mt-1">Form daftar dibuka otomatis jika ada tahun ajaran dan gelombang aktif yang sedang berada dalam tanggal/jam buka-tutup.</div>
                    </div>
                    <a href="{{ route('admin.pengaturan.spmb.periode') }}" class="btn btn-outline-primary">
                        <i class="bi bi-sliders me-1"></i>Kelola Periode
                    </a>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-power me-2"></i>Status Umum Pendaftaran</h6>
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
                                    <small class="text-muted mt-2 d-block">Status ini hanya catatan manual. Halaman /daftar sekarang mengikuti Tahun Ajaran & Gelombang yang aktif dan sedang dibuka.</small>
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
                                                <th>Kuota</th>
                                                <th>Gelombang</th>
                                                <th>Jadwal</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($periodePendaftaran as $tahun)
                                                @php
                                                    $kuotaTahun = $ringkasanKuota[$tahun->id] ?? [
                                                        'kuota_label' => 'Tidak dibatasi',
                                                        'total' => $tahun->peserta_count,
                                                        'waiting_list' => 0,
                                                        'sisa_label' => 'Tidak dibatasi',
                                                        'laki_laki' => ['kuota_label' => 'Tidak dibatasi', 'dalam_kuota' => 0],
                                                        'perempuan' => ['kuota_label' => 'Tidak dibatasi', 'dalam_kuota' => 0],
                                                    ];
                                                @endphp
                                                @forelse($tahun->gelombangPendaftaran as $gelombang)
                                                    @php
                                                        $statusGelombang = $gelombang->statusPendaftaran();
                                                    @endphp
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
                                                        <td class="small">
                                                            <span class="badge bg-primary">{{ $kuotaTahun['kuota_label'] }}</span>
                                                            <span class="d-block text-muted mt-1">
                                                                Total {{ $kuotaTahun['total'] }},
                                                                sisa {{ $kuotaTahun['sisa_label'] }},
                                                                waiting {{ $kuotaTahun['waiting_list'] }}
                                                            </span>
                                                            <span class="d-block text-muted">
                                                                L {{ $kuotaTahun['laki_laki']['dalam_kuota'] }}/{{ $kuotaTahun['laki_laki']['kuota_label'] }},
                                                                P {{ $kuotaTahun['perempuan']['dalam_kuota'] }}/{{ $kuotaTahun['perempuan']['kuota_label'] }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $gelombang->nama }}</td>
                                                        <td class="small">{{ $gelombang->labelPeriodePendaftaran() }}</td>
                                                        <td><span class="badge bg-{{ $statusGelombang['class'] }}">{{ $statusGelombang['label'] }}</span></td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>{{ $tahun->nama }}</td>
                                                        <td colspan="4" class="text-muted">Belum ada gelombang.</td>
                                                    </tr>
                                                @endforelse
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">Belum ada tahun ajaran.</td>
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
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-calendar-range me-2"></i>Pengaturan Waktu Tahapan</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
                            <div>
                                <strong>Pengaturan waktu tahap kini terpusat &amp; per gelombang.</strong>
                                <div class="small mt-1">
                                    Atur waktu buka/tutup tiap tahap, catatan untuk pendaftar, dan jadwal timeline
                                    di halaman <strong>Alur &amp; Jadwal</strong> — tersinkron otomatis ke halaman
                                    publik <a href="{{ route('jadwal') }}" target="_blank" class="alert-link">/jadwal</a>
                                    dan dashboard peserta. Tiap gelombang punya timeline sendiri.
                                </div>
                            </div>
                        </div>

                        {{-- Ringkasan read-only status tahap (tahun aktif) --}}
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tahap</th>
                                        <th style="width: 220px;">Jadwal (ringkas)</th>
                                        <th style="width: 110px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $ringkasList = [
                                            2 => ['label' => 'Lengkapi Formulir & Berkas', 'icon' => 'file-earmark-text', 'color' => 'info'],
                                            3 => ['label' => 'Pembayaran Formulir', 'icon' => 'credit-card', 'color' => 'warning'],
                                            4 => ['label' => 'Tes Online', 'icon' => 'laptop', 'color' => 'danger'],
                                            5 => ['label' => 'Wawancara & Verifikasi', 'icon' => 'people', 'color' => 'secondary'],
                                            6 => ['label' => 'Pembayaran Pertama', 'icon' => 'wallet2', 'color' => 'success'],
                                            7 => ['label' => 'Pengumuman Kelulusan', 'icon' => 'mortarboard', 'color' => 'primary'],
                                        ];
                                    @endphp
                                    @foreach($ringkasList as $num => $info)
                                    @php
                                        $r = $ringkasJadwal[$num] ?? null;
                                        $buka = $r['tanggal_buka'] ?? '';
                                        $tutup = $r['tanggal_tutup'] ?? '';
                                        $dibukaR = $r['dibuka'] ?? true;
                                        if ($buka && $tutup) {
                                            $teks = \Carbon\Carbon::parse($buka)->translatedFormat('d M Y') . ' – ' . \Carbon\Carbon::parse($tutup)->translatedFormat('d M Y');
                                        } elseif ($buka) {
                                            $teks = 'Mulai ' . \Carbon\Carbon::parse($buka)->translatedFormat('d M Y');
                                        } elseif ($tutup) {
                                            $teks = 'Sampai ' . \Carbon\Carbon::parse($tutup)->translatedFormat('d M Y');
                                        } else {
                                            $teks = 'Belum diatur';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $info['color'] }} rounded-circle me-2" style="width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;">{{ $num }}</span>
                                            <i class="bi bi-{{ $info['icon'] }} me-1"></i>{{ $info['label'] }}
                                        </td>
                                        <td class="text-muted small">{{ $teks }}</td>
                                        <td>
                                            @if(!$dibukaR)
                                                <span class="badge bg-danger"><i class="bi bi-lock me-1"></i>Ditutup</span>
                                            @else
                                                <span class="badge bg-success"><i class="bi bi-unlock me-1"></i>Dibuka</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-center mt-4">
                            <a href="{{ route('admin.alur-jadwal.index') }}" class="btn btn-success btn-lg">
                                <i class="bi bi-calendar-week-fill me-2"></i>Atur di Halaman Alur &amp; Jadwal
                            </a>
                            <div class="small text-muted mt-2">Termasuk pemilihan gelombang &amp; catatan untuk pendaftar.</div>
                        </div>
                    </div>
                </div>
            </div>
{{-- ==END-TIMELINE-TAB== --}}

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
