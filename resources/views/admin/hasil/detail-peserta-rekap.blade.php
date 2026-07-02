@extends('layouts.admin')

@section('title', 'Detail Hasil - ' . $peserta->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.hasil.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Rekap
            </a>
            <h1 class="h3 mb-0">Detail Hasil Ujian</h1>
        </div>
    </div>

    {{-- Info Peserta --}}
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Informasi Peserta</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td width="150" class="text-muted">Nama Lengkap</td>
                            <td><strong>{{ $peserta->nama }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">No. Pendaftaran</td>
                            <td><code class="fs-5">{{ $peserta->nomor_pendaftaran }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>{{ $peserta->email ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td width="150" class="text-muted">No. HP</td>
                            <td>{{ $peserta->telepon ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Tes</td>
                            <td><span class="badge bg-primary fs-6">{{ $sesiList->count() }} tes</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Rata-rata Nilai</td>
                            <td>
                                @php $rataRata = $sesiList->avg('nilai'); @endphp
                                <span class="fw-bold fs-5 {{ $rataRata >= 70 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($rataRata, 1) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Hasil Tes --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Daftar Hasil Tes</h5>
        </div>
        <div class="card-body p-0">
            @if($sesiList->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-4 d-block mb-2"></i>
                    Peserta belum mengerjakan tes apapun.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">#</th>
                                <th>Nama Tes</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Nilai Lulus</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Benar/Total</th>
                                <th class="text-center">Peringatan</th>
                                <th class="text-center">Waktu Mulai</th>
                                <th class="text-center">Waktu Selesai</th>
                                <th class="text-center">Durasi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sesiList as $index => $sesi)
                            @php
                                $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $sesi->tes_id)->exists();
                                $hasilPsikotes = $isPsikotes ? \App\Models\HasilPsikotesKepribadian::where('sesi_tes_id', $sesi->id)->first() : null;
                                
                                $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $sesi->tes_id)->first();
                                $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
                                $hasilGayaBelajar = $isGayaBelajar ? \App\Models\HasilGayaBelajar::where('sesi_tes_id', $sesi->id)->first() : null;
                                
                                $isMbti = \App\Models\MbtiConfig::where('tes_id', $sesi->tes_id)->exists();
                                $hasilMbti = $isMbti ? \App\Models\HasilMbti::where('sesi_tes_id', $sesi->id)->first() : null;
                                
                                $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $sesi->tes_id)->first();
                                $isProfiling = $profilingConfig && $profilingConfig->aktif;
                                $hasilProfiling = $isProfiling ? \App\Models\HasilProfiling::where('sesi_tes_id', $sesi->id)->first() : null;
                                
                                $colors = [
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
                                $pilarList = \App\Models\ProfilingConfig::pilarList();
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $sesi->tes->nama }}</strong>
                                    @if($isMbti)
                                        <span class="badge bg-success ms-1">MBTI</span>
                                    @elseif($isProfiling)
                                        <span class="badge bg-primary ms-1">Profiling</span>
                                    @elseif($isGayaBelajar)
                                        <span class="badge bg-warning text-dark ms-1">Gaya Belajar</span>
                                    @elseif($isPsikotes)
                                        <span class="badge bg-info ms-1">Psikotes</span>
                                    @endif
                                    @if($sesi->tes->keterangan)
                                    <br><small class="text-muted">{{ Str::limit($sesi->tes->keterangan, 50) }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isMbti && $hasilMbti)
                                        {{-- Tampilkan hasil MBTI --}}
                                        <span class="badge bg-success fs-5">
                                            <i class="bi bi-diagram-3 me-1"></i>{{ $hasilMbti->tipe_mbti }}
                                        </span>
                                    @elseif($isProfiling && $hasilProfiling)
                                        {{-- Tampilkan hasil Profiling --}}
                                        <span class="badge bg-{{ $pilarList[$hasilProfiling->pilar_dominan]['warna'] ?? 'primary' }} fs-5">
                                            <i class="bi bi-{{ $pilarList[$hasilProfiling->pilar_dominan]['icon'] ?? 'person' }} me-1"></i>
                                            {{ $pilarList[$hasilProfiling->pilar_dominan]['nama'] ?? ucfirst($hasilProfiling->pilar_dominan) }}
                                        </span>
                                    @elseif($isGayaBelajar && $hasilGayaBelajar)
                                        {{-- Tampilkan hasil gaya belajar --}}
                                        @php
                                            $hasilTipe = explode(' & ', $hasilGayaBelajar->hasil_gaya_belajar);
                                        @endphp
                                        @foreach($hasilTipe as $tipe)
                                            <span class="badge bg-{{ $colorsGB[$tipe] ?? 'secondary' }} fs-6">
                                                <i class="bi bi-{{ $iconsGB[$tipe] ?? 'person' }}"></i> {{ ucfirst($tipe) }}
                                            </span>
                                        @endforeach
                                    @elseif($isPsikotes && $hasilPsikotes)
                                        {{-- Tampilkan hasil kepribadian (2 tertinggi dengan nilai) --}}
                                        @php
                                            $hasilTipePsikotes = explode(' & ', $hasilPsikotes->hasil_kepribadian);
                                            $detailNilaiPsikotes = $hasilPsikotes->detail_nilai ?? [];
                                        @endphp
                                        @foreach($hasilTipePsikotes as $tipePsikotes)
                                            <span class="badge bg-{{ $colors[$tipePsikotes] ?? 'secondary' }} fs-6">
                                                {{ ucfirst($tipePsikotes) }}: {{ $detailNilaiPsikotes[$tipePsikotes] ?? '-' }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="fw-bold fs-4 {{ $sesi->nilai >= $sesi->tes->nilai_lulus ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($sesi->nilai, 1) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isMbti || $isGayaBelajar || $isPsikotes || $isProfiling)
                                        <span class="text-muted">-</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $sesi->tes->nilai_lulus }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isMbti && $hasilMbti)
                                        {{-- Detail nilai MBTI --}}
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                data-bs-toggle="popover" 
                                                data-bs-html="true"
                                                data-bs-trigger="hover"
                                                title="Detail Skor MBTI"
                                                data-bs-content="<span class='badge bg-primary me-1'>E: {{ $hasilMbti->skor_e }}</span><span class='badge bg-secondary me-1'>I: {{ $hasilMbti->skor_i }}</span><br><span class='badge bg-info me-1'>S: {{ $hasilMbti->skor_s }}</span><span class='badge bg-secondary me-1'>N: {{ $hasilMbti->skor_n }}</span><br><span class='badge bg-warning text-dark me-1'>T: {{ $hasilMbti->skor_t }}</span><span class='badge bg-secondary me-1'>F: {{ $hasilMbti->skor_f }}</span><br><span class='badge bg-danger me-1'>J: {{ $hasilMbti->skor_j }}</span><span class='badge bg-secondary me-1'>P: {{ $hasilMbti->skor_p }}</span>">
                                            <i class="bi bi-info-circle"></i> Detail
                                        </button>
                                    @elseif($isProfiling && $hasilProfiling)
                                        {{-- Detail nilai Profiling --}}
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="popover" 
                                                data-bs-html="true"
                                                data-bs-trigger="hover"
                                                title="Detail Skor Profiling"
                                                data-bs-content="<span class='badge bg-warning me-1'>CQ: {{ $hasilProfiling->skor_kreatif }}</span><span class='badge bg-danger me-1'>EQ: {{ $hasilProfiling->skor_emosional }}</span><br><span class='badge bg-success me-1'>AQ: {{ $hasilProfiling->skor_aksi }}</span><span class='badge bg-primary me-1'>IQ: {{ $hasilProfiling->skor_logika }}</span><br><span class='badge bg-info me-1'>SQ: {{ $hasilProfiling->skor_spiritual }}</span>">
                                            <i class="bi bi-info-circle"></i> Detail
                                        </button>
                                    @elseif($isGayaBelajar && $hasilGayaBelajar)
                                        {{-- Detail nilai gaya belajar --}}
                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                data-bs-toggle="popover" 
                                                data-bs-html="true"
                                                data-bs-trigger="hover"
                                                title="Detail Nilai"
                                                data-bs-content="@foreach($hasilGayaBelajar->detail_nilai as $tipe => $nilai)<span class='badge bg-{{ $colorsGB[$tipe] ?? 'secondary' }} me-1'><i class='bi bi-{{ $iconsGB[$tipe] ?? 'person' }}'></i> {{ ucfirst($tipe) }}: {{ $nilai }}</span>@endforeach">
                                            <i class="bi bi-info-circle"></i> Detail
                                        </button>
                                    @elseif($isPsikotes && $hasilPsikotes)
                                        {{-- Detail nilai psikotes --}}
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="popover" 
                                                data-bs-html="true"
                                                data-bs-trigger="hover"
                                                title="Detail Nilai"
                                                data-bs-content="@foreach($hasilPsikotes->detail_nilai as $tipe => $nilai)<span class='badge bg-{{ $colors[$tipe] ?? 'secondary' }} me-1'>{{ ucfirst($tipe) }}: {{ $nilai }}</span>@endforeach">
                                            <i class="bi bi-info-circle"></i> Detail
                                        </button>
                                    @elseif($sesi->nilai >= $sesi->tes->nilai_lulus)
                                        <span class="badge bg-success fs-6">
                                            <i class="bi bi-check-circle me-1"></i>LULUS
                                        </span>
                                    @else
                                        <span class="badge bg-danger fs-6">
                                            <i class="bi bi-x-circle me-1"></i>TIDAK LULUS
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $totalSoal = $sesi->jawabanPeserta->count();
                                        $benar = $sesi->jawabanPeserta->where('benar', true)->count();
                                    @endphp
                                    <span class="badge bg-info">{{ $benar }}/{{ $totalSoal }}</span>
                                </td>
                                <td class="text-center">
                                    @if($sesi->jumlah_peringatan > 0)
                                        <span class="badge bg-danger" title="Jumlah peringatan anti-cheat">
                                            <i class="bi bi-exclamation-triangle me-1"></i>{{ $sesi->jumlah_peringatan }}x
                                        </span>
                                    @else
                                        <span class="badge bg-success" title="Tidak ada peringatan">
                                            <i class="bi bi-check-circle"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <small>{{ $sesi->waktu_mulai?->format('d/m/Y H:i') ?? '-' }}</small>
                                </td>
                                <td class="text-center">
                                    <small>{{ $sesi->waktu_selesai?->format('d/m/Y H:i') ?? '-' }}</small>
                                </td>
                                <td class="text-center">
                                    @if($sesi->waktu_mulai && $sesi->waktu_selesai)
                                        <small>{{ $sesi->durasi_menit_bulat }} menit</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.hasil.detail-peserta', [$sesi->tes, $sesi]) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Lihat Detail Jawaban">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Ringkasan --}}
    @if($sesiList->isNotEmpty())
    @php
        // Warna untuk psikotes dan gaya belajar
        $colors = [
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
        
        // Filter hanya tes akademik (bukan MBTI, Psikotes, Gaya Belajar)
        $tesAkademik = $sesiList->filter(function($s) {
            $isMbti = \App\Models\MbtiConfig::where('tes_id', $s->tes_id)->exists();
            $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $s->tes_id)->exists();
            $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $s->tes_id)->first();
            $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
            $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $s->tes_id)->first();
            $isProfiling = $profilingConfig && $profilingConfig->aktif;
            return !$isMbti && !$isPsikotes && !$isGayaBelajar && !$isProfiling;
        });
        
        $tesKepribadian = $sesiList->filter(function($s) {
            $isMbti = \App\Models\MbtiConfig::where('tes_id', $s->tes_id)->exists();
            $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $s->tes_id)->exists();
            $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $s->tes_id)->first();
            $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
            $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $s->tes_id)->first();
            $isProfiling = $profilingConfig && $profilingConfig->aktif;
            return $isMbti || $isPsikotes || $isGayaBelajar || $isProfiling;
        });
    @endphp
    <div class="row mt-4">
        @if($tesAkademik->isNotEmpty())
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle text-success display-6"></i>
                    <h3 class="mt-2 mb-0 text-success">{{ $tesAkademik->filter(fn($s) => $s->nilai >= $s->tes->nilai_lulus)->count() }}</h3>
                    <small class="text-muted">Tes Lulus</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="bi bi-x-circle text-danger display-6"></i>
                    <h3 class="mt-2 mb-0 text-danger">{{ $tesAkademik->filter(fn($s) => $s->nilai < $s->tes->nilai_lulus)->count() }}</h3>
                    <small class="text-muted">Tes Tidak Lulus</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up text-primary display-6"></i>
                    <h3 class="mt-2 mb-0 text-primary">{{ number_format($tesAkademik->max('nilai'), 1) }}</h3>
                    <small class="text-muted">Nilai Tertinggi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-graph-down text-warning display-6"></i>
                    <h3 class="mt-2 mb-0 text-warning">{{ number_format($tesAkademik->min('nilai'), 1) }}</h3>
                    <small class="text-muted">Nilai Terendah</small>
                </div>
            </div>
        </div>
        @else
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>Peserta hanya mengerjakan tes kepribadian (MBTI/Psikotes/Gaya Belajar).
            </div>
        </div>
        @endif
    </div>
    
    {{-- Ringkasan Tes Kepribadian --}}
    @if($tesKepribadian->isNotEmpty())
    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Hasil Tes Kepribadian</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($tesKepribadian as $sesiKep)
                            @php
                                $isMbti = \App\Models\MbtiConfig::where('tes_id', $sesiKep->tes_id)->exists();
                                $hasilMbti = $isMbti ? \App\Models\HasilMbti::where('sesi_tes_id', $sesiKep->id)->first() : null;
                                
                                $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $sesiKep->tes_id)->exists();
                                $hasilPsikotes = $isPsikotes ? \App\Models\HasilPsikotesKepribadian::where('sesi_tes_id', $sesiKep->id)->first() : null;
                                
                                $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $sesiKep->tes_id)->first();
                                $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
                                $hasilGayaBelajar = $isGayaBelajar ? \App\Models\HasilGayaBelajar::where('sesi_tes_id', $sesiKep->id)->first() : null;
                                
                                $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $sesiKep->tes_id)->first();
                                $isProfiling = $profilingConfig && $profilingConfig->aktif;
                                $hasilProfiling = $isProfiling ? \App\Models\HasilProfiling::where('sesi_tes_id', $sesiKep->id)->first() : null;
                                
                                $pilarList = \App\Models\ProfilingConfig::pilarList();
                            @endphp
                            <div class="border rounded p-2">
                                <small class="text-muted d-block">{{ $sesiKep->tes->nama }}</small>
                                @if($isMbti && $hasilMbti)
                                    <span class="badge bg-success fs-6"><i class="bi bi-diagram-3 me-1"></i>{{ $hasilMbti->tipe_mbti }}</span>
                                @elseif($isProfiling && $hasilProfiling)
                                    <span class="badge bg-{{ $pilarList[$hasilProfiling->pilar_dominan]['warna'] ?? 'primary' }} fs-6">
                                        <i class="bi bi-{{ $pilarList[$hasilProfiling->pilar_dominan]['icon'] ?? 'person' }} me-1"></i>
                                        {{ $pilarList[$hasilProfiling->pilar_dominan]['nama'] ?? ucfirst($hasilProfiling->pilar_dominan) }}
                                    </span>
                                @elseif($isPsikotes && $hasilPsikotes)
                                    @php
                                        $hasilTipePsikotes = explode(' & ', $hasilPsikotes->hasil_kepribadian);
                                        $detailNilaiPsikotes = $hasilPsikotes->detail_nilai ?? [];
                                    @endphp
                                    @foreach($hasilTipePsikotes as $tipePsikotes)
                                        <span class="badge bg-{{ $colors[$tipePsikotes] ?? 'secondary' }} fs-6">{{ ucfirst($tipePsikotes) }}: {{ $detailNilaiPsikotes[$tipePsikotes] ?? '-' }}</span>
                                    @endforeach
                                @elseif($isGayaBelajar && $hasilGayaBelajar)
                                    <span class="badge bg-{{ $colorsGB[strtolower($hasilGayaBelajar->hasil_gaya_belajar)] ?? 'secondary' }} fs-6">{{ ucfirst($hasilGayaBelajar->hasil_gaya_belajar) }}</span>
                                @else
                                    <span class="badge bg-secondary">Belum dihitung</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
</script>
@endpush
@endsection
