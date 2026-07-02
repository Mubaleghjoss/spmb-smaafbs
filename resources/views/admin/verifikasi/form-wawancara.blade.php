@extends('layouts.admin')

@section('title', 'Form Wawancara - ' . $peserta->nama)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-clipboard-data me-2"></i>Form Wawancara & Verifikasi Berkas</h4>
            <p class="text-muted mb-0">{{ $peserta->nama }} - {{ $peserta->nomor_pendaftaran }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.verifikasi.wawancara.cetak', $peserta) }}" class="btn btn-action-print" target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak Form
            </a>
            <a href="{{ route('admin.verifikasi.wawancara') }}" class="btn btn-action-back">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
    
    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Data belum bisa disimpan:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.verifikasi.wawancara.simpan', $peserta) }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        {{-- Info Peserta --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Data Peserta</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" width="35%">Nama</td>
                                <td><strong>{{ $peserta->nama }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">No. Pendaftaran</td>
                                <td><code>{{ $peserta->nomor_pendaftaran }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Umur</td>
                                <td>
                                    @if($peserta->formulirSpmb?->tanggal_lahir)
                                        {{ $peserta->formulirSpmb->tanggal_lahir->age }} tahun
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Asal Sekolah</td>
                                <td>{{ $peserta->formulirSpmb?->asal_sekolah ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" width="35%">Nama Ayah</td>
                                <td>{{ $peserta->formulirSpmb?->nama_ayah ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nama Ibu</td>
                                <td>{{ $peserta->formulirSpmb?->nama_ibu ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Telepon Ayah</td>
                                <td>{{ $peserta->formulirSpmb?->telepon_ayah ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Telepon Ibu</td>
                                <td>{{ $peserta->formulirSpmb?->telepon_ibu ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Notifikasi jika peserta sudah mengisi --}}
        @if($peserta->wawancara?->diisi_peserta_pada)
        <div class="alert alert-info border-start border-4 border-primary mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>
                <div>
                    <strong>Peserta sudah mengisi form wawancara sendiri</strong>
                    <div class="text-muted small">Diisi pada: {{ $peserta->wawancara->diisi_peserta_pada->translatedFormat('d F Y H:i') }}</div>
                    <div class="text-muted small">Jawaban peserta ditampilkan di bawah. Admin bisa menambahkan / mengedit catatan interviewer.</div>
                </div>
            </div>
        </div>
        @endif
        
        {{-- ============================================== --}}
        {{-- WAWANCARA ORANG TUA --}}
        {{-- ============================================== --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>WAWANCARA ORANG TUA / WALI</h5>
                <small>{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</small>
            </div>
            <div class="card-body">
                {{-- Info Wawancara Ortu --}}
                <div class="row g-3 mb-4 p-3 bg-light rounded">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Wawancara Ortu</label>
                        <input type="date" name="tanggal_wawancara_ortu" class="form-control" 
                               value="{{ old('tanggal_wawancara_ortu', $peserta->wawancara?->tanggal_wawancara_ortu?->format('Y-m-d') ?? date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nama Interviewer Ortu</label>
                        <input type="text" name="interviewer_ortu" class="form-control" 
                               value="{{ old('interviewer_ortu', $peserta->wawancara?->interviewer_ortu ?? auth('pengguna')->user()->nama) }}" 
                               placeholder="Nama petugas wawancara">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kelompok</label>
                        <input type="text" name="kelompok" class="form-control" 
                               value="{{ old('kelompok', $peserta->wawancara?->kelompok) }}" placeholder="Contoh: A, B, C">
                    </div>
                </div>

                @php $filePertanyaanOrtu = $peserta->wawancara?->jawaban_ortu['_file_manual'] ?? null; @endphp
                <div class="border rounded p-3 mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-upload me-1"></i>Upload Manual Pertanyaan Orang Tua</label>
                    <input type="file" name="file_pertanyaan_ortu_manual" class="form-control" accept="application/pdf,image/png,image/jpeg">
                    <small class="text-muted">Gunakan jika jawaban sudah ada dalam file scan/foto/PDF. Maksimal 5MB.</small>
                    @if($filePertanyaanOrtu)
                        <div class="mt-2">
                            <a href="{{ asset('storage/' . $filePertanyaanOrtu) }}" target="_blank" class="btn btn-sm btn-action-view">
                                <i class="bi bi-eye me-1"></i>Lihat File Manual Orang Tua
                            </a>
                        </div>
                    @endif
                </div>
                
                {{-- Pertanyaan Ortu --}}
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">NO.</th>
                                <th width="50%">PERTANYAAN UNTUK ORANG TUA</th>
                                <th width="45%">TANGGAPAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pertanyaanOrtu as $no => $pertanyaan)
                            <tr>
                                <td class="text-center align-top">{{ $no }}</td>
                                <td class="align-top">
                                    <small>{!! nl2br(e($pertanyaan)) !!}</small>
                                </td>
                                <td>
                                    <textarea name="jawaban_ortu[{{ $no }}]" class="form-control form-control-sm" rows="3" 
                                              placeholder="Jawaban...">{{ old("jawaban_ortu.$no", $peserta->wawancara?->jawaban_ortu[$no] ?? '') }}</textarea>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{-- Catatan Interviewer Ortu --}}
                <div class="mt-4">
                    <label class="form-label fw-bold"><i class="bi bi-journal-text me-1"></i>Catatan Interviewer (Wawancara Ortu)</label>
                    <textarea name="catatan_ortu" class="form-control" rows="3" 
                              placeholder="Catatan tambahan dari interviewer untuk wawancara orang tua...">{{ old('catatan_ortu', $peserta->wawancara?->catatan_ortu) }}</textarea>
                </div>
            </div>
        </div>
        
        {{-- ============================================== --}}
        {{-- WAWANCARA SISWA --}}
        {{-- ============================================== --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>WAWANCARA CALON SISWA/I</h5>
                <small>{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</small>
            </div>
            <div class="card-body">
                {{-- Info Wawancara Siswa --}}
                <div class="row g-3 mb-4 p-3 bg-light rounded">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Wawancara Siswa</label>
                        <input type="date" name="tanggal_wawancara_siswa" class="form-control" 
                               value="{{ old('tanggal_wawancara_siswa', $peserta->wawancara?->tanggal_wawancara_siswa?->format('Y-m-d') ?? date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Interviewer Siswa</label>
                        <input type="text" name="interviewer_siswa" class="form-control" 
                               value="{{ old('interviewer_siswa', $peserta->wawancara?->interviewer_siswa ?? auth('pengguna')->user()->nama) }}" 
                               placeholder="Nama petugas wawancara">
                    </div>
                </div>

                @php $filePertanyaanSiswa = $peserta->wawancara?->jawaban_siswa['_file_manual'] ?? null; @endphp
                <div class="border rounded p-3 mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-upload me-1"></i>Upload Manual Pertanyaan Calon Siswa</label>
                    <input type="file" name="file_pertanyaan_siswa_manual" class="form-control" accept="application/pdf,image/png,image/jpeg">
                    <small class="text-muted">Gunakan jika jawaban sudah ada dalam file scan/foto/PDF. Maksimal 5MB.</small>
                    @if($filePertanyaanSiswa)
                        <div class="mt-2">
                            <a href="{{ asset('storage/' . $filePertanyaanSiswa) }}" target="_blank" class="btn btn-sm btn-action-view">
                                <i class="bi bi-eye me-1"></i>Lihat File Manual Calon Siswa
                            </a>
                        </div>
                    @endif
                </div>
                
                {{-- Pertanyaan Siswa --}}
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">NO.</th>
                                <th width="50%">PERTANYAAN UNTUK CALON SISWA</th>
                                <th width="45%">TANGGAPAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pertanyaanSiswa as $no => $pertanyaan)
                            <tr>
                                <td class="text-center align-top">{{ $no }}</td>
                                <td class="align-top">
                                    <small>{!! nl2br(e($pertanyaan)) !!}</small>
                                </td>
                                <td>
                                    <textarea name="jawaban_siswa[{{ $no }}]" class="form-control form-control-sm" rows="3" 
                                              placeholder="Jawaban...">{{ old("jawaban_siswa.$no", $peserta->wawancara?->jawaban_siswa[$no] ?? '') }}</textarea>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{-- Catatan Interviewer Siswa --}}
                <div class="mt-4">
                    <label class="form-label fw-bold"><i class="bi bi-journal-text me-1"></i>Catatan Interviewer (Wawancara Siswa)</label>
                    <textarea name="catatan_siswa" class="form-control" rows="3" 
                              placeholder="Catatan tambahan dari interviewer untuk wawancara siswa...">{{ old('catatan_siswa', $peserta->wawancara?->catatan_siswa) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Surat Pernyataan Manual --}}
        @php
            $formulir = $peserta->formulirSpmb;
            $alamatDefault = collect([
                $formulir?->alamat,
                $formulir?->alamat_kelurahan,
                $formulir?->alamat_kecamatan,
                $formulir?->alamat_kota,
                $formulir?->alamat_provinsi,
            ])->filter()->implode(', ');
            $tempatTglLahirDefault = trim(($formulir?->tempat_lahir ?? '') . ($formulir?->tanggal_lahir ? ', ' . $formulir->tanggal_lahir->format('d/m/Y') : ''));
            $namaOrtuDefault = $formulir?->nama_ayah ?: $formulir?->nama_ibu;
            $noHpOrtuDefault = $formulir?->telepon_ayah ?: ($formulir?->telepon_ibu ?: $peserta->telepon);
            $spSiswa = old('surat_pernyataan_siswa', $peserta->wawancara?->surat_pernyataan_siswa ?? []);
            $spOrtu = old('surat_pernyataan_ortu', $peserta->wawancara?->surat_pernyataan_ortu ?? []);
            $tanggalSuratSiswa = $spSiswa['tanggal_surat'] ?? now()->toDateString();
            $tanggalSuratOrtu = $spOrtu['tanggal_surat'] ?? now()->toDateString();
        @endphp
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>SURAT PERNYATAAN SISWA/I DAN ORANGTUA</h5>
                    <small>Input manual oleh admin jika peserta/orangtua belum mengisi dari akun peserta.</small>
                </div>
                @if($peserta->wawancara?->surat_pernyataan_siswa || $peserta->wawancara?->surat_pernyataan_ortu)
                    <a href="{{ route('admin.verifikasi.wawancara.surat-pernyataan', $peserta) }}" target="_blank" class="btn btn-sm btn-action-print">
                        <i class="bi bi-printer me-1"></i>Cetak Surat
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-calendar-check me-2"></i>
                    Tanggal surat otomatis disimpan saat pertama dibuat dan akan muncul di atas tanda tangan saat dicetak.
                </div>

                @php $fileSuratManual = $spSiswa['_file_manual'] ?? null; @endphp
                <div class="border rounded p-3 mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-upload me-1"></i>Upload Manual Surat Pernyataan Siswa/i dan Orangtua</label>
                    <input type="file" name="file_surat_pernyataan_manual" class="form-control" accept="application/pdf,image/png,image/jpeg">
                    <small class="text-muted">Gunakan jika surat pernyataan sudah ditandatangani dalam file scan/foto/PDF. Maksimal 5MB.</small>
                    @if($fileSuratManual)
                        <div class="mt-2">
                            <a href="{{ asset('storage/' . $fileSuratManual) }}" target="_blank" class="btn btn-sm btn-action-view">
                                <i class="bi bi-eye me-1"></i>Lihat File Surat Manual
                            </a>
                        </div>
                    @endif
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-primary mb-0">Surat Pernyataan Siswa/i</h6>
                                <span class="badge bg-primary">Tanggal: {{ \Illuminate\Support\Carbon::parse($tanggalSuratSiswa)->format('d/m/Y') }}</span>
                            </div>
                            <input type="hidden" name="surat_pernyataan_siswa[tanggal_surat]" value="{{ $tanggalSuratSiswa }}">
                            <input type="hidden" name="surat_pernyataan_siswa[setuju]" value="1">

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="surat_pernyataan_siswa[nama_lengkap]" class="form-control" value="{{ $spSiswa['nama_lengkap'] ?? $peserta->nama }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tempat, Tanggal Lahir</label>
                                <input type="text" name="surat_pernyataan_siswa[tempat_tgl_lahir]" class="form-control" value="{{ $spSiswa['tempat_tgl_lahir'] ?? $tempatTglLahirDefault }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="surat_pernyataan_siswa[alamat]" class="form-control" rows="2">{{ $spSiswa['alamat'] ?? $alamatDefault }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Orangtua/Wali</label>
                                <input type="text" name="surat_pernyataan_siswa[nama_ortu]" class="form-control" value="{{ $spSiswa['nama_ortu'] ?? $namaOrtuDefault }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No.Telp/HP Orang tua/Wali</label>
                                <input type="text" name="surat_pernyataan_siswa[no_telp_ortu]" class="form-control" value="{{ $spSiswa['no_telp_ortu'] ?? $noHpOrtuDefault }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload TTD Siswa <span class="text-muted">(opsional)</span></label>
                                <input type="file" name="tanda_tangan_peserta_upload" class="form-control" accept="image/png,image/jpeg,image/webp">
                                @if($peserta->wawancara?->tanda_tangan_peserta)
                                    <img src="{{ $peserta->wawancara->tanda_tangan_peserta }}" class="border rounded mt-2" style="max-width:220px;max-height:90px;background:#fff" alt="TTD Siswa">
                                @endif
                            </div>
                            <div class="bg-light rounded p-2 small">
                                <div class="fw-semibold mb-1">Poin surat yang akan tercetak:</div>
                                <ol class="mb-0 ps-3">
                                    @foreach($spSiswaPoin as $poin)
                                        <li class="mb-1">{{ \Illuminate\Support\Str::limit($poin, 120) }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-info mb-0">Surat Pernyataan Orangtua</h6>
                                <span class="badge bg-info">Tanggal: {{ \Illuminate\Support\Carbon::parse($tanggalSuratOrtu)->format('d/m/Y') }}</span>
                            </div>
                            <input type="hidden" name="surat_pernyataan_ortu[tanggal_surat]" value="{{ $tanggalSuratOrtu }}">
                            <input type="hidden" name="surat_pernyataan_ortu[setuju]" value="1">

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap Orangtua/Wali</label>
                                <input type="text" name="surat_pernyataan_ortu[nama_lengkap]" class="form-control" value="{{ $spOrtu['nama_lengkap'] ?? $namaOrtuDefault }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="surat_pernyataan_ortu[alamat]" class="form-control" rows="2">{{ $spOrtu['alamat'] ?? $alamatDefault }}</textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Kelompok</label>
                                    <input type="text" name="surat_pernyataan_ortu[kelompok]" class="form-control" value="{{ $spOrtu['kelompok'] ?? $formulir?->kelompok }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Desa</label>
                                    <input type="text" name="surat_pernyataan_ortu[desa]" class="form-control" value="{{ $spOrtu['desa'] ?? $formulir?->desa }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Daerah</label>
                                    <input type="text" name="surat_pernyataan_ortu[daerah]" class="form-control" value="{{ $spOrtu['daerah'] ?? $formulir?->daerah }}">
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label">Nama KI Kelompok + No. HP</label>
                                <input type="text" name="surat_pernyataan_ortu[nama_ki]" class="form-control" value="{{ $spOrtu['nama_ki'] ?? '' }}">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">No. HP Orang tua/Wali</label>
                                    <input type="text" name="surat_pernyataan_ortu[no_hp]" class="form-control" value="{{ $spOrtu['no_hp'] ?? $noHpOrtuDefault }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Siswa</label>
                                    <input type="text" name="surat_pernyataan_ortu[nama_siswa]" class="form-control" value="{{ $spOrtu['nama_siswa'] ?? $peserta->nama }}">
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label">Asal Sekolah</label>
                                <input type="text" name="surat_pernyataan_ortu[asal_sekolah]" class="form-control" value="{{ $spOrtu['asal_sekolah'] ?? $formulir?->asal_sekolah }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload TTD Orangtua/Wali <span class="text-muted">(opsional)</span></label>
                                <input type="file" name="tanda_tangan_ortu_upload" class="form-control" accept="image/png,image/jpeg,image/webp">
                                @if($peserta->wawancara?->tanda_tangan_ortu)
                                    <img src="{{ $peserta->wawancara->tanda_tangan_ortu }}" class="border rounded mt-2" style="max-width:220px;max-height:90px;background:#fff" alt="TTD Orangtua">
                                @endif
                            </div>
                            <div class="bg-light rounded p-2 small">
                                <div class="fw-semibold mb-1">Poin surat yang akan tercetak:</div>
                                <ol class="mb-0 ps-3">
                                    @foreach($spOrtuPoin as $poin)
                                        <li class="mb-1">{{ \Illuminate\Support\Str::limit($poin, 120) }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Verifikasi Berkas --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-folder-check me-2"></i>Verifikasi Kelengkapan Berkas</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($daftarBerkas as $key => $label)
                    <div class="col-md-3 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="verifikasi_berkas[{{ $key }}]" class="form-check-input" id="berkas_{{ $key }}" value="1"
                                   {{ old("verifikasi_berkas.$key", $peserta->wawancara?->verifikasi_berkas[$key] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="berkas_{{ $key }}">{{ $label }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        {{-- Kesimpulan dan Hasil --}}
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Kesimpulan / Catatan Akhir</h6>
                    </div>
                    <div class="card-body">
                        <textarea name="catatan_interviewer" class="form-control" rows="4" 
                                  placeholder="Kesimpulan dan catatan akhir dari seluruh proses wawancara...">{{ old('catatan_interviewer', $peserta->wawancara?->catatan_interviewer) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-check2-square me-2"></i>Hasil Wawancara</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3 border border-success rounded p-3 ps-5 bg-success bg-opacity-10">
                            <input type="radio" name="hasil_wawancara" class="form-check-input" id="hasil_lulus" value="lulus"
                                   {{ old('hasil_wawancara', $peserta->wawancara?->hasil_wawancara) === 'lulus' ? 'checked' : '' }}>
                            <label class="form-check-label text-success fw-bold fs-5" for="hasil_lulus">
                                <i class="bi bi-check-circle me-1"></i>LULUS
                            </label>
                        </div>
                        <div class="form-check mb-3 border border-danger rounded p-3 ps-5 bg-danger bg-opacity-10">
                            <input type="radio" name="hasil_wawancara" class="form-check-input" id="hasil_tidak_lulus" value="tidak_lulus"
                                   {{ old('hasil_wawancara', $peserta->wawancara?->hasil_wawancara) === 'tidak_lulus' ? 'checked' : '' }}>
                            <label class="form-check-label text-danger fw-bold fs-5" for="hasil_tidak_lulus">
                                <i class="bi bi-x-circle me-1"></i>TIDAK LULUS
                            </label>
                        </div>
                        <div class="form-check border border-warning rounded p-3 ps-5 bg-warning bg-opacity-25">
                            <input type="radio" name="hasil_wawancara" class="form-check-input" id="hasil_menunggu" value="menunggu"
                                   {{ old('hasil_wawancara', $peserta->wawancara?->hasil_wawancara ?? 'menunggu') === 'menunggu' ? 'checked' : '' }}>
                            <label class="form-check-label text-dark fw-bold fs-5" for="hasil_menunggu">
                                <i class="bi bi-hourglass-split me-1 text-warning"></i>MENUNGGU REVIEW
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Tombol Submit --}}

        {{-- Tanda Tangan Peserta --}}
        @if($peserta->wawancara?->tanda_tangan_peserta || $peserta->wawancara?->tanda_tangan_ortu)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="bi bi-pen me-2"></i>Tanda Tangan (dari Peserta)</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($peserta->wawancara?->tanda_tangan_peserta)
                    <div class="col-md-6 text-center">
                        <p class="fw-bold mb-1">TTD Calon Siswa</p>
                        <img src="{{ $peserta->wawancara->tanda_tangan_peserta }}" class="border rounded" style="max-width:300px;max-height:120px" alt="TTD Peserta">
                    </div>
                    @endif
                    @if($peserta->wawancara?->tanda_tangan_ortu)
                    <div class="col-md-6 text-center">
                        <p class="fw-bold mb-1">TTD Orang Tua/Wali</p>
                        <img src="{{ $peserta->wawancara->tanda_tangan_ortu }}" class="border rounded" style="max-width:300px;max-height:120px" alt="TTD Ortu">
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Surat Pernyataan --}}
        @if($peserta->wawancara?->surat_pernyataan_siswa || $peserta->wawancara?->surat_pernyataan_ortu)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Surat Pernyataan (Peserta/Admin)</h6>
                <div>
                    <a href="{{ route('admin.verifikasi.wawancara.surat-pernyataan.pdf', $peserta) }}" class="btn btn-sm btn-danger me-1" title="Download PDF">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
                    </a>
                    @php
                        $noHpSp = $peserta->wawancara?->surat_pernyataan_siswa['no_telp_ortu']
                            ?? $peserta->wawancara?->surat_pernyataan_ortu['no_hp']
                            ?? $peserta->formulirSpmb?->no_hp_ortu
                            ?? '';
                        $noHpWaSp = preg_replace('/^0/', '62', preg_replace('/\D/', '', $noHpSp));
                        $pdfUrl = route('peserta.wawancara.surat-pernyataan.pdf');
                        $cetakUrl = route('peserta.wawancara.surat-pernyataan.cetak');
                        $pesanWa = urlencode("Assalamu'alaikum,\n\nBerikut surat pernyataan SPMB SMA AFBS atas nama:\nNama Siswa: {$peserta->nama}\n\nSilakan download PDF surat pernyataan melalui link berikut:\n{$pdfUrl}\n\nAtau buka halaman cetak:\n{$cetakUrl}\n\n*Catatan: Anda harus login sebagai peserta terlebih dahulu untuk mengakses link di atas.");
                    @endphp
                    @if($noHpWaSp)
                    <a href="https://wa.me/{{ $noHpWaSp }}?text={{ $pesanWa }}" target="_blank" class="btn btn-sm btn-success me-1" title="Kirim via WhatsApp">
                        <i class="bi bi-whatsapp me-1"></i>Kirim WA ({{ $noHpSp }})
                    </a>
                    @endif
                    <a href="{{ route('admin.verifikasi.wawancara.surat-pernyataan', $peserta) }}" target="_blank" class="btn btn-sm btn-action-print" title="Cetak/Lihat">
                        <i class="bi bi-printer me-1"></i>Cetak
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($peserta->wawancara?->surat_pernyataan_siswa)
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary mb-2">Surat Pernyataan Siswa</h6>
                        <table class="table table-sm">
                            @foreach($peserta->wawancara->surat_pernyataan_siswa as $key => $val)
                                @if(!in_array($key, ['setuju', 'tanggal_surat', '_file_manual']))
                                <tr><td class="text-muted" style="width:40%">{{ ucwords(str_replace('_',' ',$key)) }}</td><td>{{ $val }}</td></tr>
                                @endif
                            @endforeach
                        </table>
                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Disetujui</span>
                    </div>
                    @endif
                    @if($peserta->wawancara?->surat_pernyataan_ortu)
                    <div class="col-md-6">
                        <h6 class="fw-bold text-info mb-2">Surat Pernyataan Orangtua</h6>
                        <table class="table table-sm">
                            @foreach($peserta->wawancara->surat_pernyataan_ortu as $key => $val)
                                @if(!in_array($key, ['setuju', 'tanggal_surat', '_file_manual']))
                                <tr><td class="text-muted" style="width:40%">{{ ucwords(str_replace('_',' ',$key)) }}</td><td>{{ $val }}</td></tr>
                                @endif
                            @endforeach
                        </table>
                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Disetujui</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Tes Pegon & Voice Quran --}}
        @if($peserta->wawancara?->file_tes_pegon || $peserta->wawancara?->file_voice_quran)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>File Tes (dari Peserta)</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if($peserta->wawancara?->file_tes_pegon)
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="fw-bold"><i class="bi bi-pencil-square me-1 text-dark"></i>Tes Pegon</h6>
                            <a href="{{ asset('storage/' . $peserta->wawancara->file_tes_pegon) }}" target="_blank" class="btn btn-sm btn-action-view">
                                <i class="bi bi-eye me-1"></i>Lihat Jawaban
                            </a>
                        </div>
                    </div>
                    @endif
                    @if($peserta->wawancara?->file_voice_quran)
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6 class="fw-bold"><i class="bi bi-mic me-1 text-success"></i>Bacaan Quran</h6>
                            <p class="text-muted small mb-2">Surat: <strong>{{ $peserta->wawancara->surat_quran_random }}</strong></p>
                            <audio controls class="w-100">
                                <source src="{{ asset('storage/' . $peserta->wawancara->file_voice_quran) }}">
                            </audio>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="d-flex justify-content-between flex-wrap gap-2">
            <a href="{{ route('admin.verifikasi.wawancara') }}" class="btn btn-action-back">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <button type="submit" class="btn btn-action-save btn-lg">
                <i class="bi bi-save me-1"></i>Simpan Hasil Wawancara
            </button>
        </div>
    </form>
</div>
@endsection
