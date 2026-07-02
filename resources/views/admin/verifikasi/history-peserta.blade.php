@extends('layouts.admin')

@section('title', 'History Peserta - ' . $peserta->nama)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-clock-history me-2"></i>History & Data Lengkap Peserta</h4>
            <p class="text-muted mb-0">{{ $peserta->nama }} - {{ $peserta->nomor_pendaftaran }}</p>
        </div>
        <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
    
    {{-- Progress Tahapan --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Progress Tahapan SPMB</h6>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                @php
                    $tahapSaatIni = $peserta->tahapanSpmb?->tahap_saat_ini ?? 1;
                    $tahapanLabel = [
                        1 => ['label' => 'Daftar', 'icon' => 'bi-person-plus'],
                        2 => ['label' => 'Formulir', 'icon' => 'bi-file-earmark-text'],
                        3 => ['label' => 'Bayar Formulir', 'icon' => 'bi-credit-card'],
                        4 => ['label' => 'Tes Online', 'icon' => 'bi-journal-check'],
                        5 => ['label' => 'Wawancara', 'icon' => 'bi-people'],
                        6 => ['label' => 'Pelunasan', 'icon' => 'bi-cash-stack'],
                        7 => ['label' => 'Kelulusan', 'icon' => 'bi-award'],
                    ];
                @endphp
                @for($t = 1; $t <= 7; $t++)
                    @php
                        $selesai = $peserta->tahapanSpmb?->{"tahap_{$t}_selesai"} ?? false;
                        $aktif = $tahapSaatIni == $t;
                    @endphp
                    <div class="text-center flex-fill">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2
                            {{ $selesai ? 'bg-success text-white' : ($aktif ? 'bg-warning text-dark' : 'bg-light text-muted') }}"
                            style="width: 50px; height: 50px;">
                            <i class="bi {{ $tahapanLabel[$t]['icon'] }} fs-5"></i>
                        </div>
                        <small class="{{ $selesai ? 'text-success fw-bold' : ($aktif ? 'text-warning fw-bold' : 'text-muted') }}">
                            {{ $tahapanLabel[$t]['label'] }}
                        </small>
                        @if($selesai)
                            <br><span class="badge bg-success">✓</span>
                        @elseif($aktif)
                            <br><span class="badge bg-warning text-dark">Aktif</span>
                        @endif
                    </div>
                    @if($t < 7)
                        <div class="flex-shrink-0 px-2">
                            <i class="bi bi-arrow-right text-muted"></i>
                        </div>
                    @endif
                @endfor
            </div>
        </div>
    </div>
    
    <div class="row">
        {{-- Kolom Kiri --}}
        <div class="col-lg-6">
            {{-- TAHAP 1: Data Pendaftaran --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-1-circle me-2"></i>Tahap 1: Data Pendaftaran</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" width="40%">No. Pendaftaran</td>
                            <td><code class="fs-6">{{ $peserta->nomor_pendaftaran }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Nama</td>
                            <td><strong>{{ $peserta->nama }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>{{ $peserta->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Telepon</td>
                            <td>{{ $peserta->telepon ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Asal Sekolah</td>
                            <td>{{ $peserta->asal_sekolah ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Daftar</td>
                            <td>{{ $peserta->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if($peserta->tahapanSpmb?->tahap_1_selesai)
                                    <span class="badge bg-success">Selesai</span>
                                @else
                                    <span class="badge bg-warning">Proses</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            {{-- TAHAP 2: Formulir SPMB --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-2-circle me-2"></i>Tahap 2: Formulir SPMB</h6>
                    @if($peserta->formulirSpmb)
                        <a href="{{ route('admin.verifikasi.formulir.detail', $peserta->formulirSpmb) }}" class="btn btn-sm btn-light">
                            <i class="bi bi-eye me-1"></i>Detail
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if($peserta->formulirSpmb)
                        @php $f = $peserta->formulirSpmb; @endphp
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><td class="text-muted">Nama Lengkap</td><td>{{ $f->nama_lengkap ?? '-' }}</td></tr>
                                    <tr><td class="text-muted">Tempat Lahir</td><td>{{ $f->tempat_lahir ?? '-' }}</td></tr>
                                    <tr><td class="text-muted">Tanggal Lahir</td><td>{{ $f->tanggal_lahir?->format('d/m/Y') ?? '-' }}</td></tr>
                                    <tr><td class="text-muted">Jenis Kelamin</td><td>{{ $f->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                                    <tr><td class="text-muted">NISN</td><td>{{ $f->nisn ?? '-' }}</td></tr>
                                    <tr><td class="text-muted">Asal Sekolah</td><td>{{ $f->asal_sekolah ?? '-' }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><td class="text-muted">Nama Ayah</td><td>{{ $f->nama_ayah ?? '-' }}</td></tr>
                                    <tr><td class="text-muted">Nama Ibu</td><td>{{ $f->nama_ibu ?? '-' }}</td></tr>
                                    <tr><td class="text-muted">Telepon Ayah</td><td>{{ $f->telepon_ayah ?? '-' }}</td></tr>
                                    <tr><td class="text-muted">Telepon Ibu</td><td>{{ $f->telepon_ibu ?? '-' }}</td></tr>
                                    <tr><td class="text-muted">Alamat</td><td>{{ $f->alamat_kelurahan ?? '-' }}, {{ $f->alamat_kecamatan ?? '' }}</td></tr>
                                </table>
                            </div>
                        </div>
                        
                        {{-- Berkas Upload --}}
                        <hr>
                        <h6 class="mb-3"><i class="bi bi-folder me-1"></i>Berkas Upload</h6>
                        <div class="row g-2">
                            @php
                                $berkas = [
                                    'file_kk' => 'Kartu Keluarga',
                                    'file_akta' => 'Akta Kelahiran',
                                    'file_ijazah' => 'Ijazah/SKL',
                                    'file_bpjs' => 'BPJS/KIS',
                                    'file_ktp_ayah' => 'KTP Ayah',
                                    'file_ktp_ibu' => 'KTP Ibu',
                                ];
                            @endphp
                            @foreach($berkas as $field => $label)
                                <div class="col-md-4">
                                    <div class="border rounded p-2 text-center {{ $f->$field ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' }}">
                                        <small class="{{ $f->$field ? 'text-success' : 'text-danger' }}">
                                            <i class="bi {{ $f->$field ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>{{ $label }}
                                        </small>
                                        @if($f->$field)
                                            <br><a href="{{ Storage::url($f->$field) }}" target="_blank" class="small">Lihat</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status Verifikasi:</span>
                            <span class="badge bg-{{ $f->status_verifikasi == 'terverifikasi' ? 'success' : ($f->status_verifikasi == 'ditolak' ? 'danger' : 'warning') }}">
                                {{ ucfirst($f->status_verifikasi) }}
                            </span>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-file-earmark-x fs-1"></i>
                            <p class="mb-0">Belum mengisi formulir</p>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- TAHAP 3: Pembayaran Formulir --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="bi bi-3-circle me-2"></i>Tahap 3: Pembayaran Formulir</h6>
                </div>
                <div class="card-body">
                    @php $bayarFormulir = $peserta->pembayaran->where('jenis', 'formulir')->first(); @endphp
                    @if($bayarFormulir)
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" width="40%">Status</td>
                                <td>
                                    <span class="badge bg-{{ $bayarFormulir->status == 'terverifikasi' ? 'success' : ($bayarFormulir->status == 'ditolak' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($bayarFormulir->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tanggal Upload</td>
                                <td>{{ $bayarFormulir->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($bayarFormulir->diverifikasi_pada)
                            <tr>
                                <td class="text-muted">Diverifikasi</td>
                                <td>{{ $bayarFormulir->diverifikasi_pada->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Bukti Pembayaran</td>
                                <td>
                                    @if($bayarFormulir->bukti_file)
                                        <a href="{{ Storage::url($bayarFormulir->bukti_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-image me-1"></i>Lihat Bukti
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @if($bayarFormulir->catatan)
                            <tr>
                                <td class="text-muted">Catatan</td>
                                <td>{{ $bayarFormulir->catatan }}</td>
                            </tr>
                            @endif
                        </table>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-credit-card fs-1"></i>
                            <p class="mb-0">Belum upload bukti pembayaran</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Kolom Kanan --}}
        <div class="col-lg-6">
            {{-- TAHAP 4: Hasil Tes --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-4-circle me-2"></i>Tahap 4: Tes Online</h6>
                </div>
                <div class="card-body">
                    @if($peserta->sesiTes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tes</th>
                                        <th>Nilai</th>
                                        <th>Status</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($peserta->sesiTes as $sesi)
                                    @php
                                        // Cek jenis tes kepribadian
                                        $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $sesi->tes_id)->exists();
                                        $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $sesi->tes_id)->first();
                                        $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
                                        $isMbti = \App\Models\MbtiConfig::where('tes_id', $sesi->tes_id)->exists();
                                        $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $sesi->tes_id)->first();
                                        $isProfiling = $profilingConfig && $profilingConfig->aktif;
                                        $isKepribadian = $isPsikotes || $isGayaBelajar || $isMbti || $isProfiling;
                                        
                                        // Ambil hasil kepribadian
                                        $hasilPsikotes = $isPsikotes ? \App\Models\HasilPsikotesKepribadian::where('sesi_tes_id', $sesi->id)->first() : null;
                                        $hasilGB = $isGayaBelajar ? \App\Models\HasilGayaBelajar::where('sesi_tes_id', $sesi->id)->first() : null;
                                        $hasilMbti = $isMbti ? \App\Models\HasilMbti::where('sesi_tes_id', $sesi->id)->first() : null;
                                        $hasilProfiling = $isProfiling ? \App\Models\HasilProfiling::where('sesi_tes_id', $sesi->id)->first() : null;
                                        
                                        // Warna untuk psikotes
                                        $colorsPsikotes = ['koleris' => 'danger', 'sanguin' => 'warning', 'plegmatis' => 'success', 'melankolis' => 'primary'];
                                        // Warna untuk gaya belajar
                                        $colorsGB = ['visual' => 'primary', 'auditori' => 'success', 'kinestetik' => 'warning'];
                                        $iconsGB = ['visual' => 'eye', 'auditori' => 'ear', 'kinestetik' => 'hand-index'];
                                        // Pilar list untuk profiling
                                        $pilarList = \App\Models\ProfilingConfig::pilarList();
                                    @endphp
                                    <tr>
                                        <td>{{ $sesi->tes->nama ?? 'Tes' }}</td>
                                        <td>
                                            @if($isMbti && $hasilMbti)
                                                <span class="badge bg-success fs-6">
                                                    <i class="bi bi-diagram-3 me-1"></i>{{ $hasilMbti->tipe_mbti }}
                                                </span>
                                            @elseif($isProfiling && $hasilProfiling)
                                                <span class="badge bg-{{ $pilarList[$hasilProfiling->pilar_dominan]['warna'] ?? 'secondary' }} fs-6">
                                                    <i class="bi bi-{{ $pilarList[$hasilProfiling->pilar_dominan]['icon'] ?? 'person' }} me-1"></i>{{ $pilarList[$hasilProfiling->pilar_dominan]['kode_qx'] ?? ucfirst($hasilProfiling->pilar_dominan) }}
                                                </span>
                                            @elseif($isGayaBelajar && $hasilGB)
                                                @php $hasilTipe = explode(' & ', $hasilGB->hasil_gaya_belajar); @endphp
                                                @foreach($hasilTipe as $tipe)
                                                    <span class="badge bg-{{ $colorsGB[strtolower($tipe)] ?? 'secondary' }} fs-6">
                                                        <i class="bi bi-{{ $iconsGB[strtolower($tipe)] ?? 'person' }} me-1"></i>{{ ucfirst($tipe) }}
                                                    </span>
                                                @endforeach
                                            @elseif($isPsikotes && $hasilPsikotes)
                                                @php
                                                    $hasilTipePsikotes = explode(' & ', $hasilPsikotes->hasil_kepribadian);
                                                    $detailNilaiPsikotes = $hasilPsikotes->detail_nilai ?? [];
                                                @endphp
                                                @foreach($hasilTipePsikotes as $tipePsikotes)
                                                    <span class="badge bg-{{ $colorsPsikotes[$tipePsikotes] ?? 'secondary' }} fs-6">
                                                        {{ ucfirst($tipePsikotes) }}: {{ $detailNilaiPsikotes[$tipePsikotes] ?? '-' }}
                                                    </span>
                                                @endforeach
                                            @elseif($isKepribadian)
                                                <span class="badge bg-secondary fs-6">Belum dihitung</span>
                                            @else
                                                <span class="badge bg-{{ $sesi->nilai >= ($sesi->tes->nilai_lulus ?? 60) ? 'success' : 'danger' }} fs-6">
                                                    {{ number_format($sesi->nilai, 1) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sesi->status == 'selesai')
                                                <span class="badge bg-success">Selesai</span>
                                            @elseif($sesi->status == 'timeout')
                                                <span class="badge bg-warning">Timeout</span>
                                            @else
                                                <span class="badge bg-info">{{ ucfirst($sesi->status) }}</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $sesi->created_at->format('d/m/Y H:i') }}</small></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-journal-x fs-1"></i>
                            <p class="mb-0">Belum mengikuti tes</p>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- TAHAP 5: Wawancara --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #6f42c1;">
                    <h6 class="mb-0"><i class="bi bi-5-circle me-2"></i>Tahap 5: Wawancara</h6>
                    <div>
                        @if($peserta->wawancara)
                            <button type="button" class="btn btn-sm btn-light me-1" data-bs-toggle="modal" data-bs-target="#modalDetailWawancara">
                                <i class="bi bi-eye me-1"></i>Lihat Detail
                            </button>
                        @endif
                        @if($peserta->wawancara || $tahapSaatIni >= 5)
                            <a href="{{ route('admin.verifikasi.wawancara.form', $peserta) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($peserta->wawancara)
                        @php $w = $peserta->wawancara; @endphp
                        
                        {{-- Info Wawancara Ortu --}}
                        <div class="mb-3 p-2 bg-warning bg-opacity-10 rounded">
                            <h6 class="mb-2"><i class="bi bi-people me-1"></i>Wawancara Orang Tua</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Tanggal:</small><br>
                                    <span>{{ $w->tanggal_wawancara_ortu?->format('d/m/Y') ?? $w->tanggal_wawancara?->format('d/m/Y') ?? '-' }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Interviewer:</small><br>
                                    <span>{{ $w->interviewer_ortu ?? $w->nama_interviewer ?? '-' }}</span>
                                </div>
                            </div>
                            @if($w->catatan_ortu)
                                <div class="mt-2">
                                    <small class="text-muted">Catatan:</small><br>
                                    <small>{{ $w->catatan_ortu }}</small>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Info Wawancara Siswa --}}
                        <div class="mb-3 p-2 bg-danger bg-opacity-10 rounded">
                            <h6 class="mb-2"><i class="bi bi-person me-1"></i>Wawancara Siswa</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Tanggal:</small><br>
                                    <span>{{ $w->tanggal_wawancara_siswa?->format('d/m/Y') ?? '-' }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Interviewer:</small><br>
                                    <span>{{ $w->interviewer_siswa ?? '-' }}</span>
                                </div>
                            </div>
                            @if($w->catatan_siswa)
                                <div class="mt-2">
                                    <small class="text-muted">Catatan:</small><br>
                                    <small>{{ $w->catatan_siswa }}</small>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Verifikasi Berkas --}}
                        @if($w->verifikasi_berkas && count($w->verifikasi_berkas) > 0)
                        <div class="mb-3 p-2 bg-secondary bg-opacity-10 rounded">
                            <h6 class="mb-2"><i class="bi bi-folder-check me-1"></i>Verifikasi Berkas</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @php
                                    $daftarBerkas = \App\Models\Wawancara::daftarBerkas();
                                @endphp
                                @foreach($daftarBerkas as $key => $label)
                                    @if(isset($w->verifikasi_berkas[$key]) && $w->verifikasi_berkas[$key])
                                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>{{ $label }}</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-x me-1"></i>{{ $label }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        {{-- Data dari Peserta (Surat Pernyataan, Tes Pegon, Voice Quran) --}}
                        @if($w->surat_pernyataan_siswa || $w->surat_pernyataan_ortu || $w->file_tes_pegon || $w->file_voice_quran || $w->tanda_tangan_peserta || $w->tanda_tangan_ortu)
                        <div class="mb-3 p-2 bg-info bg-opacity-10 rounded">
                            <h6 class="mb-2"><i class="bi bi-file-earmark-check me-1"></i>Data Diisi Peserta</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted">SP Siswa:</small><br>
                                    @if($w->surat_pernyataan_siswa && count($w->surat_pernyataan_siswa) > 0)
                                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Sudah diisi</span>
                                    @else
                                        <span class="badge bg-secondary">Belum</span>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">SP Ortu:</small><br>
                                    @if($w->surat_pernyataan_ortu && count($w->surat_pernyataan_ortu) > 0)
                                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Sudah diisi</span>
                                    @else
                                        <span class="badge bg-secondary">Belum</span>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Tes Pegon:</small><br>
                                    @if($w->file_tes_pegon)
                                        <a href="{{ Storage::url($w->file_tes_pegon) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0">
                                            <i class="bi bi-eye me-1"></i>Lihat
                                        </a>
                                    @else
                                        <span class="badge bg-secondary">Belum</span>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Voice Quran:</small><br>
                                    @if($w->file_voice_quran)
                                        <a href="{{ Storage::url($w->file_voice_quran) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0">
                                            <i class="bi bi-play-circle me-1"></i>Dengar
                                        </a>
                                    @else
                                        <span class="badge bg-secondary">Belum</span>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">TTD Peserta:</small><br>
                                    <span class="badge bg-{{ $w->tanda_tangan_peserta ? 'success' : 'secondary' }}">{{ $w->tanda_tangan_peserta ? 'Sudah' : 'Belum' }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">TTD Ortu:</small><br>
                                    <span class="badge bg-{{ $w->tanda_tangan_ortu ? 'success' : 'secondary' }}">{{ $w->tanda_tangan_ortu ? 'Sudah' : 'Belum' }}</span>
                                </div>
                            </div>
                            @if($w->diisi_peserta_pada)
                                <small class="text-muted mt-1 d-block"><i class="bi bi-clock me-1"></i>Diisi pada: {{ $w->diisi_peserta_pada->format('d/m/Y H:i') }}</small>
                            @endif
                        </div>
                        @endif

                        {{-- Hasil Wawancara --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Hasil Wawancara:</span>
                            <span class="badge fs-6 bg-{{ $w->hasil_wawancara == 'lulus' ? 'success' : ($w->hasil_wawancara == 'tidak_lulus' ? 'danger' : 'warning') }}">
                                {{ $w->hasil_wawancara == 'lulus' ? 'LULUS' : ($w->hasil_wawancara == 'tidak_lulus' ? 'TIDAK LULUS' : 'MENUNGGU') }}
                            </span>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-people fs-1"></i>
                            <p class="mb-0">Belum wawancara</p>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- TAHAP 6: Pelunasan --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-6-circle me-2"></i>Tahap 6: Pelunasan</h6>
                </div>
                <div class="card-body">
                    @php $pelunasan = $peserta->pembayaran->where('jenis', 'pertama')->first(); @endphp
                    @if($pelunasan)
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" width="40%">Status</td>
                                <td>
                                    <span class="badge bg-{{ $pelunasan->status == 'terverifikasi' ? 'success' : ($pelunasan->status == 'ditolak' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($pelunasan->status) }}
                                    </span>
                                </td>
                            </tr>
                            @if($pelunasan->nominal)
                            <tr>
                                <td class="text-muted">Nominal</td>
                                <td>Rp {{ number_format($pelunasan->nominal, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Tanggal Upload</td>
                                <td>{{ $pelunasan->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Bukti Pembayaran</td>
                                <td>
                                    @if($pelunasan->bukti_file)
                                        <a href="{{ Storage::url($pelunasan->bukti_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-image me-1"></i>Lihat Bukti
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-cash-stack fs-1"></i>
                            <p class="mb-0">Belum upload bukti pelunasan</p>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- TAHAP 7: Kelulusan --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-7-circle me-2"></i>Tahap 7: Kelulusan</h6>
                </div>
                <div class="card-body">
                    @php $statusKelulusan = $peserta->tahapanSpmb?->status_kelulusan ?? 'menunggu'; @endphp
                    <div class="text-center py-3">
                        @if($statusKelulusan == 'lulus')
                            <i class="bi bi-trophy text-success" style="font-size: 3rem;"></i>
                            <h4 class="text-success mt-2">LULUS</h4>
                            <p class="text-muted mb-0">Selamat! Peserta diterima sebagai siswa baru</p>
                        @elseif($statusKelulusan == 'tidak_lulus')
                            <i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
                            <h4 class="text-danger mt-2">TIDAK LULUS</h4>
                            <p class="text-muted mb-0">Peserta tidak diterima</p>
                        @else
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 3rem;"></i>
                            <h4 class="text-warning mt-2">MENUNGGU</h4>
                            <p class="text-muted mb-0">Menunggu pengumuman kelulusan</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Tombol Aksi --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <a href="{{ route('admin.peserta.show', $peserta) }}" class="btn btn-info text-white">
                        <i class="bi bi-person me-1"></i>Detail Peserta
                    </a>
                    @if($peserta->formulirSpmb)
                        <a href="{{ route('admin.verifikasi.formulir.detail', $peserta->formulirSpmb) }}" class="btn btn-outline-info">
                            <i class="bi bi-file-earmark-text me-1"></i>Detail Formulir
                        </a>
                    @endif
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalTahapan">
                        <i class="bi bi-arrow-up-circle me-1"></i>Update Tahapan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Update Tahapan --}}
<div class="modal fade" id="modalTahapan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Tahapan - {{ $peserta->nama }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.peserta.update-tahap', $peserta) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">Tahap saat ini: <strong>{{ $tahapSaatIni }}</strong></p>
                    <div class="mb-3">
                        <label class="form-label">Pindah ke Tahap</label>
                        <select name="tahap_baru" class="form-select" required>
                            @for($t = 1; $t <= 7; $t++)
                                <option value="{{ $t }}" {{ $tahapSaatIni == $t ? 'selected' : '' }}>
                                    Tahap {{ $t }} - {{ $tahapanLabel[$t]['label'] }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="catatan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Tahapan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Detail Wawancara --}}
@if($peserta->wawancara)
@php 
    $w = $peserta->wawancara;
    $pertanyaanOrtu = \App\Models\Wawancara::pertanyaanOrtu();
    $pertanyaanSiswa = \App\Models\Wawancara::pertanyaanSiswa();
@endphp
<div class="modal fade" id="modalDetailWawancara" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #6f42c1; color: white;">
                <h5 class="modal-title"><i class="bi bi-clipboard-data me-2"></i>Detail Wawancara - {{ $peserta->nama }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    {{-- Wawancara Orang Tua --}}
                    <div class="col-lg-6">
                        <div class="card border-warning mb-4">
                            <div class="card-header bg-warning">
                                <h6 class="mb-0"><i class="bi bi-people me-2"></i>WAWANCARA ORANG TUA / WALI</h6>
                                <small>{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</small>
                            </div>
                            <div class="card-body">
                                {{-- Info Interviewer --}}
                                <div class="row mb-3 p-2 bg-light rounded">
                                    <div class="col-6">
                                        <small class="text-muted">Tanggal:</small><br>
                                        <strong>{{ $w->tanggal_wawancara_ortu?->format('d/m/Y') ?? $w->tanggal_wawancara?->format('d/m/Y') ?? '-' }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Interviewer:</small><br>
                                        <strong>{{ $w->interviewer_ortu ?? $w->nama_interviewer ?? '-' }}</strong>
                                    </div>
                                </div>
                                
                                {{-- Pertanyaan & Jawaban --}}
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">No</th>
                                                <th width="50%">Pertanyaan</th>
                                                <th width="45%">Jawaban</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pertanyaanOrtu as $no => $pertanyaan)
                                            <tr>
                                                <td class="text-center">{{ $no }}</td>
                                                <td><small>{{ $pertanyaan }}</small></td>
                                                <td>
                                                    @if(isset($w->jawaban_ortu[$no]) && $w->jawaban_ortu[$no])
                                                        <small class="text-success">{{ $w->jawaban_ortu[$no] }}</small>
                                                    @else
                                                        <small class="text-muted fst-italic">Belum diisi</small>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Catatan --}}
                                @if($w->catatan_ortu)
                                <div class="mt-3 p-2 bg-warning bg-opacity-10 rounded">
                                    <small class="text-muted fw-bold">Catatan Interviewer:</small><br>
                                    <small>{{ $w->catatan_ortu }}</small>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Wawancara Siswa --}}
                    <div class="col-lg-6">
                        <div class="card border-danger mb-4">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>WAWANCARA CALON SISWA/I</h6>
                                <small>{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</small>
                            </div>
                            <div class="card-body">
                                {{-- Info Interviewer --}}
                                <div class="row mb-3 p-2 bg-light rounded">
                                    <div class="col-6">
                                        <small class="text-muted">Tanggal:</small><br>
                                        <strong>{{ $w->tanggal_wawancara_siswa?->format('d/m/Y') ?? '-' }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Interviewer:</small><br>
                                        <strong>{{ $w->interviewer_siswa ?? '-' }}</strong>
                                    </div>
                                </div>
                                
                                {{-- Pertanyaan & Jawaban --}}
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">No</th>
                                                <th width="50%">Pertanyaan</th>
                                                <th width="45%">Jawaban</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pertanyaanSiswa as $no => $pertanyaan)
                                            <tr>
                                                <td class="text-center">{{ $no }}</td>
                                                <td><small>{{ $pertanyaan }}</small></td>
                                                <td>
                                                    @if(isset($w->jawaban_siswa[$no]) && $w->jawaban_siswa[$no])
                                                        <small class="text-success">{{ $w->jawaban_siswa[$no] }}</small>
                                                    @else
                                                        <small class="text-muted fst-italic">Belum diisi</small>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Catatan --}}
                                @if($w->catatan_siswa)
                                <div class="mt-3 p-2 bg-danger bg-opacity-10 rounded">
                                    <small class="text-muted fw-bold">Catatan Interviewer:</small><br>
                                    <small>{{ $w->catatan_siswa }}</small>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Verifikasi Berkas --}}
                <div class="card border-secondary mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bi bi-folder-check me-2"></i>Verifikasi Kelengkapan Berkas</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @php $daftarBerkas = \App\Models\Wawancara::daftarBerkas(); @endphp
                            @foreach($daftarBerkas as $key => $label)
                            <div class="col-md-3 mb-2">
                                <div class="d-flex align-items-center">
                                    @if(isset($w->verifikasi_berkas[$key]) && $w->verifikasi_berkas[$key])
                                        <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                                        <span class="text-success">{{ $label }}</span>
                                    @else
                                        <i class="bi bi-x-circle-fill text-danger me-2 fs-5"></i>
                                        <span class="text-muted">{{ $label }}</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                {{-- Kesimpulan --}}
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Kesimpulan / Catatan Akhir</h6>
                            </div>
                            <div class="card-body">
                                @if($w->catatan_interviewer)
                                    <p class="mb-0">{{ $w->catatan_interviewer }}</p>
                                @else
                                    <p class="text-muted fst-italic mb-0">Tidak ada catatan akhir</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-{{ $w->hasil_wawancara == 'lulus' ? 'success' : ($w->hasil_wawancara == 'tidak_lulus' ? 'danger' : 'warning') }}">
                            <div class="card-header bg-{{ $w->hasil_wawancara == 'lulus' ? 'success' : ($w->hasil_wawancara == 'tidak_lulus' ? 'danger' : 'warning') }} {{ $w->hasil_wawancara == 'warning' ? '' : 'text-white' }}">
                                <h6 class="mb-0"><i class="bi bi-award me-2"></i>Hasil Wawancara</h6>
                            </div>
                            <div class="card-body text-center py-4">
                                @if($w->hasil_wawancara == 'lulus')
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                    <h4 class="text-success mt-2 mb-0">LULUS</h4>
                                @elseif($w->hasil_wawancara == 'tidak_lulus')
                                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>
                                    <h4 class="text-danger mt-2 mb-0">TIDAK LULUS</h4>
                                @else
                                    <i class="bi bi-hourglass-split text-warning" style="font-size: 3rem;"></i>
                                    <h4 class="text-warning mt-2 mb-0">MENUNGGU</h4>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('admin.verifikasi.wawancara.cetak', $peserta) }}" class="btn btn-outline-secondary" target="_blank">
                    <i class="bi bi-printer me-1"></i>Cetak
                </a>
                <a href="{{ route('admin.verifikasi.wawancara.form', $peserta) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit Wawancara
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
