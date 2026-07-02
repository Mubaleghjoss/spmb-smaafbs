@extends('layouts.admin')

@section('title', 'Edit Tes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Tes</h1>
        <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.tes.update', $tes) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Tes <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nama') is-invalid @enderror" 
                                   id="nama" name="nama" value="{{ old('nama', $tes->nama) }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control @error('keterangan') is-invalid @enderror" 
                                      id="keterangan" name="keterangan" rows="3">{{ old('keterangan', $tes->keterangan) }}</textarea>
                            @error('keterangan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="durasi_menit" class="form-label">Durasi (menit) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('durasi_menit') is-invalid @enderror" 
                                   id="durasi_menit" name="durasi_menit" 
                                   value="{{ old('durasi_menit', $tes->durasi_menit) }}" 
                                   min="1" max="600" required>
                            @error('durasi_menit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nilai_lulus" class="form-label">Nilai Lulus <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('nilai_lulus') is-invalid @enderror" 
                                   id="nilai_lulus" name="nilai_lulus" 
                                   value="{{ old('nilai_lulus', $tes->nilai_lulus) }}" 
                                   min="0" max="100" step="0.01" required>
                            @error('nilai_lulus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>


                <hr>


                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="acak_soal" 
                                       name="acak_soal" value="1" 
                                       {{ old('acak_soal', $tes->acak_soal) ? 'checked' : '' }}>
                                <label class="form-check-label" for="acak_soal">Acak Urutan Soal</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="acak_jawaban" 
                                       name="acak_jawaban" value="1" 
                                       {{ old('acak_jawaban', $tes->acak_jawaban) ? 'checked' : '' }}>
                                <label class="form-check-label" for="acak_jawaban">Acak Urutan Jawaban</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="tampilkan_nilai" 
                                       name="tampilkan_nilai" value="1" 
                                       {{ old('tampilkan_nilai', $tes->tampilkan_nilai) ? 'checked' : '' }}>
                                <label class="form-check-label" for="tampilkan_nilai">Tampilkan Nilai</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="tampilkan_pembahasan" 
                                       name="tampilkan_pembahasan" value="1" 
                                       {{ old('tampilkan_pembahasan', $tes->tampilkan_pembahasan) ? 'checked' : '' }}>
                                <label class="form-check-label" for="tampilkan_pembahasan">Tampilkan Pembahasan</label>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
