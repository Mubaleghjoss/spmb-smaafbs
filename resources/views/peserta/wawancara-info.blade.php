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

    {{-- ===== PENJELASAN ALUR TAHAP 5 ===== --}}
    @php
        $wawancaraSelesaiSemua = !empty($wawancara?->jawaban_ortu)
            && !empty($wawancara?->jawaban_siswa)
            && !empty($wawancara?->surat_pernyataan_siswa)
            && !empty($wawancara?->surat_pernyataan_ortu)
            && !empty($wawancara?->file_tes_pegon)
            && !empty($wawancara?->file_voice_quran);
        $hasilWawancara = $wawancara?->hasil_wawancara ?? 'menunggu';
        $tahap5Selesai = $peserta->tahapanSpmb?->tahap_5_selesai ?? false;
    @endphp

    <div class="card border-0 shadow-sm mb-4 border-start border-4 {{ $tahap5Selesai ? 'border-success' : ($wawancaraSelesaiSemua ? 'border-warning' : 'border-info') }}">
        <div class="card-body">
            <h6 class="fw-bold mb-2">
                <i class="bi bi-info-circle me-1"></i>Cara Tahap 5 dinyatakan selesai
            </h6>
            <p class="small mb-2">
                Data yang Anda isi di 6 langkah ini adalah <strong>bahan rujukan Tim SPMB saat wawancara tatap muka</strong>.
                Karena itu Tahap 5 baru ditandai selesai <strong>setelah wawancara dilaksanakan dan Tim SPMB meluluskan Anda</strong> —
                bukan otomatis begitu 6 langkah terisi.
            </p>
            <ol class="small mb-2 ps-3">
                <li>Anda melengkapi 6 langkah di bawah <span class="badge {{ $wawancaraSelesaiSemua ? 'bg-success' : 'bg-secondary' }}">{{ $wawancaraSelesaiSemua ? 'Selesai' : 'Sedang berjalan' }}</span></li>
                <li>Tim SPMB melakukan wawancara (tatap muka/online) memakai data Anda sebagai rujukan</li>
                <li>Tim SPMB menyatakan hasil wawancara
                    <span class="badge {{ $hasilWawancara === 'lulus' ? 'bg-success' : ($hasilWawancara === 'tidak_lulus' ? 'bg-danger' : 'bg-warning text-dark') }}">
                        {{ $hasilWawancara === 'lulus' ? 'Lulus' : ($hasilWawancara === 'tidak_lulus' ? 'Tidak Lulus' : 'Menunggu wawancara') }}
                    </span>
                </li>
                <li>Setelah dinyatakan lulus, <strong>Tahap 6 (Upload Bukti Pembayaran Pertama) otomatis terbuka</strong></li>
            </ol>

            @if($wawancaraSelesaiSemua && !$tahap5Selesai)
                <div class="alert alert-success py-2 px-3 mb-0 small">
                    <i class="bi bi-check2-all me-1"></i>
                    <strong>Semua 6 langkah sudah lengkap — tidak ada lagi yang perlu Anda kerjakan di sini.</strong>
                    Silakan tunggu jadwal wawancara dari Tim SPMB. Tahap 6 akan terbuka setelah Tim SPMB menyatakan Anda lulus wawancara.
                    @php
                        $timListW = collect($kontakTimSpmb ?? [])->filter(fn($k) => !empty($k['whatsapp']))->values();
                        $pesanW = "Assalamu'alaikum, saya *".($peserta->nama)."* nomor pendaftaran *".($peserta->nomor_pendaftaran)."*. Saya sudah melengkapi semua data Tahap 5 (Wawancara). Mohon informasi jadwal wawancara. Terima kasih.";
                    @endphp
                    @if($timListW->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach($timListW as $tim)
                            @php
                                $d = preg_replace('/[^0-9]/', '', $tim['whatsapp'] ?? '');
                                if (str_starts_with($d, '62')) { $d = substr($d, 2); }
                                $d = ltrim($d, '0');
                            @endphp
                            <a href="https://wa.me/62{{ $d }}?text={{ urlencode($pesanW) }}" target="_blank" rel="noopener" class="btn btn-sm btn-success">
                                <i class="bi bi-whatsapp me-1"></i>Tanya Jadwal ke {{ $tim['nama'] ?? 'Tim SPMB' }}
                            </a>
                        @endforeach
                    </div>
                    @endif
                </div>
            @elseif($tahap5Selesai)
                <div class="alert alert-success py-2 px-3 mb-0 small">
                    <i class="bi bi-check-circle me-1"></i>
                    <strong>Tahap 5 selesai.</strong> Anda sudah dinyatakan lulus wawancara dan dapat melanjutkan ke Tahap 6.
                </div>
            @endif
        </div>
    </div>

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
            {{-- Pembungkus yang men-scroll; isi di dalamnya yang diberi lebar minimum.
                 (Sebelumnya overflow-auto & min-width dipasang di elemen yang SAMA,
                 sehingga tidak ada yang bisa di-scroll dan langkah 4-6 terpotong.) --}}
            <div class="stepper-scroll">
                <div class="stepper-track d-flex align-items-center">
                    @foreach($steps as $num => $s)
                    <div class="text-center cursor-pointer step-indicator" data-step="{{ $num }}" onclick="goToStep({{ $num }})">
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
                    <div class="stepper-line">
                        <hr class="my-0 {{ $s['done'] ? 'border-success' : '' }}" style="border-width:2px">
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            <div class="text-center d-lg-none mt-1">
                <small class="text-muted" style="font-size:.7rem">
                    <i class="bi bi-arrow-left-right me-1"></i>Geser ke samping untuk melihat 6 langkah
                </small>
            </div>
        </div>
    </div>

    <style>
        /* Stepper 6 langkah: bisa digeser ke samping di layar sempit */
        .stepper-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding-bottom: .25rem;
        }
        .stepper-scroll::-webkit-scrollbar { height: 5px; }
        .stepper-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,.2); border-radius: 3px; }
        /* JANGAN membungkus — harus tetap satu baris agar scroll berfungsi */
        .stepper-track {
            flex-wrap: nowrap !important;
            min-width: 620px;
            justify-content: space-between;
        }
        .stepper-track .step-indicator { flex: 0 0 auto; width: 72px; }
        .stepper-track .stepper-line { flex: 1 1 auto; min-width: 24px; max-width: 60px; }
        .step-indicator.step-active small { text-decoration: underline; }
    </style>

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

                    @include('peserta.partials.info-data-formulir', ['formulir' => $formulir ?? null])

                    <p>Yang bertandatangan di bawah ini,</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" name="sp_siswa[nama_lengkap]" class="form-control bg-light" value="{{ ($spSiswa['nama_lengkap'] ?? '') ?: ($prefillWawancara['siswa']['nama_lengkap'] ?? '') }}" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tempat, Tanggal Lahir</label>
                            <input type="text" name="sp_siswa[tempat_tgl_lahir]" class="form-control bg-light" value="{{ ($spSiswa['tempat_tgl_lahir'] ?? '') ?: ($prefillWawancara['siswa']['tempat_tgl_lahir'] ?? '') }}" readonly required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Alamat</label>
                            <input type="text" name="sp_siswa[alamat]" class="form-control bg-light" value="{{ ($spSiswa['alamat'] ?? '') ?: ($prefillWawancara['siswa']['alamat'] ?? '') }}" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Orangtua/Wali</label>
                            <input type="text" name="sp_siswa[nama_ortu]" class="form-control bg-light" value="{{ ($spSiswa['nama_ortu'] ?? '') ?: ($prefillWawancara['siswa']['nama_ortu'] ?? '') }}" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No.Telp/HP Orang tua/Wali</label>
                            <input type="text" name="sp_siswa[no_telp_ortu]" class="form-control bg-light" value="{{ ($spSiswa['no_telp_ortu'] ?? '') ?: ($prefillWawancara['siswa']['no_telp_ortu'] ?? '') }}" readonly required>
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

                    @include('peserta.partials.info-data-formulir', ['formulir' => $formulir ?? null])

                    <p>Saya yang bertanda tangan di bawah ini:</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" name="sp_ortu[nama_lengkap]" class="form-control bg-light" value="{{ ($spOrtu['nama_lengkap'] ?? '') ?: ($prefillWawancara['ortu']['nama_lengkap'] ?? '') }}" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Alamat</label>
                            <input type="text" name="sp_ortu[alamat]" class="form-control bg-light" value="{{ ($spOrtu['alamat'] ?? '') ?: ($prefillWawancara['ortu']['alamat'] ?? '') }}" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kelompok</label>
                            <input type="text" name="sp_ortu[kelompok]" class="form-control bg-light" value="{{ ($spOrtu['kelompok'] ?? '') ?: ($prefillWawancara['ortu']['kelompok'] ?? '') }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama KI Kelompok + No. HP</label>
                            <input type="text" name="sp_ortu[nama_ki]" class="form-control" value="{{ $spOrtu['nama_ki'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Desa</label>
                            <input type="text" name="sp_ortu[desa]" class="form-control bg-light" value="{{ ($spOrtu['desa'] ?? '') ?: ($prefillWawancara['ortu']['desa'] ?? '') }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Daerah</label>
                            <input type="text" name="sp_ortu[daerah]" class="form-control bg-light" value="{{ ($spOrtu['daerah'] ?? '') ?: ($prefillWawancara['ortu']['daerah'] ?? '') }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">No. HP Orang tua/Wali</label>
                            <input type="text" name="sp_ortu[no_hp]" class="form-control bg-light" value="{{ ($spOrtu['no_hp'] ?? '') ?: ($prefillWawancara['ortu']['no_hp'] ?? '') }}" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama Siswa</label>
                            <input type="text" name="sp_ortu[nama_siswa]" class="form-control bg-light" value="{{ ($spOrtu['nama_siswa'] ?? '') ?: ($prefillWawancara['ortu']['nama_siswa'] ?? '') }}" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Asal Sekolah</label>
                            <input type="text" name="sp_ortu[asal_sekolah]" class="form-control bg-light" value="{{ ($spOrtu['asal_sekolah'] ?? '') ?: ($prefillWawancara['ortu']['asal_sekolah'] ?? '') }}" readonly>
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
                @php $pegonSudah = !empty($wawancara?->file_tes_pegon); @endphp

                {{-- Status jelas: sudah terkirim atau belum --}}
                @if($pegonSudah)
                    <div class="alert alert-success d-flex flex-wrap align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div class="flex-grow-1">
                            <strong>Jawaban Tes Pegon sudah terkirim.</strong>
                            <div class="small">Langkah ini sudah hijau. Anda masih boleh mengganti file bila ingin memperbaiki jawaban.</div>
                        </div>
                        <a href="{{ asset('storage/' . $wawancara->file_tes_pegon) }}" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-eye me-1"></i>Lihat File Terkirim
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <strong>Belum ada jawaban yang dikirim.</strong>
                            <div class="small">Langkah ini menjadi hijau setelah Anda mengunggah foto/scan jawaban di bawah.</div>
                        </div>
                    </div>
                @endif

                {{-- Instruksi bertahap --}}
                <div class="alert alert-info">
                    <h6 class="fw-bold mb-2"><i class="bi bi-list-ol me-1"></i>Langkah Pengerjaan:</h6>
                    <ol class="mb-0 ps-3">
                        <li><strong>Unduh soal</strong> — tekan tombol <em>Download PDF Soal</em> di bawah. File PDF langsung tersimpan di HP Anda.</li>
                        <li><strong>Cetak</strong> soal tersebut (atau tulis jawaban di kertas biasa bila tidak ada printer).</li>
                        <li><strong>Kerjakan</strong> — ubah kalimat menjadi tulisan Pegon pada kolom jawaban.</li>
                        <li><strong>Foto hasilnya</strong> — tekan <em>Ambil Foto</em> (kamera HP akan terbuka, bisa ganti kamera depan/belakang), atau <em>Pilih File</em> bila sudah ada foto/scan-nya.</li>
                        <li><strong>Kirim</strong> — tekan tombol <em>Kirim Jawaban</em>. Pastikan tulisan terbaca jelas dan tidak terpotong.</li>
                    </ol>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="border rounded p-4 text-center h-100">
                            <i class="bi bi-file-earmark-pdf fs-1 text-danger d-block mb-2"></i>
                            <h6 class="fw-bold">1. Unduh Soal</h6>
                            <p class="text-muted small mb-3">Soal Tes Pegon format A4, siap dicetak.</p>
                            <a href="{{ route('peserta.wawancara.download-pegon.pdf') }}" class="btn btn-danger w-100 mb-2">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i>Download PDF Soal
                            </a>
                            <a href="{{ route('peserta.wawancara.download-pegon') }}" class="btn btn-outline-secondary btn-sm w-100" target="_blank">
                                <i class="bi bi-eye me-1"></i>Lihat dulu di layar
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-4 h-100">
                            <div class="text-center">
                                <i class="bi bi-camera fs-1 text-success d-block mb-2"></i>
                                <h6 class="fw-bold">2. {{ $pegonSudah ? 'Ganti Jawaban' : 'Kirim Jawaban' }}</h6>
                                <p class="text-muted small">Foto/scan jawaban — JPG, PNG, atau PDF (maks 5MB).</p>
                            </div>

                            <form action="{{ route('peserta.wawancara.simpan') }}" method="POST" enctype="multipart/form-data" id="formPegon">
                                @csrf
                                <input type="hidden" name="step" value="5">

                                {{-- Dua cara memilih berkas: kamera langsung atau file tersimpan --}}
                                <input type="file" name="file_tes_pegon" id="pegonKamera"
                                       accept="image/*" capture="environment" class="d-none">
                                <input type="file" name="file_tes_pegon" id="pegonFile"
                                       accept="image/*,application/pdf" class="d-none">

                                <div class="d-grid gap-2 mb-2">
                                    <button type="button" class="btn btn-success" onclick="pilihSumberPegon('kamera')">
                                        <i class="bi bi-camera-fill me-1"></i>Ambil Foto (Kamera)
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="pilihSumberPegon('file')">
                                        <i class="bi bi-folder2-open me-1"></i>Pilih File / Galeri
                                    </button>
                                </div>

                                <div class="form-text mb-2">
                                    <i class="bi bi-arrow-repeat me-1"></i>Kamera terbuka pada kamera belakang. Untuk berganti ke kamera depan, gunakan tombol putar kamera di aplikasi kamera HP Anda.
                                </div>

                                {{-- Pratinjau sebelum dikirim --}}
                                <div id="pegonPreviewWrap" class="d-none text-center mb-2">
                                    <div class="border rounded p-2 bg-light">
                                        <img id="pegonPreview" alt="Pratinjau jawaban" class="img-fluid rounded" style="max-height:180px">
                                        <div id="pegonNamaFile" class="small text-muted mt-1"></div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100" id="btnKirimPegon" disabled>
                                    <i class="bi bi-send me-1"></i>{{ $pegonSudah ? 'Kirim Jawaban Baru (Ganti)' : 'Kirim Jawaban' }}
                                </button>
                            </form>
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
        sorotStep(step);
    }
}

