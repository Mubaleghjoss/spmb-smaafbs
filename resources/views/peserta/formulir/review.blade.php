@extends('layouts.peserta')

@section('title', 'Review Formulir SPMB')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- Status Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center py-4">
                    @if($formulir->status_verifikasi === 'menunggu')
                        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="text-warning">Menunggu Verifikasi</h5>
                        <p class="text-muted mb-0">Formulir Anda sedang diverifikasi oleh admin</p>
                    @elseif($formulir->status_verifikasi === 'terverifikasi')
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="text-success">Formulir Terverifikasi</h5>
                        <p class="text-muted mb-0">Formulir Anda sudah diverifikasi. Silakan lanjut ke tahap berikutnya.</p>
                    @elseif($formulir->status_verifikasi === 'ditolak')
                        <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="text-danger">Formulir Ditolak</h5>
                        <p class="text-muted mb-2">{{ $formulir->catatan_verifikasi ?? 'Silakan perbaiki data formulir Anda.' }}</p>
                        <a href="{{ route('peserta.formulir.isi') }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>Edit Formulir
                        </a>
                    @else
                        <div class="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-file-earmark text-secondary" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="text-secondary">Draft</h5>
                        <p class="text-muted mb-2">Formulir belum disubmit</p>
                        <a href="{{ route('peserta.formulir.isi') }}" class="btn btn-primary">
                            <i class="bi bi-pencil me-2"></i>Lanjutkan Mengisi
                        </a>
                    @endif
                </div>
            </div>

            {{-- Data Formulir --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Data Formulir SPMB</h5>
                </div>
                <div class="card-body">
                    {{-- Data Diri --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-person me-2"></i>Data Diri
                    </h6>
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

                    {{-- Data Sekolah & Prestasi --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-building me-2"></i>Data Sekolah
                    </h6>
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

                    {{-- Data Fisik & Ukuran Baju (Editable) --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-heart-pulse me-2"></i>Data Fisik & Ukuran Baju
                        <span class="badge bg-info ms-2"><i class="bi bi-pencil-square me-1"></i>Bisa Diubah</span>
                    </h6>

                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Data Fisik Penting!</strong> Anda dapat memperbarui data fisik kapan saja meskipun formulir sudah terverifikasi.
                    </div>

                    @if(session('sukses'))
                    <div class="alert alert-success alert-dismissible fade show mb-3">
                        <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form action="{{ route('peserta.formulir.update-data-fisik') }}" method="POST">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">8. Lingkar Dada (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('lingkar_dada') is-invalid @enderror" 
                                       name="lingkar_dada" value="{{ old('lingkar_dada', $formulir->lingkar_dada) }}"
                                       placeholder="Contoh: 80.5">
                                @error('lingkar_dada')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">9. Lingkar Pinggang (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('lingkar_pinggang') is-invalid @enderror" 
                                       name="lingkar_pinggang" value="{{ old('lingkar_pinggang', $formulir->lingkar_pinggang) }}"
                                       placeholder="Contoh: 65.0">
                                @error('lingkar_pinggang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">10. Lingkar Kepala (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('lingkar_kepala') is-invalid @enderror" 
                                       name="lingkar_kepala" value="{{ old('lingkar_kepala', $formulir->lingkar_kepala) }}"
                                       placeholder="Contoh: 54.0">
                                @error('lingkar_kepala')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">11. Panjang Celana/Rok (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('panjang_celana') is-invalid @enderror" 
                                       name="panjang_celana" value="{{ old('panjang_celana', $formulir->panjang_celana) }}"
                                       placeholder="Contoh: 95.0">
                                @error('panjang_celana')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">12. Tinggi Badan (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('tinggi_badan') is-invalid @enderror" 
                                       name="tinggi_badan" value="{{ old('tinggi_badan', $formulir->tinggi_badan) }}"
                                       placeholder="Contoh: 165.5">
                                @error('tinggi_badan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">13. Berat Badan (kg)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('berat_badan') is-invalid @enderror" 
                                       name="berat_badan" value="{{ old('berat_badan', $formulir->berat_badan) }}"
                                       placeholder="Contoh: 55.0">
                                @error('berat_badan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Simpan Data Fisik
                            </button>
                        </div>
                    </form>

                    {{-- Data Tambahan --}}
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-star me-2"></i>Data Tambahan
                    </h6>
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
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-people me-2"></i>Data Orang Tua
                    </h6>
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
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-geo-alt me-2"></i>Data Alamat
                    </h6>
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
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-telephone me-2"></i>Data Kontak
                    </h6>
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
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-card-list me-2"></i>Data Lainnya
                    </h6>
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
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-file-earmark me-2"></i>Dokumen Unggahan
                    </h6>
                    
                    @php
                        $berkasFields = [
                            'file_kk' => ['label' => '36. Kartu Keluarga (KK)', 'field' => 'file_kk'],
                            'file_akta' => ['label' => '37. Akta Lahir', 'field' => 'file_akta'],
                            'file_ijazah' => ['label' => '38. Ijazah SMP', 'field' => 'file_ijazah'],
                            'file_bpjs' => ['label' => '39. Kartu BPJS', 'field' => 'file_bpjs'],
                            'file_ktp_ibu' => ['label' => '40. KTP Ibu', 'field' => 'file_ktp_ibu'],
                            'file_ktp_ayah' => ['label' => '41. KTP Ayah', 'field' => 'file_ktp_ayah'],
                        ];
                        $berkasBelumLengkap = collect($berkasFields)->filter(fn($item, $key) => empty($formulir->$key));
                        $bolehUpload = in_array($formulir->status_verifikasi, ['terverifikasi', 'menunggu']);
                    @endphp
                    
                    {{-- Alert jika ada berkas belum lengkap --}}
                    @if($berkasBelumLengkap->isNotEmpty() && $bolehUpload)
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian:</strong> Ada {{ $berkasBelumLengkap->count() }} berkas yang belum diunggah. Anda dapat melengkapi berkas di bawah ini tanpa perlu verifikasi ulang.
                    </div>
                    @endif
                    
                    <div class="row g-3">
                        @foreach($berkasFields as $fieldName => $info)
                        <div class="col-md-4">
                            <div class="card h-100 {{ $formulir->$fieldName ? 'border-success' : 'border-warning' }}">
                                <div class="card-body text-center py-3">
                                    <small class="text-muted d-block mb-2">{{ $info['label'] }}</small>
                                    @if($formulir->$fieldName)
                                        <i class="bi bi-file-earmark-check text-success" style="font-size: 1.5rem;"></i>
                                        <div class="mt-2">
                                            <a href="{{ Storage::url($formulir->$fieldName) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i>Lihat
                                            </a>
                                        </div>
                                    @else
                                        <i class="bi bi-file-earmark-x text-warning" style="font-size: 1.5rem;"></i>
                                        <div class="mt-2">
                                            @if($bolehUpload)
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalUpload{{ $fieldName }}">
                                                <i class="bi bi-upload me-1"></i>Upload
                                            </button>
                                            @else
                                            <span class="badge bg-secondary">Belum diunggah</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Modals diletakkan di luar card utama untuk menghindari aria-hidden conflict --}}
            @foreach($berkasFields as $fieldName => $info)
            @if(!$formulir->$fieldName && $bolehUpload)
            <div class="modal fade" id="modalUpload{{ $fieldName }}" tabindex="-1" aria-labelledby="modalLabel{{ $fieldName }}">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('peserta.formulir.upload-berkas') }}" method="POST" enctype="multipart/form-data" class="form-upload-berkas">
                            @csrf
                            <input type="hidden" name="field" value="{{ $fieldName }}">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title" id="modalLabel{{ $fieldName }}"><i class="bi bi-upload me-2"></i>Upload {{ $info['label'] }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Pilih File <small class="text-muted">(maks. 2MB)</small></label>
                                    <input type="file" name="berkas" class="form-control input-berkas" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <div class="form-text">Format: JPG, JPEG, PNG, atau PDF. Maksimal 2MB.</div>
                                    <div class="file-error text-danger small mt-1 d-none"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x me-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-warning btn-submit-berkas">
                                    <i class="bi bi-upload me-1"></i>Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
            
            {{-- Tombol Konfirmasi WhatsApp --}}
            @if($formulir->status_verifikasi === 'menunggu' && !empty($whatsappSpmb))
            <div class="card border-0 shadow-sm mt-4 border-success">
                <div class="card-body text-center py-4">
                    <i class="bi bi-whatsapp text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Konfirmasi via WhatsApp</h5>
                    <p class="text-muted mb-3">Klik tombol di bawah untuk menghubungi Tim SPMB {{ $branding['nama_institusi'] ?? 'SMA Al Furqon' }} dan mempercepat proses verifikasi formulir Anda.</p>
                    @php
                        $pesan = "Assalamu'alaikum, saya *{$formulir->nama_lengkap}* dengan nomor pendaftaran *{$peserta->nomor_pendaftaran}* ingin mengkonfirmasi bahwa saya sudah mengisi dan mengirim formulir SPMB. Mohon untuk segera diverifikasi. Terima kasih.";
                        $waLink = "https://wa.me/62" . ltrim($whatsappSpmb, '0') . "?text=" . urlencode($pesan);
                    @endphp
                    <a href="{{ $waLink }}" target="_blank" class="btn btn-success btn-lg">
                        <i class="bi bi-whatsapp me-2"></i>Konfirmasi via WhatsApp
                    </a>
                </div>
            </div>
            @endif

            <div class="text-center mt-3">
                <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB

    // File size validation on modal upload inputs
    document.querySelectorAll('.input-berkas').forEach(input => {
        input.addEventListener('change', function() {
            const errorDiv = this.closest('.mb-3').querySelector('.file-error');
            const submitBtn = this.closest('form').querySelector('.btn-submit-berkas');
            
            errorDiv.classList.add('d-none');
            errorDiv.textContent = '';
            this.classList.remove('is-invalid');
            submitBtn.disabled = false;

            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.size > MAX_FILE_SIZE) {
                    const sizeMB = (file.size / 1024 / 1024).toFixed(1);
                    errorDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>File "' + file.name + '" terlalu besar (' + sizeMB + 'MB). Maksimal 2MB.';
                    errorDiv.classList.remove('d-none');
                    this.classList.add('is-invalid');
                    submitBtn.disabled = true;
                    this.value = '';
                }
            }
        });
    });

    // Prevent form submit if file too large
    document.querySelectorAll('.form-upload-berkas').forEach(form => {
        form.addEventListener('submit', function(e) {
            const input = this.querySelector('.input-berkas');
            if (input.files.length > 0 && input.files[0].size > MAX_FILE_SIZE) {
                e.preventDefault();
                alert('File melebihi batas ukuran 2MB. Silakan pilih file yang lebih kecil.');
            }
        });
    });
});
</script>
@endpush
