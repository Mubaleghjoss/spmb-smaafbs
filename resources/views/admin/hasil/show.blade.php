@extends('layouts.admin')

@section('title', 'Hasil: ' . $tes->nama)

@section('content')
@php
    $isMbti = \App\Models\MbtiConfig::where('tes_id', $tes->id)->exists();
    $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $tes->id)->exists();
    $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $tes->id)->first();
    $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
    $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $tes->id)->first();
    $isProfiling = $profilingConfig && $profilingConfig->aktif;
    $isKepribadian = $isMbti || $isPsikotes || $isGayaBelajar || $isProfiling;
@endphp
<div class="container-fluid">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-0">Hasil: {{ $tes->nama }}</h1>
            @if($isKepribadian)
                <small class="text-muted">
                    @if($isMbti)
                        <span class="badge bg-success">Tes MBTI</span>
                    @elseif($isProfiling)
                        <span class="badge bg-primary">Tes Profiling (PiES)</span>
                    @elseif($isPsikotes)
                        <span class="badge bg-info">Tes Psikotes Kepribadian</span>
                    @elseif($isGayaBelajar)
                        <span class="badge bg-warning text-dark">Tes Gaya Belajar</span>
                    @endif
                </small>
            @else
                <small class="text-muted">Nilai Lulus: {{ $tes->nilai_lulus }}</small>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(!$isKepribadian)
            <a href="{{ route('admin.hasil.analisis', $tes) }}" class="btn btn-action-edit">
                <i class="bi bi-graph-up-arrow me-1"></i>Analisis Butir Soal
            </a>
            @endif
            <a href="{{ route('admin.hasil.ekspor', $tes) }}" class="btn btn-action-export">
                <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel
            </a>
            <a href="{{ route('admin.hasil.index') }}" class="btn btn-action-back">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Statistik -->
    @if($isKepribadian)
        {{-- Statistik untuk tes kepribadian --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Peserta</h6>
                        <h2 class="mb-0">{{ $statistik['total_peserta'] }}</h2>
                    </div>
                </div>
            </div>
            @if($isMbti)
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Distribusi Tipe MBTI</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $hasilMbtiList = \App\Models\HasilMbti::whereIn('sesi_tes_id', $sesiList->pluck('id'))->get();
                            $distribusiMbti = $hasilMbtiList->groupBy('tipe_mbti')->map->count()->sortDesc();
                        @endphp
                        @if($distribusiMbti->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($distribusiMbti as $tipe => $jumlah)
                                    <span class="badge bg-success fs-6">{{ $tipe }}: {{ $jumlah }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">Belum ada hasil</span>
                        @endif
                    </div>
                </div>
            </div>
            @elseif($isPsikotes)
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Distribusi Kepribadian</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $hasilPsikotesList = \App\Models\HasilPsikotesKepribadian::whereIn('sesi_tes_id', $sesiList->pluck('id'))->get();
                            $distribusiPsikotes = $hasilPsikotesList->groupBy('hasil_kepribadian')->map->count()->sortDesc();
                            $colorsPsikotes = ['koleris' => 'danger', 'sanguin' => 'warning', 'plegmatis' => 'success', 'melankolis' => 'primary'];
                        @endphp
                        @if($distribusiPsikotes->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($distribusiPsikotes as $tipe => $jumlah)
                                    <span class="badge bg-{{ $colorsPsikotes[$tipe] ?? 'secondary' }} fs-6">{{ ucfirst($tipe) }}: {{ $jumlah }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">Belum ada hasil</span>
                        @endif
                    </div>
                </div>
            </div>
            @elseif($isGayaBelajar)
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bi bi-book me-2"></i>Distribusi Gaya Belajar</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $hasilGBList = \App\Models\HasilGayaBelajar::whereIn('sesi_tes_id', $sesiList->pluck('id'))->get();
                            $distribusiGB = $hasilGBList->groupBy('hasil_gaya_belajar')->map->count()->sortDesc();
                            $colorsGB = ['visual' => 'primary', 'auditori' => 'success', 'kinestetik' => 'warning'];
                        @endphp
                        @if($distribusiGB->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($distribusiGB as $tipe => $jumlah)
                                    <span class="badge bg-{{ $colorsGB[strtolower($tipe)] ?? 'secondary' }} fs-6">{{ ucfirst($tipe) }}: {{ $jumlah }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">Belum ada hasil</span>
                        @endif
                    </div>
                </div>
            </div>
            @elseif($isProfiling)
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-person-gear me-2"></i>Distribusi Pilar Dominan</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $hasilProfilingList = \App\Models\HasilProfiling::whereIn('sesi_tes_id', $sesiList->pluck('id'))->get();
                            $distribusiProfiling = $hasilProfilingList->groupBy('pilar_dominan')->map->count()->sortDesc();
                            $pilarList = \App\Models\ProfilingConfig::pilarList();
                        @endphp
                        @if($distribusiProfiling->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($distribusiProfiling as $pilar => $jumlah)
                                    <span class="badge bg-{{ $pilarList[$pilar]['warna'] ?? 'secondary' }} fs-6">
                                        <i class="bi bi-{{ $pilarList[$pilar]['icon'] ?? 'person' }} me-1"></i>
                                        {{ $pilarList[$pilar]['nama'] ?? ucfirst($pilar) }} ({{ $pilarList[$pilar]['kode_qx'] ?? '' }}): {{ $jumlah }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">Belum ada hasil</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    @else
        {{-- Statistik untuk tes akademik --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Peserta</h6>
                        <h2 class="mb-0">{{ $statistik['total_peserta'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Rata-rata Nilai</h6>
                        <h2 class="mb-0">{{ $statistik['rata_rata'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Lulus</h6>
                        <h2 class="mb-0">{{ $statistik['jumlah_lulus'] }} <small>({{ $statistik['persentase_lulus'] }}%)</small></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Tidak Lulus</h6>
                        <h2 class="mb-0">{{ $statistik['jumlah_tidak_lulus'] }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Statistik Nilai</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td>Nilai Tertinggi</td>
                                <td class="text-end"><strong>{{ $statistik['nilai_tertinggi'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Nilai Terendah</td>
                                <td class="text-end"><strong>{{ $statistik['nilai_terendah'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Standar Deviasi</td>
                                <td class="text-end"><strong>{{ $statistik['standar_deviasi'] }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Distribusi Nilai</h6>
                    </div>
                    <div class="card-body">
                        @foreach($statistik['distribusi_nilai'] as $rentang => $jumlah)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>{{ $rentang }}</span>
                                <div class="d-flex align-items-center" style="width: 70%;">
                                    <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                        @php
                                            $persen = $statistik['total_peserta'] > 0 ? ($jumlah / $statistik['total_peserta']) * 100 : 0;
                                        @endphp
                                        <div class="progress-bar" style="width: {{ $persen }}%">{{ $jumlah }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Daftar Hasil -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Daftar Hasil Peserta</h6>
                @if($isMbti)
                <form method="POST" action="{{ route('admin.hasil.hitung-ulang-mbti', $tes) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-action-reset" onclick="return confirm('Hitung ulang semua hasil MBTI?')">
                        <i class="bi bi-calculator me-1"></i>Hitung Ulang MBTI
                    </button>
                </form>
                @elseif($isPsikotes)
                <form method="POST" action="{{ route('admin.hasil.hitung-ulang-psikotes', $tes) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-action-reset" onclick="return confirm('Hitung ulang semua hasil Psikotes?')">
                        <i class="bi bi-calculator me-1"></i>Hitung Ulang Psikotes
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('admin.hasil.hitung-ulang', $tes) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-action-reset" onclick="return confirm('Hitung ulang semua nilai?')">
                        <i class="bi bi-calculator me-1"></i>Hitung Ulang Nilai
                    </button>
                </form>
                @endif
            </div>
        </div>
        <div class="card-header border-top">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama/nomor pendaftaran..." value="{{ request('cari') }}">
                </div>
                @if(!$isKepribadian)
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="lulus" {{ request('status') === 'lulus' ? 'selected' : '' }}>Lulus</option>
                        <option value="tidak_lulus" {{ request('status') === 'tidak_lulus' ? 'selected' : '' }}>Tidak Lulus</option>
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    @if(request()->hasAny(['cari', 'status']))
                        <a href="{{ route('admin.hasil.show', $tes) }}" class="btn btn-sm btn-action-reset">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            @if(!$isKepribadian)
                            <th>Peringkat</th>
                            @endif
                            <th>Peserta</th>
                            <th>Hasil</th>
                            @if(!$isKepribadian)
                            <th>Status</th>
                            @endif
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sesiList as $index => $sesi)
                            @php
                                $peringkatData = isset($peringkat) ? $peringkat->firstWhere('peserta.id', $sesi->peserta_id) : null;
                                
                                // Ambil hasil kepribadian jika ada
                                $hasilMbti = $isMbti ? \App\Models\HasilMbti::where('sesi_tes_id', $sesi->id)->first() : null;
                                $hasilPsikotes = $isPsikotes ? \App\Models\HasilPsikotesKepribadian::where('sesi_tes_id', $sesi->id)->first() : null;
                                $hasilGB = $isGayaBelajar ? \App\Models\HasilGayaBelajar::where('sesi_tes_id', $sesi->id)->first() : null;
                                $hasilProfiling = $isProfiling ? \App\Models\HasilProfiling::where('sesi_tes_id', $sesi->id)->first() : null;
                            @endphp
                            <tr>
                                @if(!$isKepribadian)
                                <td>
                                    <span class="badge bg-secondary">{{ $peringkatData['peringkat'] ?? '-' }}</span>
                                </td>
                                @endif
                                <td>
                                    <strong>{{ $sesi->peserta->nama }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $sesi->peserta->nomor_pendaftaran ?? '-' }}</small>
                                </td>
                                <td>
                                    @if($isMbti && $hasilMbti)
                                        <span class="badge bg-success fs-5">
                                            <i class="bi bi-diagram-3 me-1"></i>{{ $hasilMbti->tipe_mbti }}
                                        </span>
                                        <button type="button" class="btn btn-sm btn-action-view px-2 py-1 ms-1" 
                                                data-bs-toggle="popover" 
                                                data-bs-html="true"
                                                data-bs-trigger="hover"
                                                title="Detail Skor"
                                                data-bs-content="E:{{ $hasilMbti->skor_e }} I:{{ $hasilMbti->skor_i }}<br>S:{{ $hasilMbti->skor_s }} N:{{ $hasilMbti->skor_n }}<br>T:{{ $hasilMbti->skor_t }} F:{{ $hasilMbti->skor_f }}<br>J:{{ $hasilMbti->skor_j }} P:{{ $hasilMbti->skor_p }}">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    @elseif($isPsikotes && $hasilPsikotes)
                                        @php
                                            $colorsPsikotes = ['koleris' => 'danger', 'sanguin' => 'warning', 'plegmatis' => 'success', 'melankolis' => 'primary'];
                                            $hasilTipePsikotes = explode(' & ', $hasilPsikotes->hasil_kepribadian);
                                            $detailNilaiPsikotes = $hasilPsikotes->detail_nilai ?? [];
                                        @endphp
                                        @foreach($hasilTipePsikotes as $tipePsikotes)
                                            <span class="badge bg-{{ $colorsPsikotes[$tipePsikotes] ?? 'secondary' }} fs-5">
                                                {{ ucfirst($tipePsikotes) }}: {{ $detailNilaiPsikotes[$tipePsikotes] ?? '-' }}
                                            </span>
                                        @endforeach
                                    @elseif($isGayaBelajar && $hasilGB)
                                        @php
                                            $colorsGB = ['visual' => 'primary', 'auditori' => 'success', 'kinestetik' => 'warning'];
                                        @endphp
                                        <span class="badge bg-{{ $colorsGB[strtolower($hasilGB->hasil_gaya_belajar)] ?? 'secondary' }} fs-5">
                                            {{ ucfirst($hasilGB->hasil_gaya_belajar) }}
                                        </span>
                                    @elseif($isProfiling && $hasilProfiling)
                                        @php
                                            $pilarList = \App\Models\ProfilingConfig::pilarList();
                                        @endphp
                                        <span class="badge bg-{{ $pilarList[$hasilProfiling->pilar_dominan]['warna'] ?? 'secondary' }} fs-5">
                                            <i class="bi bi-{{ $pilarList[$hasilProfiling->pilar_dominan]['icon'] ?? 'person' }} me-1"></i>
                                            {{ $pilarList[$hasilProfiling->pilar_dominan]['nama'] ?? ucfirst($hasilProfiling->pilar_dominan) }}
                                        </span>
                                        <button type="button" class="btn btn-sm btn-action-view px-2 py-1 ms-1" 
                                                data-bs-toggle="popover" 
                                                data-bs-html="true"
                                                data-bs-trigger="hover"
                                                title="Detail Skor"
                                                data-bs-content="CQ:{{ $hasilProfiling->skor_kreatif }} EQ:{{ $hasilProfiling->skor_emosional }}<br>AQ:{{ $hasilProfiling->skor_aksi }} IQ:{{ $hasilProfiling->skor_logika }}<br>SQ:{{ $hasilProfiling->skor_spiritual }}">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    @elseif($isKepribadian)
                                        <span class="badge bg-secondary">Belum dihitung</span>
                                    @else
                                        <span class="fs-5 fw-bold {{ $sesi->nilai >= $tes->nilai_lulus ? 'text-success' : 'text-danger' }}">
                                            {{ $sesi->nilai ?? '-' }}
                                        </span>
                                    @endif
                                </td>
                                @if(!$isKepribadian)
                                <td>
                                    @if($sesi->nilai >= $tes->nilai_lulus)
                                        <span class="badge bg-success">Lulus</span>
                                    @else
                                        <span class="badge bg-danger">Tidak Lulus</span>
                                    @endif
                                </td>
                                @endif
                                <td>
                                    <small>
                                        {{ $sesi->waktu_mulai->format('d/m/Y H:i') }}
                                        <br>
                                        <span class="text-muted">s/d {{ $sesi->waktu_selesai?->format('H:i') ?? '-' }}</span>
                                    </small>
                                </td>
                                <td>
                                    @if($sesi->waktu_selesai)
                                        {{ $sesi->durasi_menit_bulat }} menit
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.hasil.detail-peserta', [$tes, $sesi]) }}" class="btn btn-sm btn-action-view" title="Detail">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isKepribadian ? 5 : 7 }}" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="mt-2 text-muted">Tidak ada data hasil.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $sesiList->links() }}
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
</script>
@endpush
@endsection
