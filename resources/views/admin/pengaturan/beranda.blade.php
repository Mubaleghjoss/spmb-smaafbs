@extends('layouts.admin')

@section('title', 'Konten Beranda')

@section('content')
<div class="container-fluid" x-data="berandaEditor()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-window-stack me-2"></i>Konten Beranda</h1>
        <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali isian:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info d-flex align-items-center">
        <i class="bi bi-info-circle fs-5 me-2"></i>
        <div class="small">
            Semua teks, angka, ikon, gambar, dan blok berikut tampil di halaman depan
            <a href="{{ url('/') }}" target="_blank">seleksi.smaafbs.sch.id</a>.
            Nama ikon memakai <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a> (tanpa awalan <code>bi-</code>), contoh: <code>people-fill</code>.
        </div>
    </div>

    <form method="POST" action="{{ route('admin.pengaturan.beranda.simpan') }}" enctype="multipart/form-data">
        @csrf

        {{-- ============ 1. HERO ============ --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-easel2 me-2 text-primary"></i>1. Bagian Hero (Header Utama)</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Badge / Label Kecil</label>
                            <input type="text" name="beranda_hero_badge" class="form-control" value="{{ old('beranda_hero_badge', $beranda['hero_badge']) }}" placeholder="Penerimaan Murid Baru">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Judul Utama <span class="text-danger">*</span></label>
                            <input type="text" name="beranda_hero_judul" class="form-control" value="{{ old('beranda_hero_judul', $beranda['hero_judul']) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sub Judul / Deskripsi</label>
                            <textarea name="beranda_hero_subjudul" class="form-control" rows="4">{{ old('beranda_hero_subjudul', $beranda['hero_subjudul']) }}</textarea>
                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Setiap <strong>Enter</strong> (baris baru) akan tampil sebagai baris terpisah di halaman publik. Cocok untuk poin 1, 2, 3 ke bawah.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teks Tombol 1 (Daftar)</label>
                                <input type="text" name="beranda_hero_tombol1_teks" class="form-control" value="{{ old('beranda_hero_tombol1_teks', $beranda['hero_tombol1_teks']) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teks Tombol 2 (Alur)</label>
                                <input type="text" name="beranda_hero_tombol2_teks" class="form-control" value="{{ old('beranda_hero_tombol2_teks', $beranda['hero_tombol2_teks']) }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gambar Hero</label>
                        <div class="border rounded p-2 text-center mb-2 bg-light">
                            @if($beranda['hero_gambar'])
                                <img src="{{ Storage::url($beranda['hero_gambar']) }}" alt="Hero" class="img-fluid rounded" style="max-height:160px">
                            @else
                                <div class="py-4"><i class="bi bi-image display-6 text-muted"></i><p class="small text-muted mb-0">Belum ada gambar</p></div>
                            @endif
                        </div>
                        <input type="file" name="beranda_hero_gambar" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp">
                        <small class="text-muted">PNG/JPG/WEBP, maks 4MB. Foto sekolah/santri disarankan.</small>
                        @if($beranda['hero_gambar'])
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="hapus_hero_gambar" value="1" id="hapusHero">
                            <label class="form-check-label small text-danger" for="hapusHero">Hapus gambar saat simpan</label>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ 2. STATISTIK ============ --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-bar-chart-fill me-2 text-success"></i>2. Statistik / Angka</h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="beranda_statistik_aktif" value="1" id="statAktif" {{ old('beranda_statistik_aktif', $beranda['statistik_aktif']) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="statAktif">Tampilkan</label>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small">Isi angka apa adanya (mis. <code>1200</code>) dan suffix (mis. <code>+</code>). Untuk memakai jumlah pendaftar real, ketik <code>auto</code> pada kolom angka.
                    Saat ini total pendaftar terdaftar: <strong>{{ $statistikPeserta['total_pendaftar'] }}</strong>.</p>
                <template x-for="(item, i) in statistik" :key="i">
                    <div class="row g-2 align-items-end mb-2 pb-2 border-bottom">
                        <div class="col-md-2"><label class="form-label small mb-0">Ikon</label><input type="text" :name="`statistik[${i}][icon]`" x-model="item.icon" class="form-control form-control-sm"></div>
                        <div class="col-md-2"><label class="form-label small mb-0">Angka</label><input type="text" :name="`statistik[${i}][angka]`" x-model="item.angka" class="form-control form-control-sm"></div>
                        <div class="col-md-2"><label class="form-label small mb-0">Suffix</label><input type="text" :name="`statistik[${i}][suffix]`" x-model="item.suffix" class="form-control form-control-sm"></div>
                        <div class="col-md-5"><label class="form-label small mb-0">Label</label><input type="text" :name="`statistik[${i}][label]`" x-model="item.label" class="form-control form-control-sm"></div>
                        <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger" @click="statistik.splice(i,1)"><i class="bi bi-x-lg"></i></button></div>
                    </div>
                </template>
                <button type="button" class="btn btn-sm btn-outline-success mt-2" @click="statistik.push({icon:'star-fill',angka:'',suffix:'',label:''})"><i class="bi bi-plus-lg me-1"></i>Tambah Statistik</button>
            </div>
        </div>

        {{-- ============ 3. KEUNGGULAN ============ --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-patch-check-fill me-2 text-info"></i>3. Keunggulan / Kenapa Memilih Kami</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Judul Bagian</label><input type="text" name="beranda_keunggulan_judul" class="form-control" value="{{ old('beranda_keunggulan_judul', $beranda['keunggulan_judul']) }}"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Sub Judul</label><input type="text" name="beranda_keunggulan_subjudul" class="form-control" value="{{ old('beranda_keunggulan_subjudul', $beranda['keunggulan_subjudul']) }}"></div>
                </div>
                <template x-for="(item, i) in keunggulan" :key="i">
                    <div class="row g-2 align-items-end mb-2 pb-2 border-bottom">
                        <div class="col-md-2"><label class="form-label small mb-0">Ikon</label><input type="text" :name="`keunggulan[${i}][icon]`" x-model="item.icon" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small mb-0">Judul</label><input type="text" :name="`keunggulan[${i}][judul]`" x-model="item.judul" class="form-control form-control-sm"></div>
                        <div class="col-md-6"><label class="form-label small mb-0">Deskripsi</label><input type="text" :name="`keunggulan[${i}][deskripsi]`" x-model="item.deskripsi" class="form-control form-control-sm"></div>
                        <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger" @click="keunggulan.splice(i,1)"><i class="bi bi-x-lg"></i></button></div>
                    </div>
                </template>
                <button type="button" class="btn btn-sm btn-outline-info mt-2" @click="keunggulan.push({icon:'check-circle',judul:'',deskripsi:''})"><i class="bi bi-plus-lg me-1"></i>Tambah Keunggulan</button>
            </div>
        </div>

        {{-- ============ 4. PROGRAM UNGGULAN ============ --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-mortarboard-fill me-2 text-warning"></i>4. Program Unggulan</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Judul Bagian</label><input type="text" name="beranda_program_judul" class="form-control" value="{{ old('beranda_program_judul', $beranda['program_judul']) }}"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Sub Judul</label><input type="text" name="beranda_program_subjudul" class="form-control" value="{{ old('beranda_program_subjudul', $beranda['program_subjudul']) }}"></div>
                </div>
                <template x-for="(item, i) in program" :key="i">
                    <div class="row g-2 align-items-end mb-2 pb-2 border-bottom">
                        <div class="col-md-2"><label class="form-label small mb-0">Ikon</label><input type="text" :name="`program[${i}][icon]`" x-model="item.icon" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small mb-0">Judul</label><input type="text" :name="`program[${i}][judul]`" x-model="item.judul" class="form-control form-control-sm"></div>
                        <div class="col-md-6"><label class="form-label small mb-0">Deskripsi</label><input type="text" :name="`program[${i}][deskripsi]`" x-model="item.deskripsi" class="form-control form-control-sm"></div>
                        <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger" @click="program.splice(i,1)"><i class="bi bi-x-lg"></i></button></div>
                    </div>
                </template>
                <button type="button" class="btn btn-sm btn-outline-warning mt-2" @click="program.push({icon:'star',judul:'',deskripsi:''})"><i class="bi bi-plus-lg me-1"></i>Tambah Program</button>
            </div>
        </div>

        {{-- ============ 5. TAHAPAN (toggle saja) ============ --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-list-ol me-2 text-secondary"></i>5. Preview 7 Tahapan SPMB</h6></div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="beranda_tahapan_aktif" value="1" id="tahapanAktif" {{ old('beranda_tahapan_aktif', $beranda['tahapan_aktif']) ? 'checked' : '' }}>
                    <label class="form-check-label" for="tahapanAktif">Tampilkan preview 7 tahapan di beranda</label>
                </div>
                <small class="text-muted">Isi/urutan tahapan diambil dari <a href="{{ route('admin.pengaturan.alur-spmb') }}">Pengaturan Alur SPMB</a>.</small>
            </div>
        </div>

        {{-- ============ 6. FAQ ============ --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-question-circle-fill me-2 text-primary"></i>6. FAQ (Pertanyaan Umum)</h6></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">Judul Bagian</label><input type="text" name="beranda_faq_judul" class="form-control" value="{{ old('beranda_faq_judul', $beranda['faq_judul']) }}"></div>
                <template x-for="(item, i) in faq" :key="i">
                    <div class="row g-2 align-items-start mb-2 pb-2 border-bottom">
                        <div class="col-md-5"><label class="form-label small mb-0">Pertanyaan</label><input type="text" :name="`faq[${i}][tanya]`" x-model="item.tanya" class="form-control form-control-sm"></div>
                        <div class="col-md-6"><label class="form-label small mb-0">Jawaban</label><textarea :name="`faq[${i}][jawab]`" x-model="item.jawab" class="form-control form-control-sm" rows="2"></textarea></div>
                        <div class="col-md-1 text-end pt-3"><button type="button" class="btn btn-sm btn-outline-danger" @click="faq.splice(i,1)"><i class="bi bi-x-lg"></i></button></div>
                    </div>
                </template>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" @click="faq.push({tanya:'',jawab:''})"><i class="bi bi-plus-lg me-1"></i>Tambah FAQ</button>
            </div>
        </div>

        {{-- ============ 7. TESTIMONI + MAPS ============ --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-chat-quote-fill me-2 text-success"></i>7. Testimoni & Lokasi</h6></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">Judul Bagian Testimoni</label><input type="text" name="beranda_testimoni_judul" class="form-control" value="{{ old('beranda_testimoni_judul', $beranda['testimoni_judul']) }}"></div>
                <template x-for="(item, i) in testimoni" :key="i">
                    <div class="row g-2 align-items-start mb-3 pb-3 border-bottom">
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Nama</label>
                            <input type="text" :name="`testimoni[${i}][nama]`" x-model="item.nama" class="form-control form-control-sm mb-1">
                            <label class="form-label small mb-0">Peran</label>
                            <input type="text" :name="`testimoni[${i}][peran]`" x-model="item.peran" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-5"><label class="form-label small mb-0">Isi Testimoni</label><textarea :name="`testimoni[${i}][isi]`" x-model="item.isi" class="form-control form-control-sm" rows="3"></textarea></div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Foto (opsional)</label>
                            <template x-if="item.foto">
                                <img :src="item.foto_url" alt="" class="rounded-circle mb-1" style="width:48px;height:48px;object-fit:cover" onerror="this.style.display='none'">
                            </template>
                            <input type="hidden" :name="`testimoni[${i}][foto_lama]`" x-model="item.foto">
                            <input type="file" :name="`testimoni[${i}][foto_file]`" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <div class="col-md-1 text-end pt-3"><button type="button" class="btn btn-sm btn-outline-danger" @click="testimoni.splice(i,1)"><i class="bi bi-x-lg"></i></button></div>
                    </div>
                </template>
                <button type="button" class="btn btn-sm btn-outline-success mt-2 mb-4" @click="testimoni.push({nama:'',peran:'',isi:'',foto:'',foto_url:''})"><i class="bi bi-plus-lg me-1"></i>Tambah Testimoni</button>

                <div class="mb-2">
                    <label class="form-label">Embed Peta Lokasi (Google Maps iframe / URL embed)</label>
                    <textarea name="beranda_maps_embed" class="form-control" rows="2" placeholder="Tempel kode <iframe ...> atau URL embed Google Maps">{{ old('beranda_maps_embed', $beranda['maps_embed']) }}</textarea>
                    <small class="text-muted">Di Google Maps: Bagikan &rarr; Sematkan peta &rarr; salin kode iframe. Kosongkan untuk menyembunyikan peta.</small>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-eye me-1"></i>Lihat Beranda</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan Semua</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function berandaEditor() {
    return {
        statistik: @js(old('statistik', $beranda['statistik'])),
        keunggulan: @js(old('keunggulan', $beranda['keunggulan'])),
        program: @js(old('program', $beranda['program'])),
        faq: @js(old('faq', $beranda['faq'])),
        testimoni: @js(collect(old('testimoni', $beranda['testimoni']))->map(function($t){
            $t['foto'] = $t['foto'] ?? '';
            $t['foto_url'] = !empty($t['foto']) ? \Illuminate\Support\Facades\Storage::url($t['foto']) : '';
            return $t;
        })->values()),
    }
}
</script>
@endpush
@endsection
