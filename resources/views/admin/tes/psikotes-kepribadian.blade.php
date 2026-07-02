@extends('layouts.admin')

@section('title', 'Pengaturan Psikotes Kepribadian - ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail Tes
            </a>
            <h1 class="h3 mb-0">
                <i class="bi bi-person-badge me-2"></i>Pengaturan Psikotes Kepribadian
            </h1>
            <small class="text-muted">{{ $tes->nama }}</small>
        </div>
        @if($config->isEmpty())
        <form action="{{ route('admin.tes.psikotes-kepribadian.init-default', $tes) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="bi bi-magic me-1"></i>Terapkan Konfigurasi Default
            </button>
        </form>
        @endif
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info Soal --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Soal</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2"><strong>Total Soal:</strong> {{ $soalTes->count() }} soal</p>
                    <p class="mb-0"><strong>Catatan:</strong> Pastikan jumlah soal minimal 32 untuk psikotes kepribadian standar.</p>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning mb-0 py-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Penting:</strong> Nomor soal mengacu pada urutan soal di tes ini, bukan ID soal.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.tes.psikotes-kepribadian.simpan', $tes) }}" method="POST">
        @csrf
        
        <div class="row">
            {{-- Nilai Jawaban --}}
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-123 me-2"></i>Nilai Jawaban</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Tentukan nilai untuk setiap pilihan jawaban (A, B, C, D)</p>
                        
                        @php
                            $nilaiMap = $nilaiJawaban->keyBy('kode_jawaban');
                        @endphp
                        
                        @foreach(['A', 'B', 'C', 'D'] as $kode)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jawaban {{ $kode }}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-{{ ['A'=>'danger','B'=>'warning','C'=>'success','D'=>'primary'][$kode] }} text-white">
                                    {{ $kode }}
                                </span>
                                <input type="number" 
                                       name="nilai_jawaban[{{ $kode }}]" 
                                       class="form-control" 
                                       value="{{ $nilaiMap->get($kode)?->nilai ?? ($loop->iteration) }}"
                                       min="1" max="10" required>
                                <span class="input-group-text">poin</span>
                            </div>
                        </div>
                        @endforeach
                        
                        <div class="alert alert-info py-2 mb-0">
                            <small><i class="bi bi-lightbulb me-1"></i>Default: A=1, B=2, C=3, D=4</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mapping Tipe Kepribadian --}}
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Mapping Tipe Kepribadian</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Tentukan nomor soal untuk setiap tipe kepribadian (pisahkan dengan koma)</p>
                        
                        @php
                            $configMap = $config->keyBy('tipe_kepribadian');
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
                        @endphp
                        
                        @foreach($tipeKepribadian as $tipe => $label)
                        <div class="card mb-3 border-{{ $colors[$tipe] }}">
                            <div class="card-header bg-{{ $colors[$tipe] }} bg-opacity-10">
                                <h6 class="mb-0">
                                    <i class="bi bi-{{ $icons[$tipe] }} text-{{ $colors[$tipe] }} me-2"></i>
                                    <span class="text-{{ $colors[$tipe] }}">{{ $label }}</span>
                                </h6>
                            </div>
                            <div class="card-body py-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Soal</label>
                                        <input type="text" 
                                               name="config[{{ $tipe }}][nomor_soal]" 
                                               class="form-control"
                                               placeholder="Contoh: 1, 5, 9, 13, 17, 21, 25, 29"
                                               value="{{ $configMap->get($tipe) ? implode(', ', $configMap->get($tipe)->nomor_soal) : implode(', ', $defaultMapping[$tipe]) }}">
                                        <small class="text-muted">Default: {{ implode(', ', $defaultMapping[$tipe]) }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Deskripsi (opsional)</label>
                                        <input type="text" 
                                               name="config[{{ $tipe }}][deskripsi]" 
                                               class="form-control"
                                               placeholder="Deskripsi singkat tipe kepribadian"
                                               value="{{ $configMap->get($tipe)?->deskripsi ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Tombol Simpan --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-2"></i>Simpan Pengaturan
                        </button>
                        <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary btn-lg ms-2">
                            Batal
                        </a>
                    </div>
                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalSimulasi">
                        <i class="bi bi-play-circle me-1"></i>Simulasi Perhitungan
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Daftar Soal --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ol me-2"></i>Daftar Soal ({{ $soalTes->count() }} soal)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="60" class="text-center">No.</th>
                            <th>Pertanyaan</th>
                            <th width="120" class="text-center">Tipe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($soalTes as $index => $soal)
                        @php
                            $nomorSoal = $index + 1;
                            $tipeSoal = null;
                            foreach($defaultMapping as $tipe => $nomors) {
                                if (in_array($nomorSoal, $nomors)) {
                                    $tipeSoal = $tipe;
                                    break;
                                }
                            }
                        @endphp
                        <tr>
                            <td class="text-center">
                                <span class="badge bg-{{ $tipeSoal ? $colors[$tipeSoal] : 'secondary' }}">
                                    {{ $nomorSoal }}
                                </span>
                            </td>
                            <td>
                                <small class="text-truncate d-block" style="max-width: 500px;">
                                    {!! strip_tags($soal->pertanyaan) !!}
                                </small>
                            </td>
                            <td class="text-center">
                                @if($tipeSoal)
                                <span class="badge bg-{{ $colors[$tipeSoal] }} bg-opacity-75">
                                    {{ ucfirst($tipeSoal) }}
                                </span>
                                @else
                                <span class="badge bg-secondary">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                Belum ada soal di tes ini.
                                <a href="{{ route('admin.tes.soal', $tes) }}">Tambah soal</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Simulasi --}}
