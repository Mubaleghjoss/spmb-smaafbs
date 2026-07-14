@extends('layouts.peserta')

@section('title', 'Wawancara')

@section('content')
<div class="container py-4">
    {{-- Header --}}
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-secondary btn-sm me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0">Wawancara & Verifikasi Berkas</h4>
            <small class="text-muted">Tahap 5 — Lengkapi semua 6 langkah di bawah ini</small>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(($peserta->tahapanSpmb?->status_kelulusan === 'lulus') && ($peserta->tahapanSpmb?->tahap_7_selesai ?? false) && (($kelengkapanWawancara['count'] ?? 0) > 0))
        <div class="alert alert-warning border-0 shadow-sm">
            <div class="d-flex gap-3">
                <i class="bi bi-exclamation-triangle fs-4"></i>
                <div>
                    <h6 class="alert-heading mb-1">Data wawancara masih belum lengkap</h6>
                    <p class="mb-2">Anda sudah dinyatakan lulus, tetapi sistem belum menemukan kelengkapan wawancara berikut:</p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($kelengkapanWawancara['fields'] as $field)
                            <span class="badge bg-warning text-dark">{{ $field }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== STEPPER BAR ===== --}}
    @php
        $steps = [
            1 => ['icon' => 'bi-people', 'label' => 'Form Ortu', 'done' => !empty($wawancara?->jawaban_ortu)],
            2 => ['icon' => 'bi-person', 'label' => 'Form Siswa', 'done' => !empty($wawancara?->jawaban_siswa)],
            3 => ['icon' => 'bi-file-earmark-text', 'label' => 'Surat Siswa', 'done' => !empty($wawancara?->surat_pernyataan_siswa)],
            4 => ['icon' => 'bi-file-earmark-person', 'label' => 'Surat Ortu', 'done' => !empty($wawancara?->surat_pernyataan_ortu)],
            5 => ['icon' => 'bi-pencil-square', 'label' => 'Tes Pegon', 'done' => !empty($wawancara?->file_tes_pegon)],
            6 => ['icon' => 'bi-mic', 'label' => 'Bacaan Quran', 'done' => !empty($wawancara?->file_voice_quran)],
        ];
    @endphp

    {{-- Banner Download Surat Pernyataan (muncul jika sudah diisi) --}}
    @if(!empty($wawancara?->surat_pernyataan_siswa) && !empty($wawancara?->surat_pernyataan_ortu))
    <div class="card border-0 shadow-sm mb-4 border-start border-4 border-success">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center">
                    <i class="bi bi-file-earmark-check fs-3 text-success me-3"></i>
                    <div>
                        <h6 class="mb-0 fw-bold text-success">Surat Pernyataan Sudah Lengkap ✓</h6>
                        <small class="text-muted">Surat pernyataan siswa dan orangtua sudah disetujui & ditandatangani</small>
                    </div>
                </div>
                <div>
                    <a href="{{ route('peserta.wawancara.surat-pernyataan.pdf') }}" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center overflow-auto" style="min-width:600px">
                @foreach($steps as $num => $s)
                <div class="text-center flex-fill cursor-pointer step-indicator" data-step="{{ $num }}" onclick="goToStep({{ $num }})">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-1
                        {{ $s['done'] ? 'bg-success text-white' : 'bg-light text-muted border' }}"
                        style="width:40px;height:40px" id="stepIcon{{ $num }}">
                        @if($s['done'])
                            <i class="bi bi-check-lg"></i>
                        @else
                            <i class="bi {{ $s['icon'] }}"></i>
                        @endif
                    </div>
                    <small class="{{ $s['done'] ? 'text-success fw-semibold' : 'text-muted' }}" style="font-size:0.7rem">{{ $s['label'] }}</small>
                </div>
                @if($num < 6)
                <div class="flex-fill" style="max-width:60px">
                    <hr class="my-0 {{ $s['done'] ? 'border-success' : '' }}" style="border-width:2px">
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- =============================== --}}
    {{-- STEP 1: Form Pertanyaan Orang Tua --}}
    {{-- =============================== --}}
    <div class="step-content" id="step1" style="display:none">
        <form action="{{ route('peserta.wawancara.simpan') }}" method="POST">
            @csrf
            <input type="hidden" name="step" value="1">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Langkah 1: Pertanyaan untuk Orang Tua / Wali</h5>
                </div>
                <div class="card-body">
                    @foreach($pertanyaanOrtu as $no => $pertanyaan)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <span class="badge bg-warning text-dark me-1">{{ $no }}</span> {{ $pertanyaan }}
                        </label>
                        <textarea name="jawaban_ortu[{{ $no }}]" class="form-control" rows="3" placeholder="Tulis jawaban...">{{ $wawancara?->jawaban_ortu[$no] ?? '' }}</textarea>
                    </div>
                    @endforeach
                </div>
                <div class="card-footer bg-white d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i>Simpan & Lanjut <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- =============================== --}}
    {{-- STEP 2: Form Pertanyaan Siswa --}}
    {{-- =============================== --}}
    <div class="step-content" id="step2" style="display:none">
        <form action="{{ route('peserta.wawancara.simpan') }}" method="POST">
            @csrf
            <input type="hidden" name="step" value="2">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Langkah 2: Pertanyaan untuk Calon Siswa</h5>
                </div>
                <div class="card-body">
                    @foreach($pertanyaanSiswa as $no => $pertanyaan)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <span class="badge bg-danger me-1">{{ $no }}</span> {{ $pertanyaan }}
                        </label>
                        <textarea name="jawaban_siswa[{{ $no }}]" class="form-control" rows="3" placeholder="Tulis jawaban...">{{ $wawancara?->jawaban_siswa[$no] ?? '' }}</textarea>
                    </div>
                    @endforeach
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="goToStep(1)">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i>Simpan & Lanjut <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- =============================== --}}
    {{-- STEP 3: Surat Pernyataan Siswa --}}
    {{-- =============================== --}}
    @php $spSiswa = $wawancara?->surat_pernyataan_siswa ?? []; @endphp
    <div class="step-content" id="step3" style="display:none">
        <form action="{{ route('peserta.wawancara.simpan') }}" method="POST">
            @csrf
            <input type="hidden" name="step" value="3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Langkah 3: Surat Pernyataan Siswa/i</h5>
                </div>
                <div class="card-body">
                    <h5 class="text-center fw-bold mb-4 text-decoration-underline">SURAT PERNYATAAN SISWA/I</h5>
                    <p>Yang bertandatangan di bawah ini,</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" name="sp_siswa[nama_lengkap]" class="form-control" value="{{ $spSiswa['nama_lengkap'] ?? $peserta->nama ?? '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tempat, Tanggal Lahir</label>
                            <input type="text" name="sp_siswa[tempat_tgl_lahir]" class="form-control" value="{{ $spSiswa['tempat_tgl_lahir'] ?? '' }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Alamat</label>
                            <input type="text" name="sp_siswa[alamat]" class="form-control" value="{{ $spSiswa['alamat'] ?? '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Orangtua/Wali</label>
                            <input type="text" name="sp_siswa[nama_ortu]" class="form-control" value="{{ $spSiswa['nama_ortu'] ?? '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No.Telp/HP Orang tua/Wali</label>
                            <input type="text" name="sp_siswa[no_telp_ortu]" class="form-control" value="{{ $spSiswa['no_telp_ortu'] ?? '' }}" required>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mb-4" style="font-size:0.92rem">
                        <p>Menyatakan dengan sungguh-sungguh, setelah memahami isi, maksud dan tujuan surat pernyataan ini. Maka selama menjadi siswa/i di SMA Al Furqon Boarding School, sanggup menetapi dan menjalankan hal-hal sebagai berikut:</p>
                        <ol>
                            @foreach($spSiswaPoin as $poin)
                            <li class="mb-2">{!! nl2br(e($poin)) !!}</li>
                            @endforeach
                        </ol>
                        <p class="mb-0">Demikian Pernyataan ini saya buat dengan sebenar-benarnya dengan penuh tanggung jawab dan tidak ada paksaan dari pihak manapun.</p>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="setujuSiswa" required {{ !empty($spSiswa['setuju']) ? 'checked' : '' }}>
                        <input type="hidden" name="sp_siswa[setuju]" value="1">
                        <label class="form-check-label fw-semibold" for="setujuSiswa">
                            Saya menyatakan setuju dengan semua pernyataan di atas
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mx-auto text-center">
                            <label class="form-label fw-semibold">Tanda Tangan Siswa</label>
                            @if($wawancara?->tanda_tangan_peserta)
                            <div class="mb-2 p-2 bg-success bg-opacity-10 border border-success rounded">
                                <small class="text-success fw-bold d-block mb-1"><i class="bi bi-check-circle me-1"></i>TTD Tersimpan:</small>
                                <img src="{{ $wawancara->tanda_tangan_peserta }}" alt="TTD Siswa" class="img-fluid border rounded" style="max-height:120px;background:#fff">
                            </div>
                            @endif
                            <canvas id="sigSiswaStep3" class="border rounded d-block mx-auto" width="400" height="180" style="width:100%;max-width:400px;cursor:crosshair;touch-action:none;background:#fff"></canvas>
                            <input type="hidden" name="tanda_tangan_peserta" id="sigSiswaStep3Data" value="{{ $wawancara?->tanda_tangan_peserta ?? '' }}">
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="clearSig('sigSiswaStep3','sigSiswaStep3Data')">
                                <i class="bi bi-eraser me-1"></i>Hapus & Tanda Tangan Ulang
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="goToStep(2)">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i>Simpan & Lanjut <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- =============================== --}}
    {{-- STEP 4: Surat Pernyataan Orangtua --}}
    {{-- =============================== --}}
    @php $spOrtu = $wawancara?->surat_pernyataan_ortu ?? []; @endphp
    <div class="step-content" id="step4" style="display:none">
        <form action="{{ route('peserta.wawancara.simpan') }}" method="POST">
            @csrf
            <input type="hidden" name="step" value="4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-person me-2"></i>Langkah 4: Surat Pernyataan Orangtua</h5>
                </div>
                <div class="card-body">
                    <h5 class="text-center fw-bold mb-4 text-decoration-underline">SURAT PERNYATAAN ORANGTUA</h5>
                    <p>Saya yang bertanda tangan di bawah ini:</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" name="sp_ortu[nama_lengkap]" class="form-control" value="{{ $spOrtu['nama_lengkap'] ?? '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Alamat</label>
                            <input type="text" name="sp_ortu[alamat]" class="form-control" value="{{ $spOrtu['alamat'] ?? '' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kelompok</label>
                            <input type="text" name="sp_ortu[kelompok]" class="form-control" value="{{ $spOrtu['kelompok'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama KI Kelompok + No. HP</label>
                            <input type="text" name="sp_ortu[nama_ki]" class="form-control" value="{{ $spOrtu['nama_ki'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Desa</label>
                            <input type="text" name="sp_ortu[desa]" class="form-control" value="{{ $spOrtu['desa'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Daerah</label>
                            <input type="text" name="sp_ortu[daerah]" class="form-control" value="{{ $spOrtu['daerah'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">No. HP Orang tua/Wali</label>
                            <input type="text" name="sp_ortu[no_hp]" class="form-control" value="{{ $spOrtu['no_hp'] ?? '' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama Siswa</label>
                            <input type="text" name="sp_ortu[nama_siswa]" class="form-control" value="{{ $spOrtu['nama_siswa'] ?? $peserta->nama ?? '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Asal Sekolah</label>
                            <input type="text" name="sp_ortu[asal_sekolah]" class="form-control" value="{{ $spOrtu['asal_sekolah'] ?? '' }}">
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mb-4" style="font-size:0.92rem">
                        <p>Saya dengan ini menyatakan menyetujui peraturan SPMB SMA AFBS yang telah ditetapkan yaitu:</p>
                        <ol>
                            @foreach($spOrtuPoin as $poin)
                            <li class="mb-2">{!! nl2br(e($poin)) !!}</li>
                            @endforeach
                        </ol>
                        <p class="mb-0">Demikian Pernyataan ini saya buat dengan sebenar-benarnya dengan penuh tanggung jawab dan tidak ada paksaan dari pihak manapun.</p>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="setujuOrtu" required {{ !empty($spOrtu['setuju']) ? 'checked' : '' }}>
                        <input type="hidden" name="sp_ortu[setuju]" value="1">
                        <label class="form-check-label fw-semibold" for="setujuOrtu">
                            Saya menyatakan setuju dengan semua pernyataan di atas
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mx-auto text-center">
                            <label class="form-label fw-semibold">Tanda Tangan Orangtua/Wali</label>
                            @if($wawancara?->tanda_tangan_ortu)
                            <div class="mb-2 p-2 bg-success bg-opacity-10 border border-success rounded">
                                <small class="text-success fw-bold d-block mb-1"><i class="bi bi-check-circle me-1"></i>TTD Tersimpan:</small>
                                <img src="{{ $wawancara->tanda_tangan_ortu }}" alt="TTD Orangtua" class="img-fluid border rounded" style="max-height:120px;background:#fff">
                            </div>
                            @endif
                            <canvas id="sigOrtuStep4" class="border rounded d-block mx-auto" width="400" height="180" style="width:100%;max-width:400px;cursor:crosshair;touch-action:none;background:#fff"></canvas>
                            <input type="hidden" name="tanda_tangan_ortu" id="sigOrtuStep4Data" value="{{ $wawancara?->tanda_tangan_ortu ?? '' }}">
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="clearSig('sigOrtuStep4','sigOrtuStep4Data')">
                                <i class="bi bi-eraser me-1"></i>Hapus & Tanda Tangan Ulang
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="goToStep(3)">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i>Simpan & Lanjut <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- =============================== --}}
    {{-- STEP 5: Tes Pegon --}}
    {{-- =============================== --}}
    <div class="step-content" id="step5" style="display:none">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Langkah 5: Tes Pegon</h5>
            </div>
            <div class="card-body">
                {{-- Instruksi --}}
                <div class="alert alert-info">
                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i>Instruksi:</h6>
                    <ol class="mb-0">
                        <li><strong>Download</strong> soal Tes Pegon di bawah ini (format A4 siap cetak)</li>
                        <li><strong>Cetak / print</strong> soal tersebut</li>
                        <li><strong>Kerjakan</strong> soal dengan menulis jawaban di kolom yang tersedia</li>
                        <li><strong>Foto / scan</strong> hasil jawaban Anda</li>
                        <li><strong>Upload</strong> foto jawaban di kolom di bawah</li>
                    </ol>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="border rounded p-4 text-center h-100">
                            <i class="bi bi-file-earmark-arrow-down fs-1 text-primary d-block mb-2"></i>
                            <h6 class="fw-bold">Download Soal</h6>
                            <p class="text-muted small">Soal Tes Pegon dalam format A4</p>
                            <a href="{{ route('peserta.wawancara.download-pegon') }}" class="btn btn-primary" target="_blank">
                                <i class="bi bi-download me-1"></i>Download Soal Pegon
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-4 text-center h-100">
                            <i class="bi bi-cloud-arrow-up fs-1 text-success d-block mb-2"></i>
                            <h6 class="fw-bold">Upload Jawaban</h6>
                            <p class="text-muted small">Upload foto/scan jawaban Anda (JPG/PNG/PDF, maks 5MB)</p>
                            <form action="{{ route('peserta.wawancara.simpan') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="step" value="5">
                                <div class="mb-2">
                                    <input type="file" name="file_tes_pegon" class="form-control form-control-sm" accept="image/*,.pdf" required>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-upload me-1"></i>Upload Jawaban
                                </button>
                            </form>

                            @if($wawancara?->file_tes_pegon)
                            <div class="mt-3 p-2 bg-success bg-opacity-10 rounded">
                                <small class="text-success"><i class="bi bi-check-circle me-1"></i>File sudah diupload</small>
                                <br>
                                <a href="{{ asset('storage/' . $wawancara->file_tes_pegon) }}" target="_blank" class="btn btn-sm btn-outline-success mt-1">
                                    <i class="bi bi-eye me-1"></i>Lihat File
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" onclick="goToStep(4)">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </button>
                <button type="button" class="btn btn-primary" onclick="goToStep(6)">
                    Lanjut <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- =============================== --}}
    {{-- STEP 6: Tes Bacaan Quran --}}
    {{-- =============================== --}}
    @php
        $daftarSurat = [
            'An-Naba (78)' => 'Surat An-Naba (78)',
            'An-Naziat (79)' => 'Surat An-Naziat (79)',
            'Abasa (80)' => 'Surat Abasa (80)',
            'At-Takwir (81)' => 'Surat At-Takwir (81)',
            'Al-Infitar (82)' => 'Surat Al-Infitar (82)',
            'Al-Mutaffifin (83)' => 'Surat Al-Mutaffifin (83)',
            'Al-Insyiqaq (84)' => 'Surat Al-Insyiqaq (84)',
            'Al-Buruj (85)' => 'Surat Al-Buruj (85)',
            'At-Tariq (86)' => 'Surat At-Tariq (86)',
            'Al-Ala (87)' => 'Surat Al-A\'la (87)',
            'Al-Ghasyiyah (88)' => 'Surat Al-Ghasyiyah (88)',
            'Al-Fajr (89)' => 'Surat Al-Fajr (89)',
            'Al-Balad (90)' => 'Surat Al-Balad (90)',
            'Asy-Syams (91)' => 'Surat Asy-Syams (91)',
            'Al-Lail (92)' => 'Surat Al-Lail (92)',
            'Ad-Duha (93)' => 'Surat Ad-Duha (93)',
            'Al-Insyirah (94)' => 'Surat Al-Insyirah (94)',
            'At-Tin (95)' => 'Surat At-Tin (95)',
            'Al-Alaq (96)' => 'Surat Al-Alaq (96)',
            'Al-Qadr (97)' => 'Surat Al-Qadr (97)',
            'Al-Bayyinah (98)' => 'Surat Al-Bayyinah (98)',
            'Az-Zalzalah (99)' => 'Surat Az-Zalzalah (99)',
        ];
        $suratTerpilih = $wawancara?->surat_quran_random;
        if (!$suratTerpilih) {
            $keys = array_keys($daftarSurat);
            $suratTerpilih = $keys[array_rand($keys)];
        }
    @endphp
    <div class="step-content" id="step6" style="display:none">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-mic me-2"></i>Langkah 6: Tes Bacaan Al-Quran</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i>Instruksi:</h6>
                    <ol class="mb-0">
                        <li>Surat yang harus Anda baca ditampilkan di bawah (<strong>acak</strong> dari Juz 30, surat 78-99)</li>
                        <li>Klik tombol <strong>"Mulai Merekam"</strong> untuk merekam suara Anda</li>
                        <li>Baca surat tersebut dengan tartil dan jelas</li>
                        <li>Klik <strong>"Selesai"</strong> untuk menghentikan rekaman</li>
                        <li>Dengarkan ulang rekaman, lalu klik <strong>"Kirim Rekaman"</strong></li>
                    </ol>
                </div>

                <div class="text-center mb-4">
                    <div class="bg-success bg-opacity-10 p-4 rounded-3 d-inline-block">
                        <small class="text-muted d-block mb-1">Surat yang harus dibaca:</small>
                        <h3 class="fw-bold text-success mb-0" id="suratLabel">{{ $daftarSurat[$suratTerpilih] ?? $suratTerpilih }}</h3>
                    </div>
                </div>

                {{-- Voice Recorder --}}
                <div class="text-center mb-4" id="recorderArea">
                    <div class="mb-3">
                        <button type="button" id="btnRecord" class="btn btn-danger btn-lg rounded-circle" onclick="toggleRecording()" style="width:80px;height:80px">
                            <i class="bi bi-mic-fill fs-3" id="micIcon"></i>
                        </button>
                        <div class="mt-2">
                            <span id="recordStatus" class="badge bg-secondary">Siap Merekam</span>
                            <span id="recordTimer" class="ms-2 fw-mono text-muted" style="display:none">00:00</span>
                        </div>
                    </div>

                    {{-- Playback --}}
                    <div id="playbackArea" style="display:none" class="mb-3">
                        <audio id="audioPlayback" controls class="mb-2" style="width:100%;max-width:400px"></audio>
                        <div>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="resetRecording()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Rekam Ulang
                            </button>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <form action="{{ route('peserta.wawancara.simpan') }}" method="POST" enctype="multipart/form-data" id="formVoice">
                        @csrf
                        <input type="hidden" name="step" value="6">
                        <input type="hidden" name="surat_quran_random" value="{{ $suratTerpilih }}">
                        <input type="file" name="file_voice_quran" id="voiceFileInput" style="display:none" accept="audio/*">
                        <button type="submit" id="btnSubmitVoice" class="btn btn-success btn-lg" style="display:none">
                            <i class="bi bi-send me-1"></i>Kirim Rekaman
                        </button>
                    </form>

                    @if($wawancara?->file_voice_quran)
                    <div class="mt-3 p-3 bg-success bg-opacity-10 rounded">
                        <small class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>Rekaman sudah dikirim</small>
                        <div class="mt-2">
                            <audio controls class="w-100" style="max-width:400px">
                                <source src="{{ asset('storage/' . $wawancara->file_voice_quran) }}">
                            </audio>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" onclick="goToStep(5)">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </button>
                <a href="{{ route('peserta.dashboard') }}" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ========================
