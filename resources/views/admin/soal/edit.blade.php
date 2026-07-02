@extends('layouts.admin')

@section('title', 'Edit Soal')

@section('content')
<div class="container-fluid" x-data="formSoal()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Soal</h1>
        <a href="{{ route('admin.soal.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.soal.update', $soal) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pertanyaan</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                            <textarea name="pertanyaan" id="pertanyaan" class="form-control @error('pertanyaan') is-invalid @enderror" 
                                      rows="5">{{ old('pertanyaan', $soal->pertanyaan) }}</textarea>
                            @error('pertanyaan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pembahasan</label>
                            <textarea name="pembahasan" id="pembahasan" class="form-control" rows="3">{{ old('pembahasan', $soal->pembahasan) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Jawaban -->
                <div class="card mb-4" x-show="tipe !== 'esai'">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Pilihan Jawaban</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="tambahJawaban()">
                            <i class="bi bi-plus"></i> Tambah Jawaban
                        </button>
                    </div>
                    <div class="card-body">
                        <template x-for="(jawaban, index) in jawabanList" :key="index">
                            <div class="input-group mb-3">
                                <div class="input-group-text">
                                    <input type="checkbox" class="form-check-input mt-0" 
                                           :name="`jawaban[${index}][benar]`" value="1"
                                           :checked="jawaban.benar"
                                           @change="jawaban.benar = $event.target.checked">
                                </div>
                                <span class="input-group-text" x-text="String.fromCharCode(65 + index)"></span>
                                <input type="text" class="form-control" 
                                       :name="`jawaban[${index}][isi]`" 
                                       x-model="jawaban.isi"
                                       placeholder="Isi jawaban...">
                                <button type="button" class="btn btn-danger" @click="hapusJawaban(index)" 
                                        x-show="jawabanList.length > 2">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </template>
                        <small class="text-muted">Centang jawaban yang benar</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pengaturan</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Topik</label>
                            <select name="topik_id" class="form-select">
                                <option value="">-- Pilih Topik --</option>
                                @foreach($topik as $t)
                                    <option value="{{ $t->id }}" {{ old('topik_id', $soal->topik_id) == $t->id ? 'selected' : '' }}>
                                        {{ $t->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipe Soal <span class="text-danger">*</span></label>
                            <select name="tipe" class="form-select" x-model="tipe">
                                <option value="pilihan_ganda">Pilihan Ganda</option>
                                <option value="jawaban_ganda">Jawaban Ganda</option>
                                <option value="benar_salah">Benar/Salah</option>
                                <option value="esai">Esai</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bobot <span class="text-danger">*</span></label>
                            <input type="number" name="bobot" class="form-control" 
                                   value="{{ old('bobot', $soal->bobot) }}" min="1">
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="aktif" value="1" class="form-check-input" 
                                       id="aktif" {{ old('aktif', $soal->aktif) ? 'checked' : '' }}>
                                <label class="form-check-label" for="aktif">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>

                @if($soal->media)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Media Saat Ini</h5>
                    </div>
                    <div class="card-body">
                        @if($soal->tipe_media === 'gambar')
                            <img src="{{ Storage::url($soal->media) }}" class="img-fluid rounded mb-2" alt="Media">
                        @elseif($soal->tipe_media === 'audio')
                            <audio controls class="w-100 mb-2">
                                <source src="{{ Storage::url($soal->media) }}">
                            </audio>
                        @endif
                        <form action="{{ route('admin.soal.hapus-media', $soal) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> Hapus Media
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Upload Media Baru</h5>
                    </div>
                    <div class="card-body">
                        <input type="file" name="media" class="form-control" accept="image/*,audio/*,video/*">
                        <small class="text-muted">Maks. 10MB</small>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function formSoal() {
    return {
        tipe: '{{ old('tipe', $soal->tipe) }}',
        jawabanList: @json($soal->jawaban->map(fn($j) => ['isi' => $j->isi_jawaban, 'benar' => $j->benar])),
        tambahJawaban() {
            this.jawabanList.push({ isi: '', benar: false });
        },
        hapusJawaban(index) {
            if (this.jawabanList.length > 2) {
                this.jawabanList.splice(index, 1);
            }
        }
    }
}
</script>
@endpush
@endsection
