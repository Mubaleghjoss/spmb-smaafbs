@extends('layouts.peserta')

@section('title', 'Review Formulir SPMB')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center py-4">
                    @if($formulir->status_verifikasi === 'menunggu')
                        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="text-warning">Menunggu Verifikasi</h5>
                        <p class="text-muted mb-0">Formulir Anda sedang diverifikasi oleh admin. Data tetap bisa diperbaiki jika ada kesalahan.</p>
                    @elseif($formulir->status_verifikasi === 'terverifikasi')
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="text-success">Formulir Terverifikasi</h5>
                        <p class="text-muted mb-0">Formulir sudah diverifikasi. Jika ada data yang salah, perbaiki melalui form di bawah.</p>
                    @elseif($formulir->status_verifikasi === 'ditolak')
                        <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="text-danger">Formulir Ditolak</h5>
                        <p class="text-muted mb-0">{{ $formulir->catatan_verifikasi ?? 'Silakan perbaiki data formulir Anda.' }}</p>
                    @else
                        <div class="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-file-earmark text-secondary" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="text-secondary">Draft</h5>
                        <p class="text-muted mb-0">Lengkapi atau perbaiki data formulir di bawah.</p>
                    @endif
                </div>
            </div>

            @if(session('sukses'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Data belum bisa disimpan.</div>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('peserta.formulir.update-data-fisik') }}" method="POST" class="card border-0 shadow-sm mb-4">
                @csrf
                <div class="card-header bg-primary text-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Perbarui Semua Data Formulir</h5>
                        <span class="badge bg-light text-primary">Bisa diedit</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Gunakan halaman ini untuk memperbaiki data yang salah. Hobi dan cita-cita boleh lebih dari satu, pisahkan dengan koma atau baris baru.
                    </div>

                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-person me-2"></i>Data Diri</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">1. Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap', $formulir->nama_lengkap) }}">
                            @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">2. Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control @error('tanggal_lahir') is-invalid @enderror" value="{{ old('tanggal_lahir', $formulir->tanggal_lahir?->format('Y-m-d')) }}">
                            @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">3. Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror">
                                <option value="">Pilih</option>
                                <option value="L" @selected(old('jenis_kelamin', $formulir->jenis_kelamin) === 'L')>Laki-laki</option>
                                <option value="P" @selected(old('jenis_kelamin', $formulir->jenis_kelamin) === 'P')>Perempuan</option>
                            </select>
                            @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">4. Kota Kelahiran</label>
                            <input type="text" name="tempat_lahir" class="form-control @error('tempat_lahir') is-invalid @enderror" value="{{ old('tempat_lahir', $formulir->tempat_lahir) }}">
                            @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">5. Provinsi Kelahiran</label>
                            <input type="text" name="provinsi_lahir" class="form-control @error('provinsi_lahir') is-invalid @enderror" value="{{ old('provinsi_lahir', $formulir->provinsi_lahir) }}">
                            @error('provinsi_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">6. Agama</label>
                            <input type="text" name="agama" class="form-control @error('agama') is-invalid @enderror" value="{{ old('agama', $formulir->agama) }}">
                            @error('agama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-building me-2"></i>Data Sekolah</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">7. Asal Sekolah SMP</label>
                            <input type="text" name="asal_sekolah" class="form-control @error('asal_sekolah') is-invalid @enderror" value="{{ old('asal_sekolah', $formulir->asal_sekolah) }}">
                            @error('asal_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">8. Alamat Sekolah</label>
                            <input type="text" name="alamat_sekolah" class="form-control @error('alamat_sekolah') is-invalid @enderror" value="{{ old('alamat_sekolah', $formulir->alamat_sekolah) }}">
                            @error('alamat_sekolah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">9. Tanggal Daftar</label>
                            <input type="date" name="tanggal_daftar" class="form-control @error('tanggal_daftar') is-invalid @enderror" value="{{ old('tanggal_daftar', $formulir->tanggal_daftar?->format('Y-m-d')) }}">
                            @error('tanggal_daftar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">10. NISN</label>
                            <input type="text" name="nisn" class="form-control @error('nisn') is-invalid @enderror" value="{{ old('nisn', $formulir->nisn) }}">
                            <div class="form-text">Cek manual: <a href="https://nisn.data.kemendikdasmen.go.id/" target="_blank" rel="noopener">nisn.data.kemendikdasmen.go.id</a></div>
                            @error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">11. Prestasi</label>
                            <input type="text" name="prestasi" class="form-control @error('prestasi') is-invalid @enderror" value="{{ old('prestasi', $formulir->prestasi) }}">
                            @error('prestasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-heart-pulse me-2"></i>Data Fisik</h6>
                    <div class="row g-3 mb-4">
                        @foreach([
                            'lingkar_dada' => ['12. Lingkar Dada', 'cm'],
                            'lingkar_pinggang' => ['13. Lingkar Pinggang', 'cm'],
                            'lingkar_kepala' => ['14. Lingkar Kepala', 'cm'],
                            'panjang_celana' => ['15. Panjang Celana/Rok', 'cm'],
                            'tinggi_badan' => ['16. Tinggi Badan', 'cm'],
                            'berat_badan' => ['17. Berat Badan', 'kg'],
                        ] as $field => [$label, $satuan])
                            <div class="col-md-4">
                                <label class="form-label">{{ $label }} ({{ $satuan }})</label>
                                <input type="number" step="0.1" inputmode="decimal" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $formulir->$field) }}">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>

                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-star me-2"></i>Data Tambahan</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">18. Hobi</label>
                            <textarea name="hobi" rows="3" class="form-control @error('hobi') is-invalid @enderror">{{ old('hobi', $formulir->hobi) }}</textarea>
                            <div class="form-text">Boleh lebih dari satu. Pisahkan dengan koma atau baris baru.</div>
                            @error('hobi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">19. Cita-cita</label>
                            <textarea name="cita_cita" rows="3" class="form-control @error('cita_cita') is-invalid @enderror">{{ old('cita_cita', $formulir->cita_cita) }}</textarea>
                            <div class="form-text">Boleh lebih dari satu. Pisahkan dengan koma atau baris baru.</div>
                            @error('cita_cita')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">20. Jumlah Saudara</label>
                            <input type="number" name="jumlah_saudara" class="form-control @error('jumlah_saudara') is-invalid @enderror" value="{{ old('jumlah_saudara', $formulir->jumlah_saudara) }}">
                            @error('jumlah_saudara')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-people me-2"></i>Data Orang Tua</h6>
                    <div class="row g-3 mb-4">
                        @foreach([
                            'nama_ayah' => '21. Nama Ayah/Wali',
                            'pekerjaan_ayah' => '22. Pekerjaan Ayah',
                            'pendidikan_ayah' => '23. Pendidikan Ayah',
                            'telepon_ayah' => '24. No HP/WA Ayah',
                            'nama_ibu' => '25. Nama Ibu/Wali',
                            'pekerjaan_ibu' => '26. Pekerjaan Ibu',
                            'pendidikan_ibu' => '27. Pendidikan Ibu',
                            'telepon_ibu' => '28. No HP/WA Ibu',
                        ] as $field => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <input type="text" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $formulir->$field) }}">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>

                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-geo-alt me-2"></i>Alamat dan Sambung</h6>
                    <div class="row g-3 mb-4">
                        @foreach([
                            'alamat_kelurahan' => '29. Kelurahan',
                            'alamat_kecamatan' => '30. Kecamatan',
                            'alamat_kota' => '31. Kota/Kabupaten',
                            'alamat_provinsi' => '32. Provinsi',
                            'kelompok' => '33. Nama Kelompok',
                            'desa' => '34. Nama Desa',
                            'daerah' => '35. Nama Daerah',
                        ] as $field => $label)
                            <div class="col-md-4">
                                <label class="form-label">{{ $label }}</label>
                                <input type="text" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $formulir->$field) }}">
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>

                    <h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-telephone me-2"></i>Kontak</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">36. Telepon Rumah</label>
                            <input type="text" name="telp_rumah" class="form-control @error('telp_rumah') is-invalid @enderror" value="{{ old('telp_rumah', $formulir->telp_rumah) }}">
                            @error('telp_rumah')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">37. No HP/WA Siswa</label>
                            <input type="text" name="telepon" class="form-control @error('telepon') is-invalid @enderror" value="{{ old('telepon', $formulir->telepon) }}">
                            @error('telepon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">38. Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $formulir->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Simpan Semua Data Formulir
                    </button>
                </div>
            </form>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Dokumen Unggahan</h5>
                </div>
                <div class="card-body">
                    @php
                        $berkasFields = [
                            'file_kk' => ['label' => '39. Kartu Keluarga (KK)', 'field' => 'file_kk'],
                            'file_akta' => ['label' => '40. Akta Lahir', 'field' => 'file_akta'],
                            'file_ijazah' => ['label' => '41. Ijazah SMP', 'field' => 'file_ijazah'],
                            'file_bpjs' => ['label' => '42. Kartu BPJS', 'field' => 'file_bpjs'],
                            'file_ktp_ibu' => ['label' => '43. KTP Ibu', 'field' => 'file_ktp_ibu'],
                            'file_ktp_ayah' => ['label' => '44. KTP Ayah', 'field' => 'file_ktp_ayah'],
                        ];
                        $berkasBelumLengkap = collect($berkasFields)->filter(fn($item, $key) => empty($formulir->$key));
                    @endphp

                    @if($berkasBelumLengkap->isNotEmpty())
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Ada {{ $berkasBelumLengkap->count() }} berkas yang belum diunggah.
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
                                            <div class="mt-2 d-flex justify-content-center gap-2">
                                                <a href="{{ Storage::url($formulir->$fieldName) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye me-1"></i>Lihat
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalUpload{{ $fieldName }}">
                                                    <i class="bi bi-arrow-repeat me-1"></i>Ganti
                                                </button>
                                            </div>
                                        @else
                                            <i class="bi bi-file-earmark-x text-warning" style="font-size: 1.5rem;"></i>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalUpload{{ $fieldName }}">
                                                    <i class="bi bi-upload me-1"></i>Upload
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @foreach($berkasFields as $fieldName => $info)
                <div class="modal fade" id="modalUpload{{ $fieldName }}" tabindex="-1" aria-labelledby="modalLabel{{ $fieldName }}">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('peserta.formulir.upload-berkas') }}" method="POST" enctype="multipart/form-data" class="form-upload-berkas">
                                @csrf
                                <input type="hidden" name="field" value="{{ $fieldName }}">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title" id="modalLabel{{ $fieldName }}"><i class="bi bi-upload me-2"></i>{{ $formulir->$fieldName ? 'Ganti' : 'Upload' }} {{ $info['label'] }}</h5>
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
                                        <i class="bi bi-upload me-1"></i>Simpan Berkas
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach

            @if($formulir->status_verifikasi === 'menunggu' && !empty($whatsappSpmb))
                <div class="card border-0 shadow-sm mt-4 border-success">
                    <div class="card-body text-center py-4">
                        <i class="bi bi-whatsapp text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Konfirmasi via WhatsApp</h5>
                        <p class="text-muted mb-3">Klik tombol di bawah untuk menghubungi Tim SPMB dan mempercepat proses verifikasi formulir Anda.</p>
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
    const MAX_FILE_SIZE = 2 * 1024 * 1024;

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
