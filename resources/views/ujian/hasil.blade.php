@extends('layouts.peserta')

@section('title', 'Hasil Ujian')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                @if($hasil['is_mbti'] ?? false)
                {{-- Header untuk MBTI --}}
                <div class="card-header bg-success text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="bi bi-diagram-3 me-2"></i> HASIL TES MBTI
                    </h2>
                </div>
                @elseif($hasil['is_profiling'] ?? false)
                {{-- Header untuk Profiling --}}
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="bi bi-person-gear me-2"></i> HASIL TES PROFILING (PiES)
                    </h2>
                </div>
                @elseif($hasil['is_psikotes'] ?? false)
                {{-- Header untuk Psikotes Kepribadian --}}
                <div class="card-header bg-info text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="bi bi-person-badge me-2"></i> HASIL PSIKOTES KEPRIBADIAN
                    </h2>
                </div>
                @elseif($hasil['is_gaya_belajar'] ?? false)
                {{-- Header untuk Gaya Belajar --}}
                <div class="card-header bg-warning text-dark text-center py-4">
                    <h2 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i> HASIL TES GAYA BELAJAR
                    </h2>
                </div>
                @else
                {{-- Header untuk Tes Biasa --}}
                <div class="card-header bg-{{ $hasil['lulus'] ? 'success' : 'warning' }} text-{{ $hasil['lulus'] ? 'white' : 'dark' }} text-center py-4">
                    <h2 class="mb-0">
                        @if($hasil['lulus'])
                            <i class="bi bi-check-circle-fill me-2"></i> LULUS
                        @else
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> BELUM MEMENUHI SYARAT
                        @endif
                    </h2>
                </div>
                @endif
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h3 class="mb-1">{{ $tes->nama }}</h3>
                        <p class="text-muted">{{ session('peserta_nama') }}</p>
                    </div>

                    @if($hasil['is_psikotes'] ?? false)
                    {{-- Hasil Psikotes Kepribadian --}}
                    @php
                        $psikotes = $hasil['psikotes_kepribadian'];
                        $colors = [
                            'koleris' => 'danger',
                            'sanguin' => 'warning', 
                            'plegmatis' => 'success',
                            'melankolis' => 'primary'
                        ];
                        $icons = [
                            'koleris' => 'fire',
                            'sanguin' => 'sun',
                            'plegmatis' => 'water',
                            'melankolis' => 'moon'
                        ];
                        $deskripsi = [
                            'koleris' => 'Tipe kepribadian yang kuat, tegas, dan berorientasi pada tujuan. Anda adalah pemimpin alami yang suka mengambil keputusan dan bertindak cepat.',
                            'sanguin' => 'Tipe kepribadian yang ceria, optimis, dan suka bersosialisasi. Anda mudah bergaul dan membawa energi positif ke lingkungan sekitar.',
                            'plegmatis' => 'Tipe kepribadian yang tenang, damai, dan mudah bergaul. Anda adalah pendengar yang baik dan mampu menjaga keharmonisan.',
                            'melankolis' => 'Tipe kepribadian yang analitis, detail, dan perfeksionis. Anda memiliki standar tinggi dan sangat teliti dalam bekerja.'
                        ];
                        // Parse hasil kepribadian (bisa lebih dari 1)
                        $hasilTipePsikotes = explode(' & ', $psikotes->hasil_kepribadian);
                    @endphp
                    
                    {{-- Hasil Utama --}}
                    <div class="text-center mb-4">
                        <div class="d-flex justify-content-center gap-3 flex-wrap mb-3">
                            @foreach($hasilTipePsikotes as $tipePsikotes)
                                <div class="d-inline-block p-4 rounded-circle bg-{{ $colors[$tipePsikotes] ?? 'secondary' }} bg-opacity-10">
                                    <i class="bi bi-{{ $icons[$tipePsikotes] ?? 'person' }} display-1 text-{{ $colors[$tipePsikotes] ?? 'secondary' }}"></i>
                                </div>
                            @endforeach
                        </div>
                        <h2 class="mb-2">
                            @foreach($hasilTipePsikotes as $index => $tipePsikotes)
                                <span class="text-{{ $colors[$tipePsikotes] ?? 'secondary' }}">{{ ucfirst($tipePsikotes) }}</span>@if($index < count($hasilTipePsikotes) - 1) & @endif
                            @endforeach
                        </h2>
                        <div class="d-flex justify-content-center gap-2 flex-wrap mb-3">
                            @foreach($hasilTipePsikotes as $tipePsikotes)
                                <span class="badge bg-{{ $colors[$tipePsikotes] ?? 'secondary' }} fs-6">
                                    {{ ucfirst($tipePsikotes) }}: {{ $psikotes->detail_nilai[$tipePsikotes] ?? '-' }}
                                </span>
                            @endforeach
                        </div>
                        @foreach($hasilTipePsikotes as $tipePsikotes)
                            <p class="text-muted px-4 mb-2">
                                <strong class="text-{{ $colors[$tipePsikotes] ?? 'secondary' }}">{{ ucfirst($tipePsikotes) }}:</strong>
                                {{ $deskripsi[$tipePsikotes] ?? '' }}
                            </p>
                        @endforeach
                    </div>

                    {{-- Detail Nilai per Tipe --}}
                    <h5 class="mb-3 text-center">Detail Nilai per Tipe Kepribadian</h5>
                    <div class="row g-3 mb-4">
                        @foreach($psikotes->detail_nilai as $tipe => $nilai)
                        @php
                            $isTertinggi = in_array($tipe, $hasilTipePsikotes);
                        @endphp
                        <div class="col-6 col-md-3">
                            <div class="card border-{{ $colors[$tipe] ?? 'secondary' }} h-100 {{ $isTertinggi ? 'bg-' . $colors[$tipe] . ' bg-opacity-10' : '' }}">
                                <div class="card-body text-center py-3">
                                    <i class="bi bi-{{ $icons[$tipe] ?? 'person' }} fs-3 text-{{ $colors[$tipe] ?? 'secondary' }} mb-2"></i>
                                    <h3 class="mb-0 text-{{ $colors[$tipe] ?? 'secondary' }}">{{ $nilai }}</h3>
                                    <small class="text-muted">{{ ucfirst($tipe) }}</small>
                                    @if($isTertinggi)
                                    <div class="mt-1">
                                        <span class="badge bg-{{ $colors[$tipe] }}">Tertinggi</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Grafik Bar --}}
                    <div class="mb-4">
                        @php
                            $maxNilai = max($psikotes->detail_nilai);
                        @endphp
                        @foreach($psikotes->detail_nilai as $tipe => $nilai)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-{{ $colors[$tipe] ?? 'secondary' }}">{{ ucfirst($tipe) }}</span>
                                <span>{{ $nilai }}</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-{{ $colors[$tipe] ?? 'secondary' }}" 
                                     role="progressbar" 
                                     style="width: {{ $maxNilai > 0 ? ($nilai / $maxNilai * 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <hr>
                    @elseif($hasil['is_gaya_belajar'] ?? false)
                    {{-- Hasil Gaya Belajar --}}
                    @php
                        $gayaBelajar = $hasil['gaya_belajar'];
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
                        $deskripsiGB = [
                            'visual' => 'Gaya belajar visual adalah gaya belajar yang lebih banyak memanfaatkan penglihatan. Anda cenderung lebih mudah memahami informasi melalui gambar, diagram, grafik, dan tulisan.',
                            'auditori' => 'Gaya belajar auditori adalah gaya belajar yang lebih banyak memanfaatkan pendengaran. Anda cenderung lebih mudah memahami informasi melalui mendengarkan penjelasan, diskusi, dan musik.',
                            'kinestetik' => 'Gaya belajar kinestetik adalah gaya belajar yang lebih banyak memanfaatkan gerakan dan sentuhan. Anda cenderung lebih mudah memahami informasi melalui praktik langsung, eksperimen, dan aktivitas fisik.'
                        ];
                        $hasilTipe = explode(' & ', $gayaBelajar->hasil_gaya_belajar);
                    @endphp
                    
                    {{-- Hasil Utama --}}
                    <div class="text-center mb-4">
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            @foreach($hasilTipe as $tipe)
                            <div class="d-inline-block p-4 rounded-circle bg-{{ $colorsGB[$tipe] ?? 'secondary' }} bg-opacity-10">
                                <i class="bi bi-{{ $iconsGB[$tipe] ?? 'person' }} display-1 text-{{ $colorsGB[$tipe] ?? 'secondary' }}"></i>
                            </div>
                            @endforeach
                        </div>
                        <h2 class="mb-2">
                            @foreach($hasilTipe as $index => $tipe)
                                <span class="text-{{ $colorsGB[$tipe] ?? 'secondary' }}">{{ ucfirst($tipe) }}</span>{{ $index < count($hasilTipe) - 1 ? ' & ' : '' }}
                            @endforeach
                        </h2>
                        @if(count($hasilTipe) == 1)
                        <p class="text-muted px-4">
                            {{ $deskripsiGB[$hasilTipe[0]] ?? '' }}
                        </p>
                        @else
                        <p class="text-muted px-4">
                            Anda memiliki kombinasi gaya belajar yang seimbang. Anda dapat belajar dengan efektif menggunakan berbagai metode.
                        </p>
                        @endif
                    </div>

                    {{-- Detail Nilai per Tipe --}}
                    <h5 class="mb-3 text-center">Detail Nilai per Tipe Gaya Belajar</h5>
                    <div class="row g-3 mb-4 justify-content-center">
                        @foreach($gayaBelajar->detail_nilai as $tipe => $nilai)
                        @php
                            $isHasil = in_array($tipe, $hasilTipe);
                        @endphp
                        <div class="col-6 col-md-4">
                            <div class="card border-{{ $colorsGB[$tipe] ?? 'secondary' }} h-100 {{ $isHasil ? 'bg-' . $colorsGB[$tipe] . ' bg-opacity-10' : '' }}">
                                <div class="card-body text-center py-3">
                                    <i class="bi bi-{{ $iconsGB[$tipe] ?? 'person' }} fs-3 text-{{ $colorsGB[$tipe] ?? 'secondary' }} mb-2"></i>
                                    <h3 class="mb-0 text-{{ $colorsGB[$tipe] ?? 'secondary' }}">{{ $nilai }}</h3>
                                    <small class="text-muted">{{ ucfirst($tipe) }}</small>
                                    @if($isHasil)
                                    <div class="mt-1">
                                        <span class="badge bg-{{ $colorsGB[$tipe] }}">Tertinggi</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Grafik Bar --}}
                    <div class="mb-4">
                        @php
                            $maxNilaiGB = max($gayaBelajar->detail_nilai);
                        @endphp
                        @foreach($gayaBelajar->detail_nilai as $tipe => $nilai)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-{{ $colorsGB[$tipe] ?? 'secondary' }}">
                                    <i class="bi bi-{{ $iconsGB[$tipe] ?? 'person' }} me-1"></i>{{ ucfirst($tipe) }}
                                </span>
                                <span>{{ $nilai }} jawaban</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-{{ $colorsGB[$tipe] ?? 'secondary' }}" 
                                     role="progressbar" 
                                     style="width: {{ $maxNilaiGB > 0 ? ($nilai / $maxNilaiGB * 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <hr>
                    @elseif($hasil['is_mbti'] ?? false)
                    {{-- Hasil MBTI --}}
                    @php
                        $mbti = $hasil['mbti'];
                        $mbtiDeskripsi = $hasil['mbti_deskripsi'] ?? null;
                    @endphp
                    
                    {{-- Hasil Utama --}}
                    <div class="text-center mb-4">
                        <div class="d-inline-block p-4 rounded-circle bg-success bg-opacity-10 mb-3">
                            <span class="display-1 fw-bold text-success">{{ $mbti->tipe_mbti }}</span>
                        </div>
                        @if($mbtiDeskripsi)
                        <h3 class="text-success mb-2">{{ $mbtiDeskripsi->nama }}</h3>
                        <p class="text-muted px-4">{{ $mbtiDeskripsi->deskripsi }}</p>
                        @endif
                    </div>

                    {{-- Detail Skor per Dimensi dengan Hasil per Bagian --}}
                    <h5 class="mb-3 text-center">Detail Skor per Dimensi</h5>
                    
                    {{-- Ringkasan Hasil Akhir per Dimensi --}}
                    @php
                        $detailPerhitungan = $mbti->detail_perhitungan ?? [];
                    @endphp
                    <div class="alert alert-success mb-4">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bi bi-trophy-fill me-2 fs-4"></i>
                            <strong class="fs-5">Ringkasan Hasil Akhir (Mayoritas 2 dari 3 Bagian)</strong>
                        </div>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            @foreach(['EI', 'SN', 'TF', 'JP'] as $dimensi)
                                @php
                                    $detail = $detailPerhitungan[$dimensi] ?? null;
                                    $labelA = $detail['label_a'] ?? substr($dimensi, 0, 1);
                                    $labelB = $detail['label_b'] ?? substr($dimensi, 1, 1);
                                    $hasilAkhir = $detail['hasil'] ?? $labelA;
                                    $jumlahA = $detail['jumlah_bagian_a'] ?? 0;
                                    $jumlahB = $detail['jumlah_bagian_b'] ?? 0;
                                @endphp
                                <div class="text-center px-3 py-2 bg-white rounded shadow-sm">
                                    <div class="fw-bold text-success fs-4">{{ $hasilAkhir }}</div>
                                    <small class="text-muted">
                                        {{ $labelA }}={{ $jumlahA }} vs {{ $labelB }}={{ $jumlahB }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    @php
                        $dimensiColors = [
                            'EI' => 'primary',
                            'SN' => 'info', 
                            'TF' => 'warning',
                            'JP' => 'danger'
                        ];
                        $dimensiNames = [
                            'EI' => 'Extraversion (E) vs Introversion (I)',
                            'SN' => 'Sensing (S) vs iNtuition (N)',
                            'TF' => 'Thinking (T) vs Feeling (F)',
                            'JP' => 'Judging (J) vs Perceiving (P)'
                        ];
                    @endphp
                    <div class="row g-3 mb-4">
                        @foreach(['EI', 'SN', 'TF', 'JP'] as $dimensi)
                        @php
                            $color = $dimensiColors[$dimensi];
                            $detail = $detailPerhitungan[$dimensi] ?? null;
                            $labelA = $detail['label_a'] ?? substr($dimensi, 0, 1);
                            $labelB = $detail['label_b'] ?? substr($dimensi, 1, 1);
                            $skorA = $mbti->{'skor_' . strtolower($labelA)} ?? 0;
                            $skorB = $mbti->{'skor_' . strtolower($labelB)} ?? 0;
                            $totalSkor = $skorA + $skorB;
                            $pctA = $totalSkor > 0 ? ($skorA / $totalSkor * 100) : 50;
                            $hasilAkhir = $detail['hasil'] ?? ($skorB > $skorA ? $labelB : $labelA);
                        @endphp
                        <div class="col-md-6">
                            <div class="card border-{{ $color }} h-100">
                                <div class="card-header py-2 bg-{{ $color }} bg-opacity-10">
                                    <strong>{{ $dimensiNames[$dimensi] }}</strong>
                                </div>
                                <div class="card-body py-2">
                                    {{-- Total Skor --}}
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge {{ $skorA >= $skorB ? 'bg-' . $color . ($color == 'warning' ? ' text-dark' : '') : 'bg-secondary' }}">{{ $labelA }}: {{ $skorA }}</span>
                                        <span class="badge {{ $skorB > $skorA ? 'bg-' . $color . ($color == 'warning' ? ' text-dark' : '') : 'bg-secondary' }}">{{ $labelB }}: {{ $skorB }}</span>
                                    </div>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $color }}" style="width: {{ $pctA }}%">{{ $labelA }}</div>
                                        <div class="progress-bar bg-secondary" style="width: {{ 100 - $pctA }}%">{{ $labelB }}</div>
                                    </div>
                                    
                                    {{-- Hasil per Bagian dengan Pemenang --}}
                                    @if($detail)
                                    <div class="small border-top pt-2 mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted fw-bold">Hasil per Bagian:</span>
                                            <span class="badge bg-success">
                                                <i class="bi bi-trophy me-1"></i>
                                                {{ $labelA }}: {{ $detail['jumlah_bagian_a'] ?? 0 }} | {{ $labelB }}: {{ $detail['jumlah_bagian_b'] ?? 0 }}
                                            </span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0" style="font-size: 0.8rem;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center">Bagian</th>
                                                        <th class="text-center">{{ $labelA }}</th>
                                                        <th class="text-center">{{ $labelB }}</th>
                                                        <th class="text-center">Pemenang</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach(['bagian_1' => 'B1', 'bagian_2' => 'B2', 'bagian_3' => 'B3'] as $bagianKey => $bagianLabel)
                                                        @php
                                                            $hasilBagian = $detail['hasil_' . $bagianKey] ?? '-';
                                                            $skorBagian = $detail['skor_' . $bagianKey] ?? ['a' => 0, 'b' => 0];
                                                            $isPemenang = $hasilBagian == $hasilAkhir;
                                                        @endphp
                                                        <tr>
                                                            <td class="text-center">{{ $bagianLabel }}</td>
                                                            <td class="text-center {{ $skorBagian['a'] > $skorBagian['b'] ? 'fw-bold text-success' : '' }}">{{ $skorBagian['a'] ?? 0 }}</td>
                                                            <td class="text-center {{ $skorBagian['b'] > $skorBagian['a'] ? 'fw-bold text-success' : '' }}">{{ $skorBagian['b'] ?? 0 }}</td>
                                                            <td class="text-center">
                                                                <span class="badge {{ $isPemenang ? 'bg-' . $color . ($color == 'warning' ? ' text-dark' : '') : 'bg-light text-dark border' }}">
                                                                    {{ $hasilBagian }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="text-center mt-2 pt-2 border-top">
                                        <strong class="text-{{ $color }}">
                                            <i class="bi bi-trophy me-1"></i>Hasil Akhir: {{ $hasilAkhir }}
                                            <small class="text-muted">({{ $detail['jumlah_bagian_a'] ?? 0 }} vs {{ $detail['jumlah_bagian_b'] ?? 0 }} bagian)</small>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Kekuatan, Kelemahan, Karir --}}
                    @if($mbtiDeskripsi)
                    <div class="row g-3 mb-4">
                        @if($mbtiDeskripsi->kekuatan)
                        <div class="col-md-6">
                            <div class="card border-success h-100">
                                <div class="card-header py-2 bg-success text-white">
                                    <i class="bi bi-plus-circle me-1"></i> Kekuatan
                                </div>
                                <div class="card-body py-2">
                                    <small>{{ $mbtiDeskripsi->kekuatan }}</small>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($mbtiDeskripsi->kelemahan)
                        <div class="col-md-6">
                            <div class="card border-danger h-100">
                                <div class="card-header py-2 bg-danger text-white">
                                    <i class="bi bi-dash-circle me-1"></i> Kelemahan
                                </div>
                                <div class="card-body py-2">
                                    <small>{{ $mbtiDeskripsi->kelemahan }}</small>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($mbtiDeskripsi->karir_cocok)
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header py-2 bg-info text-white">
                                    <i class="bi bi-briefcase me-1"></i> Karir yang Cocok
                                </div>
                                <div class="card-body py-2">
                                    <small>{{ $mbtiDeskripsi->karir_cocok }}</small>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <hr>
                    @endif

                    @if($hasil['is_profiling'] ?? false)
                    {{-- Hasil Profiling (PiES) --}}
                    @php
                        $profiling = $hasil['profiling'];
                        $profilingDeskripsi = $hasil['profiling_deskripsi'] ?? null;
                        $pilarList = \App\Models\ProfilingConfig::pilarList();
                        $skorArray = $profiling->getSkorArray();
                        $maxSkor = max($skorArray);
                    @endphp
                    
                    {{-- Hasil Utama --}}
                    <div class="text-center mb-4">
                        <div class="d-inline-block p-4 rounded-circle bg-{{ $pilarList[$profiling->pilar_dominan]['warna'] ?? 'primary' }} bg-opacity-10 mb-3">
                            <i class="bi bi-{{ $pilarList[$profiling->pilar_dominan]['icon'] ?? 'person' }} display-1 text-{{ $pilarList[$profiling->pilar_dominan]['warna'] ?? 'primary' }}"></i>
                        </div>
                        <h2 class="text-{{ $pilarList[$profiling->pilar_dominan]['warna'] ?? 'primary' }} mb-2">
                            {{ $pilarList[$profiling->pilar_dominan]['nama'] ?? ucfirst($profiling->pilar_dominan) }}
                        </h2>
                        <h4 class="text-muted mb-3">
                            {{ $pilarList[$profiling->pilar_dominan]['kode_qx'] ?? '' }} - {{ $pilarList[$profiling->pilar_dominan]['nama_qx'] ?? '' }}
                        </h4>
                        @if($profilingDeskripsi)
                        <p class="text-muted px-4">{{ $profilingDeskripsi->deskripsi }}</p>
                        @endif
                    </div>

                    {{-- Detail Skor per Pilar --}}
                    <h5 class="mb-3 text-center">Detail Skor per Pilar</h5>
                    <div class="row g-3 mb-4">
                        @foreach($pilarList as $pilar => $info)
                        @php
                            $skor = $skorArray[$pilar] ?? 0;
                            $isDominan = $pilar === $profiling->pilar_dominan;
                        @endphp
                        <div class="col-6 col-md">
                            <div class="card border-{{ $info['warna'] }} h-100 {{ $isDominan ? 'bg-' . $info['warna'] . ' bg-opacity-10' : '' }}">
                                <div class="card-body text-center py-3">
                                    <i class="bi bi-{{ $info['icon'] }} fs-3 text-{{ $info['warna'] }} mb-2"></i>
                                    <h3 class="mb-0 text-{{ $info['warna'] }}">{{ $skor }}</h3>
                                    <small class="text-muted">{{ $info['nama'] }}</small>
                                    <div class="small text-muted">{{ $info['kode_qx'] }}</div>
                                    @if($isDominan)
                                    <div class="mt-1">
                                        <span class="badge bg-{{ $info['warna'] }}">Dominan</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Grafik Bar --}}
                    <div class="mb-4">
                        @foreach($pilarList as $pilar => $info)
                        @php
                            $skor = $skorArray[$pilar] ?? 0;
                            $isDominan = $pilar === $profiling->pilar_dominan;
                        @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-{{ $info['warna'] }}">
                                    <i class="bi bi-{{ $info['icon'] }} me-1"></i>{{ $info['nama'] }} ({{ $info['kode_qx'] }})
                                </span>
                                <span>{{ $skor }}</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-{{ $info['warna'] }}" 
                                     role="progressbar" 
                                     style="width: {{ $maxSkor > 0 ? ($skor / $maxSkor * 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Kekuatan dan Saran Pengembangan --}}
                    @if($profilingDeskripsi)
                    <div class="row g-3 mb-4">
                        @if($profilingDeskripsi->kekuatan)
                        <div class="col-md-6">
                            <div class="card border-success h-100">
                                <div class="card-header py-2 bg-success text-white">
                                    <i class="bi bi-plus-circle me-1"></i> Kekuatan
                                </div>
                                <div class="card-body py-2">
                                    <small>{{ $profilingDeskripsi->kekuatan }}</small>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($profilingDeskripsi->saran_pengembangan)
                        <div class="col-md-6">
                            <div class="card border-info h-100">
                                <div class="card-header py-2 bg-info text-white">
                                    <i class="bi bi-lightbulb me-1"></i> Saran Pengembangan
                                </div>
                                <div class="card-body py-2">
                                    <small>{{ $profilingDeskripsi->saran_pengembangan }}</small>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <hr>
                    @endif

                    @if(!($hasil['is_psikotes'] ?? false) && !($hasil['is_gaya_belajar'] ?? false) && !($hasil['is_mbti'] ?? false) && !($hasil['is_profiling'] ?? false))
                    {{-- Hasil Tes Biasa --}}
                    <div class="row text-center mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="h2 mb-0 {{ $hasil['lulus'] ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($hasil['nilai'], 1) }}
                                </div>
                                <small class="text-muted">Nilai Anda</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="h2 mb-0">{{ number_format($hasil['nilai_lulus'], 0) }}</div>
                                <small class="text-muted">Nilai Lulus</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                @php
                                    $totalDetik = $hasil['waktu_mulai']->diffInSeconds($hasil['waktu_selesai']);
                                    $menit = floor($totalDetik / 60);
                                    $detik = $totalDetik % 60;
                                @endphp
                                <div class="h4 mb-0">{{ $menit }} <small>menit</small> {{ $detik }} <small>detik</small></div>
                                <small class="text-muted">Durasi Pengerjaan</small>
                            </div>
                        </div>
                    </div>
                    @elseif($hasil['is_psikotes'] ?? false)
                    {{-- Info Waktu untuk Psikotes --}}
                    <div class="row text-center mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                @php
                                    $totalDetik = $hasil['waktu_mulai']->diffInSeconds($hasil['waktu_selesai']);
                                    $menit = floor($totalDetik / 60);
                                    $detik = $totalDetik % 60;
                                @endphp
                                <div class="h4 mb-0">{{ $menit }} <small>menit</small> {{ $detik }} <small>detik</small></div>
                                <small class="text-muted">Durasi Pengerjaan</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <div class="h4 mb-0">{{ $hasil['sesi']->jawabanPeserta->count() }}</div>
                                <small class="text-muted">Soal Dijawab</small>
                            </div>
                        </div>
                    </div>
                    @elseif($hasil['is_gaya_belajar'] ?? false)
                    {{-- Info Waktu untuk Gaya Belajar --}}
                    <div class="row text-center mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                @php
                                    $totalDetik = $hasil['waktu_mulai']->diffInSeconds($hasil['waktu_selesai']);
                                    $menit = floor($totalDetik / 60);
                                    $detik = $totalDetik % 60;
                                @endphp
                                <div class="h4 mb-0">{{ $menit }} <small>menit</small> {{ $detik }} <small>detik</small></div>
                                <small class="text-muted">Durasi Pengerjaan</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <div class="h4 mb-0">{{ $hasil['sesi']->jawabanPeserta->count() }}</div>
                                <small class="text-muted">Soal Dijawab</small>
                            </div>
                        </div>
                    </div>
                    @elseif($hasil['is_mbti'] ?? false)
                    {{-- Info Waktu untuk MBTI --}}
                    <div class="row text-center mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                @php
                                    $totalDetik = $hasil['waktu_mulai']->diffInSeconds($hasil['waktu_selesai']);
                                    $menit = floor($totalDetik / 60);
                                    $detik = $totalDetik % 60;
                                @endphp
                                <div class="h4 mb-0">{{ $menit }} <small>menit</small> {{ $detik }} <small>detik</small></div>
                                <small class="text-muted">Durasi Pengerjaan</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <div class="h4 mb-0">{{ $hasil['sesi']->jawabanPeserta->count() }}</div>
                                <small class="text-muted">Soal Dijawab</small>
                            </div>
                        </div>
                    </div>
                    @elseif($hasil['is_profiling'] ?? false)
                    {{-- Info Waktu untuk Profiling --}}
                    <div class="row text-center mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                @php
                                    $totalDetik = $hasil['waktu_mulai']->diffInSeconds($hasil['waktu_selesai']);
                                    $menit = floor($totalDetik / 60);
                                    $detik = $totalDetik % 60;
                                @endphp
                                <div class="h4 mb-0">{{ $menit }} <small>menit</small> {{ $detik }} <small>detik</small></div>
                                <small class="text-muted">Durasi Pengerjaan</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <div class="h4 mb-0">{{ $hasil['sesi']->jawabanPeserta->count() }}</div>
                                <small class="text-muted">Soal Dijawab</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">Waktu Mulai</td>
                            <td>{{ $hasil['waktu_mulai']->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Waktu Selesai</td>
                            <td>{{ $hasil['waktu_selesai']->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if($hasil['sesi']->status === 'selesai')
                                    <span class="badge bg-success">Selesai</span>
                                @elseif($hasil['sesi']->status === 'timeout')
                                    <span class="badge bg-warning text-dark">Waktu Habis</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($hasil['sesi']->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                    
                    {{-- Info status verifikasi untuk peserta yang tidak lulus (hanya untuk tes akademik, bukan kepribadian) --}}
                    @if(!$hasil['lulus'] && !($hasil['is_psikotes'] ?? false) && !($hasil['is_gaya_belajar'] ?? false) && !($hasil['is_mbti'] ?? false))
                        @php
                            $kontakTimSpmb = json_decode(\App\Models\Pengaturan::where('kunci', 'kontak_tim_spmb')->value('nilai') ?? '[]', true);
                        @endphp
                        <div class="alert {{ $hasil['sesi']->status_verifikasi_tes === 'diloloskan' ? 'alert-success' : ($hasil['sesi']->status_verifikasi_tes === 'ditolak' ? 'alert-danger' : 'alert-warning') }} mt-3">
                            @if($hasil['sesi']->status_verifikasi_tes === 'menunggu')
                                <h6 class="alert-heading"><i class="bi bi-hourglass-split me-2"></i>Menunggu Keputusan Admin</h6>
                                <p class="mb-2">Nilai Anda belum memenuhi syarat SPMB kami. Hasil tes Anda sedang ditinjau oleh admin untuk menentukan apakah Anda dapat melanjutkan ke tahap berikutnya atau melakukan tes ini ulang.</p>
                                
                                @if(!empty($kontakTimSpmb))
                                    <hr>
                                    <p class="mb-2"><strong><i class="bi bi-telephone me-1"></i> Hubungi Admin untuk Mempercepat Verifikasi:</strong></p>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($kontakTimSpmb as $kontak)
                                            @if(!empty($kontak['whatsapp']))
                                                @php
                                                    $waNumber = preg_replace('/[^0-9]/', '', $kontak['whatsapp']);
                                                    if (substr($waNumber, 0, 1) === '0') {
                                                        $waNumber = '62' . substr($waNumber, 1);
                                                    }
                                                @endphp
                                                <a href="https://wa.me/{{ $waNumber }}?text=Assalamu'alaikum, saya {{ session('peserta_nama') }} ingin menanyakan hasil tes SPMB saya." 
                                                   class="btn btn-success btn-sm" target="_blank">
                                                    <i class="bi bi-whatsapp me-1"></i> 
                                                    {{ $kontak['nama'] ?? 'Admin' }} ({{ $kontak['whatsapp'] }})
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @elseif($hasil['sesi']->status_verifikasi_tes === 'diloloskan')
                                <h6 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Diloloskan oleh Admin</h6>
                                <p class="mb-0">Meskipun nilai Anda di bawah nilai lulus, admin telah memutuskan untuk meloloskan Anda ke tahap berikutnya.</p>
                                @if($hasil['sesi']->catatan_verifikasi)
                                    <hr>
                                    <small><strong>Catatan:</strong> {{ $hasil['sesi']->catatan_verifikasi }}</small>
                                @endif
                            @elseif($hasil['sesi']->status_verifikasi_tes === 'ditolak')
                                <h6 class="alert-heading"><i class="bi bi-x-circle me-2"></i>Tidak Dapat Melanjutkan</h6>
                                <p class="mb-0">Mohon maaf, berdasarkan hasil tes, Anda tidak dapat melanjutkan ke tahap berikutnya.</p>
                                @if($hasil['sesi']->catatan_verifikasi)
                                    <hr>
                                    <small><strong>Alasan:</strong> {{ $hasil['sesi']->catatan_verifikasi }}</small>
                                @endif
                            @endif
                        </div>
                    @endif

                    @if($tes->tampilkan_pembahasan && isset($hasil['detail_jawaban']) && !($hasil['is_psikotes'] ?? false) && !($hasil['is_gaya_belajar'] ?? false) && !($hasil['is_mbti'] ?? false))
                        <hr>
                        <h5 class="mb-3">Pembahasan</h5>
                        @foreach($hasil['detail_jawaban'] as $index => $detail)
                            <div class="card mb-3 {{ $detail['benar'] ? 'border-success' : 'border-danger' }}">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Soal {{ $index + 1 }}</span>
                                    @if($detail['benar'])
                                        <span class="badge bg-success">Benar</span>
                                    @else
                                        <span class="badge bg-danger">Salah</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        {!! $detail['soal']->pertanyaan !!}
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Jawaban Anda:</strong>
                                            <p class="{{ $detail['benar'] ? 'text-success' : 'text-danger' }}">
                                                @if($detail['jawaban_peserta']->jawaban)
                                                    {!! $detail['jawaban_peserta']->jawaban->isi_jawaban !!}
                                                @elseif($detail['jawaban_peserta']->jawaban_esai)
                                                    {{ $detail['jawaban_peserta']->jawaban_esai }}
                                                @else
                                                    <em>Tidak dijawab</em>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Jawaban Benar:</strong>
                                            <p class="text-success">
                                                @if($detail['jawaban_benar'])
                                                    {!! $detail['jawaban_benar']->isi_jawaban !!}
                                                @else
                                                    <em>-</em>
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    @if($detail['soal']->pembahasan)
                                        <div class="alert alert-info mt-2">
                                            <strong>Pembahasan:</strong>
                                            {!! $detail['soal']->pembahasan !!}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('ujian.index') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Ujian
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
