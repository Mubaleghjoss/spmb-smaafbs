@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<div class="container-fluid py-4">
    @include('partials.periode-banner')
    <div class="row mb-4">
        <div class="col">
            <h4 class="fw-bold">Dashboard</h4>
            <p class="text-muted">Selamat datang, {{ auth('pengguna')->user()->nama }}</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="bi bi-people text-primary" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $stats['total_peserta'] }}</h3>
                            <p class="text-muted mb-0 small">Total Peserta</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="bi bi-person-plus text-success" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $stats['peserta_baru'] }}</h3>
                            <p class="text-muted mb-0 small">Peserta Hari Ini</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="bi bi-file-earmark-text text-warning" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $stats['total_tes'] }}</h3>
                            <p class="text-muted mb-0 small">Total Tes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="bi bi-question-circle text-info" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0">{{ $stats['total_soal'] }}</h3>
                            <p class="text-muted mb-0 small">Bank Soal</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Menu Cepat</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="{{ route('admin.peserta.index') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-people d-block mb-2" style="font-size: 1.5rem;"></i>
                        Kelola Peserta
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-success w-100 py-3">
                        <i class="bi bi-clipboard-check d-block mb-2" style="font-size: 1.5rem;"></i>
                        Verifikasi SPMB
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.tes.index') }}" class="btn btn-outline-warning w-100 py-3">
                        <i class="bi bi-file-earmark-text d-block mb-2" style="font-size: 1.5rem;"></i>
                        Kelola Tes
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.soal.index') }}" class="btn btn-outline-info w-100 py-3">
                        <i class="bi bi-question-circle d-block mb-2" style="font-size: 1.5rem;"></i>
                        Bank Soal
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Row 2 -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="{{ route('admin.alur-peserta.index') }}" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-signpost-split d-block mb-2" style="font-size: 1.5rem;"></i>
                        Alur Peserta
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.monitoring-ujian.index') }}" class="btn btn-outline-dark w-100 py-3">
                        <i class="bi bi-display d-block mb-2" style="font-size: 1.5rem;"></i>
                        Monitoring Ujian
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.hasil.index') }}" class="btn btn-outline-danger w-100 py-3">
                        <i class="bi bi-bar-chart d-block mb-2" style="font-size: 1.5rem;"></i>
                        Hasil Ujian
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-outline-secondary w-100 py-3">
                        <i class="bi bi-gear d-block mb-2" style="font-size: 1.5rem;"></i>
                        Pengaturan
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= KUOTA PENERIMAAN SPMB ================= --}}
    @php $k = $ringkasanKuota['kuota'] ?? null; @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Kuota Penerimaan SPMB</h5>
                <small class="text-muted">Periode {{ $ringkasanKuota['periode_label'] }}</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-success"
                        data-bs-toggle="modal" data-bs-target="#modalPenjelasanKuota">
                    <i class="bi bi-patch-question me-1"></i>Cara Masuk Kuota
                </button>
                <a href="{{ route('admin.peserta.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-people me-1"></i>Lihat Peserta
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($semuaPeriode ?? false)
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Saat ini menampilkan <strong>Semua Periode</strong>. Kuota hanya bermakna per periode —
                    pilih satu tahun ajaran pada pemilih periode di atas untuk melihat angka kuotanya.
                </div>
            @else
                {{-- Angka utama --}}
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Kuota Periode</div>
                            <div class="h4 mb-0">{{ $k['kuota_label'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100 border-success">
                            <div class="text-muted small">Dalam Kuota</div>
                            <div class="h4 mb-0 text-success">{{ number_format($k['dalam_kuota'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100 border-warning">
                            <div class="text-muted small">Waiting List</div>
                            <div class="h4 mb-0 text-warning">{{ number_format($k['waiting_list'] ?? 0) }}</div>
                            <div class="text-muted" style="font-size:.7rem">Syarat lengkap, kursi habis</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small">Belum Lengkap</div>
                            <div class="h4 mb-0 text-secondary">{{ number_format($k['belum_lengkap'] ?? 0) }}</div>
                            <div class="text-muted" style="font-size:.7rem">Formulir / bayar Tahap 3</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded-3 p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <span class="text-muted small">Sisa Kursi</span>
                                <span class="h5 mb-0 ms-2">{{ $k['sisa_label'] ?? '-' }}</span>
                                @if($k['penuh'] ?? false)
                                    <span class="badge bg-warning text-dark ms-2">Kuota Penuh</span>
                                @endif
                                @if($k['dikunci'] ?? false)
                                    <span class="badge bg-info text-dark ms-2">
                                        <i class="bi bi-lock-fill me-1"></i>Status kuota dikunci (aturan lama)
                                    </span>
                                @endif
                            </div>
                            <span class="text-muted small">Total pendaftar: {{ number_format($k['total'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Rincian jenis kelamin --}}
                <div class="row g-3 mb-4">
                    @php
                        $genderRows = [
                            ['L', 'Laki-laki', 'primary', 'gender-male', $k['laki_laki'] ?? [], (int) ($k['kuota_laki_laki'] ?? 0), $k['kuota_laki_laki_label'] ?? '-'],
                            ['P', 'Perempuan', 'danger', 'gender-female', $k['perempuan'] ?? [], (int) ($k['kuota_perempuan'] ?? 0), $k['kuota_perempuan_label'] ?? '-'],
                        ];
                    @endphp
                    @foreach($genderRows as [$kode, $label, $warna, $ikon, $data, $kuotaG, $kuotaGLabel])
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <span class="fw-semibold">
                                    <i class="bi bi-{{ $ikon }} text-{{ $warna }} me-1"></i>{{ $label }}
                                </span>
                                <span class="badge bg-{{ $warna }}">
                                    {{ number_format($data['dalam_kuota'] ?? 0) }} / {{ $kuotaGLabel }}
                                </span>
                            </div>
                            <div class="progress" style="height:8px">
                                <div class="progress-bar bg-{{ $warna }}"
                                     style="width: {{ $kuotaG > 0 ? min(100, round(($data['dalam_kuota'] ?? 0) / $kuotaG * 100)) : 0 }}%"></div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                                <span>Total: {{ number_format($data['total'] ?? 0) }}</span>
                                <span>Waiting: {{ number_format($data['waiting_list'] ?? 0) }}</span>
                                <span>Belum lengkap: {{ number_format($data['belum_lengkap'] ?? 0) }}</span>
                                @if($kuotaG > 0)
                                    <span>Sisa: {{ max(0, $kuotaG - ($data['dalam_kuota'] ?? 0)) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if(($k['belum_isi_gender']['total'] ?? 0) > 0)
                <div class="alert alert-warning py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ $k['belum_isi_gender']['total'] }} peserta belum mengisi jenis kelamin di formulir,
                    sehingga belum terhitung pada rincian laki-laki/perempuan.
                </div>
                @endif

                {{-- Pecahan per JALUR: dashboard satu-satunya halaman lintas jalur --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h6 class="mb-0"><i class="bi bi-signpost-split me-1"></i>Pendaftar per Jalur</h6>
                    <span class="badge bg-light text-dark border">Halaman kerja lain hanya menampilkan satu jalur</span>
                </div>
                <div class="row g-3 mb-4">
                    @foreach(($ringkasanKuota['per_jalur'] ?? []) as $kunci => $jalur)
                    @php $a = $jalur['angka']; @endphp
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100 border-{{ $jalur['warna'] }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <span class="fw-semibold">
                                    <i class="bi bi-{{ $jalur['ikon'] }} text-{{ $jalur['warna'] }} me-1"></i>{{ $jalur['label'] }}
                                    <span class="text-muted small ms-1">({{ $jalur['keterangan'] }})</span>
                                </span>
                                <form action="{{ route('admin.jalur-aktif.ganti') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="jenis_pendaftaran" value="{{ $kunci }}">
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $jalur['warna'] }} py-0">
                                        Kelola <i class="bi bi-arrow-right"></i>
                                    </button>
                                </form>
                            </div>

                            <div class="row g-2 text-center">
                                <div class="col-3">
                                    <div class="bg-light rounded py-2">
                                        <div class="fw-bold">{{ number_format($a['total']) }}</div>
                                        <div class="text-muted" style="font-size:.68rem">Pendaftar</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="bg-light rounded py-2">
                                        <div class="fw-bold text-success">{{ number_format($a['dalam_kuota']) }}</div>
                                        <div class="text-muted" style="font-size:.68rem">Kuota</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="bg-light rounded py-2">
                                        <div class="fw-bold text-warning">{{ number_format($a['waiting_list']) }}</div>
                                        <div class="text-muted" style="font-size:.68rem">Waiting</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="bg-light rounded py-2">
                                        <div class="fw-bold text-secondary">{{ number_format($a['belum_lengkap']) }}</div>
                                        <div class="text-muted" style="font-size:.68rem">Blm lengkap</div>
                                    </div>
                                </div>
                            </div>

                            @if(!empty($jalur['kelas']))
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($jalur['kelas'] as $kls => $ak)
                                <span class="badge bg-light text-dark border">
                                    Kelas {{ $kls }}: {{ $ak['total'] }} pendaftar
                                    &middot; {{ $ak['dalam_kuota'] }} kuota
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Rekap asal SMP / kelompok / desa / daerah --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h6 class="mb-0">Rincian Peserta Periode Ini</h6>
                    <span class="badge bg-light text-dark border">Top 10 per kelompok</span>
                </div>
                <div class="row g-3">
                    @foreach(($ringkasanKuota['rekap'] ?? []) as $bagian)
                    <div class="col-12 col-lg-6 col-xl-3">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-header bg-white d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <h6 class="mb-0 text-truncate">
                                        <i class="bi bi-{{ $bagian['ikon'] }} me-1"></i>{{ $bagian['label'] }}
                                    </h6>
                                    <small class="text-muted">{{ $bagian['total_grup'] }} kategori</small>
                                </div>
                                <span class="badge bg-primary">{{ number_format($bagian['total_peserta']) }}</span>
                            </div>
                            <div class="list-group list-group-flush">
                                @forelse($bagian['items'] as $item)
                                    <a href="{{ route('admin.peserta.index', [$bagian['param'] => $item->filter_value]) }}"
                                       class="list-group-item list-group-item-action py-2">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="min-w-0">
                                                <div class="text-truncate" title="{{ $item->nama }}">{{ $item->nama }}</div>
                                                <small class="text-muted">
                                                    L {{ (int) $item->laki_laki }} &middot; P {{ (int) $item->perempuan }}
                                                    &middot; Kuota {{ (int) $item->dalam_kuota }}
                                                    &middot; WL {{ (int) $item->waiting_list }}
                                                </small>
                                            </div>
                                            <span class="badge bg-success rounded-pill">{{ (int) $item->jumlah }}</span>
                                        </div>
                                    </a>
                                @empty
                                    <div class="list-group-item text-muted small py-3">Belum ada data.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@include('partials.modal-penjelasan-kuota')
@endsection
