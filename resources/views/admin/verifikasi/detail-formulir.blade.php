@extends('layouts.admin')

@section('title', 'Detail Formulir - ' . $formulir->nama_lengkap)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Detail Formulir SPMB</h4>
        <a href="{{ route('admin.verifikasi.formulir') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Data Formulir Lengkap</h5>
                </div>
                <div class="card-body">
                    {{-- Data Diri --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-person me-2"></i>Data Diri</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block">1. Nama Lengkap</small>
                            <strong>{{ $formulir->nama_lengkap ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">2. Tanggal Lahir</small>
                            <strong>{{ $formulir->tanggal_lahir?->format('d F Y') ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">5. Jenis Kelamin</small>
                            <strong>{{ $formulir->jenis_kelamin === 'L' ? 'Laki-laki' : ($formulir->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">3. Kota Kelahiran</small>
                            <strong>{{ $formulir->tempat_lahir ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">4. Provinsi Kelahiran</small>
                            <strong>{{ $formulir->provinsi_lahir ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Sekolah --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-building me-2"></i>Data Sekolah</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block">6. Asal Sekolah SMP</small>
                            <strong>{{ $formulir->asal_sekolah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">7. Prestasi</small>
                            <strong>{{ $formulir->prestasi ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">23. Tanggal Daftar</small>
                            <strong>{{ $formulir->tanggal_daftar?->format('d F Y') ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">24. NISN</small>
                            <strong>{{ $formulir->nisn ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Fisik --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-heart-pulse me-2"></i>Data Fisik & Ukuran Baju</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">8. Lingkar Dada</small>
                            <strong>{{ $formulir->lingkar_dada ?? '-' }} cm</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">9. Lingkar Pinggang</small>
                            <strong>{{ $formulir->lingkar_pinggang ?? '-' }} cm</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">10. Lingkar Kepala</small>
                            <strong>{{ $formulir->lingkar_kepala ?? '-' }} cm</strong>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <small class="text-muted d-block">11. Panjang Celana/Rok</small>
                            <strong>{{ $formulir->panjang_celana ?? '-' }} cm</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">12. Tinggi Badan</small>
                            <strong>{{ $formulir->tinggi_badan ?? '-' }} cm</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">13. Berat Badan</small>
                            <strong>{{ $formulir->berat_badan ?? '-' }} kg</strong>
                        </div>
                    </div>

                    {{-- Data Tambahan --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-star me-2"></i>Data Tambahan</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block">14. Hobi</small>
                            <strong>{{ $formulir->hobi ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">15. Cita-cita</small>
                            <strong>{{ $formulir->cita_cita ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Orang Tua --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-people me-2"></i>Data Orang Tua</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted d-block">16. Nama Ayah/Wali</small>
                            <strong>{{ $formulir->nama_ayah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">17. Nama Ibu/Wali</small>
                            <strong>{{ $formulir->nama_ibu ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">18. Pekerjaan Ayah</small>
                            <strong>{{ $formulir->pekerjaan_ayah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">19. Pekerjaan Ibu</small>
                            <strong>{{ $formulir->pekerjaan_ibu ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">20. Pendidikan Terakhir Ayah</small>
                            <strong>{{ $formulir->pendidikan_ayah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">21. Pendidikan Terakhir Ibu</small>
                            <strong>{{ $formulir->pendidikan_ibu ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Alamat --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-geo-alt me-2"></i>Data Alamat</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <small class="text-muted d-block">22. Kelurahan</small>
                            <strong>{{ $formulir->alamat_kelurahan ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">23. Kecamatan</small>
                            <strong>{{ $formulir->alamat_kecamatan ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">24. Kota/Kab</small>
                            <strong>{{ $formulir->alamat_kota ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">25. Provinsi</small>
                            <strong>{{ $formulir->alamat_provinsi ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Kontak --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-telephone me-2"></i>Data Kontak</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <small class="text-muted d-block">26. No Telepon Rumah</small>
                            <strong>{{ $formulir->telp_rumah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">27. No HP/WA Siswa</small>
                            <strong>{{ $formulir->telepon ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">28. No HP/WA Ayah</small>
                            <strong>{{ $formulir->telepon_ayah ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">29. No HP/WA Ibu</small>
                            <strong>{{ $formulir->telepon_ibu ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Data Lainnya --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-card-list me-2"></i>Data Lainnya</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <small class="text-muted d-block">30. Jumlah Saudara</small>
                            <strong>{{ $formulir->jumlah_saudara ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">31. Tanggal Daftar</small>
                            <strong>{{ $formulir->tanggal_daftar?->format('d F Y') ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">32. NISN</small>
                            <strong>{{ $formulir->nisn ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">33. Nama Kelompok</small>
                            <strong>{{ $formulir->kelompok ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">34. Nama Desa</small>
                            <strong>{{ $formulir->desa ?? '-' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">35. Nama Daerah</small>
                            <strong>{{ $formulir->daerah ?? '-' }}</strong>
                        </div>
                    </div>

                    {{-- Dokumen --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-file-earmark me-2"></i>Dokumen Unggahan</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">36. KK</small>
                            @if($formulir->file_kk)
                                <a href="{{ Storage::url($formulir->file_kk) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                            @else
                                <span class="badge bg-secondary">Belum diunggah</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">37. Akta Lahir</small>
                            @if($formulir->file_akta)
                                <a href="{{ Storage::url($formulir->file_akta) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                            @else
                                <span class="badge bg-secondary">Belum diunggah</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">38. Ijazah SMP</small>
                            @if($formulir->file_ijazah)
                                <a href="{{ Storage::url($formulir->file_ijazah) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                            @else
                                <span class="badge bg-secondary">Belum diunggah</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">39. Kartu BPJS</small>
                            @if($formulir->file_bpjs)
                                <a href="{{ Storage::url($formulir->file_bpjs) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                            @else
                                <span class="badge bg-secondary">Belum diunggah</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">40. KTP Ibu</small>
                            @if($formulir->file_ktp_ibu)
                                <a href="{{ Storage::url($formulir->file_ktp_ibu) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                            @else
                                <span class="badge bg-secondary">Belum diunggah</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">41. KTP Ayah</small>
                            @if($formulir->file_ktp_ayah)
                                <a href="{{ Storage::url($formulir->file_ktp_ayah) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Lihat
                                </a>
                            @else
                                <span class="badge bg-secondary">Belum diunggah</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Info & Aksi --}}
        <div class="col-lg-4">
            {{-- Info Peserta --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Info Peserta</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">No. Pendaftaran</td>
                            <td><code>{{ $formulir->peserta->nomor_pendaftaran }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>{{ $formulir->peserta->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Telepon</td>
                            <td>{{ $formulir->telepon ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Submit</td>
                            <td>{{ $formulir->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if($formulir->status_verifikasi === 'menunggu')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>
                                @elseif($formulir->status_verifikasi === 'terverifikasi')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Terverifikasi</span>
                                @elseif($formulir->status_verifikasi === 'ditolak')
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Ditolak</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-file-earmark me-1"></i>Draft</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Aksi Verifikasi --}}
            @if($formulir->status_verifikasi === 'menunggu')
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Aksi Verifikasi</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Periksa data formulir dengan teliti sebelum memverifikasi.</p>
                    
                    <div class="d-grid gap-2">
                        <form action="{{ route('admin.verifikasi.formulir.terima', $formulir) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Verifikasi formulir {{ $formulir->nama_lengkap }}?')">
                                <i class="bi bi-check-circle me-2"></i>Verifikasi / Terima
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalTolak">
                            <i class="bi bi-x-circle me-2"></i>Tolak / Perbaiki
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Catatan Penolakan --}}
            @if($formulir->status_verifikasi === 'ditolak' && $formulir->catatan_verifikasi)
            <div class="card border-0 shadow-sm border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Catatan Penolakan</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $formulir->catatan_verifikasi }}</p>
                    <hr>
                    <small class="text-muted">
                        Ditolak pada: {{ $formulir->diverifikasi_pada?->format('d/m/Y H:i') ?? '-' }}
                    </small>
                </div>
            </div>
            @endif

            {{-- Info Verifikasi --}}
            @if($formulir->status_verifikasi === 'terverifikasi')
            <div class="card border-0 shadow-sm border-success mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Terverifikasi</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-success">Formulir ini sudah diverifikasi.</p>
                    <hr>
                    <small class="text-muted">
                        Diverifikasi pada: {{ $formulir->diverifikasi_pada?->format('d/m/Y H:i') ?? '-' }}
                    </small>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal Tolak --}}
<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.verifikasi.formulir.tolak', $formulir) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Tolak Formulir</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Peserta: <strong>{{ $formulir->nama_lengkap }}</strong></p>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan / Data yang Perlu Diperbaiki</label>
                        <textarea class="form-control" name="alasan" rows="4" required placeholder="Masukkan alasan penolakan atau data yang perlu diperbaiki..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Tolak Formulir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
