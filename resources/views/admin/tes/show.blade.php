@extends('layouts.admin')

@section('title', 'Detail Tes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $tes->nama }}</h1>
        <div class="btn-group">
            @if($tes->soal->count() > 0)
            <a href="{{ route('admin.soal.preview', ['tes_id' => $tes->id]) }}" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i> Preview Soal
            </a>
            @endif
            <a href="{{ route('admin.tes.edit', $tes) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <form action="{{ route('admin.tes.duplikat', $tes) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-copy me-1"></i> Duplikat
                </button>
            </form>
            <a href="{{ route('admin.tes.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

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

    <div class="row">
        <div class="col-md-8">
            <!-- Info Tes -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Informasi Tes</h5>
                    <div>
                        @if($tes->status === 'draft')
                            <span class="badge bg-secondary fs-6">Draft</span>
                        @elseif($tes->status === 'aktif')
                            @if($tes->sedangBerlangsung())
                                <span class="badge bg-success fs-6">Sedang Berlangsung</span>
                            @else
                                <span class="badge bg-primary fs-6">Aktif</span>
                            @endif
                        @else
                            <span class="badge bg-dark fs-6">Selesai</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($tes->keterangan)
                        <p class="text-muted">{{ $tes->keterangan }}</p>
                        <hr>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">Durasi</td>
                                    <td><strong>{{ $tes->durasi_menit }} menit</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nilai Lulus</td>
                                    <td><strong>{{ number_format($tes->nilai_lulus, 0) }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Waktu Mulai</td>
                                    <td>{{ $tes->mulai?->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Waktu Selesai</td>
                                    <td>{{ $tes->selesai?->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" width="50%">Acak Soal</td>
                                    <td>
                                        @if($tes->acak_soal)
                                            <i class="bi bi-check-circle text-success"></i> Ya
                                        @else
                                            <i class="bi bi-x-circle text-muted"></i> Tidak
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Acak Jawaban</td>
                                    <td>
                                        @if($tes->acak_jawaban)
                                            <i class="bi bi-check-circle text-success"></i> Ya
                                        @else
                                            <i class="bi bi-x-circle text-muted"></i> Tidak
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tampilkan Nilai</td>
                                    <td>
                                        @if($tes->tampilkan_nilai)
                                            <i class="bi bi-check-circle text-success"></i> Ya
                                        @else
                                            <i class="bi bi-x-circle text-muted"></i> Tidak
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tampilkan Pembahasan</td>
                                    <td>
                                        @if($tes->tampilkan_pembahasan)
                                            <i class="bi bi-check-circle text-success"></i> Ya
                                        @else
                                            <i class="bi bi-x-circle text-muted"></i> Tidak
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        @if($tes->status === 'draft')
                            <form action="{{ route('admin.tes.ubah-status', $tes) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="aktif">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-play-fill me-1"></i> Aktifkan Tes
                                </button>
                            </form>
                        @elseif($tes->status === 'aktif')
                            <form action="{{ route('admin.tes.ubah-status', $tes) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="selesai">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="bi bi-stop-fill me-1"></i> Akhiri Tes
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.tes.ubah-status', $tes) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="draft">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Kembalikan ke Draft
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Daftar Soal -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Soal ({{ $statistik['jumlah_soal'] }})</h5>
                    <a href="{{ route('admin.tes.soal', $tes) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-gear me-1"></i> Atur Soal
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($tes->soal->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Pertanyaan</th>
                                        <th class="text-center">Tipe</th>
                                        <th class="text-center">Bobot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tes->soal as $soal)
                                        <tr>
                                            <td>{{ $soal->pivot->urutan }}</td>
                                            <td>
                                                {!! Str::limit(strip_tags($soal->pertanyaan), 80) !!}
                                                @if($soal->topik)
                                                    <br><small class="text-muted">{{ $soal->topik->nama }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ ucfirst($soal->tipe) }}</span>
                                            </td>
                                            <td class="text-center">
                                                {{ $soal->pivot->bobot_custom ?? $soal->bobot }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            Belum ada soal. <a href="{{ route('admin.tes.soal', $tes) }}">Tambah soal</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Statistik -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Statistik</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <div class="h4 mb-0">{{ $statistik['jumlah_soal'] }}</div>
                                <small class="text-muted">Soal</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('admin.tes.sesi', [$tes, 'status' => 'semua']) }}" class="text-decoration-none">
                                <div class="border rounded p-3 text-center bg-light">
                                    <div class="h4 mb-0 text-primary">{{ $statistik['jumlah_peserta'] }}</div>
                                    <small class="text-muted">Peserta</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('admin.tes.sesi', [$tes, 'status' => 'berlangsung']) }}" class="text-decoration-none">
                                <div class="border rounded p-3 text-center bg-success bg-opacity-10">
                                    <div class="h4 mb-0 text-success">{{ $statistik['sesi_berlangsung'] }}</div>
                                    <small class="text-muted">Sedang Ujian</small>
                                    <i class="bi bi-box-arrow-up-right ms-1 text-success"></i>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('admin.tes.sesi', [$tes, 'status' => 'selesai']) }}" class="text-decoration-none">
                                <div class="border rounded p-3 text-center bg-secondary bg-opacity-10">
                                    <div class="h4 mb-0 text-secondary">{{ $statistik['sesi_selesai'] }}</div>
                                    <small class="text-muted">Selesai</small>
                                    <i class="bi bi-box-arrow-up-right ms-1 text-secondary"></i>
                                </div>
                            </a>
                        </div>
                    </div>

                    @if($statistik['sesi_selesai'] > 0)
                        <hr>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted">Rata-rata Nilai</td>
                                <td class="text-end"><strong>{{ number_format($statistik['nilai_rata_rata'], 1) }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nilai Tertinggi</td>
                                <td class="text-end">{{ number_format($statistik['nilai_tertinggi'], 1) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nilai Terendah</td>
                                <td class="text-end">{{ number_format($statistik['nilai_terendah'], 1) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Lulus</td>
                                <td class="text-end text-success">{{ $statistik['jumlah_lulus'] }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tidak Lulus</td>
                                <td class="text-end text-danger">{{ $statistik['jumlah_tidak_lulus'] }}</td>
                            </tr>
                        </table>
                    @endif
                </div>
            </div>

            <!-- Grup Peserta -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Grup Peserta</h5>
                    <a href="{{ route('admin.tes.grup', $tes) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-people me-1"></i> Atur Grup
                    </a>
                </div>
                <div class="card-body">
                    @php
                        $grupTerpilih = $tes->grup;
                    @endphp
                    @if($grupTerpilih->count() > 0)
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($grupTerpilih as $grup)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    {{ $grup->nama }}
                                    <span class="badge bg-info">{{ $grup->peserta()->count() }} peserta</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="text-center text-muted">
                            <small>Potensial: {{ $tes->grup->sum(fn($g) => $g->peserta()->count()) }} peserta</small>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="bi bi-globe fs-3 d-block mb-2"></i>
                            <p class="mb-0">Tersedia untuk semua peserta</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Psikotes Kepribadian -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-info bg-opacity-10">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Psikotes Kepribadian</h5>
                    <a href="{{ route('admin.tes.psikotes-kepribadian', $tes) }}" class="btn btn-info btn-sm text-white">
                        <i class="bi bi-gear me-1"></i> Pengaturan
                    </a>
                </div>
                <div class="card-body">
                    @php
                        $psikotesConfig = \App\Models\PsikotesKepribadianConfig::where('tes_id', $tes->id)->get();
                    @endphp
                    @if($psikotesConfig->isNotEmpty())
                        <div class="alert alert-success py-2 mb-2">
                            <i class="bi bi-check-circle me-1"></i>
                            Psikotes kepribadian sudah dikonfigurasi
                        </div>
                        <small class="text-muted">
                            Tipe: {{ $psikotesConfig->pluck('tipe_kepribadian')->map(fn($t) => ucfirst($t))->implode(', ') }}
                        </small>
                    @else
                        <div class="text-center text-muted">
                            <i class="bi bi-person-badge fs-3 d-block mb-2"></i>
                            <p class="mb-2">Belum dikonfigurasi</p>
                            <a href="{{ route('admin.tes.psikotes-kepribadian', $tes) }}" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-plus me-1"></i>Konfigurasi Sekarang
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Gaya Belajar -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-warning bg-opacity-10">
                    <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Gaya Belajar</h5>
                    <a href="{{ route('admin.tes.gaya-belajar', $tes) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-gear me-1"></i> Pengaturan
                    </a>
                </div>
                <div class="card-body">
                    @php
                        $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $tes->id)->first();
                    @endphp
                    @if($gayaBelajarConfig && $gayaBelajarConfig->aktif)
                        <div class="alert alert-success py-2 mb-2">
                            <i class="bi bi-check-circle me-1"></i>
                            Tes gaya belajar sudah aktif
                        </div>
                        <small class="text-muted">
                            Tipe: Visual, Auditori, Kinestetik
                        </small>
                    @else
                        <div class="text-center text-muted">
                            <i class="bi bi-lightbulb fs-3 d-block mb-2"></i>
                            <p class="mb-2">Belum dikonfigurasi</p>
                            <a href="{{ route('admin.tes.gaya-belajar', $tes) }}" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-plus me-1"></i>Konfigurasi Sekarang
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- MBTI -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-success bg-opacity-10">
                    <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>MBTI</h5>
                    <a href="{{ route('admin.tes.mbti', $tes) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-gear me-1"></i> Pengaturan
                    </a>
                </div>
                <div class="card-body">
                    @php
                        $mbtiConfig = \App\Models\MbtiConfig::where('tes_id', $tes->id)->get();
                    @endphp
                    @if($mbtiConfig->isNotEmpty())
                        <div class="alert alert-success py-2 mb-2">
                            <i class="bi bi-check-circle me-1"></i>
                            MBTI sudah dikonfigurasi
                        </div>
                        <small class="text-muted">
                            Dimensi: E/I, S/N, T/F, J/P (16 tipe kepribadian)
                        </small>
                        @if($statistik['sesi_selesai'] > 0)
                        <hr>
                        <form action="{{ route('admin.hasil.hitung-ulang-mbti', $tes) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-success btn-sm w-100" onclick="return confirm('Hitung ulang hasil MBTI untuk semua peserta?')">
                                <i class="bi bi-calculator me-1"></i> Hitung Ulang Hasil MBTI
                            </button>
                        </form>
                        @endif
                    @else
                        <div class="text-center text-muted">
                            <i class="bi bi-diagram-3 fs-3 d-block mb-2"></i>
                            <p class="mb-2">Belum dikonfigurasi</p>
                            <a href="{{ route('admin.tes.mbti', $tes) }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-plus me-1"></i>Konfigurasi Sekarang
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Profiling (PiES) -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary bg-opacity-10">
                    <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Profiling (PiES)</h5>
                    <a href="{{ route('admin.tes.profiling', $tes) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-gear me-1"></i> Pengaturan
                    </a>
                </div>
                <div class="card-body">
                    @php
                        $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $tes->id)->first();
                    @endphp
                    @if($profilingConfig && $profilingConfig->aktif)
                        <div class="alert alert-success py-2 mb-2">
                            <i class="bi bi-check-circle me-1"></i>
                            Profiling sudah dikonfigurasi
                        </div>
                        <small class="text-muted">
                            5 Pilar: CQ (Kreatif), EQ (Emosional), AQ (Aksi), IQ (Logika), SQ (Spiritual)
                        </small>
                        @if($statistik['sesi_selesai'] > 0)
                        <hr>
                        <form action="{{ route('admin.hasil.hitung-ulang-profiling', $tes) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100" onclick="return confirm('Hitung ulang hasil Profiling untuk semua peserta?')">
                                <i class="bi bi-calculator me-1"></i> Hitung Ulang Hasil Profiling
                            </button>
                        </form>
                        @endif
                    @else
                        <div class="text-center text-muted">
                            <i class="bi bi-person-gear fs-3 d-block mb-2"></i>
                            <p class="mb-2">Belum dikonfigurasi</p>
                            <a href="{{ route('admin.tes.profiling', $tes) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus me-1"></i>Konfigurasi Sekarang
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Token -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Token Akses</h5>
                    <a href="{{ route('admin.tes.token.index', $tes) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-key me-1"></i> Kelola
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="h5 mb-0">{{ $statistikToken['total'] }}</div>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-4">
                            <div class="h5 mb-0 text-success">{{ $statistikToken['tersedia'] }}</div>
                            <small class="text-muted">Tersedia</small>
                        </div>
                        <div class="col-4">
                            <div class="h5 mb-0 text-secondary">{{ $statistikToken['terpakai'] }}</div>
                            <small class="text-muted">Terpakai</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
