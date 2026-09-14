@extends('layouts.peserta')

@section('title', 'Status Pembayaran Formulir')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Status Pembayaran Formulir</h5>
                </div>
                <div class="card-body">
                    @if(!$pembayaran)
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">Belum ada bukti pembayaran</p>
                            <a href="{{ route('peserta.pembayaran.formulir') }}" class="btn btn-success">
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
                                @if($peserta->status_kuota === \App\Models\Peserta::STATUS_KUOTA_DALAM)
                                    <p class="text-muted mb-1">Bukti pembayaran sedang diverifikasi oleh panitia.</p>
                                    <span class="badge bg-success">Kuota Diamankan #{{ $peserta->urutan_kuota }}</span>
                                    <p class="small text-muted mt-2 mb-0">Urutan ditentukan saat bukti diupload. Tes online tetap menunggu bukti dinyatakan valid.</p>
                                @elseif($peserta->status_kuota === \App\Models\Peserta::STATUS_KUOTA_WAITING)
                                    <p class="text-muted mb-1">Bukti pembayaran sedang diverifikasi oleh panitia.</p>
                                    <span class="badge bg-warning text-dark">Waiting List #{{ $peserta->urutan_kuota }}</span>
                                    <p class="small text-muted mt-2 mb-0">Posisi akan diperbarui otomatis bila kuota tersedia.</p>
                                @else
                                    <p class="text-muted">Bukti pembayaran sedang diverifikasi oleh panitia.</p>
                                @endif
                            @elseif($pembayaran->status === 'terverifikasi')
                                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="text-success">Terverifikasi</h5>
                                <p class="text-muted">Pembayaran Anda sudah diverifikasi</p>
                            @else
                                <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="text-danger">Bukti Pembayaran Tidak Valid</h5>
                                <p class="text-muted mb-2">{{ $pembayaran->catatan ?? 'Bukti pembayaran ditolak oleh panitia.' }}</p>
                                <div class="alert alert-danger text-start small">
                                    <strong>Anda belum berhasil mendapatkan kuota.</strong> Kuota dari bukti ini telah dilepas karena bukti pembayaran tidak valid atau nominalnya tidak sesuai. Transfer dan upload ulang bukti pembayaran formulir senilai tagihan penuh untuk memperoleh urutan kuota baru.
                                </div>
                                <a href="{{ route('peserta.pembayaran.formulir') }}" class="btn btn-warning">
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
                        @if($pembayaran->status === 'terverifikasi' && isset($kwitansi) && $kwitansi)
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
                                <a href="{{ route('peserta.pembayaran.kwitansi', $pembayaran) }}" target="_blank" class="btn btn-info btn-sm w-100">
                                    <i class="bi bi-printer me-2"></i>Cetak Kwitansi
                                </a>
                            </div>
                        </div>
                        @endif
                        
                        <p class="small text-muted mb-1">Bukti yang diupload:</p>
                        <img src="{{ Storage::url($pembayaran->bukti_file) }}" class="img-fluid rounded border" alt="Bukti Pembayaran">
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
