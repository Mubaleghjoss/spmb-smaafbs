@extends('layouts.admin')

@section('title', 'Analisis Butir Soal: ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Analisis Butir Soal</h1>
            <small class="text-muted">{{ $tes->nama }}</small>
        </div>
        <a href="{{ route('admin.hasil.show', $tes) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Info Statistik -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $statistik['total_peserta'] }}</h4>
                    <small>Total Peserta</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $analisis->count() }}</h4>
                    <small>Total Soal</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $statistik['rata_rata'] }}</h4>
                    <small>Rata-rata Nilai</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $statistik['persentase_lulus'] }}%</h4>
                    <small>Persentase Lulus</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan Interpretasi -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-book me-2"></i>Panduan Interpretasi Analisis Butir Soal</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary"><i class="bi bi-speedometer me-1"></i> Indeks Kesukaran (P)</h6>
                    <p class="small text-muted mb-2">
                        Mengukur proporsi peserta yang menjawab benar. Semakin tinggi nilai P, semakin mudah soal tersebut.
                    </p>
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Rentang</th>
                                <th>Kategori</th>
                                <th>Penjelasan</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td><span class="badge bg-danger">0.00 - 0.30</span></td>
                                <td><strong>Sukar</strong></td>
                                <td>Hanya &lt;30% peserta menjawab benar. Soal terlalu sulit atau ambigu.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark">0.31 - 0.70</span></td>
                                <td><strong>Sedang</strong></td>
                                <td>30-70% peserta menjawab benar. <span class="text-success fw-bold">Ideal untuk tes seleksi.</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success">0.71 - 1.00</span></td>
                                <td><strong>Mudah</strong></td>
                                <td>&gt;70% peserta menjawab benar. Soal terlalu mudah atau sudah umum diketahui.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info py-2 small mb-0">
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tips:</strong> Tes yang baik memiliki komposisi soal: <strong>25% Sukar</strong>, <strong>50% Sedang</strong>, <strong>25% Mudah</strong>.
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary"><i class="bi bi-bar-chart me-1"></i> Indeks Daya Beda (D)</h6>
                    <p class="small text-muted mb-2">
                        Mengukur kemampuan soal membedakan peserta berkemampuan tinggi dan rendah. Semakin tinggi nilai D, semakin baik soal tersebut.
                    </p>
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Rentang</th>
                                <th>Kategori</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td><span class="badge bg-danger">&lt; 0.20</span></td>
                                <td><strong>Jelek</strong></td>
                                <td class="text-danger">Perlu direvisi total atau dihapus.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark">0.20 - 0.40</span></td>
                                <td><strong>Cukup</strong></td>
                                <td>Bisa dipakai dengan sedikit perbaikan pada opsi jawaban.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info">0.41 - 0.70</span></td>
                                <td><strong>Baik</strong></td>
                                <td class="text-success">Layak digunakan tanpa perubahan.</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success">&gt; 0.70</span></td>
                                <td><strong>Sangat Baik</strong></td>
                                <td class="text-success fw-bold">Soal berkualitas tinggi, layak masuk bank soal.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Perhatian:</strong> Soal dengan daya beda negatif menunjukkan peserta lemah justru lebih sering benar — perlu diperiksa kunci jawabannya.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Analisis -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Hasil Analisis Butir Soal</h6>
        </div>
        <div class="card-body">
            @if($analisis->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-data display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Belum ada data untuk dianalisis.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pertanyaan</th>
                                <th>Topik</th>
                                <th>Tipe</th>
                                <th class="text-center">Indeks Kesukaran</th>
                                <th class="text-center">Indeks Daya Beda</th>
                                <th>Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analisis as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <span title="{{ strip_tags($item['pertanyaan']) }}">
                                            {{ Str::limit(strip_tags($item['pertanyaan']), 50) }}
                                        </span>
                                    </td>
                                    <td>{{ $item['topik'] }}</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ ucfirst(str_replace('_', ' ', $item['tipe'])) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $kesukaranClass = match($item['kesukaran']['kategori']) {
                                                'Sukar' => 'bg-danger',
                                                'Sedang' => 'bg-warning text-dark',
                                                'Mudah' => 'bg-success',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $kesukaranClass }}">
                                            {{ $item['kesukaran']['indeks'] }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $item['kesukaran']['kategori'] }}</small>
                                        <br>
                                        <small class="text-muted">
                                            ({{ $item['kesukaran']['jumlah_benar'] }}/{{ $item['kesukaran']['total_peserta'] }})
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $dayaBedaClass = match($item['daya_beda']['kategori']) {
                                                'Jelek' => 'bg-danger',
                                                'Cukup' => 'bg-warning text-dark',
                                                'Baik' => 'bg-info',
                                                'Sangat Baik' => 'bg-success',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $dayaBedaClass }}">
                                            {{ $item['daya_beda']['indeks'] }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $item['daya_beda']['kategori'] }}</small>
                                    </td>
                                    <td>
                                        <small class="{{ str_contains($item['rekomendasi'], 'PERHATIAN') ? 'text-danger fw-bold' : '' }}">
                                            {{ $item['rekomendasi'] }}
                                        </small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Ringkasan -->
                <div class="mt-4">
                    <h6>Ringkasan Analisis</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <small class="fw-bold">Distribusi Tingkat Kesukaran</small>
                                </div>
                                <div class="card-body">
                                    @php
                                        $sukar = $analisis->where('kesukaran.kategori', 'Sukar')->count();
                                        $sedang = $analisis->where('kesukaran.kategori', 'Sedang')->count();
                                        $mudah = $analisis->where('kesukaran.kategori', 'Mudah')->count();
                                        $totalSoal = $analisis->count();
                                    @endphp
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Sukar</span>
                                        <span><span class="badge bg-danger">{{ $sukar }}</span> <small class="text-muted">({{ $totalSoal > 0 ? round($sukar/$totalSoal*100) : 0 }}%)</small></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Sedang</span>
                                        <span><span class="badge bg-warning text-dark">{{ $sedang }}</span> <small class="text-muted">({{ $totalSoal > 0 ? round($sedang/$totalSoal*100) : 0 }}%)</small></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Mudah</span>
                                        <span><span class="badge bg-success">{{ $mudah }}</span> <small class="text-muted">({{ $totalSoal > 0 ? round($mudah/$totalSoal*100) : 0 }}%)</small></span>
                                    </div>
                                    @if($totalSoal > 0)
                                    <div class="progress mt-3" style="height: 24px;">
                                        <div class="progress-bar bg-danger" style="width: {{ $sukar/$totalSoal*100 }}%">{{ $sukar }}</div>
                                        <div class="progress-bar bg-warning" style="width: {{ $sedang/$totalSoal*100 }}%">{{ $sedang }}</div>
                                        <div class="progress-bar bg-success" style="width: {{ $mudah/$totalSoal*100 }}%">{{ $mudah }}</div>
                                    </div>
                                    <small class="text-muted">Sukar | Sedang | Mudah</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <small class="fw-bold">Distribusi Daya Beda</small>
                                </div>
                                <div class="card-body">
                                    @php
                                        $jelek = $analisis->where('daya_beda.kategori', 'Jelek')->count();
                                        $cukup = $analisis->where('daya_beda.kategori', 'Cukup')->count();
                                        $baik = $analisis->where('daya_beda.kategori', 'Baik')->count();
                                        $sangatBaik = $analisis->where('daya_beda.kategori', 'Sangat Baik')->count();
                                    @endphp
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Jelek</span>
                                        <span><span class="badge bg-danger">{{ $jelek }}</span> <small class="text-muted">({{ $totalSoal > 0 ? round($jelek/$totalSoal*100) : 0 }}%)</small></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Cukup</span>
                                        <span><span class="badge bg-warning text-dark">{{ $cukup }}</span> <small class="text-muted">({{ $totalSoal > 0 ? round($cukup/$totalSoal*100) : 0 }}%)</small></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Baik</span>
                                        <span><span class="badge bg-info">{{ $baik }}</span> <small class="text-muted">({{ $totalSoal > 0 ? round($baik/$totalSoal*100) : 0 }}%)</small></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Sangat Baik</span>
                                        <span><span class="badge bg-success">{{ $sangatBaik }}</span> <small class="text-muted">({{ $totalSoal > 0 ? round($sangatBaik/$totalSoal*100) : 0 }}%)</small></span>
                                    </div>
                                    @if($totalSoal > 0)
                                    <div class="progress mt-3" style="height: 24px;">
                                        <div class="progress-bar bg-danger" style="width: {{ $jelek/$totalSoal*100 }}%">{{ $jelek }}</div>
                                        <div class="progress-bar bg-warning" style="width: {{ $cukup/$totalSoal*100 }}%">{{ $cukup }}</div>
                                        <div class="progress-bar bg-info" style="width: {{ $baik/$totalSoal*100 }}%">{{ $baik }}</div>
                                        <div class="progress-bar bg-success" style="width: {{ $sangatBaik/$totalSoal*100 }}%">{{ $sangatBaik }}</div>
                                    </div>
                                    <small class="text-muted">Jelek | Cukup | Baik | Sangat Baik</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Saran & Rekomendasi -->
                <div class="mt-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-chat-square-text me-2"></i>Saran & Rekomendasi</h6>
                        </div>
                        <div class="card-body">
                            @php
                                $totalSoal = $analisis->count();
                                $sukar = $analisis->where('kesukaran.kategori', 'Sukar')->count();
                                $sedang = $analisis->where('kesukaran.kategori', 'Sedang')->count();
                                $mudah = $analisis->where('kesukaran.kategori', 'Mudah')->count();
                                $jelek = $analisis->where('daya_beda.kategori', 'Jelek')->count();
                                $cukup = $analisis->where('daya_beda.kategori', 'Cukup')->count();
                                $baik = $analisis->where('daya_beda.kategori', 'Baik')->count();
                                $sangatBaik = $analisis->where('daya_beda.kategori', 'Sangat Baik')->count();
                                $soalBagus = $baik + $sangatBaik;
                                $persenBagus = $totalSoal > 0 ? round($soalBagus/$totalSoal*100) : 0;
                                $persenSedang = $totalSoal > 0 ? round($sedang/$totalSoal*100) : 0;
                            @endphp

                            {{-- Kualitas Tes Keseluruhan --}}
                            <h6 class="fw-bold mb-3"><i class="bi bi-trophy me-1"></i> Kualitas Tes Keseluruhan</h6>
                            @if($persenBagus >= 70)
                                <div class="alert alert-success py-2">
                                    <i class="bi bi-check-circle me-1"></i>
                                    <strong>Kualitas Baik!</strong> {{ $persenBagus }}% soal memiliki daya beda Baik/Sangat Baik. Tes ini efektif membedakan kemampuan peserta.
                                </div>
                            @elseif($persenBagus >= 40)
                                <div class="alert alert-warning py-2">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>Kualitas Cukup.</strong> {{ $persenBagus }}% soal memiliki daya beda Baik/Sangat Baik. Masih ada ruang untuk perbaikan pada {{ $jelek + $cukup }} soal.
                                </div>
                            @else
                                <div class="alert alert-danger py-2">
                                    <i class="bi bi-x-circle me-1"></i>
                                    <strong>Perlu Perbaikan Besar.</strong> Hanya {{ $persenBagus }}% soal berkualitas baik. Sebagian besar soal tidak mampu membedakan peserta.
                                </div>
                            @endif

                            <div class="row mt-3">
                                {{-- Saran untuk Komposisi Kesukaran --}}
                                <div class="col-md-6">
                                    <h6 class="fw-bold"><i class="bi bi-sliders me-1"></i> Saran Komposisi Soal</h6>
                                    <ul class="small mb-3">
                                        @if($sukar > $totalSoal * 0.35)
                                            <li class="text-danger mb-1">
                                                <strong>Terlalu banyak soal sukar ({{ $sukar }} soal).</strong> 
                                                Kurangi menjadi sekitar {{ round($totalSoal * 0.25) }} soal. Soal terlalu sulit dapat menurunkan motivasi peserta.
                                            </li>
                                        @elseif($sukar < $totalSoal * 0.15)
                                            <li class="text-warning mb-1">
                                                <strong>Soal sukar terlalu sedikit ({{ $sukar }} soal).</strong>
                                                Tambahkan soal sukar agar tes lebih menantang bagi peserta berkemampuan tinggi.
                                            </li>
                                        @else
                                            <li class="text-success mb-1">
                                                <i class="bi bi-check"></i> Proporsi soal sukar sudah baik ({{ $sukar }} soal).
                                            </li>
                                        @endif

                                        @if($persenSedang >= 40 && $persenSedang <= 60)
                                            <li class="text-success mb-1">
                                                <i class="bi bi-check"></i> Proporsi soal sedang sudah ideal ({{ $sedang }} soal / {{ $persenSedang }}%).
                                            </li>
                                        @else
                                            <li class="text-warning mb-1">
                                                <strong>Soal kategori sedang: {{ $sedang }} ({{ $persenSedang }}%).</strong>
                                                Idealnya 40-60% soal bertingkat kesukaran sedang.
                                            </li>
                                        @endif

                                        @if($mudah > $totalSoal * 0.35)
                                            <li class="text-danger mb-1">
                                                <strong>Terlalu banyak soal mudah ({{ $mudah }} soal).</strong>
                                                Tingkatkan tingkat kesulitan agar tes lebih selektif.
                                            </li>
                                        @elseif($mudah < $totalSoal * 0.15)
                                            <li class="text-warning mb-1">
                                                <strong>Soal mudah terlalu sedikit ({{ $mudah }} soal).</strong>
                                                Tambahkan soal pembuka yang mudah untuk membangun kepercayaan diri peserta.
                                            </li>
                                        @else
                                            <li class="text-success mb-1">
                                                <i class="bi bi-check"></i> Proporsi soal mudah sudah baik ({{ $mudah }} soal).
                                            </li>
                                        @endif
                                    </ul>
                                </div>

                                {{-- Saran untuk Daya Beda --}}
                                <div class="col-md-6">
                                    <h6 class="fw-bold"><i class="bi bi-wrench me-1"></i> Saran Perbaikan Soal</h6>
                                    <ul class="small mb-3">
                                        @if($jelek > 0)
                                            <li class="text-danger mb-1">
                                                <strong>{{ $jelek }} soal berdaya beda jelek</strong> — perlu direvisi ulang. 
                                                Periksa kunci jawaban, opsi pengecoh, atau formulasi pertanyaan.
                                            </li>
                                        @endif

                                        @if($cukup > 0)
                                            <li class="text-warning mb-1">
                                                <strong>{{ $cukup }} soal berdaya beda cukup</strong> — perbaiki opsi pengecoh agar lebih efektif.
                                            </li>
                                        @endif

                                        @if($soalBagus > 0)
                                            <li class="text-success mb-1">
                                                <i class="bi bi-check"></i> <strong>{{ $soalBagus }} soal berdaya beda baik/sangat baik</strong> — simpan di bank soal sebagai aset berharga.
                                            </li>
                                        @endif

                                        @if($jelek == 0 && $cukup == 0)
                                            <li class="text-success mb-1">
                                                <i class="bi bi-star-fill"></i> <strong>Semua soal berkualitas baik!</strong> Tidak perlu revisi.
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>

                            {{-- Soal yang Perlu Perhatian --}}
                            @php
                                $soalBermasalah = $analisis->filter(function($item) {
                                    return $item['daya_beda']['kategori'] === 'Jelek' || 
                                           ($item['kesukaran']['kategori'] === 'Sukar' && $item['daya_beda']['kategori'] === 'Jelek');
                                });
                            @endphp
                            @if($soalBermasalah->count() > 0)
                                <hr>
                                <h6 class="fw-bold text-danger"><i class="bi bi-exclamation-octagon me-1"></i> Soal yang Perlu Segera Direvisi</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-danger">
                                            <tr>
                                                <th>No</th>
                                                <th>Pertanyaan</th>
                                                <th class="text-center">Kesukaran</th>
                                                <th class="text-center">Daya Beda</th>
                                                <th>Masalah</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
                                            @foreach($soalBermasalah as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ Str::limit(strip_tags($item['pertanyaan']), 60) }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ $item['kesukaran']['kategori'] === 'Sukar' ? 'bg-danger' : ($item['kesukaran']['kategori'] === 'Mudah' ? 'bg-success' : 'bg-warning text-dark') }}">
                                                        {{ $item['kesukaran']['indeks'] }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger">{{ $item['daya_beda']['indeks'] }}</span>
                                                </td>
                                                <td>
                                                    @if($item['daya_beda']['indeks'] < 0)
                                                        <span class="text-danger">Kunci jawaban mungkin salah — peserta lemah lebih banyak benar.</span>
                                                    @elseif($item['kesukaran']['kategori'] === 'Sukar')
                                                        <span>Soal terlalu sulit dan tidak membedakan peserta.</span>
                                                    @elseif($item['kesukaran']['kategori'] === 'Mudah')
                                                        <span>Soal terlalu mudah sehingga tidak membedakan.</span>
                                                    @else
                                                        <span>Opsi pengecoh tidak berfungsi dengan baik.</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
