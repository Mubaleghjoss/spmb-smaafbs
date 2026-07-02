@extends('layouts.admin')

@section('title', 'Panel Verifikasi SPMB')

@section('content')
<div class="container-fluid py-4">
    <h4 class="mb-4"><i class="bi bi-clipboard-check me-2"></i>Panel Verifikasi SPMB</h4>
    
    {{-- Statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3">
                            <i class="bi bi-credit-card text-warning fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['pembayaran_menunggu'] }}</h3>
                            <small class="text-muted">Pembayaran</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-info bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-25 p-3 me-3">
                            <i class="bi bi-file-earmark-text text-info fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['formulir_menunggu'] }}</h3>
                            <small class="text-muted">Formulir</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-danger bg-opacity-25 p-3 me-3">
                            <i class="bi bi-journal-check text-danger fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['hasil_tes_menunggu'] ?? 0 }}</h3>
                            <small class="text-muted">Hasil Tes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-25 p-3 me-3">
                            <i class="bi bi-people text-primary fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['total_peserta'] }}</h3>
                            <small class="text-muted">Total Peserta</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['peserta_per_tahap'][7] ?? 0 }}</h3>
                            <small class="text-muted">Diterima</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Peserta per Tahap (Clickable) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Filter Peserta per Tahap</h5>
        </div>
        <div class="card-body">
            <div class="row text-center g-2">
                <div class="col">
                    <a href="{{ route('admin.verifikasi.index') }}" class="text-decoration-none">
                        <div class="border rounded p-3 {{ empty($filter['tahap'] ?? null) ? 'bg-primary text-white' : '' }}">
                            <h4 class="mb-1">{{ $statistik['total_peserta'] }}</h4>
                            <small>Semua</small>
                        </div>
                    </a>
                </div>
                @php
                    $tahapanLabel = [
                        1 => 'Daftar',
                        2 => 'Formulir',
                        3 => 'Bayar',
                        4 => 'Tes',
                        5 => 'Wawancara',
                        6 => 'Pelunasan',
                        7 => 'Diterima',
                    ];
                @endphp
                @for($i = 1; $i <= 7; $i++)
                <div class="col">
                    <a href="{{ route('admin.verifikasi.index', ['tahap' => $i]) }}" class="text-decoration-none">
                        <div class="border rounded p-3 {{ ($filter['tahap'] ?? null) == $i ? 'bg-primary text-white' : '' }}">
                            <h4 class="mb-1">{{ $statistik['peserta_per_tahap'][$i] ?? 0 }}</h4>
                            <small>{{ $tahapanLabel[$i] }}</small>
                        </div>
                    </a>
                </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Search & Export --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                @if(!empty($filter['tahap']))
                    <input type="hidden" name="tahap" value="{{ $filter['tahap'] }}">
                @endif
                <div class="col-md-6">
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama atau nomor pendaftaran..." value="{{ $filter['cari'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i> Cari</button>
                    <a href="{{ route('admin.verifikasi.index', ['tahap' => $filter['tahap'] ?? null]) }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </a>
                    <a href="{{ route('admin.verifikasi.ekspor-peserta', ['tahap' => $filter['tahap'] ?? null, 'cari' => $filter['cari'] ?? null]) }}" 
                       class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-1"></i>Download Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Peserta --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Daftar Peserta</h5>
            <span class="badge bg-secondary">{{ $peserta->total() }} peserta</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>Tahap</th>
                            <th>Status Tahapan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peserta as $p)
                        @php
                            $tahapSaatIni = $p->tahapanSpmb?->tahap_saat_ini ?? 1;
                        @endphp
                        <tr>
                            <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                            <td>{{ $p->nama }}</td>
                            <td>
                                <span class="badge bg-{{ $tahapSaatIni == 7 ? 'success' : 'primary' }}">
                                    Tahap {{ $tahapSaatIni }}
                                </span>
                            </td>
                            <td>
                                {{-- Status per tahap --}}
                                <div class="d-flex gap-1 flex-wrap">
                                    @for($t = 1; $t <= 7; $t++)
                                        @php
                                            $selesai = $p->tahapanSpmb?->{"tahap_{$t}_selesai"} ?? false;
                                            $aktif = $tahapSaatIni == $t;
                                        @endphp
                                        <span class="badge {{ $selesai ? 'bg-success' : ($aktif ? 'bg-warning' : 'bg-light text-dark') }}" 
                                              title="Tahap {{ $t }}: {{ $tahapanLabel[$t] }}">
                                            {{ $t }}
                                        </span>
                                    @endfor
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center flex-wrap">
                                    {{-- History/Detail Lengkap --}}
                                    <a href="{{ route('admin.verifikasi.history', $p) }}" 
                                       class="btn btn-sm btn-dark" title="Lihat semua data peserta">
                                        <i class="bi bi-clock-history me-1"></i>History
                                    </a>
                                    
                                    {{-- Tombol Verifikasi sesuai tahap yang sedang dilakukan --}}
                                    @php
                                        $bayarFormulir = $p->pembayaran->where('jenis', 'formulir')->first();
                                        $pelunasan = $p->pembayaran->where('jenis', 'pertama')->first();
                                        $sesiTes = $p->sesiTes->whereIn('status', ['selesai', 'timeout'])->first();
                                        $wawancara = $p->wawancara ?? null;
                                    @endphp
                                    
                                    @switch($tahapSaatIni)
                                        @case(2)
                                            {{-- Tahap 2: Verifikasi Formulir --}}
                                            @if($p->formulirSpmb)
                                                <a href="{{ route('admin.verifikasi.formulir.detail', $p->formulirSpmb) }}" 
                                                   class="btn btn-sm btn-{{ $p->formulirSpmb->status_verifikasi === 'menunggu' ? 'warning' : 'outline-success' }}">
                                                    <i class="bi bi-file-earmark-check me-1"></i>Verifikasi Formulir
                                                </a>
                                            @else
                                                <span class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-file-earmark-x me-1"></i>Belum Isi Formulir
                                                </span>
                                            @endif
                                            @break
                                        
                                        @case(3)
                                            {{-- Tahap 3: Verifikasi Pembayaran Formulir --}}
                                            @if($bayarFormulir)
                                                <a href="{{ route('admin.verifikasi.pembayaran-formulir') }}" 
                                                   class="btn btn-sm btn-{{ $bayarFormulir->status === 'menunggu' ? 'warning' : 'outline-success' }}">
                                                    <i class="bi bi-credit-card me-1"></i>Verifikasi Pembayaran
                                                </a>
                                            @else
                                                <span class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-credit-card me-1"></i>Belum Upload Bukti
                                                </span>
                                            @endif
                                            @break
                                        
                                        @case(4)
                                            {{-- Tahap 4: Verifikasi Hasil Tes --}}
                                            @if($sesiTes)
                                                <a href="{{ route('admin.verifikasi.hasil-tes') }}" 
                                                   class="btn btn-sm btn-{{ $sesiTes->nilai >= 60 ? 'outline-success' : 'warning' }}">
                                                    <i class="bi bi-journal-check me-1"></i>Verifikasi Tes ({{ number_format($sesiTes->nilai, 0) }})
                                                </a>
                                            @else
                                                <span class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-journal-x me-1"></i>Belum Tes
                                                </span>
                                            @endif
                                            @break
                                        
                                        @case(5)
                                            {{-- Tahap 5: Verifikasi Wawancara --}}
                                            @php $statusWawancara = $wawancara?->hasil_wawancara ?? 'belum'; @endphp
                                            @if($statusWawancara === 'lulus')
                                                <span class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check-circle me-1"></i>Lulus Wawancara
                                                </span>
                                            @elseif($statusWawancara === 'tidak_lulus')
                                                <span class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-circle me-1"></i>Tidak Lulus
                                                </span>
                                            @else
                                                <span class="btn btn-sm btn-outline-warning">
                                                    <i class="bi bi-hourglass me-1"></i>{{ $statusWawancara === 'menunggu' ? 'Menunggu Review' : 'Belum Wawancara' }}
                                                </span>
                                            @endif
                                            <a href="{{ route('admin.verifikasi.wawancara.form', $p) }}" 
                                               class="btn btn-sm {{ $statusWawancara === 'menunggu' && $wawancara?->diisi_peserta_pada ? 'btn-warning' : 'btn-primary' }}">
                                                @if($statusWawancara === 'menunggu' && $wawancara?->diisi_peserta_pada)
                                                    <i class="bi bi-clipboard-check me-1"></i>Review / Verifikasi
                                                @else
                                                    <i class="bi bi-pencil-square me-1"></i>Input Manual
                                                @endif
                                            </a>
                                            @if($wawancara?->surat_pernyataan_siswa || $wawancara?->surat_pernyataan_ortu)
                                                <a href="{{ route('admin.verifikasi.wawancara.surat-pernyataan', $p) }}" target="_blank"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-file-earmark-text me-1"></i>Surat
                                                </a>
                                            @endif
                                            @break
                                        
                                        @case(6)
                                            {{-- Tahap 6: Verifikasi Pelunasan --}}
                                            @if($pelunasan)
                                                <a href="{{ route('admin.verifikasi.pelunasan') }}" 
                                                   class="btn btn-sm btn-{{ $pelunasan->status === 'menunggu' ? 'warning' : 'outline-success' }}">
                                                    <i class="bi bi-cash-stack me-1"></i>Verifikasi Pelunasan
                                                </a>
                                            @else
                                                <span class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-cash-stack me-1"></i>Belum Upload Bukti
                                                </span>
                                            @endif
                                            @break
                                        
                                        @case(7)
                                            {{-- Tahap 7: Kelulusan --}}
                                            @php $statusKelulusan = $p->tahapanSpmb?->status_kelulusan ?? 'menunggu'; @endphp
                                            @if($statusKelulusan === 'lulus')
                                                <span class="btn btn-sm btn-success">
                                                    <i class="bi bi-trophy me-1"></i>LULUS
                                                </span>
                                            @elseif($statusKelulusan === 'tidak_lulus')
                                                <span class="btn btn-sm btn-danger">
                                                    <i class="bi bi-x-circle me-1"></i>Tidak Lulus
                                                </span>
                                            @else
                                                <a href="{{ route('admin.verifikasi.kelulusan') }}" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-award me-1"></i>Verifikasi Kelulusan
                                                </a>
                                            @endif
                                            @break
                                        
                                        @default
                                            {{-- Tahap 1: Pendaftaran --}}
                                            <span class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-person-plus me-1"></i>Baru Daftar
                                            </span>
                                    @endswitch
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada peserta ditemukan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($peserta->hasPages())
        <div class="card-footer bg-white">
            {{ $peserta->links() }}
        </div>
        @endif
    </div>

    {{-- Quick Links --}}
    <div class="row g-3 mt-4">
        <div class="col-md-4">
            <a href="{{ route('admin.verifikasi.pembayaran-formulir') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-credit-card text-warning fs-1 mb-2"></i>
                    <h6>Verifikasi Pembayaran</h6>
                    <span class="badge bg-warning">{{ $statistik['pembayaran_menunggu'] }} menunggu</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.verifikasi.formulir') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-file-earmark-text text-info fs-1 mb-2"></i>
                    <h6>Verifikasi Formulir</h6>
                    <span class="badge bg-info">{{ $statistik['formulir_menunggu'] }} menunggu</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.verifikasi.hasil-tes') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-journal-check text-danger fs-1 mb-2"></i>
                    <h6>Verifikasi Hasil Tes</h6>
                    <span class="badge bg-danger">{{ $statistik['hasil_tes_menunggu'] ?? 0 }} menunggu</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.verifikasi.wawancara') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-people text-purple fs-1 mb-2" style="color: #6f42c1;"></i>
                    <h6>Verifikasi Wawancara</h6>
                    <span class="badge" style="background-color: #6f42c1;">{{ $statistik['wawancara_menunggu'] ?? $statistik['peserta_per_tahap'][5] ?? 0 }} peserta</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.verifikasi.pelunasan') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-cash-stack text-success fs-1 mb-2"></i>
                    <h6>Verifikasi Pelunasan</h6>
                    <span class="badge bg-{{ ($statistik['pelunasan_menunggu'] ?? 0) > 0 ? 'warning' : 'success' }}">
                        {{ ($statistik['pelunasan_menunggu'] ?? 0) > 0 ? ($statistik['pelunasan_menunggu'] . ' menunggu') : 'Lihat Daftar' }}
                    </span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.verifikasi.kelulusan') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-award text-primary fs-1 mb-2"></i>
                    <h6>Verifikasi Kelulusan</h6>
                    <span class="badge bg-primary">{{ $statistik['peserta_per_tahap'][6] ?? 0 }} menunggu</span>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
