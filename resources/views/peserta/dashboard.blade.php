@extends('layouts.peserta')

@section('title', 'Dashboard SPMB')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h4 class="fw-bold">Dashboard SPMB</h4>
            <p class="text-muted">Selamat datang, {{ $peserta->nama }}</p>
        </div>
    </div>
    
    <!-- Info Peserta -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Nomor Pendaftaran:</strong></p>
                    <p class="h5 text-success">{{ $peserta->nomor_pendaftaran }}</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Tahap Saat Ini:</strong></p>
                    <p class="h5">Tahap {{ $tahapan->tahap_saat_ini ?? 1 }} dari 7</p>
                </div>
            </div>
        </div>
    </div>
    
    @php
        $statusKelulusan = $tahapan->status_kelulusan ?? 'menunggu';
        $tahapSaatIni = $tahapan->tahap_saat_ini ?? 1;
    @endphp
    
    {{-- Banner Status Kelulusan --}}
    @if($tahapSaatIni >= 6)
        @if($statusKelulusan === 'lulus' && ($tahapan->tahap_7_selesai ?? false))
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i class="bi bi-trophy text-success" style="font-size: 2.5rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1">Selamat! Anda Dinyatakan LULUS</h5>
                    <p class="mb-0">Anda resmi diterima sebagai peserta didik baru. Klik tombol di samping untuk melihat detail.</p>
                </div>
                <div class="flex-shrink-0 ms-3">
                    <a href="{{ route('peserta.konfirmasi-diterima') }}" class="btn btn-success">
                        <i class="bi bi-arrow-right me-1"></i>Lihat Detail
                    </a>
                </div>
            </div>
        </div>
        @elseif($statusKelulusan === 'tidak_lulus')
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i class="bi bi-x-circle text-danger" style="font-size: 2.5rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1">Pengumuman Kelulusan</h5>
                    <p class="mb-0">Mohon maaf, Anda dinyatakan tidak lulus seleksi. Klik tombol di samping untuk melihat detail.</p>
                </div>
                <div class="flex-shrink-0 ms-3">
                    <a href="{{ route('peserta.konfirmasi-diterima') }}" class="btn btn-outline-danger">
                        <i class="bi bi-arrow-right me-1"></i>Lihat Detail
                    </a>
                </div>
            </div>
        </div>
        @elseif($tahapSaatIni == 7 && $statusKelulusan === 'menunggu')
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i class="bi bi-hourglass-split text-warning" style="font-size: 2.5rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1">Menunggu Pengumuman</h5>
                    <p class="mb-0">Hasil seleksi Anda sedang dalam proses verifikasi. Silakan tunggu pengumuman resmi.</p>
                </div>
                <div class="flex-shrink-0 ms-3">
                    <a href="{{ route('peserta.konfirmasi-diterima') }}" class="btn btn-outline-warning">
                        <i class="bi bi-arrow-right me-1"></i>Lihat Status
                    </a>
                </div>
            </div>
        </div>
        @endif
    @endif
    
    <!-- Progress Tahapan -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Progress Tahapan SPMB</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @foreach($statusTahapan as $nomor => $item)
                <div class="list-group-item py-3">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0 me-3">
                            @if($nomor == 7 && $statusKelulusan === 'tidak_lulus')
                                {{-- Tahap 7 dengan status tidak lulus - warna merah --}}
                                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="bi bi-x-lg fs-5"></i>
                                </div>
                            @elseif($nomor == 7 && $statusKelulusan === 'lulus' && ($tahapan->tahap_7_selesai ?? false))
                                {{-- Tahap 7 dengan status lulus - warna hijau --}}
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="bi bi-check-lg fs-5"></i>
                                </div>
                            @elseif($item['selesai'])
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="bi bi-check-lg fs-5"></i>
                                </div>
                            @elseif($item['dibuka'] && $nomor == ($tahapan->tahap_saat_ini ?? 1))
                                <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <span class="fw-bold">{{ $nomor }}</span>
                                </div>
                            @else
                                <div class="rounded-circle bg-secondary bg-opacity-25 text-muted d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <span>{{ $nomor }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    @if($nomor == 7 && $statusKelulusan === 'tidak_lulus')
                                        {{-- Tahap 7 tidak lulus --}}
                                        <h6 class="mb-1 text-danger">
                                            <i class="bi bi-{{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                                        </h6>
                                        <p class="text-danger small mb-2">Maaf, Anda belum diqodar menjadi peserta didik {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}</p>
                                    @elseif($nomor == 7 && $statusKelulusan === 'lulus' && ($tahapan->tahap_7_selesai ?? false))
                                        {{-- Tahap 7 lulus --}}
                                        <h6 class="mb-1 text-success">
                                            <i class="bi bi-{{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                                        </h6>
                                        <p class="text-success small mb-2">Selamat! Anda resmi menjadi peserta didik {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}</p>
                                    @else
                                        <h6 class="mb-1 {{ $item['selesai'] ? 'text-success' : '' }}">
                                            <i class="bi bi-{{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                                        </h6>
                                        <p class="text-muted small mb-2">{{ $item['deskripsi'] ?? '' }}</p>
                                    @endif

                                    @if(!empty($item['jadwal_label']))
                                        <div class="alert alert-info py-2 px-3 mb-2 small">
                                            <i class="bi bi-calendar-event me-1"></i>{{ $item['jadwal_label'] }}
                                        </div>
                                    @endif
                                    
                                    {{-- Info berkas belum lengkap untuk tahap 2 (Isi Formulir) --}}
                                    @if($nomor == 2 && $item['selesai'] && isset($berkasBelumLengkap) && $berkasBelumLengkap['count'] > 0)
                                        <div class="alert alert-warning py-2 px-3 mb-2 small">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            <strong>Perhatian:</strong> Ada {{ $berkasBelumLengkap['count'] }} berkas yang belum diunggah. 
                                            <a href="{{ route('peserta.formulir.review') }}" class="alert-link">Lengkapi sekarang</a>
                                        </div>
                                    @endif
                                    
                                    @if($nomor == 7 && $statusKelulusan === 'tidak_lulus')
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Tidak Lulus</span>
                                    @elseif($nomor == 7 && $statusKelulusan === 'lulus' && ($tahapan->tahap_7_selesai ?? false))
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Lulus</span>
                                    @elseif($item['selesai'])
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Selesai</span>
                                    @elseif($item['dibuka'] && $nomor == ($tahapan->tahap_saat_ini ?? 1))
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Sedang Berlangsung</span>
                                        
                                        {{-- Info khusus untuk tahap 4 (Tes Online) jika sudah mengerjakan tapi tidak lulus --}}
                                        @if($nomor == 4 && isset($sesiTesList) && $sesiTesList->count() > 0)
                                            @foreach($sesiTesList as $sesiItem)
                                                @if($sesiItem->status === 'timeout')
                                                    <div class="alert alert-warning mt-2 mb-0 py-2 px-3 small">
                                                        <i class="bi bi-hourglass-bottom me-1"></i>
                                                        <strong>{{ $sesiItem->tes->nama }}</strong>: Waktu habis.
                                                        @if($sesiItem->permohonan_ulang_status === \App\Models\SesiTes::PERMOHONAN_ULANG_PENDING)
                                                            <br>Permohonan {{ $sesiItem->labelPermohonanUlangTipe() }} sedang menunggu keputusan admin.
                                                        @else
                                                            <br><a href="{{ route('ujian.index') }}" class="alert-link">Ajukan perpanjangan waktu atau ulang dari 0</a>.
                                                        @endif
                                                    </div>
                                                @elseif($sesiItem->status_verifikasi_tes === 'menunggu')
                                                    <div class="alert alert-warning mt-2 mb-0 py-2 px-3 small">
                                                        <i class="bi bi-hourglass-split me-1"></i>
                                                        <strong>{{ $sesiItem->tes->nama }}</strong>: Nilai {{ number_format($sesiItem->nilai, 1) }} (di bawah nilai lulus {{ $sesiItem->tes->nilai_lulus }})
                                                        <br>Hasil tes Anda sedang ditinjau oleh admin.
                                                    </div>
                                                @elseif($sesiItem->status_verifikasi_tes === 'ditolak')
                                                    <div class="alert alert-danger mt-2 mb-0 py-2 px-3 small">
                                                        <i class="bi bi-x-circle me-1"></i>
                                                        <strong>{{ $sesiItem->tes->nama }}</strong>: Tidak Dapat Melanjutkan
                                                        <br>{{ $sesiItem->catatan_verifikasi ?? 'Mohon maaf, Anda tidak dapat melanjutkan ke tahap berikutnya.' }}
                                                    </div>
                                                @endif
                                            @endforeach
                                        @elseif($nomor == 4 && isset($sesiTes) && $sesiTes)
                                            @if($sesiTes->status === 'timeout')
                                                <div class="alert alert-warning mt-2 mb-0 py-2 px-3 small">
                                                    <i class="bi bi-hourglass-bottom me-1"></i>
                                                    <strong>{{ $sesiTes->tes->nama }}</strong>: Waktu habis.
                                                    @if($sesiTes->permohonan_ulang_status === \App\Models\SesiTes::PERMOHONAN_ULANG_PENDING)
                                                        <br>Permohonan {{ $sesiTes->labelPermohonanUlangTipe() }} sedang menunggu keputusan admin.
                                                    @else
                                                        <br><a href="{{ route('ujian.index') }}" class="alert-link">Ajukan perpanjangan waktu atau ulang dari 0</a>.
                                                    @endif
                                                </div>
                                            @elseif($sesiTes->status_verifikasi_tes === 'menunggu')
                                                <div class="alert alert-warning mt-2 mb-0 py-2 px-3 small">
                                                    <i class="bi bi-hourglass-split me-1"></i>
                                                    <strong>{{ $sesiTes->tes->nama }}</strong>: Nilai {{ number_format($sesiTes->nilai, 1) }} (di bawah nilai lulus {{ $sesiTes->tes->nilai_lulus }})
                                                    <br>Hasil tes Anda sedang ditinjau oleh admin.
                                                </div>
                                            @elseif($sesiTes->status_verifikasi_tes === 'ditolak')
                                                <div class="alert alert-danger mt-2 mb-0 py-2 px-3 small">
                                                    <i class="bi bi-x-circle me-1"></i>
                                                    <strong>{{ $sesiTes->tes->nama }}</strong>: Tidak Dapat Melanjutkan
                                                    <br>{{ $sesiTes->catatan_verifikasi ?? 'Mohon maaf, Anda tidak dapat melanjutkan ke tahap berikutnya.' }}
                                                </div>
                                            @endif
                                        @endif
                                    @elseif(!$item['dibuka'])
                                        <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i>Belum Dibuka</span>
                                        @if(!empty($item['alasan']))
                                        <br><small class="text-muted mt-1 d-inline-block">
                                            <i class="bi bi-info-circle me-1"></i>{{ $item['alasan'] }}
                                        </small>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-muted"><i class="bi bi-hourglass me-1"></i>Menunggu</span>
                                    @endif
                                </div>
                                
                                <div class="flex-shrink-0 ms-3">
                                    @if($nomor == 7 && $statusKelulusan === 'tidak_lulus')
                                        <a href="{{ route('peserta.konfirmasi-diterima') }}" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-eye me-1"></i>Lihat Detail
                                        </a>
                                    @elseif($nomor == 7 && $statusKelulusan === 'lulus' && ($tahapan->tahap_7_selesai ?? false))
                                        <a href="{{ route('peserta.konfirmasi-diterima') }}" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-eye me-1"></i>Lihat Detail
                                        </a>
                                    @elseif($item['selesai'] && $item['route'])
                                        <a href="{{ route($item['route']) }}" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-eye me-1"></i>Lihat
                                        </a>
                                    @elseif($item['dibuka'] && $item['route'] && $nomor == ($tahapan->tahap_saat_ini ?? 1))
                                        <a href="{{ route($item['route']) }}" class="btn btn-sm btn-success">
                                            <i class="bi bi-arrow-right me-1"></i>{{ $item['aksi'] ?? 'Buka' }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <h5 class="mb-3">Aksi Cepat</h5>
        </div>
        @if(!($statusTahapan[2]['selesai'] ?? false) && (($statusTahapan[2]['dibuka'] ?? false) || ($tahapan->tahap_1_selesai ?? false)))
        <div class="col-md-4 mb-3">
            <a href="{{ route('peserta.formulir.isi') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-file-earmark-text text-primary fs-1 mb-2"></i>
                    <h6 class="mb-0">Isi Formulir</h6>
                    <small class="text-muted">Lengkapi data pendaftaran</small>
                    @if(!empty($statusTahapan[2]['jadwal_label']))
                        <small class="text-info d-block mt-2">
                            <i class="bi bi-calendar-event me-1"></i>{{ $statusTahapan[2]['jadwal_label'] }}
                        </small>
                    @endif
                </div>
            </a>
        </div>
        @endif
        
        @if(!($statusTahapan[3]['selesai'] ?? false) && ($statusTahapan[3]['dibuka'] ?? false))
        <div class="col-md-4 mb-3">
            <a href="{{ route('peserta.pembayaran.formulir') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-credit-card text-success fs-1 mb-2"></i>
                    <h6 class="mb-0">Upload Bukti Bayar</h6>
                    <small class="text-muted">Upload bukti pembayaran formulir</small>
                    @if(!empty($statusTahapan[3]['jadwal_label']))
                        <small class="text-info d-block mt-2">
                            <i class="bi bi-calendar-event me-1"></i>{{ $statusTahapan[3]['jadwal_label'] }}
                        </small>
                    @endif
                </div>
            </a>
        </div>
        @endif
        
        @if(!($statusTahapan[4]['selesai'] ?? false) && ($statusTahapan[4]['dibuka'] ?? false))
        <div class="col-md-4 mb-3">
            <a href="{{ route('ujian.index') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-laptop text-warning fs-1 mb-2"></i>
                    <h6 class="mb-0">Tes Online</h6>
                    <small class="text-muted">Ikuti tes seleksi</small>
                    @if(!empty($statusTahapan[4]['jadwal_label']))
                        <small class="text-info d-block mt-2">
                            <i class="bi bi-calendar-event me-1"></i>{{ $statusTahapan[4]['jadwal_label'] }}
                        </small>
                    @endif
                </div>
            </a>
        </div>
        @endif
        
        {{-- Tombol Bantuan WhatsApp --}}
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-decoration-none h-100 bg-success bg-opacity-10 border-success" role="button" data-bs-toggle="modal" data-bs-target="#modalBantuanWa" style="cursor: pointer;">
                <div class="card-body text-center py-4">
                    <i class="bi bi-whatsapp text-success fs-1 mb-2"></i>
                    <h6 class="mb-0 text-success">Bantuan Tim SPMB</h6>
                    <small class="text-success">Hubungi via WhatsApp</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Bantuan WhatsApp --}}
