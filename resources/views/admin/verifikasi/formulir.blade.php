@extends('layouts.admin')

@section('title', 'Verifikasi Formulir SPMB')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Verifikasi Formulir SPMB</h4>
        <div>
            <a href="{{ route('admin.verifikasi.formulir.ekspor') }}" class="btn btn-success me-2">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </a>
            <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    {{-- Statistik Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <a href="{{ route('admin.verifikasi.formulir', ['filter' => 'semua']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ ($filter ?? '') === 'semua' ? 'border-primary border-2' : '' }}">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-files text-primary" style="font-size: 1.5rem;"></i>
                        <h4 class="mb-0 mt-2">{{ $statistik['total'] ?? 0 }}</  h4>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.verifikasi.formulir', ['filter' => 'menunggu']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ ($filter ?? 'menunggu') === 'menunggu' ? 'border-warning border-2' : '' }}">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-hourglass-split text-warning" style="font-size: 1.5rem;"></i>
                        <h4 class="mb-0 mt-2">{{ $statistik['menunggu'] ?? 0 }}</h4>
                        <small class="text-muted">Menunggu</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.verifikasi.formulir', ['filter' => 'terverifikasi']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ ($filter ?? '') === 'terverifikasi' ? 'border-success border-2' : '' }}">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-check-circle text-success" style="font-size: 1.5rem;"></i>
                        <h4 class="mb-0 mt-2">{{ $statistik['terverifikasi'] ?? 0 }}</h4>
                        <small class="text-muted">Terverifikasi</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.verifikasi.formulir', ['filter' => 'ditolak']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ ($filter ?? '') === 'ditolak' ? 'border-danger border-2' : '' }}">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-x-circle text-danger" style="font-size: 1.5rem;"></i>
                        <h4 class="mb-0 mt-2">{{ $statistik['ditolak'] ?? 0 }}</h4>
                        <small class="text-muted">Ditolak</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.verifikasi.formulir', ['filter' => 'belum_lengkap']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm {{ ($filter ?? '') === 'belum_lengkap' ? 'border-info border-2' : '' }}">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-exclamation-triangle text-info" style="font-size: 1.5rem;"></i>
                        <h4 class="mb-0 mt-2">{{ $statistik['belum_lengkap'] ?? 0 }}</h4>
                        <small class="text-muted">Berkas Belum Lengkap</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                @if(($filter ?? 'menunggu') === 'menunggu')
                    <i class="bi bi-hourglass-split text-warning me-2"></i>Formulir Menunggu Verifikasi
                @elseif($filter === 'semua')
                    <i class="bi bi-files text-primary me-2"></i>Semua Formulir
                @elseif($filter === 'belum_lengkap')
                    <i class="bi bi-exclamation-triangle text-info me-2"></i>Berkas Belum Lengkap
                @else
                    <i class="bi bi-file-earmark-text me-2"></i>Daftar Formulir
                @endif
            </h6>
        </div>
        <div class="card-body">
            @if($formulir->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Tidak ada data formulir</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No. Pendaftaran</th>
                                <th>Nama</th>
                                <th>Asal Sekolah</th>
                                <th>Status Berkas</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($formulir as $f)
                            @php
                                $berkasLengkap = $f->file_kk && $f->file_akta && $f->file_ijazah && $f->file_bpjs && $f->file_ktp_ibu && $f->file_ktp_ayah;
                                $jumlahBerkas = collect([$f->file_kk, $f->file_akta, $f->file_ijazah, $f->file_bpjs, $f->file_ktp_ibu, $f->file_ktp_ayah])->filter()->count();
                            @endphp
                            <tr>
                                <td><code class="bg-light px-2 py-1 rounded">{{ $f->peserta->nomor_pendaftaran }}</code></td>
                                <td>
                                    <strong>{{ $f->nama_lengkap }}</strong>
                                    <br><small class="text-muted">{{ $f->peserta->email ?? '-' }}</small>
                                </td>
                                <td>{{ $f->asal_sekolah ?? '-' }}</td>
                                <td>
                                    @if($berkasLengkap)
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Lengkap (6/6)</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>{{ $jumlahBerkas }}/6</span>
                                    @endif
                                </td>
                                <td>
                                    @if($f->status_verifikasi === 'menunggu')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>
                                    @elseif($f->status_verifikasi === 'terverifikasi')
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Terverifikasi</span>
                                    @elseif($f->status_verifikasi === 'ditolak')
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Ditolak</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="bi bi-file-earmark me-1"></i>Draft</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalBerkas{{ $f->id }}">
                                            <i class="bi bi-folder2-open me-1"></i>Berkas
                                        </button>
                                        <a href="{{ route('admin.verifikasi.formulir.detail', $f) }}" 
                                           class="btn btn-sm btn-info text-white">
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
                                        @if($f->status_verifikasi === 'menunggu')
                                        <form action="{{ route('admin.verifikasi.formulir.terima', $f) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('Verifikasi formulir {{ $f->nama_lengkap }}?')">
                                                <i class="bi bi-check-lg me-1"></i>Terima
                                            </button>
                                        </form>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalTolak{{ $f->id }}">
                                            <i class="bi bi-x-lg me-1"></i>Tolak
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            
                            {{-- Modal Berkas --}}
                            <div class="modal fade" id="modalBerkas{{ $f->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="bi bi-folder2-open me-2"></i>Berkas Peserta</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-3">
                                                <strong>{{ $f->nama_lengkap }}</strong> 
                                                <code class="ms-2">{{ $f->peserta->nomor_pendaftaran }}</code>
                                                <span class="badge {{ $berkasLengkap ? 'bg-success' : 'bg-warning text-dark' }} ms-2">
                                                    {{ $jumlahBerkas }}/6 Berkas
                                                </span>
                                            </p>
                                            <div class="row g-3">
                                                {{-- KK --}}
                                                <div class="col-md-4">
                                                    <div class="card h-100 {{ $f->file_kk ? 'border-success' : 'border-secondary' }}">
                                                        <div class="card-body text-center py-3">
                                                            <i class="bi bi-{{ $f->file_kk ? 'file-earmark-check text-success' : 'file-earmark-x text-secondary' }}" style="font-size: 2rem;"></i>
                                                            <p class="mb-2 mt-2 fw-bold">Kartu Keluarga</p>
                                                            @if($f->file_kk)
                                                                <a href="{{ Storage::url($f->file_kk) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                                </a>
                                                            @else
                                                                <span class="badge bg-secondary">Belum Upload</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- Akta --}}
                                                <div class="col-md-4">
                                                    <div class="card h-100 {{ $f->file_akta ? 'border-success' : 'border-secondary' }}">
                                                        <div class="card-body text-center py-3">
                                                            <i class="bi bi-{{ $f->file_akta ? 'file-earmark-check text-success' : 'file-earmark-x text-secondary' }}" style="font-size: 2rem;"></i>
                                                            <p class="mb-2 mt-2 fw-bold">Akta Lahir</p>
                                                            @if($f->file_akta)
                                                                <a href="{{ Storage::url($f->file_akta) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                                </a>
                                                            @else
                                                                <span class="badge bg-secondary">Belum Upload</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- Ijazah --}}
                                                <div class="col-md-4">
                                                    <div class="card h-100 {{ $f->file_ijazah ? 'border-success' : 'border-secondary' }}">
                                                        <div class="card-body text-center py-3">
                                                            <i class="bi bi-{{ $f->file_ijazah ? 'file-earmark-check text-success' : 'file-earmark-x text-secondary' }}" style="font-size: 2rem;"></i>
                                                            <p class="mb-2 mt-2 fw-bold">Ijazah SMP</p>
                                                            @if($f->file_ijazah)
                                                                <a href="{{ Storage::url($f->file_ijazah) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                                </a>
                                                            @else
                                                                <span class="badge bg-secondary">Belum Upload</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- BPJS --}}
                                                <div class="col-md-4">
                                                    <div class="card h-100 {{ $f->file_bpjs ? 'border-success' : 'border-secondary' }}">
                                                        <div class="card-body text-center py-3">
                                                            <i class="bi bi-{{ $f->file_bpjs ? 'file-earmark-check text-success' : 'file-earmark-x text-secondary' }}" style="font-size: 2rem;"></i>
                                                            <p class="mb-2 mt-2 fw-bold">Kartu BPJS</p>
                                                            @if($f->file_bpjs)
                                                                <a href="{{ Storage::url($f->file_bpjs) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                                </a>
                                                            @else
                                                                <span class="badge bg-secondary">Belum Upload</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- KTP Ibu --}}
                                                <div class="col-md-4">
                                                    <div class="card h-100 {{ $f->file_ktp_ibu ? 'border-success' : 'border-secondary' }}">
                                                        <div class="card-body text-center py-3">
                                                            <i class="bi bi-{{ $f->file_ktp_ibu ? 'file-earmark-check text-success' : 'file-earmark-x text-secondary' }}" style="font-size: 2rem;"></i>
                                                            <p class="mb-2 mt-2 fw-bold">KTP Ibu</p>
                                                            @if($f->file_ktp_ibu)
                                                                <a href="{{ Storage::url($f->file_ktp_ibu) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                                </a>
                                                            @else
                                                                <span class="badge bg-secondary">Belum Upload</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- KTP Ayah --}}
                                                <div class="col-md-4">
                                                    <div class="card h-100 {{ $f->file_ktp_ayah ? 'border-success' : 'border-secondary' }}">
                                                        <div class="card-body text-center py-3">
                                                            <i class="bi bi-{{ $f->file_ktp_ayah ? 'file-earmark-check text-success' : 'file-earmark-x text-secondary' }}" style="font-size: 2rem;"></i>
                                                            <p class="mb-2 mt-2 fw-bold">KTP Ayah</p>
                                                            @if($f->file_ktp_ayah)
                                                                <a href="{{ Storage::url($f->file_ktp_ayah) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                                </a>
                                                            @else
                                                                <span class="badge bg-secondary">Belum Upload</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="{{ route('admin.verifikasi.formulir.detail', $f) }}" class="btn btn-info">
                                                <i class="bi bi-eye me-1"></i>Lihat Detail Lengkap
                                            </a>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="bi bi-x me-1"></i>Tutup
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Modal Tolak --}}
                            @if($f->status_verifikasi === 'menunggu')
                            <div class="modal fade" id="modalTolak{{ $f->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.verifikasi.formulir.tolak', $f) }}" method="POST">
                                            @csrf
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Tolak Formulir</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Peserta: <strong>{{ $f->nama_lengkap }}</strong></p>
                                                <div class="mb-3">
                                                    <label class="form-label">Alasan Penolakan</label>
                                                    <textarea class="form-control" name="alasan" rows="3" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="bi bi-x me-1"></i>Batal
                                                </button>
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-x-circle me-1"></i>Tolak
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $formulir->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
    
    {{-- Legend --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan Aksi:</strong>
                <span class="ms-3"><i class="bi bi-folder2-open text-primary"></i> Lihat Berkas</span>
                <span class="ms-3"><i class="bi bi-eye text-info"></i> Lihat Detail</span>
                <span class="ms-3"><i class="bi bi-check-lg text-success"></i> Verifikasi</span>
                <span class="ms-3"><i class="bi bi-x-lg text-danger"></i> Tolak</span>
            </small>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
@endpush
@endsection