// STEP NAVIGATION
// ========================
let currentStep = 1;

function goToStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.style.display = 'none');
    const target = document.getElementById('step' + step);
    if (target) {
        target.style.display = 'block';
        currentStep = step;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        // Init sig pads when visible
        if (step === 3) initSigPad('sigSiswaStep3', 'sigSiswaStep3Data');
        if (step === 4) initSigPad('sigOrtuStep4', 'sigOrtuStep4Data');
    }
}

// Auto-show first incomplete step
document.addEventListener('DOMContentLoaded', function() {
    @php
        $autoStep = 1;
        if (!empty($wawancara?->jawaban_ortu)) $autoStep = 2;
        if (!empty($wawancara?->jawaban_siswa)) $autoStep = 3;
        if (!empty($wawancara?->surat_pernyataan_siswa)) $autoStep = 4;
        if (!empty($wawancara?->surat_pernyataan_ortu)) $autoStep = 5;
        if (!empty($wawancara?->file_tes_pegon)) $autoStep = 6;
        if (!empty($wawancara?->file_voice_quran)) $autoStep = 6;
    @endphp
    goToStep({{ $autoStep }});
});

// ========================
// SIGNATURE PAD
// ========================
const sigPadsInit = {};
function initSigPad(canvasId, hiddenId) {
    if (sigPadsInit[canvasId]) return;
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    sigPadsInit[canvasId] = true;
    const ctx = canvas.getContext('2d');
    let drawing = false, lastX = 0, lastY = 0;

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const src = e.touches ? e.touches[0] : e;
        return { x: (src.clientX - rect.left) * scaleX, y: (src.clientY - rect.top) * scaleY };
    }
    function start(e) { e.preventDefault(); drawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; }
    function draw(e) { if (!drawing) return; e.preventDefault(); const p = getPos(e); ctx.beginPath(); ctx.moveTo(lastX,lastY); ctx.lineTo(p.x,p.y); ctx.strokeStyle='#222'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.stroke(); lastX=p.x; lastY=p.y; }
    function end() { drawing = false; document.getElementById(hiddenId).value = canvas.toDataURL('image/png'); }
    canvas.addEventListener('mousedown', start); canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', end); canvas.addEventListener('mouseleave', end);
    canvas.addEventListener('touchstart', start); canvas.addEventListener('touchmove', draw); canvas.addEventListener('touchend', end);
}