<div class="modal fade" id="modalBantuanWa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-whatsapp me-2"></i>Hubungi Tim SPMB</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @php
                    $kontakTim = app(\App\Services\PengaturanService::class)->ambilKontakTimSpmb();
                    $pesanBantuan = "Assalamu'alaikum,\n\nSaya " . $peserta->nama . " dengan nomor pendaftaran " . $peserta->nomor_pendaftaran . ".\n\nSaya ingin bertanya mengenai proses SPMB.\n\nTerima kasih.";
                @endphp
                
                <p class="text-muted mb-3">Pilih kontak Tim SPMB yang ingin dihubungi:</p>
                <div class="list-group">
                    @forelse($kontakTim as $kontak)
                    @if(!empty($kontak['whatsapp']))
                    @php
                        $waNumber = preg_replace('/[^0-9]/', '', $kontak['whatsapp']);
                        if (substr($waNumber, 0, 1) === '0') {
                            $waNumber = '62' . substr($waNumber, 1);
                        } elseif (substr($waNumber, 0, 2) !== '62') {
                            $waNumber = '62' . $waNumber;
                        }
                    @endphp
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($pesanBantuan) }}" 
                       target="_blank" 
                       class="list-group-item list-group-item-action d-flex align-items-center">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="bi bi-whatsapp"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $kontak['nama'] ?: 'Tim SPMB' }}</h6>
                            <small class="text-muted">+62{{ ltrim($kontak['whatsapp'], '0') }}</small>
                        </div>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                    @endif
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-info-circle fs-3 mb-2"></i>
                        <p class="mb-0">Belum ada kontak Tim SPMB yang tersedia.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
