@extends('layouts.peserta')

@section('title', 'Konfirmasi Ujian')

@section('content')
@php
    $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $tes->id)->exists();
    $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $tes->id)->first();
    $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
    $isMbti = \App\Models\MbtiConfig::where('tes_id', $tes->id)->exists();
    $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $tes->id)->first();
    $isProfiling = $profilingConfig && $profilingConfig->aktif;
@endphp
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                @if($isMbti)
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Konfirmasi Mulai Tes MBTI</h5>
                </div>
                @elseif($isProfiling)
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Konfirmasi Mulai Tes Profiling (PiES)</h5>
                </div>
                @elseif($isGayaBelajar)
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Konfirmasi Mulai Tes Gaya Belajar</h5>
                </div>
                @elseif($isPsikotes)
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Konfirmasi Mulai Psikotes Kepribadian</h5>
                </div>
                @else
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Konfirmasi Mulai Ujian</h5>
                </div>
                @endif
                <div class="card-body">
                    <h4 class="mb-3">{{ $tes->nama }}</h4>
                    
                    @if($tes->keterangan)
                        <p class="text-muted">{{ $tes->keterangan }}</p>
                    @endif

                    <hr>

                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock fs-4 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Durasi</small>
                                    <strong>{{ $tes->durasi_menit }} menit</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-list-ol fs-4 text-primary me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Jumlah Soal</small>
                                    <strong>{{ $tes->jumlah_soal }} soal</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($isMbti)
                    {{-- Info khusus MBTI --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-success py-2 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Tes ini akan menentukan tipe kepribadian MBTI Anda:</strong>
                                <div class="mt-2">
                                    <div class="row">
                                        <div class="col-6 col-md-3 mb-1"><small><strong>E</strong>xtraversion vs <strong>I</strong>ntroversion</small></div>
                                        <div class="col-6 col-md-3 mb-1"><small><strong>S</strong>ensing vs i<strong>N</strong>tuition</small></div>
                                        <div class="col-6 col-md-3 mb-1"><small><strong>T</strong>hinking vs <strong>F</strong>eeling</small></div>
                                        <div class="col-6 col-md-3 mb-1"><small><strong>J</strong>udging vs <strong>P</strong>erceiving</small></div>
                                    </div>
                                    <small class="text-muted d-block mt-1">Menghasilkan 16 tipe kepribadian: ISTJ, ISFJ, INFJ, INTJ, ISTP, ISFP, INFP, INTP, ESTP, ESFP, ENFP, ENTP, ESTJ, ESFJ, ENFJ, ENTJ</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @elseif($isProfiling)
                    {{-- Info khusus Profiling --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-primary py-2 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Tes ini akan menentukan potensi diri Anda berdasarkan 5 Pilar:</strong>
                                <div class="mt-2 d-flex gap-3 flex-wrap">
                                    <span class="text-warning"><i class="bi bi-lightbulb"></i> CQ (Kreatif)</span>
                                    <span class="text-danger"><i class="bi bi-heart"></i> EQ (Emosional)</span>
                                    <span class="text-success"><i class="bi bi-lightning"></i> AQ (Aksi)</span>
                                    <span class="text-primary"><i class="bi bi-cpu"></i> IQ (Logika)</span>
                                    <span class="text-info"><i class="bi bi-stars"></i> SQ (Spiritual)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @elseif($isGayaBelajar)
                    {{-- Info khusus Gaya Belajar --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Tes ini akan menentukan gaya belajar Anda:</strong>
                                <div class="mt-2 d-flex gap-3 flex-wrap">
                                    <span><i class="bi bi-eye text-primary"></i> Visual</span>
                                    <span><i class="bi bi-ear text-success"></i> Auditori</span>
                                    <span><i class="bi bi-hand-index text-warning"></i> Kinestetik</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @elseif($isPsikotes)
                    {{-- Info khusus Psikotes --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info py-2 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Tes ini akan menentukan tipe kepribadian Anda:</strong>
                                <div class="mt-2 d-flex gap-3 flex-wrap">
                                    <span class="text-danger"><i class="bi bi-fire"></i> Koleris</span>
                                    <span class="text-warning"><i class="bi bi-sun"></i> Sanguin</span>
                                    <span class="text-success"><i class="bi bi-water"></i> Plegmatis</span>
                                    <span class="text-primary"><i class="bi bi-moon"></i> Melankolis</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    {{-- Info Tes Biasa --}}
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle fs-4 text-success me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Nilai Lulus</small>
                                    <strong>{{ number_format($tes->nilai_lulus, 0) }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shuffle fs-4 text-info me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Urutan Soal</small>
                                    <strong>{{ $tes->acak_soal ? 'Diacak' : 'Tetap' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="alert alert-warning">
                        <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Perhatian!</h6>
                        <ul class="mb-0 small">
                            <li>Setelah memulai, waktu akan terus berjalan</li>
                            <li>Pastikan koneksi internet Anda stabil</li>
                            <li>Jangan menutup atau me-refresh halaman</li>
                            <li>Jawaban akan tersimpan otomatis</li>
                            <li>Ujian akan otomatis berakhir saat waktu habis</li>
                            @if($isMbti || $isGayaBelajar || $isPsikotes || $isProfiling)
                            <li><strong>Jawablah dengan jujur sesuai kepribadian Anda</strong></li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('ujian.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <form action="{{ route('ujian.mulai', $tes) }}" method="POST">
                        @csrf
                        @if($isMbti)
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-play-fill me-1"></i> Mulai Tes MBTI
                        </button>
                        @elseif($isProfiling)
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-play-fill me-1"></i> Mulai Tes Profiling
                        </button>
                        @elseif($isGayaBelajar)
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-play-fill me-1"></i> Mulai Tes
                        </button>
                        @elseif($isPsikotes)
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-play-fill me-1"></i> Mulai Psikotes
                        </button>
                        @else
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-play-fill me-1"></i> Mulai Ujian
                        </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
