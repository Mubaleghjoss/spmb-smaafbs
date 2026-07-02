@extends('layouts.tim-spmb')

@section('title', 'Detail Peserta')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Peserta</h1>
        <a href="{{ route('tim-spmb.peserta.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    @if($peserta->foto)
                        <img src="{{ Storage::url($peserta->foto) }}" class="rounded-circle mb-3" width="120" height="120" style="object-fit: cover;">
                    @else
                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                            <i class="bi bi-person text-white" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                    <h5>{{ $peserta->nama }}</h5>
                    <p class="text-muted mb-2"><code>{{ $peserta->nomor_pendaftaran }}</code></p>
                    <span class="badge bg-{{ $peserta->tahap_saat_ini >= 7 ? 'success' : 'primary' }} fs-6">
                        Tahap {{ $peserta->tahap_saat_ini }}
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Informasi Kontak</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>{{ $peserta->email }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Telepon</td>
                            <td>{{ $peserta->telepon ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Asal Sekolah</td>
                            <td>{{ $peserta->asal_sekolah ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Terdaftar</td>
                            <td>{{ $peserta->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Progress Tahapan -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Progress Tahapan SPMB</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        @for($i = 1; $i <= 7; $i++)
                            <div class="text-center">
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $peserta->tahap_saat_ini >= $i ? 'bg-success' : 'bg-secondary' }}" style="width: 40px; height: 40px;">
                                    @if($peserta->tahap_saat_ini > $i)
                                        <i class="bi bi-check text-white"></i>
                                    @else
                                        <span class="text-white">{{ $i }}</span>
                                    @endif
                                </div>
                                <small class="d-block mt-1 text-muted">Tahap {{ $i }}</small>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            <!-- Hasil Tes -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Hasil Tes</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tes</th>
                                    <th>Nilai</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($peserta->sesiTes as $sesi)
                                <tr>
                                    <td>{{ $sesi->tes->nama }}</td>
                                    <td>
                                        <span class="badge bg-{{ $sesi->nilai >= 70 ? 'success' : ($sesi->nilai >= 50 ? 'warning' : 'danger') }}">
                                            {{ number_format($sesi->nilai, 1) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sesi->lulus)
                                            <span class="badge bg-success">Lulus</span>
                                        @else
                                            <span class="badge bg-danger">Tidak Lulus</span>
                                        @endif
                                    </td>
                                    <td>{{ $sesi->selesai_pada?->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Belum ada hasil tes</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pembayaran -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Riwayat Pembayaran</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($peserta->pembayaran as $bayar)
                                <tr>
                                    <td>{{ ucfirst($bayar->jenis) }}</td>
                                    <td>Rp {{ number_format($bayar->jumlah, 0, ',', '.') }}</td>
                                    <td>
                                        @if($bayar->status == 'terverifikasi')
                                            <span class="badge bg-success">Terverifikasi</span>
                                        @elseif($bayar->status == 'menunggu')
                                            <span class="badge bg-warning">Menunggu</span>
                                        @else
                                            <span class="badge bg-danger">Ditolak</span>
                                        @endif
                                    </td>
                                    <td>{{ $bayar->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Belum ada pembayaran</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
