@extends('layouts.admin')

@section('title', 'Verifikasi Wawancara - Tahap 5')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-people me-2"></i>Verifikasi Wawancara & Berkas</h4>
            <p class="text-muted mb-0">Tahap 5 - Interview Orang Tua/Wali dan Calon Siswa</p>
        </div>
        <div>
            <a href="{{ route('admin.pengaturan.wawancara') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-gear me-1"></i>Pengaturan Pertanyaan
            </a>
            <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
    
    {{-- Statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3">
                            <i class="bi bi-hourglass-split text-warning fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['menunggu'] }}</h3>
                            <small class="text-muted">Menunggu Wawancara</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-25 p-3 me-3">
                            <i class="bi bi-clipboard-check text-info fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['sudah_wawancara'] }}</h3>
                            <small class="text-muted">Sudah Wawancara</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['lulus'] }}</h3>
                            <small class="text-muted">Lulus Wawancara</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-danger bg-opacity-25 p-3 me-3">
                            <i class="bi bi-x-circle text-danger fs-4"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $statistik['tidak_lulus'] }}</h3>
                            <small class="text-muted">Tidak Lulus</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Daftar Peserta --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Peserta Tahap 5</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Pendaftaran</th>
                            <th>Nama Peserta</th>
                            <th>Asal Sekolah</th>
                            <th>Diisi Peserta</th>
                            <th>Status Wawancara</th>
                            <th>Tanggal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peserta as $p)
                        @php
                            $wawancara = $p->wawancara;
                            $statusWawancara = $wawancara?->hasil_wawancara ?? 'belum';
                        @endphp
                        <tr>
                            <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                            <td>
                                <strong>{{ $p->nama }}</strong>
                                @if($p->formulirSpmb?->nama_ayah)
                                <br><small class="text-muted">Ayah: {{ $p->formulirSpmb->nama_ayah }}</small>
                                @endif
                            </td>
                            <td>{{ $p->formulirSpmb?->asal_sekolah ?? $p->asal_sekolah ?? '-' }}</td>
                            <td>
                                @if($wawancara?->diisi_peserta_pada)
                                    <span class="badge bg-success"><i class="bi bi-check me-1"></i>Sudah</span>
                                    <br><small class="text-muted">{{ $wawancara->diisi_peserta_pada->format('d/m/Y') }}</small>
                                @else
                                    <span class="badge bg-secondary">Belum</span>
                                @endif
                            </td>
                            <td>
                                @if($statusWawancara === 'lulus')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Lulus</span>
                                @elseif($statusWawancara === 'tidak_lulus')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Tidak Lulus</span>
                                @elseif($statusWawancara === 'menunggu')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass me-1"></i>Menunggu Review</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-clock me-1"></i>Belum Wawancara</span>
                                @endif
                            </td>
                            <td>
                                @if($wawancara?->tanggal_wawancara)
                                    {{ $wawancara->tanggal_wawancara->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('admin.verifikasi.wawancara.form', $p) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-upload me-1"></i>Input Manual
                                    </a>
                                    @if($wawancara?->surat_pernyataan_siswa || $wawancara?->surat_pernyataan_ortu)
                                    <a href="{{ route('admin.verifikasi.wawancara.surat-pernyataan', $p) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-file-earmark-text me-1"></i>Surat
                                    </a>
                                    @endif
                                    <a href="{{ route('admin.verifikasi.wawancara.cetak', $p) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="bi bi-printer me-1"></i>Cetak
                                    </a>
                                    @if($statusWawancara !== 'lulus')
                                    <form action="{{ route('admin.verifikasi.wawancara.loloskan', $p) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Loloskan peserta ini?')" title="Loloskan Wawancara">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada peserta di tahap wawancara
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($peserta->hasPages())
        <div class="card-footer bg-white">
            {{ $peserta->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
