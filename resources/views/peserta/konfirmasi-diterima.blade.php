@extends('layouts.peserta')

@section('title', $statusKelulusan === 'lulus' ? 'Selamat Bergabung!' : ($statusKelulusan === 'tidak_lulus' ? 'Pengumuman Kelulusan' : 'Menunggu Pengumuman'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @php
                $warnaLulus = $pengaturanKelulusan['warna_lulus'] ?? '#198754';
                $warnaTidakLulus = $pengaturanKelulusan['warna_tidak_lulus'] ?? '#dc3545';
            @endphp
            
            @if($statusKelulusan === 'lulus' && $tahap7Selesai)
            {{-- LULUS --}}
            <div class="card border-0 shadow-sm" style="border-left: 4px solid {{ $warnaLulus }} !important;">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px; background-color: {{ $warnaLulus }}20;">
                            <i class="bi bi-trophy" style="font-size: 3rem; color: {{ $warnaLulus }};"></i>
                        </div>
                    </div>
                    
                    <h2 class="mb-3" style="color: {{ $warnaLulus }};">{{ $pengaturanKelulusan['judul_lulus'] ?? 'Selamat Bergabung!' }}</h2>
                    <p class="lead text-muted mb-4">
                        {{ $pengaturanKelulusan['teks_lulus'] ?? 'Anda resmi diterima sebagai peserta didik baru' }}<br>
                        <strong style="color: {{ $warnaLulus }};">{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</strong><br>
                        Tahun Ajaran {{ $branding['tahun_ajaran'] ?? (date('Y') . '/' . (date('Y') + 1)) }}
                    </p>
                    
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-person-badge me-2"></i>Data Peserta</h5>
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Nomor Pendaftaran</td>
                                    <td class="fw-bold">{{ $peserta->nomor_pendaftaran }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama</td>
                                    <td class="fw-bold">{{ $peserta->nama }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Asal Sekolah</td>
                                    <td class="fw-bold">{{ $peserta->formulirSpmb?->asal_sekolah ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if(!empty($skKelulusan['file']))
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body text-center">
                            <h6 class="card-title"><i class="bi bi-file-earmark-pdf me-2"></i>Surat Keterangan Kelulusan</h6>
                            @if(!empty($skKelulusan['nama']))
                            <p class="text-muted small mb-2">SK {{ $skKelulusan['nama'] }}</p>
                            @endif
                            <a href="{{ route('peserta.surat-kelulusan.download') }}" class="btn btn-lg" style="background-color: {{ $warnaLulus }}; color: white;">
                                <i class="bi bi-download me-2"></i>Download SK Kelulusan
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    @if(!empty($pengaturanKelulusan['keterangan_lulus']))
                    <div class="alert text-start" style="background-color: {{ $warnaLulus }}20; border-color: {{ $warnaLulus }};">
                        <h6 class="alert-heading" style="color: {{ $warnaLulus }};"><i class="bi bi-info-circle me-2"></i>Informasi Penting</h6>
                        <hr style="border-color: {{ $warnaLulus }}40;">
                        <div class="mb-0">{!! nl2br(e($pengaturanKelulusan['keterangan_lulus'])) !!}</div>
                    </div>
                    @else
                    <div class="alert alert-info text-start">
                        <h6 class="alert-heading"><i class="bi bi-calendar-event me-2"></i>Informasi Penting</h6>
                        <hr>
                        <ul class="mb-0">
                            <li>Kegiatan Belajar Mengajar (KBM) dimulai sesuai jadwal yang akan diumumkan</li>
                            <li>Silakan pantau informasi terbaru melalui website atau media sosial sekolah</li>
                            <li>Persiapkan dokumen asli untuk verifikasi saat daftar ulang</li>
                        </ul>
                    </div>
                    @endif
                    
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-primary">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
                        <button class="btn" style="background-color: {{ $warnaLulus }}; color: white;" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Cetak
                        </button>
                    </div>
                </div>
            </div>
            
            @elseif($statusKelulusan === 'tidak_lulus')
            {{-- TIDAK LULUS --}}
            <div class="card border-0 shadow-sm" style="border-left: 4px solid {{ $warnaTidakLulus }} !important;">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px; background-color: {{ $warnaTidakLulus }}20;">
                            <i class="bi bi-x-circle" style="font-size: 3rem; color: {{ $warnaTidakLulus }};"></i>
                        </div>
                    </div>
                    
                    <h2 class="mb-3" style="color: {{ $warnaTidakLulus }};">{{ $pengaturanKelulusan['judul_tidak_lulus'] ?? 'Pengumuman Kelulusan' }}</h2>
                    <p class="lead text-muted mb-4">
                        {{ $pengaturanKelulusan['teks_tidak_lulus'] ?? 'Maaf, Anda belum diqodar menjadi peserta didik' }}<br>
                        <strong style="color: {{ $warnaTidakLulus }};">{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</strong><br>
                        Tahun Ajaran {{ $branding['tahun_ajaran'] ?? (date('Y') . '/' . (date('Y') + 1)) }}
                    </p>
                    
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-person-badge me-2"></i>Data Peserta</h5>
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Nomor Pendaftaran</td>
                                    <td class="fw-bold">{{ $peserta->nomor_pendaftaran }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama</td>
                                    <td class="fw-bold">{{ $peserta->nama }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Asal Sekolah</td>
                                    <td class="fw-bold">{{ $peserta->formulirSpmb?->asal_sekolah ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if(!empty($pengaturanKelulusan['keterangan_tidak_lulus']))
                    <div class="alert text-start" style="background-color: {{ $warnaTidakLulus }}20; border-color: {{ $warnaTidakLulus }};">
                        <h6 class="alert-heading" style="color: {{ $warnaTidakLulus }};"><i class="bi bi-info-circle me-2"></i>Informasi</h6>
                        <hr style="border-color: {{ $warnaTidakLulus }}40;">
                        <div class="mb-0">{!! nl2br(e($pengaturanKelulusan['keterangan_tidak_lulus'])) !!}</div>
                    </div>
                    @else
                    <div class="alert alert-secondary text-start">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Informasi</h6>
                        <hr>
                        <p class="mb-0">Terima kasih telah berpartisipasi dalam seleksi penerimaan peserta didik baru. Semoga sukses di kesempatan berikutnya.</p>
                    </div>
                    @endif
                    
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-primary">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            @else
            {{-- MENUNGGU PENGUMUMAN --}}
            @php
                $tahap7 = $pengaturanTahapan['tahap_7'] ?? [];
                $tanggalPengumuman = $tahap7['tanggal_buka'] ?? null;
                $teksTanggalPengumuman = null;
                $catatanPengumuman = trim($tahap7['keterangan'] ?? '');
                
                if (!empty($tanggalPengumuman)) {
                    try {
                        $tanggal = \Carbon\Carbon::parse($tanggalPengumuman)->locale('id');
                        $teksTanggalPengumuman = 'Pengumuman Hasil SPMB akan jatuh pada hari ' . $tanggal->translatedFormat('l, d F Y') . '.';
                    } catch (\Throwable $e) {
                        $teksTanggalPengumuman = null;
                    }
                }
                
                $catatanBerisiTanggalManual = $catatanPengumuman !== ''
                    && \Illuminate\Support\Str::of($catatanPengumuman)->lower()->contains(['pengumuman hasil spmb', 'jatuh pada hari']);
                $tampilkanCatatanPengumuman = $catatanPengumuman !== ''
                    && !($teksTanggalPengumuman && $catatanBerisiTanggalManual);
            @endphp
            <div class="card border-0 shadow-sm border-warning">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    
                    <h2 class="text-warning mb-3">Menunggu Pengumuman</h2>
                    <p class="lead text-muted mb-4">
                        Hasil seleksi Anda sedang dalam proses verifikasi<br>
                        <strong class="text-primary">{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</strong><br>
                        Tahun Ajaran {{ $branding['tahun_ajaran'] ?? (date('Y') . '/' . (date('Y') + 1)) }}
                    </p>
                    
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-person-badge me-2"></i>Data Peserta</h5>
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Nomor Pendaftaran</td>
                                    <td class="fw-bold">{{ $peserta->nomor_pendaftaran }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama</td>
                                    <td class="fw-bold">{{ $peserta->nama }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Asal Sekolah</td>
                                    <td class="fw-bold">{{ $peserta->formulirSpmb?->asal_sekolah ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($teksTanggalPengumuman || $tampilkanCatatanPengumuman)
                    <div class="alert alert-info text-start">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Informasi</h6>
                        <hr>
                        @if($teksTanggalPengumuman)
                            <p class="{{ $tampilkanCatatanPengumuman ? 'mb-2' : 'mb-0' }}">{{ $teksTanggalPengumuman }}</p>
                        @endif
                        @if($tampilkanCatatanPengumuman)
                            <div class="mb-0">{!! nl2br(e($catatanPengumuman)) !!}</div>
                        @endif
                    </div>
                    @else
                    <div class="alert alert-info text-start">
                        <h6 class="alert-heading"><i class="bi bi-clock me-2"></i>Informasi</h6>
                        <hr>
                        <p class="mb-0">Silakan tunggu pengumuman hasil seleksi. Anda akan mendapatkan notifikasi ketika hasil sudah diumumkan.</p>
                    </div>
                    @endif
                    
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-primary">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    .btn, nav, footer { display: none !important; }
    .card { box-shadow: none !important; }
}
</style>
@endpush
