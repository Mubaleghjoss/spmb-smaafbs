@extends('layouts.admin')

@section('title', 'Detail Hasil: ' . $sesi->peserta->nama)

@section('content')
@php
    $isMbti = \App\Models\MbtiConfig::where('tes_id', $tes->id)->exists();
    $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $tes->id)->exists();
    $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $tes->id)->first();
    $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
    $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $tes->id)->first();
    $isProfiling = $profilingConfig && $profilingConfig->aktif;
    $isKepribadian = $isMbti || $isPsikotes || $isGayaBelajar || $isProfiling;
    
    // Ambil hasil kepribadian jika ada
    $bolehTampilkanHasilPsikometri = $sesi->status !== 'timeout';
    $hasilMbti = ($isMbti && $bolehTampilkanHasilPsikometri) ? \App\Models\HasilMbti::where('sesi_tes_id', $sesi->id)->first() : null;
    $hasilPsikotes = ($isPsikotes && $bolehTampilkanHasilPsikometri) ? \App\Models\HasilPsikotesKepribadian::where('sesi_tes_id', $sesi->id)->first() : null;
    $hasilGB = ($isGayaBelajar && $bolehTampilkanHasilPsikometri) ? \App\Models\HasilGayaBelajar::where('sesi_tes_id', $sesi->id)->first() : null;
    $hasilProfiling = ($isProfiling && $bolehTampilkanHasilPsikometri) ? \App\Models\HasilProfiling::where('sesi_tes_id', $sesi->id)->first() : null;
    $keamananService = app(\App\Services\KeamananService::class);
    $renderRichText = fn ($value) => $keamananService->sanitasiHtml((string) $value);
    $renderJawaban = function ($value) use ($keamananService) {
        $html = $keamananService->sanitasiHtml((string) $value);
        $html = preg_replace('/<\/?(p|div)[^>]*>/i', '', $html);
        $html = trim($html ?? '');

        return $html !== '' ? $html : '<span class="text-muted">-</span>';
    };
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Detail Hasil Ujian</h1>
            <small class="text-muted">{{ $tes->nama }}</small>
            @if($isMbti)
                <span class="badge bg-success ms-2">MBTI</span>
            @elseif($isProfiling)
                <span class="badge bg-primary ms-2">Profiling</span>
            @elseif($isPsikotes)
                <span class="badge bg-info ms-2">Psikotes</span>
            @elseif($isGayaBelajar)
                <span class="badge bg-warning text-dark ms-2">Gaya Belajar</span>
            @endif
        </div>
        <a href="{{ route('admin.hasil.show', $tes) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($hasPerbedaanPenilaian ?? false)
        <div class="alert alert-warning d-flex justify-content-between align-items-start gap-3">
            <div>
                <h6 class="alert-heading mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Nilai perlu dihitung ulang</h6>
                <p class="mb-0">Ada jawaban yang status tersimpannya berbeda dari kunci jawaban saat ini. Tampilan di bawah sudah memakai kunci terkini.</p>
            </div>
            <form action="{{ route('admin.hasil.hitung-ulang-sesi', [$tes, $sesi]) }}" method="POST" class="flex-shrink-0">
                @csrf
                <button type="submit" class="btn btn-warning text-dark">
                    <i class="bi bi-calculator me-1"></i>Hitung Ulang Sesi
                </button>
            </form>
        </div>
    @endif

    <!-- Info Peserta -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Informasi Peserta</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="40%">Nama</td>
                            <td><strong>{{ $sesi->peserta->nama }}</strong></td>
                        </tr>
                        <tr>
                            <td>Nomor Pendaftaran</td>
                            <td>{{ $sesi->peserta->nomor_pendaftaran ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>{{ $sesi->peserta->email ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Hasil Ujian</h6>
                </div>
                <div class="card-body">
                    @if($isKepribadian)
                        {{-- Tampilan untuk tes kepribadian --}}
                        <div class="text-center mb-3">
                            @if($isMbti && $hasilMbti)
                                <h1 class="display-4 text-success mb-2">
                                    <i class="bi bi-diagram-3 me-2"></i>{{ $hasilMbti->tipe_mbti }}
                                </h1>
                                @php
                                    $deskripsiMbti = \App\Models\MbtiTipeDeskripsi::where('tes_id', $tes->id)
                                        ->where('tipe', $hasilMbti->tipe_mbti)->first();
                                    if (!$deskripsiMbti) {
                                        $tipeMbtiList = \App\Models\MbtiConfig::tipeMbtiList();
                                        $deskripsiMbti = (object) ($tipeMbtiList[$hasilMbti->tipe_mbti] ?? ['nama' => $hasilMbti->tipe_mbti, 'deskripsi' => '']);
                                    }
                                @endphp
                                <h5 class="text-muted">{{ $deskripsiMbti->nama ?? '' }}</h5>
                            @elseif($isPsikotes && $hasilPsikotes)
                                @php
                                    $colorsPsikotes = ['koleris' => 'danger', 'sanguin' => 'warning', 'plegmatis' => 'success', 'melankolis' => 'primary'];
                                    $hasilTipePsikotes = explode(' & ', $hasilPsikotes->hasil_kepribadian);
                                    $detailNilaiPsikotes = $hasilPsikotes->detail_nilai ?? [];
                                @endphp
                                <h1 class="display-4 mb-2">
                                    @foreach($hasilTipePsikotes as $index => $tipePsikotes)
                                        <span class="text-{{ $colorsPsikotes[$tipePsikotes] ?? 'secondary' }}">{{ ucfirst($tipePsikotes) }}</span>@if($index < count($hasilTipePsikotes) - 1) & @endif
                                    @endforeach
                                </h1>
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    @foreach($hasilTipePsikotes as $tipePsikotes)
                                        <span class="badge bg-{{ $colorsPsikotes[$tipePsikotes] ?? 'secondary' }} fs-6">
                                            {{ ucfirst($tipePsikotes) }}: {{ $detailNilaiPsikotes[$tipePsikotes] ?? '-' }}
                                        </span>
                                    @endforeach
                                </div>
                            @elseif($isGayaBelajar && $hasilGB)
                                @php
                                    $colorsGB = ['visual' => 'primary', 'auditori' => 'success', 'kinestetik' => 'warning'];
                                    $iconsGB = ['visual' => 'eye', 'auditori' => 'ear', 'kinestetik' => 'hand-index'];
                                @endphp
                                <h1 class="display-4 text-{{ $colorsGB[strtolower($hasilGB->hasil_gaya_belajar)] ?? 'secondary' }} mb-2">
                                    <i class="bi bi-{{ $iconsGB[strtolower($hasilGB->hasil_gaya_belajar)] ?? 'person' }} me-2"></i>{{ ucfirst($hasilGB->hasil_gaya_belajar) }}
                                </h1>
                            @elseif($isProfiling && $hasilProfiling)
                                @php
                                    $pilarList = \App\Models\ProfilingConfig::pilarList();
                                    $pilarDominan = $hasilProfiling->pilar_dominan;
                                @endphp
                                <h1 class="display-4 text-{{ $pilarList[$pilarDominan]['warna'] ?? 'primary' }} mb-2">
                                    <i class="bi bi-{{ $pilarList[$pilarDominan]['icon'] ?? 'person' }} me-2"></i>{{ $pilarList[$pilarDominan]['nama'] ?? ucfirst($pilarDominan) }}
                                </h1>
                                <h5 class="text-muted">{{ $pilarList[$pilarDominan]['kode_qx'] ?? '' }} - {{ $pilarList[$pilarDominan]['nama_qx'] ?? '' }}</h5>
                            @else
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    {{ $sesi->status === 'timeout' ? 'Hasil belum final karena waktu habis.' : 'Hasil belum dihitung' }}
                                </div>
                                @if($isMbti && $sesi->status !== 'timeout')
                                <form action="{{ route('admin.hasil.hitung-ulang-mbti', $tes) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-calculator me-1"></i>Hitung Hasil MBTI
                                    </button>
                                </form>
                                @endif
                            @endif
                        </div>
                        
                        @if($isMbti && $hasilMbti)
                        {{-- Detail skor MBTI dengan hasil per bagian --}}
                        @php
                            $detailPerhitungan = $hasilMbti->detail_perhitungan ?? [];
                        @endphp
                        <div class="row g-2 mb-3">
                            @foreach(['EI' => ['E', 'I', 'primary'], 'SN' => ['S', 'N', 'info'], 'TF' => ['T', 'F', 'warning'], 'JP' => ['J', 'P', 'danger']] as $dimensi => $info)
                            @php
                                $labelA = $info[0];
                                $labelB = $info[1];
                                $color = $info[2];
                                $skorA = $hasilMbti->{'skor_' . strtolower($labelA)} ?? 0;
                                $skorB = $hasilMbti->{'skor_' . strtolower($labelB)} ?? 0;
                                $detail = $detailPerhitungan[$dimensi] ?? null;
                                $hasilAkhir = $detail['hasil'] ?? ($skorB > $skorA ? $labelB : $labelA);
                            @endphp
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge {{ $skorA >= $skorB ? 'bg-' . $color . ($color == 'warning' ? ' text-dark' : '') : 'bg-secondary' }}">{{ $labelA }}: {{ $skorA }}</span>
                                        <strong class="text-{{ $color }}">{{ $hasilAkhir }}</strong>
                                        <span class="badge {{ $skorB > $skorA ? 'bg-' . $color . ($color == 'warning' ? ' text-dark' : '') : 'bg-secondary' }}">{{ $labelB }}: {{ $skorB }}</span>
                                    </div>
                                    @if($detail)
                                    <div class="d-flex gap-1 justify-content-center" style="font-size: 0.7rem;">
                                        @foreach(['bagian_1' => 'B1', 'bagian_2' => 'B2', 'bagian_3' => 'B3'] as $bagianKey => $bagianLabel)
                                            @php $hasilBagian = $detail['hasil_' . $bagianKey] ?? '-'; @endphp
                                            <span class="badge {{ $hasilBagian == $hasilAkhir ? 'bg-' . $color . ($color == 'warning' ? ' text-dark' : '') : 'bg-light text-dark border' }}">
                                                {{ $bagianLabel }}:{{ $hasilBagian }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        
                        @if($isProfiling && $hasilProfiling)
                        {{-- Detail skor Profiling --}}
                        @php
                            $pilarList = \App\Models\ProfilingConfig::pilarList();
                            $skorArray = $hasilProfiling->getSkorArray();
                        @endphp
                        <div class="row g-2 mb-3">
                            @foreach($pilarList as $pilar => $info)
                            @php
                                $skor = $skorArray[$pilar] ?? 0;
                                $isDominan = $pilar === $hasilProfiling->pilar_dominan;
                            @endphp
                            <div class="col">
                                <div class="border rounded p-2 text-center {{ $isDominan ? 'bg-' . $info['warna'] . ' bg-opacity-10 border-' . $info['warna'] : '' }}">
                                    <i class="bi bi-{{ $info['icon'] }} text-{{ $info['warna'] }}"></i>
                                    <div class="fw-bold text-{{ $info['warna'] }}">{{ $skor }}</div>
                                    <small class="text-muted">{{ $info['kode_qx'] }}</small>
                                    @if($isDominan)
                                    <div><span class="badge bg-{{ $info['warna'] }}" style="font-size: 0.6rem;">Dominan</span></div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    @else
                        {{-- Tampilan untuk tes akademik --}}
                        <div class="row text-center">
                            <div class="col-4">
                                <h2 class="{{ $sesi->nilai >= $tes->nilai_lulus ? 'text-success' : 'text-danger' }}">
                                    {{ $sesi->nilai ?? '-' }}
                                </h2>
                                <small class="text-muted">Nilai</small>
                            </div>
                            <div class="col-4">
                                <h2>{{ $tes->nilai_lulus }}</h2>
                                <small class="text-muted">Nilai Lulus</small>
                            </div>
                            <div class="col-4">
                                @if($sesi->nilai >= $tes->nilai_lulus)
                                    <span class="badge bg-success fs-5 py-2 px-3">LULUS</span>
                                @else
                                    <span class="badge bg-danger fs-5 py-2 px-3">TIDAK LULUS</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    <hr>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td>Waktu Mulai</td>
                            <td>{{ $sesi->waktu_mulai->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td>Waktu Selesai</td>
                            <td>{{ $sesi->waktu_selesai?->format('d/m/Y H:i:s') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Durasi</td>
                            <td>
                                @if($sesi->waktu_selesai)
                                    {{ $sesi->durasi_menit_bulat }} menit
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                @switch($sesi->status)
                                    @case('selesai')
                                        <span class="badge bg-success">Selesai</span>
                                        @break
                        @case('timeout')
                                        <span class="badge bg-warning text-dark">Waktu Habis</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $sesi->status }}</span>
                                @endswitch
                            </td>
                        </tr>
                        @if($sesi->status === 'timeout')
                        <tr>
                            <td>Permohonan Timeout</td>
                            <td>
                                @if($sesi->permohonan_ulang_status)
                                    <span class="badge bg-{{ $sesi->permohonan_ulang_status === \App\Models\SesiTes::PERMOHONAN_ULANG_PENDING ? 'warning text-dark' : ($sesi->permohonan_ulang_status === \App\Models\SesiTes::PERMOHONAN_ULANG_DISETUJUI ? 'success' : 'danger') }}">
                                        {{ ucfirst($sesi->permohonan_ulang_status) }}
                                    </span>
                                    <small class="text-muted ms-1">{{ $sesi->labelPermohonanUlangTipe() }}</small>
                                @else
                                    <span class="text-muted">Belum diajukan</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td>Peringatan Anti-Cheat</td>
                            <td>
                                @if($sesi->jumlah_peringatan > 0)
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $sesi->jumlah_peringatan }}x peringatan
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Tidak ada peringatan
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @if(!$isKepribadian && $sesi->status_verifikasi_tes)
                        <tr>
                            <td>Status Verifikasi</td>
                            <td>
                                @if($sesi->status_verifikasi_tes === 'diloloskan')
                                    <span class="badge bg-info">Diloloskan Admin</span>
                                @elseif($sesi->status_verifikasi_tes === 'menunggu')
                                    <span class="badge bg-warning">Menunggu Keputusan</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </table>
                    
                    {{-- Tombol Aksi untuk peserta tidak lulus (hanya untuk tes akademik) --}}
                    @if(!$isKepribadian && $sesi->nilai < $tes->nilai_lulus)
                        <hr>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($sesi->status_verifikasi_tes !== 'diloloskan')
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalLoloskan">
                                    <i class="bi bi-check-lg me-1"></i>Loloskan
                                </button>
                            @endif
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalUlang">
                                <i class="bi bi-arrow-repeat me-1"></i>Izinkan Ulang Tes
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @if($isMbti && $hasilMbti && isset($deskripsiMbti))
    {{-- Deskripsi MBTI --}}
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Deskripsi Tipe {{ $hasilMbti->tipe_mbti }}</h6>
        </div>
        <div class="card-body">
            <p>{{ $deskripsiMbti->deskripsi ?? '' }}</p>
            @if(isset($deskripsiMbti->kekuatan) && $deskripsiMbti->kekuatan)
            <div class="mb-2">
                <strong class="text-success"><i class="bi bi-plus-circle me-1"></i>Kekuatan:</strong>
                <span>{{ $deskripsiMbti->kekuatan }}</span>
            </div>
            @endif
            @if(isset($deskripsiMbti->kelemahan) && $deskripsiMbti->kelemahan)
            <div class="mb-2">
                <strong class="text-danger"><i class="bi bi-dash-circle me-1"></i>Kelemahan:</strong>
                <span>{{ $deskripsiMbti->kelemahan }}</span>
            </div>
            @endif
            @if(isset($deskripsiMbti->karir_cocok) && $deskripsiMbti->karir_cocok)
            <div>
                <strong class="text-primary"><i class="bi bi-briefcase me-1"></i>Karir yang Cocok:</strong>
                <span>{{ $deskripsiMbti->karir_cocok }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif
    
    {{-- Modal Loloskan --}}
    @if(!$isKepribadian && $sesi->nilai < $tes->nilai_lulus && $sesi->status_verifikasi_tes !== 'diloloskan')
    <div class="modal fade" id="modalLoloskan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.verifikasi.hasil-tes.loloskan', $sesi) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Loloskan Peserta</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Peserta <strong>{{ $sesi->peserta->nama }}</strong> mendapat nilai <strong>{{ number_format($sesi->nilai, 1) }}</strong> 
                            (di bawah nilai lulus {{ $tes->nilai_lulus }}).
                        </div>
                        <p>Apakah Anda yakin ingin meloloskan peserta ini?</p>
                        <div class="mb-3">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea class="form-control" name="catatan" rows="2" placeholder="Alasan meloloskan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i>Ya, Loloskan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
    {{-- Modal Izinkan Ulang Tes --}}
    @if(!$isKepribadian && $sesi->nilai < $tes->nilai_lulus)
    <div class="modal fade" id="modalUlang" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.hasil.izinkan-ulang', [$tes, $sesi]) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Izinkan Ulang Tes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Sesi tes akan <strong>dihapus</strong> sehingga peserta dapat mengerjakan ulang tes ini.
                        </div>
                        <p>Peserta: <strong>{{ $sesi->peserta->nama }}</strong></p>
                        <p>Tes: <strong>{{ $tes->nama }}</strong></p>
                        <p>Nilai: <span class="badge bg-danger">{{ number_format($sesi->nilai, 1) }}</span></p>
                        <p class="text-muted small">Semua jawaban peserta pada tes ini akan dihapus.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-repeat me-1"></i>Ya, Izinkan Ulang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Ringkasan Jawaban -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">Ringkasan Jawaban</h6>
        </div>
        <div class="card-body">
            @php
                $benar = $detailJawaban->where('benar', true)->count();
                $salah = $detailJawaban->where('benar', false)->count();
                $total = $detailJawaban->count();
            @endphp
            @if($isKepribadian)
                {{-- Untuk tes kepribadian, tampilkan jumlah jawaban saja --}}
                <div class="row text-center">
                    <div class="col-md-6">
                        <div class="p-3 bg-primary bg-opacity-10 rounded">
                            <h3 class="text-primary mb-0">{{ $total }}</h3>
                            <small>Total Soal Dijawab</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <h3 class="text-success mb-0">{{ $total > 0 ? '100%' : '0%' }}</h3>
                            <small>Tingkat Penyelesaian</small>
                        </div>
                    </div>
                </div>
            @else
                {{-- Untuk tes akademik, tampilkan benar/salah --}}
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <h3 class="text-success mb-0">{{ $benar }}</h3>
                            <small>Benar</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-danger bg-opacity-10 rounded">
                            <h3 class="text-danger mb-0">{{ $salah }}</h3>
                            <small>Salah</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-secondary bg-opacity-10 rounded">
                            <h3 class="text-secondary mb-0">{{ $total }}</h3>
                            <small>Total Soal</small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Detail Jawaban -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Detail Jawaban</h6>
        </div>
        <div class="card-body">
            @foreach($detailJawaban as $index => $detail)
                @if($isKepribadian)
                    {{-- Tampilan untuk tes kepribadian (tanpa benar/salah) --}}
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary">Soal {{ $index + 1 }}</span>
                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $detail['soal']->tipe)) }}</span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Pertanyaan:</strong>
                            <div class="mt-1">{!! $renderRichText($detail['soal']->pertanyaan) !!}</div>
                        </div>

                        <div>
                            <strong>Jawaban Peserta:</strong>
                            <div class="mt-1">
                                @if($detail['soal']->tipe === 'jawaban_ganda')
                                    @if($detail['jawaban_peserta']->jawaban_ganda)
                                        @foreach($detail['soal']->jawaban->whereIn('id', $detail['jawaban_peserta']->jawaban_ganda) as $jwb)
                                            <div class="text-primary">&bull; {!! $renderJawaban($jwb->isi_jawaban) !!}</div>
                                        @endforeach
                                    @else
                                        <span class="text-muted">Tidak dijawab</span>
                                    @endif
                                @else
                                    @if($detail['jawaban_peserta']->jawaban)
                                        <span class="text-primary fw-bold">{!! $renderJawaban($detail['jawaban_peserta']->jawaban->isi_jawaban) !!}</span>
                                    @else
                                        <span class="text-muted">Tidak dijawab</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Tampilan untuk tes akademik (dengan benar/salah) --}}
                    <div class="border rounded p-3 mb-3 {{ $detail['benar'] ? 'border-success bg-success bg-opacity-10' : 'border-danger bg-danger bg-opacity-10' }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge {{ $detail['benar'] ? 'bg-success' : 'bg-danger' }}">
                                Soal {{ $index + 1 }} - {{ $detail['benar'] ? 'Benar' : 'Salah' }}
                            </span>
                            @if($detail['perlu_hitung_ulang'] ?? false)
                                <span class="badge bg-warning text-dark">Perlu hitung ulang</span>
                            @endif
                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $detail['soal']->tipe)) }}</span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Pertanyaan:</strong>
                            <div class="mt-1">{!! $renderRichText($detail['soal']->pertanyaan) !!}</div>
                        </div>

                        @if(in_array($detail['soal']->tipe, ['pilihan_ganda', 'benar_salah', 'jawaban_ganda']))
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Jawaban Peserta:</strong>
                                    <div class="mt-1">
                                        @if($detail['soal']->tipe === 'jawaban_ganda')
                                            @if($detail['jawaban_peserta']->jawaban_ganda)
                                                @foreach($detail['soal']->jawaban->whereIn('id', $detail['jawaban_peserta']->jawaban_ganda) as $jwb)
                                                    <div class="{{ $detail['benar'] ? 'text-success' : 'text-danger' }}">&bull; {!! $renderJawaban($jwb->isi_jawaban) !!}</div>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Tidak dijawab</span>
                                            @endif
                                        @else
                                            @if($detail['jawaban_peserta']->jawaban)
                                                <span class="{{ $detail['benar'] ? 'text-success' : 'text-danger' }}">
                                                    {!! $renderJawaban($detail['jawaban_peserta']->jawaban->isi_jawaban) !!}
                                                </span>
                                            @else
                                                <span class="text-muted">Tidak dijawab</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Jawaban Benar:</strong>
                                    <div class="mt-1 text-success">
                                        @if($detail['soal']->tipe === 'jawaban_ganda')
                                            @foreach($detail['soal']->jawaban->where('benar', true) as $jwb)
                                                <div>&bull; {!! $renderJawaban($jwb->isi_jawaban) !!}</div>
                                            @endforeach
                                        @else
                                            {!! $detail['jawaban_benar'] ? $renderJawaban($detail['jawaban_benar']->isi_jawaban) : '<span class="text-muted">-</span>' !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($detail['perlu_hitung_ulang'] ?? false)
                                <div class="alert alert-warning py-2 mt-3 mb-0">
                                    Status tersimpan: <strong>{{ $detail['benar_tersimpan'] ? 'Benar' : 'Salah' }}</strong>.
                                    Kunci saat ini: <strong>{{ $detail['benar_terkini'] ? 'Benar' : 'Salah' }}</strong>.
                                </div>
                            @endif
                        @elseif($detail['soal']->tipe === 'esai')
                            <div>
                                <strong>Jawaban Peserta:</strong>
                                <div class="mt-1 p-2 bg-white rounded">
                                    {{ $detail['jawaban_peserta']->jawaban_esai ?? 'Tidak dijawab' }}
                                </div>
                            </div>
                        @endif

                        @if($detail['soal']->pembahasan)
                            <div class="mt-3 p-2 bg-info bg-opacity-10 rounded">
                                <strong><i class="bi bi-lightbulb"></i> Pembahasan:</strong>
                                <div class="mt-1">{!! $renderRichText($detail['soal']->pembahasan) !!}</div>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endsection
