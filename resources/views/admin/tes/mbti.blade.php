@extends('layouts.admin')

@section('title', 'Pengaturan MBTI - ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail Tes
            </a>
            <h1 class="h3 mb-0">Pengaturan MBTI</h1>
            <small class="text-muted">{{ $tes->nama }} ({{ $soalTes->count() }} soal)</small>
        </div>
        <div>
            <form action="{{ route('admin.tes.mbti.init-default', $tes) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Terapkan konfigurasi default? Data yang sudah ada akan ditimpa.')">
                    <i class="bi bi-magic me-1"></i>Terapkan Default
                </button>
            </form>
        </div>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info MBTI --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Tentang Tes MBTI</h5>
        </div>
        <div class="card-body">
            <p class="mb-2">MBTI (Myers-Briggs Type Indicator) mengukur 4 dimensi kepribadian:</p>
            <div class="row">
                @foreach($dimensiList as $kode => $info)
                <div class="col-md-3 mb-2">
                    <div class="border rounded p-2 h-100">
                        <strong class="text-primary">{{ $info['label_a'] }} vs {{ $info['label_b'] }}</strong>
                        <br><small class="text-muted">{{ $info['nama'] }}</small>
                    </div>
                </div>
                @endforeach
            </div>
            <hr>
            <p class="mb-1"><strong>Struktur Soal (100 soal):</strong></p>
            <ul class="mb-0 small">
                <li><strong>Bagian I</strong>: Soal 1-60 (15 soal per dimensi, pola: 1,5,9,13... | 2,6,10,14... | 3,7,11,15... | 4,8,12,16...)</li>
                <li><strong>Bagian II</strong>: Soal 61-96 (9 soal per dimensi, pola: 61,65,69... | 62,66,70... | 63,67,71... | 64,68,72...)</li>
                <li><strong>Bagian III</strong>: Soal 97-100 (1 soal per dimensi: 97, 98, 99, 100)</li>
            </ul>
            <p class="mt-2 mb-0 small text-muted">
                <i class="bi bi-lightbulb me-1"></i>
                Jawaban A = skor untuk sisi kiri (E, S, T, J), Jawaban B = skor untuk sisi kanan (I, N, F, P).
                Skor tertinggi menentukan huruf hasil.
            </p>
        </div>
    </div>

    <form action="{{ route('admin.tes.mbti.simpan', $tes) }}" method="POST">
        @csrf

        {{-- Konfigurasi Dimensi --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Konfigurasi Dimensi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="120">Dimensi</th>
                                <th>Bagian I (15 soal)</th>
                                <th>Bagian II (9 soal)</th>
                                <th width="100">Bagian III</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dimensiList as $kode => $info)
                            @php
                                $cfg = $config->get($kode);
                                $defaultSoal = $defaultMapping[$kode] ?? [];
                            @endphp
                            <tr>
                                <td>
                                    <strong class="text-primary">{{ $info['label_a'] }}/{{ $info['label_b'] }}</strong>
                                    <br><small class="text-muted">{{ $info['nama'] }}</small>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="config[{{ $kode }}][soal_bagian_1]" 
                                           class="form-control form-control-sm"
                                           value="{{ $cfg ? implode(', ', $cfg->soal_bagian_1 ?? []) : implode(', ', $defaultSoal['soal_bagian_1'] ?? []) }}"
                                           placeholder="1, 5, 9, 13, ...">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="config[{{ $kode }}][soal_bagian_2]" 
                                           class="form-control form-control-sm"
                                           value="{{ $cfg ? implode(', ', $cfg->soal_bagian_2 ?? []) : implode(', ', $defaultSoal['soal_bagian_2'] ?? []) }}"
                                           placeholder="61, 65, 69, ...">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="config[{{ $kode }}][soal_bagian_3]" 
                                           class="form-control form-control-sm"
                                           value="{{ $cfg ? implode(', ', $cfg->soal_bagian_3 ?? []) : implode(', ', $defaultSoal['soal_bagian_3'] ?? []) }}"
                                           placeholder="97">
                                </td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">
                                                <span class="badge bg-success">{{ $info['label_a'] }}</span> Deskripsi
                                            </label>
                                            <textarea name="config[{{ $kode }}][deskripsi_a]" 
                                                      class="form-control form-control-sm" 
                                                      rows="2"
                                                      placeholder="{{ $info['deskripsi_a'] }}">{{ $cfg->deskripsi_a ?? $info['deskripsi_a'] }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">
                                                <span class="badge bg-warning text-dark">{{ $info['label_b'] }}</span> Deskripsi
                                            </label>
                                            <textarea name="config[{{ $kode }}][deskripsi_b]" 
                                                      class="form-control form-control-sm" 
                                                      rows="2"
                                                      placeholder="{{ $info['deskripsi_b'] }}">{{ $cfg->deskripsi_b ?? $info['deskripsi_b'] }}</textarea>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Masukkan nomor soal dipisahkan koma. Contoh: 1, 5, 9, 13, 17
                </small>
            </div>
        </div>

        {{-- Deskripsi 16 Tipe --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Deskripsi 16 Tipe Kepribadian</h5>
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#collapseTipe">
                    <i class="bi bi-chevron-down"></i> Toggle
                </button>
            </div>
            <div class="collapse show" id="collapseTipe">
                <div class="card-body">
                    <div class="row">
                        @foreach($tipeMbtiList as $tipe => $info)
                        @php $tipeCfg = $tipeDeskripsi->get($tipe); @endphp
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-secondary">
                                <div class="card-header py-2 bg-light">
                                    <strong class="text-primary fs-5">{{ $tipe }}</strong>
                                    <input type="text" 
                                           name="tipe[{{ $tipe }}][nama]" 
                                           class="form-control form-control-sm mt-1"
                                           value="{{ $tipeCfg->nama ?? $info['nama'] }}"
                                           placeholder="Nama tipe">
                                </div>
                                <div class="card-body py-2">
                                    <label class="form-label small mb-1 fw-bold">Deskripsi</label>
                                    <textarea name="tipe[{{ $tipe }}][deskripsi]" 
                                              class="form-control form-control-sm mb-2" 
                                              rows="3"
                                              placeholder="Deskripsi...">{{ $tipeCfg->deskripsi ?? $info['deskripsi'] }}</textarea>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">
                                                <i class="bi bi-plus-circle text-success me-1"></i>Kekuatan
                                            </label>
                                            <textarea name="tipe[{{ $tipe }}][kekuatan]" 
                                                      class="form-control form-control-sm mb-2" 
                                                      rows="2"
                                                      placeholder="Kekuatan...">{{ $tipeCfg->kekuatan ?? $info['kekuatan'] }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">
                                                <i class="bi bi-dash-circle text-danger me-1"></i>Kelemahan
                                            </label>
                                            <textarea name="tipe[{{ $tipe }}][kelemahan]" 
                                                      class="form-control form-control-sm mb-2" 
                                                      rows="2"
                                                      placeholder="Kelemahan...">{{ $tipeCfg->kelemahan ?? $info['kelemahan'] }}</textarea>
                                        </div>
                                    </div>
                                    
                                    <label class="form-label small mb-1">
                                        <i class="bi bi-briefcase text-info me-1"></i>Karir yang Cocok
                                    </label>
                                    <textarea name="tipe[{{ $tipe }}][karir_cocok]" 
                                              class="form-control form-control-sm" 
                                              rows="2"
                                              placeholder="Karir yang cocok...">{{ $tipeCfg->karir_cocok ?? $info['karir_cocok'] }}</textarea>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Tombol Simpan --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-secondary">
                <i class="bi bi-x-lg me-1"></i>Batal
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Simpan Pengaturan
            </button>
        </div>
    </form>

    {{-- Preview Soal --}}
    @if($soalTes->count() > 0)
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Preview Soal dalam Tes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="50">#</th>
                            <th>Pertanyaan</th>
                            <th width="100">Jawaban A</th>
                            <th width="100">Jawaban B</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($soalTes as $soal)
                        <tr>
                            <td>{{ $soal->pivot->urutan }}</td>
                            <td>
                                <span title="{{ strip_tags($soal->pertanyaan) }}">
                                    {!! Str::limit(strip_tags($soal->pertanyaan), 60) !!}
                                </span>
                            </td>
                            <td>
                                @php $jawaban = $soal->jawaban->sortBy('urutan'); @endphp
                                {{ Str::limit($jawaban->get(0)?->isi_jawaban ?? '-', 20) }}
                            </td>
                            <td>
                                {{ Str::limit($jawaban->get(1)?->isi_jawaban ?? '-', 20) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted">Total {{ $soalTes->count() }} soal dalam tes ini</small>
        </div>
    </div>
    @endif
</div>
@endsection
