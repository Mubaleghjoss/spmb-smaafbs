@extends('layouts.admin')

@section('title', 'Pengaturan Profiling - ' . $tes->nama)

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="bi bi-person-gear me-2"></i>Pengaturan Profiling (PiES)
                    </h4>
                    <p class="text-muted mb-0">{{ $tes->nama }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>

            @if(session('sukses'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Info Profiling --}}
            <div class="alert alert-info mb-4">
                <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Tentang Profiling (PiES Self Potency Method)</h6>
                <p class="mb-2">Tes Profiling mengukur 5 pilar potensi diri yang terintegrasi dengan konsep Intelligence Quotient (QX):</p>
                <div class="row">
                    @foreach($pilarList as $pilar => $info)
                    <div class="col-md-4 col-lg mb-2">
                        <span class="badge bg-{{ $info['warna'] }} me-1">
                            <i class="bi bi-{{ $info['icon'] }}"></i> {{ $info['kode_qx'] }}
                        </span>
                        <small>{{ $info['nama'] }} ({{ $info['nama_qx'] }})</small>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Tombol Inisialisasi Default --}}
            @if(!$config)
            <div class="card mb-4">
                <div class="card-body text-center py-5">
                    <i class="bi bi-gear display-1 text-muted mb-3"></i>
                    <h5>Konfigurasi Profiling Belum Diatur</h5>
                    <p class="text-muted">Klik tombol di bawah untuk menerapkan konfigurasi default.</p>
                    <form action="{{ route('admin.tes.profiling.init-default', $tes) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-magic me-2"></i>Terapkan Konfigurasi Default
                        </button>
                    </form>
                </div>
            </div>
            @else
            <form action="{{ route('admin.tes.profiling.simpan', $tes) }}" method="POST">
                @csrf
                
                {{-- Status Konfigurasi --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-toggle-on me-2"></i>Status Konfigurasi</h6>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="aktif" value="1" 
                                           id="aktif" {{ $config->aktif ? 'checked' : '' }}>
                                    <label class="form-check-label" for="aktif">
                                        Aktifkan Profiling untuk tes ini
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Soal</label>
                                <input type="number" class="form-control" name="jumlah_soal" 
                                       value="{{ $config->jumlah_soal ?? 30 }}" min="1" max="100">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mapping Jawaban --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Mapping Jawaban ke Pilar</h6>
                        <div>
                            <button type="button" class="btn btn-sm btn-success me-2" onclick="isiSemuaPilar()">
                                <i class="bi bi-check-all me-1"></i>Isi Semua Pilar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="kosongkanSemua()">
                                <i class="bi bi-x-lg me-1"></i>Kosongkan Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="resetToDefault()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset ke Default
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Tentukan pilar mana yang akan mendapat skor untuk setiap jawaban pada setiap soal.
                            Kosongkan jika jawaban tersebut tidak memberikan skor ke pilar manapun.
                        </p>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 60px;">No</th>
                                        <th class="text-center">Soal</th>
                                        <th class="text-center" style="width: 120px;">Jawaban A</th>
                                        <th class="text-center" style="width: 120px;">Jawaban B</th>
                                        <th class="text-center" style="width: 120px;">Jawaban C</th>
                                        <th class="text-center" style="width: 120px;">Jawaban D</th>
                                        <th class="text-center" style="width: 120px;">Jawaban E</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($i = 1; $i <= ($config->jumlah_soal ?? 30); $i++)
                                    @php
                                        $soal = $soalTes->get($i - 1);
                                        $mapData = $mapping->get($i);
                                        $defaultMap = $defaultMapping[$i] ?? [];
                                    @endphp
                                    <tr>
                                        <td class="text-center align-middle">{{ $i }}</td>
                                        <td class="small">
                                            @if($soal)
                                                {{ Str::limit(strip_tags($soal->pertanyaan), 50) }}
                                            @else
                                                <span class="text-muted">Soal {{ $i }}</span>
                                            @endif
                                        </td>
                                        @foreach(['a', 'b', 'c', 'd', 'e'] as $jawaban)
                                        <td>
                                            <select class="form-select form-select-sm mapping-select" 
                                                    name="mapping[{{ $i }}][{{ $jawaban }}]"
                                                    data-soal="{{ $i }}" data-jawaban="{{ $jawaban }}"
                                                    data-default="{{ $defaultMap[$jawaban] ?? '' }}">
                                                <option value="">-</option>
                                                @foreach($pilarList as $pilar => $info)
                                                <option value="{{ $pilar }}" 
                                                    {{ ($mapData ? $mapData->{'jawaban_' . $jawaban} : ($defaultMap[$jawaban] ?? '')) == $pilar ? 'selected' : '' }}>
                                                    {{ $info['kode_qx'] }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Deskripsi Pilar --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-card-text me-2"></i>Deskripsi Pilar</h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionPilar">
                            @foreach($pilarList as $pilar => $info)
                            @php
                                $desc = $pilarDeskripsi->get($pilar);
                            @endphp
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#pilar-{{ $pilar }}">
                                        <span class="badge bg-{{ $info['warna'] }} me-2">
                                            <i class="bi bi-{{ $info['icon'] }}"></i>
                                        </span>
                                        {{ $info['nama'] }} ({{ $info['kode_qx'] }} - {{ $info['nama_qx'] }})
                                    </button>
                                </h2>
                                <div id="pilar-{{ $pilar }}" class="accordion-collapse collapse" 
                                     data-bs-parent="#accordionPilar">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label class="form-label">Deskripsi</label>
                                            <textarea class="form-control" name="pilar[{{ $pilar }}][deskripsi]" 
                                                      rows="3">{{ $desc->deskripsi ?? $info['deskripsi'] }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Kekuatan</label>
                                            <textarea class="form-control" name="pilar[{{ $pilar }}][kekuatan]" 
                                                      rows="2">{{ $desc->kekuatan ?? $info['kekuatan'] }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Saran Pengembangan</label>
                                            <textarea class="form-control" name="pilar[{{ $pilar }}][saran_pengembangan]" 
                                                      rows="2">{{ $desc->saran_pengembangan ?? $info['saran_pengembangan'] }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Tombol Simpan --}}
                <div class="d-flex justify-content-between">
                    <form action="{{ route('admin.tes.profiling.hapus', $tes) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Yakin ingin menghapus konfigurasi Profiling?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>Hapus Konfigurasi
                        </button>
                    </form>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-lg me-2"></i>Simpan Pengaturan
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Pola standar: A=kreatif, B=emosional, C=aksi, D=logika, E=spiritual
const pilarMapping = {
    'a': 'kreatif',
    'b': 'emosional',
    'c': 'aksi',
    'd': 'logika',
    'e': 'spiritual'
};

function isiSemuaPilar() {
    if (!confirm('Isi semua mapping dengan pola standar?\n\nA = Kreatif (CQ)\nB = Emosional (EQ)\nC = Aksi (AQ)\nD = Logika (IQ)\nE = Spiritual (SQ)')) return;
    
    document.querySelectorAll('.mapping-select').forEach(select => {
        const jawaban = select.dataset.jawaban;
        if (pilarMapping[jawaban]) {
            select.value = pilarMapping[jawaban];
        }
    });
}

function kosongkanSemua() {
    if (!confirm('Kosongkan semua mapping jawaban?')) return;
    
    document.querySelectorAll('.mapping-select').forEach(select => {
        select.value = '';
    });
}

function resetToDefault() {
    if (!confirm('Reset semua mapping ke nilai default?')) return;
    
    document.querySelectorAll('.mapping-select').forEach(select => {
        select.value = select.dataset.default || '';
    });
}
</script>
@endpush
@endsection
