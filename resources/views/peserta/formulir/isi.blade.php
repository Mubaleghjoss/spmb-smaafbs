@extends('layouts.peserta')

@section('title', 'Formulir SPMB')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Formulir SPMB - Biodata Siswa</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($formulir && $formulir->status_verifikasi === 'ditolak')
                        <div class="alert alert-warning">
                            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Formulir Ditolak</h6>
                            <p class="mb-0">{{ $formulir->catatan_verifikasi ?? 'Silakan perbaiki data formulir Anda.' }}</p>
                        </div>
                    @endif

                    <form action="{{ route('peserta.formulir.simpan') }}" method="POST" enctype="multipart/form-data" id="form-ppdb">
                        @csrf
                        
                        {{-- Baris 1: Nama, Tanggal Lahir, Jenis Kelamin --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">1. Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror" 
                                       name="nama_lengkap" maxlength="255" value="{{ old('nama_lengkap', $formulir?->nama_lengkap ?? $peserta->nama) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">2. Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_lahir') is-invalid @enderror" 
                                       name="tanggal_lahir" value="{{ old('tanggal_lahir', $formulir?->tanggal_lahir?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">5. Jenis Kelamin <span class="text-danger">*</span></label>
                                <select class="form-select @error('jenis_kelamin') is-invalid @enderror" name="jenis_kelamin">
                                    <option value="">-- Pilih --</option>
                                    <option value="L" {{ old('jenis_kelamin', $formulir?->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('jenis_kelamin', $formulir?->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                        </div>

                        {{-- Baris 2: Kota & Provinsi Kelahiran --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">3. Kota Kelahiran <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('tempat_lahir') is-invalid @enderror" 
                                       name="tempat_lahir" maxlength="100" value="{{ old('tempat_lahir', $formulir?->tempat_lahir) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">4. Provinsi Kelahiran <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('provinsi_lahir') is-invalid @enderror" 
                                       name="provinsi_lahir" maxlength="100" value="{{ old('provinsi_lahir', $formulir?->provinsi_lahir) }}">
                            </div>
                        </div>

                        {{-- Baris 3: Asal Sekolah & Prestasi --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">6. Asal Sekolah SMP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('asal_sekolah') is-invalid @enderror" 
                                       name="asal_sekolah" maxlength="255" value="{{ old('asal_sekolah', $formulir?->asal_sekolah ?? $peserta->asal_sekolah) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">7. Prestasi</label>
                                <input type="text" class="form-control @error('prestasi') is-invalid @enderror" 
                                       name="prestasi" maxlength="255" value="{{ old('prestasi', $formulir?->prestasi) }}">
                            </div>
                        </div>

                        {{-- Baris 4: Data Fisik / Ukuran Baju --}}
                        <div class="alert alert-warning py-2 mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Penting!</strong> Data pengukuran baju sangat diperlukan untuk pembuatan seragam. Silakan isi sekarang atau update setelah submit.
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label class="form-label">8. Lingkar Dada (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('lingkar_dada') is-invalid @enderror" 
                                       name="lingkar_dada" value="{{ old('lingkar_dada', $formulir?->lingkar_dada) }}" placeholder="cth: 85">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">9. Lingkar Pinggang (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('lingkar_pinggang') is-invalid @enderror" 
                                       name="lingkar_pinggang" value="{{ old('lingkar_pinggang', $formulir?->lingkar_pinggang) }}" placeholder="cth: 70">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">10. Lingkar Kepala (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('lingkar_kepala') is-invalid @enderror" 
                                       name="lingkar_kepala" value="{{ old('lingkar_kepala', $formulir?->lingkar_kepala) }}" placeholder="cth: 55">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label class="form-label">11. Panjang Celana/Rok (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('panjang_celana') is-invalid @enderror" 
                                       name="panjang_celana" value="{{ old('panjang_celana', $formulir?->panjang_celana) }}" placeholder="cth: 100">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">12. Tinggi Badan (cm)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('tinggi_badan') is-invalid @enderror" 
                                       name="tinggi_badan" value="{{ old('tinggi_badan', $formulir?->tinggi_badan) }}" placeholder="cth: 170">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">13. Berat Badan (kg)</label>
                                <input type="number" step="0.1" inputmode="decimal" class="form-control @error('berat_badan') is-invalid @enderror" 
                                       name="berat_badan" value="{{ old('berat_badan', $formulir?->berat_badan) }}" placeholder="cth: 60">
                            </div>
                        </div>

                        {{-- Baris 5: Hobi & Cita-cita --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">14. Hobi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('hobi') is-invalid @enderror" 
                                       name="hobi" value="{{ old('hobi', $formulir?->hobi) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">15. Cita-cita <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('cita_cita') is-invalid @enderror" 
                                       name="cita_cita" value="{{ old('cita_cita', $formulir?->cita_cita) }}">
                            </div>
                        </div>

                        {{-- Baris 6: Data Orang Tua --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">16. Nama Ayah/Wali <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_ayah') is-invalid @enderror" 
                                       name="nama_ayah" value="{{ old('nama_ayah', $formulir?->nama_ayah) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">17. Nama Ibu/Wali <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_ibu') is-invalid @enderror" 
                                       name="nama_ibu" value="{{ old('nama_ibu', $formulir?->nama_ibu) }}">
                            </div>
                        </div>

                        {{-- Baris 7: Pekerjaan Orang Tua --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">18. Pekerjaan Ayah <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('pekerjaan_ayah') is-invalid @enderror" 
                                       name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $formulir?->pekerjaan_ayah) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">19. Pekerjaan Ibu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('pekerjaan_ibu') is-invalid @enderror" 
                                       name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $formulir?->pekerjaan_ibu) }}">
                            </div>
                        </div>

                        {{-- Baris 7b: Pendidikan Orang Tua --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">20. Pendidikan Terakhir Ayah</label>
                                <select class="form-select @error('pendidikan_ayah') is-invalid @enderror" name="pendidikan_ayah">
                                    <option value="">-- Pilih --</option>
                                    @foreach(['SD', 'SMP', 'SMA/SMK', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'] as $pend)
                                        <option value="{{ $pend }}" {{ old('pendidikan_ayah', $formulir?->pendidikan_ayah) == $pend ? 'selected' : '' }}>{{ $pend }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">21. Pendidikan Terakhir Ibu</label>
                                <select class="form-select @error('pendidikan_ibu') is-invalid @enderror" name="pendidikan_ibu">
                                    <option value="">-- Pilih --</option>
                                    @foreach(['SD', 'SMP', 'SMA/SMK', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'] as $pend)
                                        <option value="{{ $pend }}" {{ old('pendidikan_ibu', $formulir?->pendidikan_ibu) == $pend ? 'selected' : '' }}>{{ $pend }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Baris 8: Alamat Detail --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-3">
                                <label class="form-label">22. Kelurahan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('alamat_kelurahan') is-invalid @enderror" 
                                       name="alamat_kelurahan" value="{{ old('alamat_kelurahan', $formulir?->alamat_kelurahan) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">23. Kecamatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('alamat_kecamatan') is-invalid @enderror" 
                                       name="alamat_kecamatan" value="{{ old('alamat_kecamatan', $formulir?->alamat_kecamatan) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">24. Kota/Kab <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('alamat_kota') is-invalid @enderror" 
                                       name="alamat_kota" value="{{ old('alamat_kota', $formulir?->alamat_kota) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">25. Provinsi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('alamat_provinsi') is-invalid @enderror" 
                                       name="alamat_provinsi" value="{{ old('alamat_provinsi', $formulir?->alamat_provinsi) }}">
                            </div>
                        </div>

                        {{-- Baris 9: Telepon --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-3">
                                <label class="form-label">26. No Telepon Rumah</label>
                                <input type="text" class="form-control @error('telp_rumah') is-invalid @enderror" 
                                       name="telp_rumah" value="{{ old('telp_rumah', $formulir?->telp_rumah) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">27. No HP/WA Siswa</label>
                                <input type="text" class="form-control @error('telepon') is-invalid @enderror" 
                                       name="telepon" value="{{ old('telepon', $formulir?->telepon ?? $peserta->telepon) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">28. No HP/WA Ayah</label>
                                <input type="text" class="form-control @error('telepon_ayah') is-invalid @enderror" 
                                       name="telepon_ayah" value="{{ old('telepon_ayah', $formulir?->telepon_ayah) }}">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">29. No HP/WA Ibu</label>
                                <input type="text" class="form-control @error('telepon_ibu') is-invalid @enderror" 
                                       name="telepon_ibu" value="{{ old('telepon_ibu', $formulir?->telepon_ibu) }}">
                            </div>
                        </div>

                        {{-- Baris 10: Jumlah Saudara --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label class="form-label">30. Jumlah Saudara Kandung <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('jumlah_saudara') is-invalid @enderror" 
                                       name="jumlah_saudara" value="{{ old('jumlah_saudara', $formulir?->jumlah_saudara) }}" min="0">
                            </div>
                        </div>

                        {{-- Baris 11: Tanggal Daftar & NISN --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label class="form-label">31. Tanggal Daftar SMA AFBS <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_daftar') is-invalid @enderror" 
                                       name="tanggal_daftar" value="{{ old('tanggal_daftar', $formulir?->tanggal_daftar?->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">32. NISN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nisn') is-invalid @enderror" 
                                       name="nisn" maxlength="20" value="{{ old('nisn', $formulir?->nisn) }}">
                            </div>
                        </div>

                        {{-- Baris 12: Kelompok, Desa, Daerah --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label class="form-label">33. Nama Kelompok <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('kelompok') is-invalid @enderror" 
                                       name="kelompok" value="{{ old('kelompok', $formulir?->kelompok) }}"
                                       placeholder="Nama tempat sambung Kelompok">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">34. Nama Desa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('desa') is-invalid @enderror" 
                                       name="desa" value="{{ old('desa', $formulir?->desa) }}"
                                       placeholder="Nama tempat sambung Desa ">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">35. Nama Daerah <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('daerah') is-invalid @enderror" 
                                       name="daerah" value="{{ old('daerah', $formulir?->daerah) }}"
                                       placeholder="Nama tempat sambung Daerah">
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3"><i class="bi bi-upload me-2"></i>Unggahan Dokumen</h5>

                        {{-- Baris 13: KK & Akta --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">36. KK (pdf/foto) - Wajib <small class="text-muted">(maks. 2MB)</small></label>
                                <input type="file" class="form-control @error('file_kk') is-invalid @enderror" 
                                       name="file_kk" accept=".jpg,.jpeg,.png,.pdf">
                                @if(!empty($formulir?->file_kk))
                                    <div class="form-text text-success">
                                        <i class="bi bi-check-circle me-1"></i>Berkas ada: 
                                        <a href="{{ Storage::url($formulir->file_kk) }}" target="_blank">Lihat</a>
                                    </div>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">37. Akta Lahir (pdf/foto) - Wajib <small class="text-muted">(maks. 2MB)</small></label>
                                <input type="file" class="form-control @error('file_akta') is-invalid @enderror" 
                                       name="file_akta" accept=".jpg,.jpeg,.png,.pdf">
                                @if(!empty($formulir?->file_akta))
                                    <div class="form-text text-success">
                                        <i class="bi bi-check-circle me-1"></i>Berkas ada: 
                                        <a href="{{ Storage::url($formulir->file_akta) }}" target="_blank">Lihat</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Baris 14: Ijazah & BPJS --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">38. Ijazah SMP (pdf/foto) - Boleh menyusul <small class="text-muted">(maks. 2MB)</small></label>
                                <input type="file" class="form-control @error('file_ijazah') is-invalid @enderror" 
                                       name="file_ijazah" accept=".jpg,.jpeg,.png,.pdf">
                                @if(!empty($formulir?->file_ijazah))
                                    <div class="form-text text-success">
                                        <i class="bi bi-check-circle me-1"></i>Berkas ada: 
                                        <a href="{{ Storage::url($formulir->file_ijazah) }}" target="_blank">Lihat</a>
                                    </div>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">39. Kartu BPJS (pdf/foto) - Boleh menyusul <small class="text-muted">(maks. 2MB)</small></label>
                                <input type="file" class="form-control @error('file_bpjs') is-invalid @enderror" 
                                       name="file_bpjs" accept=".jpg,.jpeg,.png,.pdf">
                                @if(!empty($formulir?->file_bpjs))
                                    <div class="form-text text-success">
                                        <i class="bi bi-check-circle me-1"></i>Berkas ada: 
                                        <a href="{{ Storage::url($formulir->file_bpjs) }}" target="_blank">Lihat</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Baris 15: KTP Ibu & KTP Ayah --}}
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">40. KTP Ibu Kandung (pdf/foto) - Boleh menyusul <small class="text-muted">(maks. 2MB)</small></label>
                                <input type="file" class="form-control @error('file_ktp_ibu') is-invalid @enderror" 
                                       name="file_ktp_ibu" accept=".jpg,.jpeg,.png,.pdf">
                                @if(!empty($formulir?->file_ktp_ibu))
                                    <div class="form-text text-success">
                                        <i class="bi bi-check-circle me-1"></i>Berkas ada: 
                                        <a href="{{ Storage::url($formulir->file_ktp_ibu) }}" target="_blank">Lihat</a>
                                    </div>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">41. KTP Ayah Kandung (pdf/foto) - Boleh menyusul <small class="text-muted">(maks. 2MB)</small></label>
                                <input type="file" class="form-control @error('file_ktp_ayah') is-invalid @enderror" 
                                       name="file_ktp_ayah" accept=".jpg,.jpeg,.png,.pdf">
                                @if(!empty($formulir?->file_ktp_ayah))
                                    <div class="form-text text-success">
                                        <i class="bi bi-check-circle me-1"></i>Berkas ada: 
                                        <a href="{{ Storage::url($formulir->file_ktp_ayah) }}" target="_blank">Lihat</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <hr>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Simpan
                            </button>
                            <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB per file
    const MAX_FILE_SIZE_LABEL = '2MB';

    // Client-side file size validation
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            // Remove previous error
            const prev = this.parentNode.querySelector('.file-size-error');
            if (prev) prev.remove();
            this.classList.remove('is-invalid');

            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.size > MAX_FILE_SIZE) {
                    const sizeMB = (file.size / 1024 / 1024).toFixed(1);
                    const errDiv = document.createElement('div');
                    errDiv.className = 'file-size-error text-danger small mt-1';
                    errDiv.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>File "${file.name}" terlalu besar (${sizeMB}MB). Maksimal ${MAX_FILE_SIZE_LABEL}.`;
                    this.parentNode.appendChild(errDiv);
                    this.classList.add('is-invalid');
                    this.value = ''; // Clear the input
                }
            }
        });
    });

    // Character counter for text inputs with maxlength
    document.querySelectorAll('input[maxlength]').forEach(input => {
        const max = input.getAttribute('maxlength');
        const counter = document.createElement('small');
        counter.className = 'form-text text-muted char-counter';
        counter.textContent = `Maks. ${max} karakter`;
        input.parentNode.appendChild(counter);

        input.addEventListener('input', function() {
            const remaining = max - this.value.length;
            if (remaining <= 10) {
                counter.className = 'form-text text-danger char-counter';
                counter.textContent = `Sisa ${remaining} karakter`;
            } else {
                counter.className = 'form-text text-muted char-counter';
                counter.textContent = `Maks. ${max} karakter`;
            }
        });
    });

    // Form submit — prevent if any file is too large
    const form = document.getElementById('form-ppdb');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            this.querySelectorAll('input[type="file"]').forEach(input => {
                if (input.files.length > 0 && input.files[0].size > MAX_FILE_SIZE) {
                    hasError = true;
                }
            });
            if (hasError) {
                e.preventDefault();
                alert('Beberapa file melebihi batas ukuran 2MB. Silakan perkecil ukuran file terlebih dahulu.');
            }
        });
    }
});
</script>
@endpush