function clearSig(canvasId, hiddenId) {
    const canvas = document.getElementById(canvasId);
    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById(hiddenId).value = '';
}

// ========================
// VOICE RECORDER
// ========================
let mediaRecorder = null;
let audioChunks = [];
let recordingTimer = null;
let recordingSeconds = 0;
let isRecording = false;

function toggleRecording() {
    if (isRecording) {
        stopRecording();
    } else {
        startRecording();
    }
}

async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
        mediaRecorder.onstop = () => {
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            const url = URL.createObjectURL(blob);
            document.getElementById('audioPlayback').src = url;
            document.getElementById('playbackArea').style.display = 'block';
            document.getElementById('btnSubmitVoice').style.display = 'inline-block';
            // Create file for upload
            const file = new File([blob], 'bacaan-quran.webm', { type: 'audio/webm' });
            const dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('voiceFileInput').files = dt.files;
            stream.getTracks().forEach(t => t.stop());
        };

        mediaRecorder.start();
        isRecording = true;
        document.getElementById('btnRecord').classList.add('btn-dark');
        document.getElementById('btnRecord').classList.remove('btn-danger');
        document.getElementById('micIcon').className = 'bi bi-stop-fill fs-3';
        document.getElementById('recordStatus').textContent = 'Merekam...';
        document.getElementById('recordStatus').className = 'badge bg-danger';
        document.getElementById('recordTimer').style.display = 'inline';
        recordingSeconds = 0;
        recordingTimer = setInterval(() => {
            recordingSeconds++;
            const m = String(Math.floor(recordingSeconds / 60)).padStart(2, '0');
            const s = String(recordingSeconds % 60).padStart(2, '0');
            document.getElementById('recordTimer').textContent = m + ':' + s;
        }, 1000);
    } catch (err) {
        alert('Tidak dapat mengakses mikrofon. Pastikan Anda memberikan izin akses mikrofon.');
    }
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
    isRecording = false;
    clearInterval(recordingTimer);
    document.getElementById('btnRecord').classList.remove('btn-dark');
    document.getElementById('btnRecord').classList.add('btn-danger');
    document.getElementById('micIcon').className = 'bi bi-mic-fill fs-3';
    document.getElementById('recordStatus').textContent = 'Rekaman Selesai';
    document.getElementById('recordStatus').className = 'badge bg-success';
}

function resetRecording() {
    document.getElementById('playbackArea').style.display = 'none';
    document.getElementById('btnSubmitVoice').style.display = 'none';
    document.getElementById('recordStatus').textContent = 'Siap Merekam';
    document.getElementById('recordStatus').className = 'badge bg-secondary';
    document.getElementById('recordTimer').style.display = 'none';
    audioChunks = [];
}
</script>
@endpush
@endsection
