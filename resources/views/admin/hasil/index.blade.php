@extends('layouts.admin')

@section('title', 'Rekap Hasil Ujian')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-clipboard-data me-2"></i>Rekap Hasil Ujian</h1>
        @if($tesList->isNotEmpty())
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.hasil.hitung-ulang-semua-psikotes') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning text-dark" onclick="return confirm('Hitung ulang semua hasil tes kepribadian (Psikotes, Gaya Belajar, MBTI, Profiling)?')">
                    <i class="bi bi-calculator me-1"></i>Hitung Ulang Semua
                </button>
            </form>
            <a href="{{ route('admin.hasil.ekspor-rekap') }}" class="btn btn-action-export">
                <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Rekap Excel
            </a>
        </div>
        @endif
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="cari" class="form-control" placeholder="Cari nama/no pendaftaran/asal sekolah..." value="{{ request('cari') }}">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Cari
                    </button>
                    @if(request()->hasAny(['cari']))
                        <a href="{{ route('admin.hasil.index') }}" class="btn btn-action-reset">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($tesList->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard-data display-1 text-muted"></i>
                <p class="mt-3 text-muted">Belum ada hasil ujian.</p>
            </div>
        </div>
    @else
        {{-- Statistik per Tes --}}
        <div class="row mb-3">
            @foreach($tesList as $tes)
            <div class="col-md-4 col-lg-3 mb-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">{{ Str::limit($tes->nama, 20) }}</small>
                                <span class="badge bg-primary">{{ $tes->peserta_selesai }} peserta</span>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">Nilai Lulus</small>
                                <div class="fw-bold text-success">{{ $tes->nilai_lulus }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Tabel Rekap --}}
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Rekap Nilai Peserta</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th style="min-width: 200px;">Nama Peserta</th>
                                <th style="min-width: 180px;">Asal Sekolah SMP</th>
                                @foreach($tesList as $tes)
                                <th class="text-center" style="min-width: 120px;">
                                    {{ Str::limit($tes->nama, 15) }}
                                    <br><small class="fw-normal opacity-75">(Lulus: {{ $tes->nilai_lulus }})</small>
                                </th>
                                @endforeach
                                <th class="text-center" style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rekapPeserta as $index => $data)
                            <tr>
                                <td class="text-center">{{ ($rekapPeserta->currentPage() - 1) * $rekapPeserta->perPage() + $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('admin.hasil.detail-peserta-rekap', $data['peserta']->id) }}" class="text-decoration-none fw-bold text-primary">
                                        {{ $data['peserta']->nama ?? '-' }}
                                    </a>
                                </td>
                                <td>
                                    {{ $data['asal_sekolah_smp'] ?? '-' }}
                                </td>
                                @foreach($tesList as $tes)
                                <td class="text-center">
                                    @if(isset($data['hasil'][$tes->id]))
                                        @php
                                            $hasil = $data['hasil'][$tes->id];
                                            $isPsikotes = isset($hasil['is_psikotes']) && $hasil['is_psikotes'];
                                            $isGayaBelajar = isset($hasil['is_gaya_belajar']) && $hasil['is_gaya_belajar'];
                                            $isMbti = isset($hasil['is_mbti']) && $hasil['is_mbti'];
                                            $isProfiling = isset($hasil['is_profiling']) && $hasil['is_profiling'];
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
                                        @if($isMbti && isset($hasil['hasil_mbti']))
                                            {{-- MBTI: hanya 1 hasil --}}
                                            <span class="badge bg-success fs-6">
                                                <i class="bi bi-diagram-3"></i> {{ $hasil['hasil_mbti'] }}
                                            </span>
                                        @elseif($isProfiling && isset($hasil['pilar_dominan']))
                                            {{-- Profiling: 1 hasil, jika sama baru 2 --}}
                                            @php
                                                $skorProfiling = $hasil['skor_profiling'] ?? [];
                                            @endphp
                                            <span class="badge bg-{{ $pilarList[$hasil['pilar_dominan']]['warna'] ?? 'secondary' }}">
                                                <i class="bi bi-{{ $pilarList[$hasil['pilar_dominan']]['icon'] ?? 'person' }}"></i> {{ $pilarList[$hasil['pilar_dominan']]['kode_qx'] ?? ucfirst($hasil['pilar_dominan']) }}: {{ $skorProfiling[$hasil['pilar_dominan']] ?? '-' }}
                                            </span>
                                            @if(isset($hasil['pilar_dominan_2']) && $hasil['pilar_dominan_2'])
                                                <span class="badge bg-{{ $pilarList[$hasil['pilar_dominan_2']]['warna'] ?? 'secondary' }}">
                                                    <i class="bi bi-{{ $pilarList[$hasil['pilar_dominan_2']]['icon'] ?? 'person' }}"></i> {{ $pilarList[$hasil['pilar_dominan_2']]['kode_qx'] ?? ucfirst($hasil['pilar_dominan_2']) }}: {{ $skorProfiling[$hasil['pilar_dominan_2']] ?? '-' }}
                                                </span>
                                            @endif
                                        @elseif($isGayaBelajar && isset($hasil['hasil_gaya_belajar']))
                                            {{-- Gaya Belajar: 1 hasil, jika sama baru 2 --}}
                                            @php
                                                $hasilTipe = explode(' & ', $hasil['hasil_gaya_belajar']);
                                                $detailNilaiGB = $hasil['detail_nilai_gb'] ?? [];
                                            @endphp
                                            @foreach($hasilTipe as $tipe)
                                                <span class="badge bg-{{ $colorsGB[$tipe] ?? 'secondary' }}">
                                                    <i class="bi bi-{{ $iconsGB[$tipe] ?? 'person' }}"></i> {{ ucfirst($tipe) }}: {{ $detailNilaiGB[$tipe] ?? '-' }}
                                                </span>
                                            @endforeach
                                        @elseif($isPsikotes && isset($hasil['hasil_kepribadian']))
                                            @php
                                                $hasilTipePsikotes = explode(' & ', $hasil['hasil_kepribadian']);
                                                $detailNilaiPsikotes = $hasil['detail_nilai_psikotes'] ?? [];
                                            @endphp
                                            @foreach($hasilTipePsikotes as $tipePsikotes)
                                                <span class="badge bg-{{ $colors[$tipePsikotes] ?? 'secondary' }}">
                                                    {{ ucfirst($tipePsikotes) }}: {{ $detailNilaiPsikotes[$tipePsikotes] ?? '-' }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="fw-bold {{ $hasil['lulus'] ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($hasil['nilai'], 1) }}
                                            </span>
                                            @if($hasil['lulus'])
                                                <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                            @else
                                                <i class="bi bi-x-circle-fill text-danger ms-1"></i>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @endforeach
                                <td class="text-center">
                                    <a href="{{ route('admin.hasil.detail-peserta-rekap', $data['peserta']->id) }}" 
                                       class="btn btn-sm btn-action-view" title="Lihat Detail">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ 4 + $tesList->count() }}" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    Tidak ada data peserta yang ditemukan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($rekapPeserta->hasPages())
            <div class="card-footer">
                {{ $rekapPeserta->links() }}
            </div>
            @endif
        </div>

        {{-- Link ke detail per tes --}}
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Lihat Detail per Tes</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($tesList as $tes)
                    <a href="{{ route('admin.hasil.show', $tes) }}" class="btn btn-action-view btn-sm">
                        <i class="bi bi-bar-chart-line me-1"></i>{{ $tes->nama }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="card mt-3">
            <div class="card-body py-2">
                <small class="text-muted">
                    <strong>Keterangan:</strong>
                    <span class="ms-3"><i class="bi bi-check-circle-fill text-success"></i> Lulus</span>
                    <span class="ms-3"><i class="bi bi-x-circle-fill text-danger"></i> Tidak Lulus</span>
                    <span class="ms-3"><span class="text-muted">-</span> Belum mengerjakan</span>
                    <br class="d-md-none">
                    <span class="ms-md-3"><span class="badge bg-danger">Koleris</span> <span class="badge bg-warning">Sanguin</span> <span class="badge bg-success">Plegmatis</span> <span class="badge bg-primary">Melankolis</span> = Hasil Psikotes</span>
                    <br class="d-md-none">
                    <span class="ms-md-3"><span class="badge bg-primary"><i class="bi bi-eye"></i> Visual</span> <span class="badge bg-success"><i class="bi bi-ear"></i> Auditori</span> <span class="badge bg-warning"><i class="bi bi-hand-index"></i> Kinestetik</span> = Hasil Gaya Belajar</span>
                    <br class="d-md-none">
                    <span class="ms-md-3"><span class="badge bg-success"><i class="bi bi-diagram-3"></i> XXXX</span> = Hasil MBTI (16 tipe kepribadian)</span>
                    <br class="d-md-none">
                    <span class="ms-md-3"><span class="badge bg-warning"><i class="bi bi-lightbulb"></i> CQ</span> <span class="badge bg-danger"><i class="bi bi-heart"></i> EQ</span> <span class="badge bg-success"><i class="bi bi-lightning"></i> AQ</span> <span class="badge bg-primary"><i class="bi bi-cpu"></i> IQ</span> <span class="badge bg-info"><i class="bi bi-stars"></i> SQ</span> = Hasil Profiling (PiES)</span>
                </small>
            </div>
        </div>
    @endif
</div>
@endsection
