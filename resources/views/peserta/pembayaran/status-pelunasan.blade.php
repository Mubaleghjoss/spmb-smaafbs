@extends('layouts.peserta')

@section('title', 'Status Pembayaran Pelunasan')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Status Pembayaran Tahap Pertama</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center g-2 mb-4">
                        <div class="col-4"><div class="border rounded p-2"><small class="text-muted d-block">Tagihan</small><strong>Rp {{ number_format($ringkasan['tagihan'], 0, ',', '.') }}</strong></div></div>
                        <div class="col-4"><div class="border rounded p-2"><small class="text-muted d-block">Terverifikasi</small><strong class="text-success">Rp {{ number_format($ringkasan['terverifikasi'], 0, ',', '.') }}</strong></div></div>
                        <div class="col-4"><div class="border rounded p-2"><small class="text-muted d-block">Sisa</small><strong class="text-danger">Rp {{ number_format($ringkasan['sisa'], 0, ',', '.') }}</strong></div></div>
                    </div>
                    @if(!$pembayaran)
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">Belum ada bukti pembayaran</p>
                            <a href="{{ route('peserta.pembayaran.pelunasan') }}" class="btn btn-success">
                                <i class="bi bi-upload me-2"></i>Upload Bukti
                            </a>
                        </div>
                    @else
                        <div class="text-center mb-4">
                            @if($pembayaran->status === 'menunggu')
                                <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="text-warning">Menunggu Verifikasi</h5>
                                <p class="text-muted">Bukti pembayaran sedang diverifikasi oleh admin</p>
                            @elseif($pembayaran->status === 'terverifikasi')
                                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="text-success">Pembayaran Terverifikasi</h5>
                                <p class="text-muted">Pembayaran Anda sudah diverifikasi. Periksa sisa tagihan di atas.</p>
                                <div class="alert alert-success mt-3 text-start">
                                    <div class="mb-1">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <strong>Pembayaran tahap pertama Anda sudah diterima.</strong>
                                    </div>
                                    <div class="small">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Keputusan penerimaan sebagai peserta didik baru {{ $branding['nama_institusi'] ?? 'SMA Al Furqon' }}
                                        dinyatakan resmi melalui <strong>SK Kelulusan</strong> pada tahap akhir. Mohon menunggu pengumuman dari Tim SPMB.
                                    </div>
                                </div>
                            @else
                                <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="text-danger">Ditolak</h5>
                                <p class="text-muted">{{ $pembayaran->catatan ?? 'Bukti pembayaran ditolak' }}</p>
                                <a href="{{ route('peserta.pembayaran.pelunasan') }}" class="btn btn-warning">
                                    <i class="bi bi-upload me-2"></i>Upload Ulang
                                </a>
                            @endif
                        </div>

                        <hr>
                        
                        @if($pembayaran->catatan && str_contains($pembayaran->catatan, 'Diupload oleh Tim SPMB'))
                        <div class="alert alert-success small mb-3">
                            <i class="bi bi-whatsapp me-1"></i>
                            {{ $pembayaran->catatan }}
                        </div>
                        @endif
                        
                        {{-- Kwitansi Section --}}
                        @if($ringkasan['lunas'] && isset($kwitansi) && $kwitansi)
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-receipt me-2"></i>Kwitansi Pembayaran</h6>
                                <table class="table table-sm table-borderless mb-3">
                                    <tr>
                                        <td class="text-muted" width="40%">No. Kwitansi</td>
                                        <td><strong>{{ $kwitansi['nomor_kwitansi'] }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tanggal Verifikasi</td>
                                        <td>{{ $kwitansi['tanggal_verifikasi']->format('d F Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Nominal</td>
                                        <td><strong>Rp {{ number_format($kwitansi['nominal'], 0, ',', '.') }}</strong></td>
                                    </tr>
                                </table>
                                <a href="{{ route('peserta.pembayaran.kwitansi', $pembayaranKwitansi) }}" target="_blank" class="btn btn-info btn-sm w-100">
                                    <i class="bi bi-printer me-2"></i>Cetak Kwitansi
                                </a>
                            </div>
                        </div>
                        @endif
                        
                        <h6 class="mt-4">Riwayat pembayaran</h6>
                        <div class="list-group list-group-flush border rounded">
                            @foreach($riwayat as $item)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div><strong>Rp {{ number_format($item->nominal, 0, ',', '.') }}</strong><br><small class="text-muted">Upload {{ $item->created_at?->format('d M Y H:i') }}</small></div>
                                        <span class="badge align-self-start bg-{{ $item->status === 'terverifikasi' ? 'success' : ($item->status === 'ditolak' ? 'danger' : 'warning text-dark') }}">{{ ucfirst($item->status) }}</span>
                                    </div>
                                    @if($item->status === 'terverifikasi')<small class="text-success"><i class="bi bi-patch-check me-1"></i>Diverifikasi {{ $item->diverifikasi_pada?->format('d M Y H:i') }}</small>@endif
                                    @if($item->status === 'ditolak' && $item->catatan)<small class="text-danger d-block">{{ $item->catatan }}</small>@endif
                                </div>
                            @endforeach
                        </div>
                        @if(!$ringkasan['lunas'] && !$riwayat->contains('status', 'menunggu'))
                            <a href="{{ route('peserta.pembayaran.pelunasan') }}" class="btn btn-success w-100 mt-3"><i class="bi bi-plus-circle me-1"></i>Tambah pembayaran</a>
                        @endif
                    @endif
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
