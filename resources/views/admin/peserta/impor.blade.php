@extends('layouts.admin')

@section('title', 'Impor Peserta')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Impor Peserta</h1>
        <a href="{{ route('admin.peserta.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses') || session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') ?? session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('errors_impor'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Beberapa peserta gagal diimpor:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('errors_impor') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warnings_impor'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>Catatan import:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('warnings_impor') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('rekap_preview'))
        @php
            $preview = session('rekap_preview');
            $summary = $preview['summary'] ?? [];
            $conflicts = $preview['conflicts'] ?? [];
        @endphp
        <div class="card border-warning shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <h5 class="mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Validasi Data Berbeda</h5>
                        <div class="small">Pilih tindakan untuk peserta yang datanya berbeda dengan data yang sudah ada.</div>
                    </div>
                    <span class="badge bg-dark">{{ count($conflicts) }} konflik</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Baris Dibaca</div>
                            <div class="fs-5 fw-bold">{{ $summary['total'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Siswa Baru</div>
                            <div class="fs-5 fw-bold text-success">{{ $summary['baru'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Tidak Berubah</div>
                            <div class="fs-5 fw-bold text-secondary">{{ $summary['tidak_berubah'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Perlu Keputusan</div>
                            <div class="fs-5 fw-bold text-warning">{{ $summary['konflik'] ?? count($conflicts) }}</div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.peserta.impor-rekap-seleksi.proses') }}" method="POST">
                    @csrf
                    <input type="hidden" name="rekap_preview_token" value="{{ $preview['token'] }}">

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px">Baris</th>
                                    <th>Peserta</th>
                                    <th>Perbedaan</th>
                                    <th style="width: 260px">Keputusan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($conflicts as $conflict)
                                    <tr>
                                        <td class="text-center fw-semibold">{{ $conflict['baris'] }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $conflict['data']['nama'] ?? '-' }}</div>
                                            <div class="small text-muted">{{ $conflict['data']['asal_smp'] ?? '-' }}</div>
                                            @if(!empty($conflict['peserta']))
                                                <div class="small mt-1">
                                                    Data lama:
                                                    <span class="badge bg-light text-dark border">
                                                        {{ $conflict['peserta']['nomor_pendaftaran'] ?? 'tanpa nomor' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-grid gap-1">
                                                @foreach($conflict['differences'] as $diff)
                                                    <div class="border rounded p-2 bg-light">
                                                        <div class="small fw-semibold">{{ $diff['label'] }}</div>
                                                        <div class="row g-2 small">
                                                            <div class="col-md-6">
                                                                <span class="text-muted">Lama:</span>
                                                                <strong>{{ filled($diff['lama']) ? $diff['lama'] : '-' }}</strong>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <span class="text-muted">Baru:</span>
                                                                <strong class="text-success">{{ filled($diff['baru']) ? $diff['baru'] : '-' }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio"
                                                       name="keputusan[{{ $conflict['row_id'] }}]"
                                                       id="baru_{{ $loop->index }}" value="baru" required>
                                                <label class="form-check-label fw-semibold" for="baru_{{ $loop->index }}">
                                                    Tiban dengan data baru
                                                </label>
                                                <div class="form-text">Data Excel akan mengganti data lama.</div>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio"
                                                       name="keputusan[{{ $conflict['row_id'] }}]"
                                                       id="lama_{{ $loop->index }}" value="lama" required>
                                                <label class="form-check-label fw-semibold" for="lama_{{ $loop->index }}">
                                                    Pakai data lama
                                                </label>
                                                <div class="form-text">Baris ini dilewati, data lama tetap.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check2-circle"></i> Terapkan Keputusan
                        </button>
                        <button type="submit" name="aksi_rekap" value="batal" class="btn btn-outline-secondary" formnovalidate>
                            Batalkan Preview
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table"></i> Impor Rekap Seleksi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-7">
                            <p class="text-muted mb-3">
                                Gunakan import ini untuk file rekap berisi peserta, Personality Plus, Modalitas, nilai INDO/INGG/MTK/IPA, total, dan kelas penempatan.
                            </p>
                            <form action="{{ route('admin.peserta.impor-rekap-seleksi.proses') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">File Rekap Excel <span class="text-danger">*</span></label>
                                    <input type="file" name="file_rekap" class="form-control @error('file_rekap') is-invalid @enderror"
                                           accept=".xlsx,.xls" required>
                                    @error('file_rekap')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Format kolom: NO, NAMA, JK, ASAL SMP, PERSONALITY PLUS, MODALITAS, INDO, INGG, MTK, IPA, JML, KLS.</small>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-upload"></i> Impor Rekap Seleksi
                                    </button>
                                    <a href="{{ route('admin.peserta.template-rekap-seleksi') }}" class="btn btn-outline-success">
                                        <i class="bi bi-download"></i> Download Template Rekap
                                    </a>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-5">
                            <div class="alert alert-light border mb-0">
                                <div class="fw-semibold mb-2">Yang dilakukan sistem</div>
                                <ul class="small mb-0">
                                    <li>Membuat atau mengupdate peserta secara otomatis.</li>
                                    <li>Membuat login internal jika email/No HP kosong.</li>
                                    <li>Mengisi hasil tes dan menandai peserta lulus final.</li>
                                    <li>Memberi catatan jika JML tidak sama dengan total nilai.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-excel text-success"></i> Impor dari Excel
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Upload file Excel (.xlsx, .xls) dengan format yang sesuai template.
                    </p>

                    <form action="{{ route('admin.peserta.impor.proses') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">File Excel <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                   accept=".xlsx,.xls" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maksimal 5MB</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload"></i> Impor Peserta
                            </button>
                            <a href="{{ route('admin.peserta.template') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Panduan Format</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Kolom yang diperlukan:</p>
                    <ol class="small">
                        <li>Nama (wajib)</li>
                        <li>Email (wajib, harus unik)</li>
                        <li>Telepon</li>
                        <li>Alamat</li>
                        <li>Asal Sekolah</li>
                        <li>Tahun Ajaran (opsional, contoh 2026-2027)</li>
                        <li>Gelombang (opsional)</li>
                        <li>Jenis Pendaftaran (siswa_baru/pindahan, opsional)</li>
                        <li>Kelas Tujuan (10/11, opsional)</li>
                    </ol>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle"></i> 
                        Password default untuk semua peserta yang diimpor adalah <code>password123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
