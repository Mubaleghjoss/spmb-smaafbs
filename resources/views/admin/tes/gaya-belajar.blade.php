@extends('layouts.admin')

@section('title', 'Pengaturan Gaya Belajar - ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Pengaturan Gaya Belajar</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.tes.index') }}">Manajemen Tes</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.tes.show', $tes) }}">{{ $tes->nama }}</a></li>
                    <li class="breadcrumb-item active">Gaya Belajar</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.tes.gaya-belajar.simpan', $tes) }}" method="POST">
                @csrf
                
                {{-- Status Aktif --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-gear me-2"></i>Status Konfigurasi
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="aktif" name="aktif" value="1"
                                   {{ ($config && $config->aktif) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="aktif">
                                Aktifkan sebagai Tes Gaya Belajar
                            </label>
                        </div>
                        <small class="text-muted">
                            Jika diaktifkan, tes ini akan dihitung sebagai tes gaya belajar (Visual/Auditori/Kinestetik) 
                            dan tidak menggunakan sistem nilai lulus/tidak lulus.
                        </small>
                    </div>
                </div>

                {{-- Mapping Jawaban --}}
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-diagram-3 me-2"></i>Mapping Jawaban ke Tipe Gaya Belajar
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Tentukan tipe gaya belajar untuk setiap pilihan jawaban. 
                            Sistem akan menghitung total jawaban per tipe dan menentukan gaya belajar dominan.
                        </p>
                        
                        <div class="row">
                            @php
                                $currentMapping = $config ? $config->mapping_jawaban : $defaultMapping;
                            @endphp
                            
                            @foreach(['A', 'B', 'C'] as $kode)
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">
                                        <span class="badge bg-secondary me-1">{{ $kode }}</span>
                                        Pilihan {{ $kode }}
                                    </label>
                                    <select name="mapping_jawaban[{{ $kode }}]" class="form-select" required>
                                        @foreach($tipeGayaBelajar as $tipe => $label)
                                            <option value="{{ $tipe }}" 
                                                {{ ($currentMapping[$kode] ?? '') === $tipe ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                        <div class="alert alert-info py-2 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <small>
                                <strong>Logika:</strong> Jika peserta menjawab A pada soal, maka nilai tipe yang dipilih untuk A akan bertambah 1.
                                Tipe dengan total tertinggi menjadi hasil gaya belajar. Jika ada nilai sama, hasilnya gabungan.
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Deskripsi Tipe --}}
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-card-text me-2"></i>Deskripsi Tipe Gaya Belajar
                    </div>
                    <div class="card-body">
                        @php
                            $currentDeskripsi = $config ? ($config->deskripsi_tipe ?? $defaultDeskripsi) : $defaultDeskripsi;
                        @endphp
                        
                        @foreach($tipeGayaBelajar as $tipe => $label)
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    @if($tipe === 'visual')
                                        <i class="bi bi-eye text-primary me-1"></i>
                                    @elseif($tipe === 'auditori')
                                        <i class="bi bi-ear text-success me-1"></i>
                                    @else
                                        <i class="bi bi-hand-index text-warning me-1"></i>
                                    @endif
                                    {{ $label }}
                                </label>
                                <textarea name="deskripsi_tipe[{{ $tipe }}]" class="form-control" rows="2"
                                          placeholder="Deskripsi untuk tipe {{ $label }}...">{{ $currentDeskripsi[$tipe] ?? '' }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan
                    </button>
                    <form action="{{ route('admin.tes.gaya-belajar.init-default', $tes) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary" 
                                onclick="return confirm('Reset ke konfigurasi default?')">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Default
                        </button>
                    </form>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            {{-- Info Soal --}}
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-list-ol me-2"></i>Info Soal
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Total Soal:</strong> 
                        <span class="badge bg-primary">{{ $soalTes->count() }}</span>
                    </p>
                    <p class="text-muted small mb-0">
                        Pastikan setiap soal memiliki 3 pilihan jawaban (A, B, C) yang masing-masing 
                        mewakili tipe gaya belajar yang berbeda.
                    </p>
                </div>
            </div>

            {{-- Simulasi --}}
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <i class="bi bi-calculator me-2"></i>Simulasi Perhitungan
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Masukkan contoh jawaban untuk melihat hasil perhitungan.
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label">Jumlah Jawaban A (Visual)</label>
                        <input type="number" class="form-control simulasi-input" id="simA" min="0" max="{{ $soalTes->count() }}" value="15">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Jawaban B (Auditori)</label>
                        <input type="number" class="form-control simulasi-input" id="simB" min="0" max="{{ $soalTes->count() }}" value="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Jawaban C (Kinestetik)</label>
                        <input type="number" class="form-control simulasi-input" id="simC" min="0" max="{{ $soalTes->count() }}" value="9">
                    </div>
                    
                    <button type="button" class="btn btn-warning w-100" id="btnSimulasi">
                        <i class="bi bi-play-fill me-1"></i> Hitung Simulasi
                    </button>
                    
                    <div id="hasilSimulasi" class="mt-3" style="display: none;">
                        <hr>
                        <h6>Hasil:</h6>
                        <div class="alert alert-success py-2 mb-2">
                            <strong id="hasilLabel">-</strong>
                        </div>
                        <div class="small">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-eye text-primary"></i> Visual:</span>
                                <strong id="nilaiVisual">0</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-ear text-success"></i> Auditori:</span>
                                <strong id="nilaiAuditori">0</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-hand-index text-warning"></i> Kinestetik:</span>
                                <strong id="nilaiKinestetik">0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preview Soal --}}
            @if($soalTes->count() > 0)
            <div class="card">
                <div class="card-header bg-light">
                    <i class="bi bi-eye me-2"></i>Preview Soal ({{ min(5, $soalTes->count()) }} pertama)
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                        @foreach($soalTes->take(5) as $index => $soal)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong class="small">Soal {{ $index + 1 }}</strong>
                                    <span class="badge bg-secondary">{{ $soal->jawaban->count() }} pilihan</span>
                                </div>
                                <p class="mb-1 small text-muted">{{ Str::limit(strip_tags($soal->pertanyaan), 80) }}</p>
                                <div class="small">
                                    @foreach($soal->jawaban->sortBy('urutan')->take(3) as $idx => $jawaban)
                                        <span class="badge bg-light text-dark me-1">
                                            {{ chr(65 + $idx) }}. {{ Str::limit(strip_tags($jawaban->jawaban), 20) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnSimulasi = document.getElementById('btnSimulasi');
    const hasilSimulasi = document.getElementById('hasilSimulasi');
    
    btnSimulasi.addEventListener('click', function() {
        const simA = parseInt(document.getElementById('simA').value) || 0;
        const simB = parseInt(document.getElementById('simB').value) || 0;
        const simC = parseInt(document.getElementById('simC').value) || 0;
        
        // Ambil mapping dari form
        const mappingA = document.querySelector('select[name="mapping_jawaban[A]"]').value;
        const mappingB = document.querySelector('select[name="mapping_jawaban[B]"]').value;
        const mappingC = document.querySelector('select[name="mapping_jawaban[C]"]').value;
        
        // Hitung nilai per tipe
        const nilai = {
            visual: 0,
            auditori: 0,
            kinestetik: 0
        };
        
        nilai[mappingA] += simA;
        nilai[mappingB] += simB;
        nilai[mappingC] += simC;
        
        // Tentukan hasil
        const maxNilai = Math.max(nilai.visual, nilai.auditori, nilai.kinestetik);
        const hasilTipe = [];
        
        if (nilai.visual === maxNilai) hasilTipe.push('Visual');
        if (nilai.auditori === maxNilai) hasilTipe.push('Auditori');
        if (nilai.kinestetik === maxNilai) hasilTipe.push('Kinestetik');
        
        // Tampilkan hasil
        document.getElementById('hasilLabel').textContent = hasilTipe.join(' & ');
        document.getElementById('nilaiVisual').textContent = nilai.visual;
        document.getElementById('nilaiAuditori').textContent = nilai.auditori;
        document.getElementById('nilaiKinestetik').textContent = nilai.kinestetik;
        
        hasilSimulasi.style.display = 'block';
    });
});
</script>
@endpush
@endsection
