@extends('layouts.admin')

@section('title', 'Verifikasi Pembayaran Formulir')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-credit-card me-2"></i>Verifikasi Pembayaran Formulir</h4>
        <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    {{-- Peserta yang belum upload (untuk bantuan Tim SPMB) --}}
    @if(isset($pesertaBelumUpload) && $pesertaBelumUpload->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="bi bi-whatsapp me-2"></i>Bantuan Upload (Peserta Belum Upload)</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Peserta yang menghubungi Tim SPMB via WhatsApp untuk bantuan upload bukti pembayaran.</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pesertaBelumUpload as $p)
                        <tr>
                            <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                            <td>{{ $p->nama }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success" 
                                        data-bs-toggle="modal" data-bs-target="#modalUpload{{ $p->id }}">
                                    <i class="bi bi-upload me-1"></i>Upload Bukti
                                </button>
                            </td>
                        </tr>
                        
                        {{-- Modal Upload --}}
                        <div class="modal fade" id="modalUpload{{ $p->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.verifikasi.pembayaran-formulir.upload', $p) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Bukti Pembayaran</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info small">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Upload bukti pembayaran untuk peserta <strong>{{ $p->nama }}</strong> ({{ $p->nomor_pendaftaran }}).
                                                Bukti akan langsung terverifikasi dan peserta dapat melanjutkan ke tahap berikutnya.
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Bukti Pembayaran <span class="text-danger">*</span></label>
                                                <input type="file" class="form-control" name="bukti" accept="image/*" required>
                                                <div class="form-text">Format: JPG, PNG. Maksimal 2MB</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-lg me-1"></i>Upload & Verifikasi
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Menunggu Verifikasi</h6>
        </div>
        <div class="card-body">
            @if($pembayaran->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Tidak ada pembayaran yang menunggu verifikasi</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No. Pendaftaran</th>
                                <th>Nama</th>
                                <th>Tanggal Upload</th>
                                <th>Bukti</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pembayaran as $p)
                            @if($p->peserta)
                            <tr>
                                <td><code>{{ $p->peserta->nomor_pendaftaran }}</code></td>
                                <td>{{ $p->peserta->nama }}</td>
                                <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ Storage::url($p->bukti_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-image me-1"></i>Lihat Bukti
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form action="{{ route('admin.verifikasi.pembayaran-formulir.terima', $p) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Verifikasi pembayaran ini?')">
                                                <i class="bi bi-check-lg me-1"></i>Terima
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" data-bs-target="#modalTolak{{ $p->id }}">
                                            <i class="bi bi-x-lg me-1"></i>Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            {{-- Modal Tolak --}}
                            <div class="modal fade" id="modalTolak{{ $p->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.verifikasi.pembayaran-formulir.tolak', $p) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Tolak Pembayaran</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Peserta: <strong>{{ $p->peserta->nama }}</strong></p>
                                                <div class="mb-3">
                                                    <label class="form-label">Alasan Penolakan</label>
                                                    <textarea class="form-control" name="alasan" rows="3" required placeholder="Masukkan alasan penolakan..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger">Tolak</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @else
                            <tr class="table-warning">
                                <td colspan="5">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Data pembayaran tanpa peserta (ID: {{ $p->id }}) - 
                                    <form action="{{ route('admin.verifikasi.pembayaran-formulir.tolak', $p) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="alasan" value="Data peserta tidak ditemukan">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data pembayaran ini?')">
                                            <i class="bi bi-trash me-1"></i>Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{ $pembayaran->links() }}
            @endif
        </div>
    </div>
    
    {{-- Legend --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan Aksi:</strong>
                <span class="ms-3"><i class="bi bi-image text-primary"></i> Lihat Bukti</span>
                <span class="ms-3"><i class="bi bi-check-lg text-success"></i> Verifikasi</span>
                <span class="ms-3"><i class="bi bi-x-lg text-danger"></i> Tolak</span>
                <span class="ms-3"><i class="bi bi-receipt text-info"></i> Kwitansi</span>
            </small>
        </div>
    </div>
    
    {{-- Pembayaran Terverifikasi (untuk cetak kwitansi) --}}
    @php
        $pembayaranTerverifikasi = \App\Models\Pembayaran::with('peserta')
            ->where('jenis', 'formulir')
            ->where('status', 'terverifikasi')
            ->whereNotNull('nomor_kwitansi')
            ->whereHas('peserta') // Hanya ambil yang punya peserta
            ->latest()
            ->take(10)
            ->get();
    @endphp
    
    @if($pembayaranTerverifikasi->count() > 0)
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Pembayaran Terverifikasi (10 Terakhir)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>No. Kwitansi</th>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>Tanggal Verifikasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pembayaranTerverifikasi as $pv)
                        @if($pv->peserta)
                        <tr>
                            <td><code>{{ $pv->nomor_kwitansi }}</code></td>
                            <td><code>{{ $pv->peserta->nomor_pendaftaran }}</code></td>
                            <td>{{ $pv->peserta->nama }}</td>
                            <td>{{ $pv->diverifikasi_pada?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.verifikasi.kwitansi', $pv) }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="bi bi-receipt me-1"></i>Kwitansi
                                </a>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
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
