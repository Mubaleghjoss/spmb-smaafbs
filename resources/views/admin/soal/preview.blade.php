@extends('layouts.admin')

@section('title', 'Preview Soal')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="bg-dark text-white py-2 px-3 rounded mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                @if($tes)
                    <strong>Preview Tes: {{ $tes->nama }}</strong>
                @else
                    <strong>Preview Bank Soal</strong>
                @endif
                <span class="ms-3 badge bg-secondary">Soal {{ $nomor }} / {{ $totalSoal }}</span>
                @if($filter['topik_id'] && !$tes)
                    @php $topikNama = $topik->firstWhere('id', $filter['topik_id'])?->nama ?? '-'; @endphp
                    <span class="ms-2 badge bg-info">{{ $topikNama }}</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($tes)
                    <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail Tes
                    </a>
                @else
                    <a href="{{ route('admin.soal.index', $filter) }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Konten Soal -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <!-- Info Soal -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-primary me-2">Soal {{ $nomor }}</span>
                            @switch($soalSaatIni->tipe)
                                @case('pilihan_ganda')
                                    <span class="badge bg-info">Pilihan Ganda</span>
                                    @break
                                @case('jawaban_ganda')
                                    <span class="badge bg-warning">Jawaban Ganda</span>
                                    @break
                                @case('esai')
                                    <span class="badge bg-secondary">Esai</span>
                                    @break
                                @case('benar_salah')
                                    <span class="badge bg-dark">Benar/Salah</span>
                                    @break
                            @endswitch
                            <span class="badge bg-outline-secondary border ms-2">Bobot: {{ $soalSaatIni->bobot }}</span>
                        </div>
                        <div>
                            <a href="{{ route('admin.soal.edit', $soalSaatIni) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                        </div>
                    </div>

                    <!-- Pertanyaan -->
                    <div class="soal-pertanyaan mb-4 p-3 bg-light rounded">
                        <div class="pertanyaan-text fs-5">
                            {!! $soalSaatIni->pertanyaan !!}
                        </div>
                        @if($soalSaatIni->media)
                            <div class="mt-3">
                                <img src="{{ asset('storage/' . $soalSaatIni->media) }}" 
                                     class="img-fluid rounded" alt="Media Soal" style="max-height: 300px;">
                            </div>
                        @endif
                    </div>

                    <!-- Pilihan Jawaban -->
                    <div class="soal-jawaban">
                        @if($soalSaatIni->tipe === 'pilihan_ganda' || $soalSaatIni->tipe === 'benar_salah')
                            @foreach($soalSaatIni->jawaban as $index => $jawaban)
                                <div class="form-check mb-3 p-3 border rounded {{ $jawaban->benar ? 'border-success bg-success bg-opacity-10' : '' }}">
                                    <input class="form-check-input" type="radio" disabled {{ $jawaban->benar ? 'checked' : '' }}>
                                    <label class="form-check-label w-100">
                                        <strong>{{ chr(65 + $index) }}.</strong> {!! $jawaban->isi_jawaban !!}
                                        @if($jawaban->benar)
                                            <span class="badge bg-success ms-2"><i class="bi bi-check"></i> Jawaban Benar</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        @elseif($soalSaatIni->tipe === 'jawaban_ganda')
                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle"></i> Pilih semua jawaban yang benar
                            </p>
                            @foreach($soalSaatIni->jawaban as $index => $jawaban)
                                <div class="form-check mb-3 p-3 border rounded {{ $jawaban->benar ? 'border-success bg-success bg-opacity-10' : '' }}">
                                    <input class="form-check-input" type="checkbox" disabled {{ $jawaban->benar ? 'checked' : '' }}>
                                    <label class="form-check-label w-100">
                                        <strong>{{ chr(65 + $index) }}.</strong> {!! $jawaban->isi_jawaban !!}
                                        @if($jawaban->benar)
                                            <span class="badge bg-success ms-2"><i class="bi bi-check"></i> Jawaban Benar</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        @elseif($soalSaatIni->tipe === 'esai')
                            <div class="mb-3">
                                <label class="form-label">Jawaban Esai:</label>
                                <textarea class="form-control" rows="4" disabled placeholder="(Jawaban peserta akan muncul di sini)"></textarea>
                            </div>
                        @endif
                    </div>

                    <!-- Pembahasan -->
                    @if($soalSaatIni->pembahasan)
                        <div class="mt-4 p-3 bg-info bg-opacity-10 border border-info rounded">
                            <h6 class="text-info"><i class="bi bi-lightbulb me-1"></i> Pembahasan:</h6>
                            <div>{!! $soalSaatIni->pembahasan !!}</div>
                        </div>
                    @endif

                    <!-- Navigasi Soal -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        @if($nomor > 1)
                            <a href="{{ route('admin.soal.preview', array_merge($filter, ['nomor' => $nomor - 1])) }}" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-chevron-left"></i> Sebelumnya
                            </a>
                        @else
                            <span></span>
                        @endif

                        @if($nomor < $totalSoal)
                            <a href="{{ route('admin.soal.preview', array_merge($filter, ['nomor' => $nomor + 1])) }}" 
                               class="btn btn-primary">
                                Selanjutnya <i class="bi bi-chevron-right"></i>
                            </a>
                        @else
                            <a href="{{ route('admin.soal.index', $filter) }}" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Selesai
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Navigasi -->
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-header">
                    <h6 class="mb-0">Navigasi Soal</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3" style="max-height: 300px; overflow-y: auto;">
                        @foreach($soalList as $index => $item)
                            <a href="{{ route('admin.soal.preview', array_merge($filter, ['nomor' => $index + 1])) }}"
                               class="btn btn-sm {{ ($index + 1) == $nomor ? 'btn-primary' : 'btn-outline-secondary' }}"
                               style="width: 40px; height: 40px;"
                               title="Soal {{ $index + 1 }}">
                                {{ $index + 1 }}
                            </a>
                        @endforeach
                    </div>

                    <hr>

                    <div class="small text-muted">
                        @if($tes)
                            <div class="mb-2">
                                <i class="bi bi-file-earmark-text me-1"></i>
                                Tes: <strong>{{ $tes->nama }}</strong>
                            </div>
                        @endif
                        <div class="mb-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Total: <strong>{{ $totalSoal }}</strong> soal
                        </div>
                        @if($filter['topik_id'] && !$tes)
                            <div class="mb-2">
                                <i class="bi bi-folder me-1"></i>
                                Topik: <strong>{{ $topik->firstWhere('id', $filter['topik_id'])?->nama ?? '-' }}</strong>
                            </div>
                        @endif
                        @if($filter['tipe'] && !$tes)
                            <div class="mb-2">
                                <i class="bi bi-tag me-1"></i>
                                Tipe: <strong>{{ ucfirst(str_replace('_', ' ', $filter['tipe'])) }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.soal-pertanyaan img {
    max-width: 100%;
    height: auto;
}
.form-check-label img {
    max-width: 100%;
    height: auto;
}
</style>
@endsection