// Tandai langkah aktif dan geser stepper agar langkah itu terlihat
// (penting untuk langkah 4-6 yang berada di luar layar HP).
function sorotStep(step) {
    document.querySelectorAll('.step-indicator').forEach(function (el) {
        el.classList.toggle('step-active', Number(el.dataset.step) === Number(step));
    });
    const aktif = document.querySelector('.step-indicator[data-step="' + step + '"]');
    const wrap = document.querySelector('.stepper-scroll');
    if (aktif && wrap) {
        const geser = aktif.offsetLeft - (wrap.clientWidth / 2) + (aktif.offsetWidth / 2);
        wrap.scrollTo({ left: Math.max(0, geser), behavior: 'smooth' });
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
// UPLOAD JAWABAN TES PEGON (kamera / file)
// ========================
// Dua input file dipakai agar tombol "Ambil Foto" langsung membuka kamera
// (atribut capture) sementara "Pilih File" membuka galeri/berkas. Input yang
// tidak terpakai DIMATIKAN supaya tidak ikut terkirim sebagai nilai kosong.
function pilihSumberPegon(sumber) {
    const kamera = document.getElementById('pegonKamera');
    const berkas = document.getElementById('pegonFile');
    if (!kamera || !berkas) return;
    (sumber === 'kamera' ? kamera : berkas).click();
}

document.addEventListener('DOMContentLoaded', function () {
    const kamera = document.getElementById('pegonKamera');
    const berkas = document.getElementById('pegonFile');
    const tombol = document.getElementById('btnKirimPegon');
    const wrap = document.getElementById('pegonPreviewWrap');
    const img = document.getElementById('pegonPreview');
    const nama = document.getElementById('pegonNamaFile');
    if (!kamera || !berkas || !tombol) return;

    function terapkan(dipilih, lainnya) {
        const file = dipilih.files && dipilih.files[0];
        if (!file) return;

        // Hanya satu input yang aktif saat submit
        lainnya.value = '';
        lainnya.disabled = true;
        dipilih.disabled = false;

        tombol.disabled = false;
        if (nama) nama.textContent = file.name + ' (' + (file.size / 1048576).toFixed(2) + ' MB)';

        if (wrap && img) {
            if (file.type && file.type.startsWith('image/')) {
                img.src = URL.createObjectURL(file);
                img.classList.remove('d-none');
            } else {
                img.classList.add('d-none'); // PDF: tampilkan nama saja
            }
            wrap.classList.remove('d-none');
        }
    }

    kamera.addEventListener('change', () => terapkan(kamera, berkas));
    berkas.addEventListener('change', () => terapkan(berkas, kamera));
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
