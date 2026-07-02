@extends('layouts.peserta')

@section('title', 'Daftar Ujian')

@section('content')
<div class="container py-4">
    <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
    </a>
    <h1 class="h3 mb-4">Daftar Ujian Tersedia</h1>
    <p class="text-muted mb-4">Pilih ujian yang ingin Anda kerjakan. Anda bebas memilih urutan pengerjaan.</p>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($aksesUjian) && !$aksesUjian['dibuka'])
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-lock me-2"></i>
            <strong>Tes online belum dibuka.</strong>
            <span>{{ $aksesUjian['alasan'] ?? 'Silakan tunggu jadwal dari panitia.' }}</span>
        </div>
    @endif

    <div class="row">
        @forelse($daftarTes as $item)
            <div class="col-md-6 col-lg-4 mb-4">
                @php
                    $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $item['tes']->id)->exists();
                    $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $item['tes']->id)->first();
                    $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
                    $isMbti = \App\Models\MbtiConfig::where('tes_id', $item['tes']->id)->exists();
                    $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $item['tes']->id)->first();
                    $isProfiling = $profilingConfig && $profilingConfig->aktif;
                    
                    $hasilPsikotes = ($isPsikotes && $item['sesi_selesai']) 
                        ? \App\Models\HasilPsikotesKepribadian::where('sesi_tes_id', $item['sesi_selesai']->id)->first() 
                        : null;
                    $hasilGayaBelajar = ($isGayaBelajar && $item['sesi_selesai']) 
                        ? \App\Models\HasilGayaBelajar::where('sesi_tes_id', $item['sesi_selesai']->id)->first() 
                        : null;
                    $hasilMbti = ($isMbti && $item['sesi_selesai']) 
                        ? \App\Models\HasilMbti::where('sesi_tes_id', $item['sesi_selesai']->id)->first() 
                        : null;
                    $hasilProfiling = ($isProfiling && $item['sesi_selesai']) 
                        ? \App\Models\HasilProfiling::where('sesi_tes_id', $item['sesi_selesai']->id)->first() 
                        : null;
                    
                    // Ambil daftar pilar untuk Profiling
                    $pilarList = \App\Models\ProfilingConfig::pilarList();
                    
                    // Ambil deskripsi dari config
                    $deskripsiPsikotes = null;
                    $deskripsiPsikotesList = [];
                    if ($hasilPsikotes) {
                        $hasilTipePsikotes = explode(' & ', $hasilPsikotes->hasil_kepribadian);
                        foreach ($hasilTipePsikotes as $tipePsikotes) {
                            $psikotesConfig = \App\Models\PsikotesKepribadianConfig::where('tes_id', $item['tes']->id)
                                ->where('tipe_kepribadian', $tipePsikotes)
                                ->first();
                            if ($psikotesConfig?->deskripsi) {
                                $deskripsiPsikotesList[$tipePsikotes] = $psikotesConfig->deskripsi;
                            }
                        }
                        // Untuk backward compatibility, ambil deskripsi pertama
                        $deskripsiPsikotes = !empty($deskripsiPsikotesList) ? reset($deskripsiPsikotesList) : null;
                    }
                    
                    $deskripsiGayaBelajar = null;
                    if ($hasilGayaBelajar && $gayaBelajarConfig) {
                        $hasilTipe = explode(' & ', $hasilGayaBelajar->hasil_gaya_belajar);
                        $deskripsiList = [];
                        foreach ($hasilTipe as $tipe) {
                            if (isset($gayaBelajarConfig->deskripsi_tipe[$tipe])) {
                                $deskripsiList[$tipe] = $gayaBelajarConfig->deskripsi_tipe[$tipe];
                            }
                        }
                        $deskripsiGayaBelajar = $deskripsiList;
                    }
                    
                    // Ambil deskripsi MBTI
                    $deskripsiMbti = null;
                    if ($hasilMbti) {
                        $mbtiTipeDeskripsi = \App\Models\MbtiTipeDeskripsi::where('tes_id', $item['tes']->id)
                            ->where('tipe', $hasilMbti->tipe_mbti)
                            ->first();
                        if (!$mbtiTipeDeskripsi) {
                            // Ambil dari default
                            $tipeMbtiList = \App\Models\MbtiConfig::tipeMbtiList();
                            if (isset($tipeMbtiList[$hasilMbti->tipe_mbti])) {
                                $deskripsiMbti = $tipeMbtiList[$hasilMbti->tipe_mbti];
                            }
                        } else {
                            $deskripsiMbti = [
                                'nama' => $mbtiTipeDeskripsi->nama,
                                'deskripsi' => $mbtiTipeDeskripsi->deskripsi,
                                'kekuatan' => $mbtiTipeDeskripsi->kekuatan,
                                'kelemahan' => $mbtiTipeDeskripsi->kelemahan,
                                'karir_cocok' => $mbtiTipeDeskripsi->karir_cocok,
                            ];
                        }
                    }
                        
                    $colorsPsikotes = [
                        'koleris' => 'danger',
                        'sanguin' => 'warning', 
                        'plegmatis' => 'success',
                        'melankolis' => 'primary'
                    ];
                    $colorsGB = [
                        'visual' => 'primary',
                        'auditori' => 'success', 
                        'kinestetik' => 'warning'
                    ];
                    $iconsGB = [
                        'visual' => 'eye',
                        'auditori' => 'ear',
                        'kinestetik' => 'hand-index'
                    ];
                    $iconsPsikotes = [
                        'koleris' => 'fire',
                        'sanguin' => 'emoji-smile',
                        'plegmatis' => 'peace',
                        'melankolis' => 'search'
                    ];
                    $colorsMbti = [
                        'E' => 'primary', 'I' => 'secondary',
                        'S' => 'info', 'N' => 'purple',
                        'T' => 'warning', 'F' => 'pink',
                        'J' => 'danger', 'P' => 'success'
                    ];
                    
                    // Tentukan border class
                    $borderClass = '';
                    $sudahDikerjakan = in_array($item['status'], ['lulus', 'diloloskan', 'selesai_psikotes', 'selesai_gaya_belajar', 'selesai_mbti', 'selesai_profiling']) || ($isMbti && $hasilMbti) || ($isProfiling && $hasilProfiling);
                    if ($sudahDikerjakan) {
                        $borderClass = 'border-success';
                    } elseif ($item['status'] === 'menunggu') {
                        $borderClass = 'border-warning';
                    } elseif (!$item['bisa_akses']) {
                        $borderClass = 'border-secondary';
                    }
                    
                    $cardId = 'card-' . $item['tes']->id;
                    $collapseId = 'collapse-' . $item['tes']->id;
                @endphp
                <div class="card h-100 {{ $borderClass }}" id="{{ $cardId }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $item['tes']->nama }}</h5>
                            <div class="d-flex align-items-center gap-1">
                                @if($isMbti)
                                    <span class="badge bg-success"><i class="bi bi-diagram-3"></i> MBTI</span>
                                @elseif($isProfiling)
                                    <span class="badge bg-primary"><i class="bi bi-person-gear"></i> Profiling</span>
                                @elseif($isGayaBelajar)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-lightbulb"></i> Gaya Belajar</span>
                                @elseif($isPsikotes)
                                    <span class="badge bg-info"><i class="bi bi-person-badge"></i> Psikotes</span>
                                @elseif($sudahDikerjakan)
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Selesai</span>
                                @elseif($item['status'] === 'menunggu')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                                @endif
                                
                                {{-- Tombol minimize/expand untuk hasil --}}
                                @if(($isGayaBelajar && $hasilGayaBelajar) || ($isPsikotes && $hasilPsikotes) || ($isMbti && $hasilMbti) || ($isProfiling && $hasilProfiling))
                                    <button class="btn btn-sm btn-outline-secondary p-1" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" 
                                            aria-expanded="true" aria-controls="{{ $collapseId }}"
                                            title="Tampilkan/Sembunyikan Detail">
                                        <i class="bi bi-chevron-down toggle-icon"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        
                        @if($item['tes']->keterangan)
                            <p class="card-text text-muted small">{{ Str::limit($item['tes']->keterangan, 100) }}</p>
                        @endif
                        
                        <ul class="list-unstyled small mb-3">
                            <li><i class="bi bi-clock me-2"></i>Durasi: {{ $item['tes']->durasi_menit }} menit</li>
                            <li><i class="bi bi-list-ol me-2"></i>Jumlah Soal: {{ $item['tes']->soal_count }}</li>
                        </ul>
                        
                        {{-- Tampilkan hasil gaya belajar dengan collapse --}}
                        @if($isGayaBelajar && $hasilGayaBelajar)
                            @php
                                $hasilTipe = explode(' & ', $hasilGayaBelajar->hasil_gaya_belajar);
                            @endphp
                            <div class="hasil-tes">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <strong class="small">Hasil: </strong>
                                    @foreach($hasilTipe as $tipe)
                                        <span class="badge bg-{{ $colorsGB[$tipe] ?? 'secondary' }}">
                                            <i class="bi bi-{{ $iconsGB[$tipe] ?? 'person' }}"></i> {{ ucfirst($tipe) }}
                                        </span>
                                    @endforeach
                                </div>
                                
                                {{-- Collapsible detail --}}
                                <div class="collapse show" id="{{ $collapseId }}">
                                    @if($deskripsiGayaBelajar)
                                        @foreach($deskripsiGayaBelajar as $tipe => $deskripsi)
                                            <div class="alert alert-{{ $colorsGB[$tipe] ?? 'secondary' }} py-2 px-3 mb-2 small">
                                                <div class="d-flex align-items-start">
                                                    <i class="bi bi-{{ $iconsGB[$tipe] ?? 'info-circle' }} me-2 mt-1"></i>
                                                    <div>
                                                        <strong>{{ ucfirst($tipe) }}:</strong>
                                                        <p class="mb-0 mt-1">{{ $deskripsi }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                    
                                    {{-- Detail nilai --}}
                                    @if($hasilGayaBelajar->detail_nilai)
                                        <div class="mt-2">
                                            <small class="text-muted d-block mb-1">Detail Skor:</small>
                                            @foreach($hasilGayaBelajar->detail_nilai as $tipe => $nilai)
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="small me-2" style="width: 80px;">{{ ucfirst($tipe) }}</span>
                                                    <div class="progress flex-grow-1" style="height: 8px;">
                                                        @php
                                                            $maxNilai = max($hasilGayaBelajar->detail_nilai);
                                                            $persen = $maxNilai > 0 ? ($nilai / $maxNilai) * 100 : 0;
                                                        @endphp
                                                        <div class="progress-bar bg-{{ $colorsGB[$tipe] ?? 'secondary' }}" 
                                                             style="width: {{ $persen }}%"></div>
                                                    </div>
                                                    <span class="small ms-2">{{ $nilai }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        {{-- Tampilkan hasil psikotes kepribadian dengan collapse --}}
                        @elseif($isPsikotes && $hasilPsikotes)
                            @php
                                $hasilTipePsikotes = explode(' & ', $hasilPsikotes->hasil_kepribadian);
                                $detailNilaiPsikotes = $hasilPsikotes->detail_nilai ?? [];
                            @endphp
                            <div class="hasil-tes">
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <strong class="small">Hasil: </strong>
                                    @foreach($hasilTipePsikotes as $tipePsikotes)
                                        <span class="badge bg-{{ $colorsPsikotes[$tipePsikotes] ?? 'secondary' }}">
                                            <i class="bi bi-{{ $iconsPsikotes[$tipePsikotes] ?? 'person' }}"></i>
                                            {{ ucfirst($tipePsikotes) }}: {{ $detailNilaiPsikotes[$tipePsikotes] ?? '-' }}
                                        </span>
                                    @endforeach
                                </div>
                                
                                {{-- Collapsible detail --}}
                                <div class="collapse show" id="{{ $collapseId }}">
                                    @if(!empty($deskripsiPsikotesList))
                                        @foreach($deskripsiPsikotesList as $tipePsikotes => $deskripsi)
                                            <div class="alert alert-{{ $colorsPsikotes[$tipePsikotes] ?? 'secondary' }} py-2 px-3 mb-2 small">
                                                <div class="d-flex align-items-start">
                                                    <i class="bi bi-{{ $iconsPsikotes[$tipePsikotes] ?? 'info-circle' }} me-2 mt-1"></i>
                                                    <div>
                                                        <strong>{{ ucfirst($tipePsikotes) }} ({{ $detailNilaiPsikotes[$tipePsikotes] ?? '-' }}):</strong>
                                                        <p class="mb-0 mt-1">{{ $deskripsi }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                    
                                    {{-- Detail nilai --}}
                                    @if($hasilPsikotes->detail_nilai)
                                        <div class="mt-2">
                                            <small class="text-muted d-block mb-1">Detail Skor:</small>
                                            @foreach($hasilPsikotes->detail_nilai as $tipe => $nilai)
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="small me-2" style="width: 80px;">{{ ucfirst($tipe) }}</span>
                                                    <div class="progress flex-grow-1" style="height: 8px;">
                                                        @php
                                                            $maxNilai = max($hasilPsikotes->detail_nilai);
                                                            $persen = $maxNilai > 0 ? ($nilai / $maxNilai) * 100 : 0;
                                                        @endphp
                                                        <div class="progress-bar bg-{{ $colorsPsikotes[$tipe] ?? 'secondary' }}" 
                                                             style="width: {{ $persen }}%"></div>
                                                    </div>
                                                    <span class="small ms-2">{{ $nilai }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        {{-- Tampilkan hasil MBTI dengan collapse --}}
                        @elseif($isMbti && $hasilMbti)
                            <div class="hasil-tes">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <strong class="small">Hasil: </strong>
                                    <span class="badge bg-success fs-6">
                                        <i class="bi bi-diagram-3 me-1"></i>{{ $hasilMbti->tipe_mbti }}
                                    </span>
                                    @if($deskripsiMbti)
                                        <small class="text-muted">- {{ $deskripsiMbti['nama'] ?? '' }}</small>
                                    @endif
                                </div>
                                
                                {{-- Collapsible detail --}}
                                <div class="collapse show" id="{{ $collapseId }}">
                                    @if($deskripsiMbti && isset($deskripsiMbti['deskripsi']))
                                        <div class="alert alert-success py-2 px-3 mb-2 small">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-info-circle me-2 mt-1"></i>
                                                <div>
                                                    <p class="mb-0">{{ Str::limit($deskripsiMbti['deskripsi'], 200) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    {{-- Detail skor per dimensi --}}
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">Detail Skor per Dimensi:</small>
                                        {{-- E vs I --}}
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="small me-2" style="width: 50px;">E/I</span>
                                            <div class="progress flex-grow-1" style="height: 12px;">
                                                @php
                                                    $totalEI = $hasilMbti->skor_e + $hasilMbti->skor_i;
                                                    $pctE = $totalEI > 0 ? ($hasilMbti->skor_e / $totalEI * 100) : 50;
                                                @endphp
                                                <div class="progress-bar bg-primary" style="width: {{ $pctE }}%">E</div>
                                                <div class="progress-bar bg-secondary" style="width: {{ 100 - $pctE }}%">I</div>
                                            </div>
                                            <span class="small ms-2 fw-bold">{{ $hasilMbti->skor_i > $hasilMbti->skor_e ? 'I' : 'E' }}</span>
                                        </div>
                                        {{-- S vs N --}}
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="small me-2" style="width: 50px;">S/N</span>
                                            <div class="progress flex-grow-1" style="height: 12px;">
                                                @php
                                                    $totalSN = $hasilMbti->skor_s + $hasilMbti->skor_n;
                                                    $pctS = $totalSN > 0 ? ($hasilMbti->skor_s / $totalSN * 100) : 50;
                                                @endphp
                                                <div class="progress-bar bg-info" style="width: {{ $pctS }}%">S</div>
                                                <div class="progress-bar bg-secondary" style="width: {{ 100 - $pctS }}%">N</div>
                                            </div>
                                            <span class="small ms-2 fw-bold">{{ $hasilMbti->skor_n > $hasilMbti->skor_s ? 'N' : 'S' }}</span>
                                        </div>
                                        {{-- T vs F --}}
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="small me-2" style="width: 50px;">T/F</span>
                                            <div class="progress flex-grow-1" style="height: 12px;">
                                                @php
                                                    $totalTF = $hasilMbti->skor_t + $hasilMbti->skor_f;
                                                    $pctT = $totalTF > 0 ? ($hasilMbti->skor_t / $totalTF * 100) : 50;
                                                @endphp
                                                <div class="progress-bar bg-warning" style="width: {{ $pctT }}%">T</div>
                                                <div class="progress-bar bg-secondary" style="width: {{ 100 - $pctT }}%">F</div>
                                            </div>
                                            <span class="small ms-2 fw-bold">{{ $hasilMbti->skor_f > $hasilMbti->skor_t ? 'F' : 'T' }}</span>
                                        </div>
                                        {{-- J vs P --}}
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="small me-2" style="width: 50px;">J/P</span>
                                            <div class="progress flex-grow-1" style="height: 12px;">
                                                @php
                                                    $totalJP = $hasilMbti->skor_j + $hasilMbti->skor_p;
                                                    $pctJ = $totalJP > 0 ? ($hasilMbti->skor_j / $totalJP * 100) : 50;
                                                @endphp
                                                <div class="progress-bar bg-danger" style="width: {{ $pctJ }}%">J</div>
                                                <div class="progress-bar bg-secondary" style="width: {{ 100 - $pctJ }}%">P</div>
                                            </div>
                                            <span class="small ms-2 fw-bold">{{ $hasilMbti->skor_p > $hasilMbti->skor_j ? 'P' : 'J' }}</span>
                                        </div>
                                    </div>
                                    
                                    {{-- Karir yang cocok --}}
                                    @if($deskripsiMbti && isset($deskripsiMbti['karir_cocok']))
                                        <div class="mt-2">
                                            <small class="text-muted d-block mb-1"><i class="bi bi-briefcase me-1"></i>Karir Cocok:</small>
                                            <small class="text-success">{{ Str::limit($deskripsiMbti['karir_cocok'], 100) }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        {{-- Tampilkan hasil Profiling dengan collapse --}}
                        @elseif($isProfiling && $hasilProfiling)
                            @php
                                $pilarDominan = $hasilProfiling->pilar_dominan;
                                $skorArray = $hasilProfiling->getSkorArray();
                                $maxSkor = max($skorArray);
                            @endphp
                            <div class="hasil-tes">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <strong class="small">Hasil: </strong>
                                    <span class="badge bg-{{ $pilarList[$pilarDominan]['warna'] ?? 'primary' }} fs-6">
                                        <i class="bi bi-{{ $pilarList[$pilarDominan]['icon'] ?? 'person' }} me-1"></i>
                                        {{ $pilarList[$pilarDominan]['nama'] ?? ucfirst($pilarDominan) }}
                                    </span>
                                    <small class="text-muted">- {{ $pilarList[$pilarDominan]['kode_qx'] ?? '' }}</small>
                                </div>
                                
                                {{-- Collapsible detail --}}
                                <div class="collapse show" id="{{ $collapseId }}">
                                    @if(isset($pilarList[$pilarDominan]['deskripsi']))
                                        <div class="alert alert-{{ $pilarList[$pilarDominan]['warna'] ?? 'primary' }} py-2 px-3 mb-2 small">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-{{ $pilarList[$pilarDominan]['icon'] ?? 'info-circle' }} me-2 mt-1"></i>
                                                <div>
                                                    <p class="mb-0">{{ Str::limit($pilarList[$pilarDominan]['deskripsi'], 200) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    {{-- Detail skor per pilar --}}
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">Detail Skor per Pilar:</small>
                                        @foreach($pilarList as $pilar => $info)
                                            @php
                                                $skor = $skorArray[$pilar] ?? 0;
                                                $persen = $maxSkor > 0 ? ($skor / $maxSkor) * 100 : 0;
                                            @endphp
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="small me-2" style="width: 60px;">
                                                    <i class="bi bi-{{ $info['icon'] }} text-{{ $info['warna'] }}"></i> {{ $info['kode_qx'] }}
                                                </span>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-{{ $info['warna'] }}" style="width: {{ $persen }}%"></div>
                                                </div>
                                                <span class="small ms-2 {{ $pilar === $pilarDominan ? 'fw-bold text-' . $info['warna'] : '' }}">{{ $skor }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    {{-- Kekuatan --}}
                                    @if(isset($pilarList[$pilarDominan]['kekuatan']))
                                        <div class="mt-2">
                                            <small class="text-muted d-block mb-1"><i class="bi bi-star me-1"></i>Kekuatan:</small>
                                            <small class="text-{{ $pilarList[$pilarDominan]['warna'] ?? 'primary' }}">{{ Str::limit($pilarList[$pilarDominan]['kekuatan'], 100) }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-transparent">
                        @if($item['sesi_aktif'])
                            <a href="{{ route('ujian.kerjakan', $item['sesi_aktif']) }}" class="btn btn-warning w-100">
                                <i class="bi bi-play-fill me-1"></i> Lanjutkan {{ $item['tes']->nama }}
                            </a>
                        @elseif($item['bisa_akses'])
                            @if($isMbti)
                            <a href="{{ route('ujian.konfirmasi', $item['tes']) }}" class="btn btn-success w-100">
                                <i class="bi bi-diagram-3 me-1"></i> Mulai {{ $item['tes']->nama }}
                            </a>
                            @elseif($isProfiling)
                            <a href="{{ route('ujian.konfirmasi', $item['tes']) }}" class="btn btn-primary w-100">
                                <i class="bi bi-person-gear me-1"></i> Mulai {{ $item['tes']->nama }}
                            </a>
                            @elseif($isGayaBelajar)
                            <a href="{{ route('ujian.konfirmasi', $item['tes']) }}" class="btn btn-warning w-100">
                                <i class="bi bi-lightbulb me-1"></i> Mulai {{ $item['tes']->nama }}
                            </a>
                            @elseif($isPsikotes)
                            <a href="{{ route('ujian.konfirmasi', $item['tes']) }}" class="btn btn-info w-100">
                                <i class="bi bi-person-badge me-1"></i> Mulai {{ $item['tes']->nama }}
                            </a>
                            @else
                            <a href="{{ route('ujian.konfirmasi', $item['tes']) }}" class="btn btn-primary w-100">
                                <i class="bi bi-pencil-square me-1"></i> Mulai {{ $item['tes']->nama }}
                            </a>
                            @endif
                        @elseif($sudahDikerjakan)
                            <div class="d-flex gap-2">
                                <button class="btn btn-success flex-grow-1" disabled>
                                    <i class="bi bi-check-circle me-1"></i> Selesai
                                </button>
                                @if($item['sesi_selesai'])
                                <a href="{{ route('ujian.hasil', $item['sesi_selesai']) }}" class="btn btn-outline-primary" title="Lihat Detail Hasil">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                            </div>
                        @elseif($item['status'] === 'menunggu')
                            <button class="btn btn-warning w-100" disabled>
                                <i class="bi bi-hourglass-split me-1"></i> Menunggu Keputusan Admin
                            </button>
                        @elseif($item['status'] === 'kerjakan_ulang')
                            <a href="{{ route('ujian.konfirmasi', $item['tes']) }}" class="btn btn-info w-100">
                                <i class="bi bi-arrow-repeat me-1"></i> Kerjakan Ulang
                            </a>
                        @else
                            <button class="btn btn-secondary w-100" disabled>
                                {{ $item['pesan'] }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Tidak ada ujian yang tersedia saat ini. Pastikan Anda sudah terdaftar di grup yang benar.
                </div>
            </div>
        @endforelse
    </div>
</div>

@push('styles')
<style>
    .hasil-tes .collapse.show + .toggle-icon,
    .hasil-tes .collapsing + .toggle-icon {
        transform: rotate(180deg);
    }
    
    .toggle-icon {
        transition: transform 0.3s ease;
    }
    
    [data-bs-toggle="collapse"][aria-expanded="false"] .toggle-icon {
        transform: rotate(0deg);
    }
    
    [data-bs-toggle="collapse"][aria-expanded="true"] .toggle-icon {
        transform: rotate(180deg);
    }
    
    .hasil-tes .alert {
        border-left-width: 4px;
    }
    
    .hasil-tes .progress {
        background-color: #e9ecef;
    }
</style>
@endpush

@push('scripts')
<script>
    // Toggle icon rotation on collapse
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(button) {
        button.addEventListener('click', function() {
            const icon = this.querySelector('.toggle-icon');
            if (icon) {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                // Icon will be rotated via CSS based on aria-expanded
            }
        });
    });
</script>
@endpush
@endsection