<div class="modal fade" id="modalSimulasi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-play-circle me-2"></i>Simulasi Perhitungan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Masukkan jawaban simulasi untuk melihat hasil perhitungan:</p>
                
                <div class="row mb-4">
                    @foreach($tipeKepribadian as $tipe => $label)
                    <div class="col-md-6 mb-3">
                        <div class="card border-{{ $colors[$tipe] }}">
                            <div class="card-header bg-{{ $colors[$tipe] }} bg-opacity-10 py-2">
                                <strong class="text-{{ $colors[$tipe] }}">{{ $label }}</strong>
                                <small class="text-muted ms-2">(Soal: {{ implode(', ', $defaultMapping[$tipe]) }})</small>
                            </div>
                            <div class="card-body py-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($defaultMapping[$tipe] as $nomor)
                                    <select class="form-select form-select-sm simulasi-jawaban" 
                                            data-nomor="{{ $nomor }}" style="width: 60px;">
                                        <option value="">-</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="text-center mb-3">
                    <button type="button" class="btn btn-primary" id="btnHitungSimulasi">
                        <i class="bi bi-calculator me-1"></i>Hitung Hasil
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnResetSimulasi">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </button>
                </div>

                {{-- Hasil Simulasi --}}
                <div id="hasilSimulasi" class="d-none">
                    <hr>
                    <h5 class="text-center mb-3">Hasil Simulasi</h5>
                    <div class="row">
                        @foreach($tipeKepribadian as $tipe => $label)
                        <div class="col-md-3 text-center">
                            <div class="card border-{{ $colors[$tipe] }}">
                                <div class="card-body py-2">
                                    <h6 class="text-{{ $colors[$tipe] }}">{{ $label }}</h6>
                                    <h3 class="mb-0" id="nilai-{{ $tipe }}">0</h3>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="text-center mt-3">
                        <div class="alert alert-success py-2">
                            <strong>Hasil Kepribadian:</strong>
                            <span id="hasilKepribadian" class="fs-5 fw-bold ms-2">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nilaiJawaban = {
        'A': {{ $nilaiMap->get('A')?->nilai ?? 1 }},
        'B': {{ $nilaiMap->get('B')?->nilai ?? 2 }},
        'C': {{ $nilaiMap->get('C')?->nilai ?? 3 }},
        'D': {{ $nilaiMap->get('D')?->nilai ?? 4 }}
    };
    
    const mapping = {
        'koleris': [1, 5, 9, 13, 17, 21, 25, 29],
        'sanguin': [2, 6, 10, 14, 18, 22, 26, 30],
        'plegmatis': [3, 7, 11, 15, 19, 23, 27, 31],
        'melankolis': [4, 8, 12, 16, 20, 24, 28, 32]
    };

    document.getElementById('btnHitungSimulasi').addEventListener('click', function() {
        // Kumpulkan jawaban
        const jawaban = {};
        document.querySelectorAll('.simulasi-jawaban').forEach(select => {
            const nomor = select.dataset.nomor;
            if (select.value) {
                jawaban[nomor] = select.value;
            }
        });

        // Hitung per tipe
        const hasil = {};
        for (const [tipe, nomors] of Object.entries(mapping)) {
            let total = 0;
            nomors.forEach(nomor => {
                if (jawaban[nomor]) {
                    total += nilaiJawaban[jawaban[nomor]] || 0;
                }
            });
            hasil[tipe] = total;
            document.getElementById('nilai-' + tipe).textContent = total;
        }

        // Tentukan hasil tertinggi
        const maxTipe = Object.keys(hasil).reduce((a, b) => hasil[a] > hasil[b] ? a : b);
        document.getElementById('hasilKepribadian').textContent = maxTipe.charAt(0).toUpperCase() + maxTipe.slice(1);
        
        document.getElementById('hasilSimulasi').classList.remove('d-none');
    });

    document.getElementById('btnResetSimulasi').addEventListener('click', function() {
        document.querySelectorAll('.simulasi-jawaban').forEach(select => {
            select.value = '';
        });
        document.getElementById('hasilSimulasi').classList.add('d-none');
    });
});
</script>
@endpush
@endsection
