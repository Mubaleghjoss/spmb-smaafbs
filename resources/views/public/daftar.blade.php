@extends('layouts.public')

@section('title', 'Pendaftaran SPMB')

@push('styles')
<style>
    .wizard-step { display: none; }
    .wizard-step.active { display: block; animation: fadeStep .3s ease; }
    @keyframes fadeStep { from { opacity:0; transform: translateY(8px);} to {opacity:1; transform:none;} }
    .step-dots { display:flex; gap:.5rem; justify-content:center; }
    .step-dot { width:34px; height:6px; border-radius:6px; background:#dee2e6; transition:background .3s; }
    .step-dot.done, .step-dot.current { background: var(--primary-color); }
    .periode-btn.selected { border-color: var(--primary-color) !important; box-shadow: 0 0 0 .15rem rgba(46,139,87,.25); }
</style>
@endpush

@section('content')
<section class="tk-hero" style="padding: clamp(2.5rem,6vw,4rem) 0;">
    <div class="container text-center">
        <span class="tk-eyebrow mb-3"><span class="dot"></span>Pendaftaran</span>
        <h1 class="fw-bold display-6 mb-1">Pendaftaran SPMB</h1>
        <p class="tk-sub mb-0" style="opacity:.9">{{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }} · Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
    </div>
</section>
<section class="tk-section cream" style="padding: clamp(2rem,5vw,4rem) 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="tk-card">
                    <div class="core p-3 p-md-4">
                        @if(!$pendaftaranDibuka)
                        {{-- Pendaftaran belum dibuka --}}
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-warning" style="font-size: 3.5rem;"></i>
                            <h4 class="text-muted mt-3 mb-2">Mohon Maaf</h4>
                            <p class="text-muted">{{ $pesanTutup }}</p>
                            @if($jadwalBerikutnya)
                            <div class="alert alert-info d-inline-block text-start">
                                <i class="bi bi-info-circle me-2"></i>Gelombang berikutnya:
                                <strong>{{ $jadwalBerikutnya->tahunAjaran?->nama }} - {{ $jadwalBerikutnya->nama }}</strong><br>
                                <span>{{ $jadwalBerikutnya->labelPeriodePendaftaran() }}</span>
                            </div>
                            @endif
                            <div class="mt-4">
                                <a href="{{ route('beranda') }}" class="btn btn-outline-success me-2"><i class="bi bi-house me-1"></i>Beranda</a>
                                <a href="{{ route('peserta.login') }}" class="btn btn-success"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
                            </div>
                        </div>
                        @else

                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                            </ul>
                        </div>
                        @endif
                        <form method="POST" action="{{ route('daftar.proses') }}"
                              x-data="wizardDaftar(
                                  @js($periodePayload),
                                  @js((string) old('tahun_ajaran_id', $tahunDefaultId)),
                                  @js((string) old('gelombang_pendaftaran_id')),
                                  @js(old('jenis_pendaftaran', 'siswa_baru')),
                                  @js((string) old('kelas_tujuan', 10)),
                                  @js((string) old('jenis_kelamin', '')),
                                  @js((string) old('telepon', '')),
                                  @js((string) old('telepon_ayah', '')),
                                  @js((string) old('telepon_ibu', ''))
                              )"
                              @submit="onSubmit($event)">
                            @csrf
                            <input type="hidden" name="tahun_ajaran_id" x-model="tahunAjaranId">
                            <input type="hidden" name="gelombang_pendaftaran_id" x-model="gelombangId">

                            {{-- Step indicator --}}
                            <div class="step-dots mb-4">
                                <div class="step-dot" :class="{'current': step===1, 'done': step>1}"></div>
                                <div class="step-dot" :class="{'current': step===2, 'done': step>2}"></div>
                                <div class="step-dot" :class="{'current': step===3, 'done': step>3}"></div>
                            </div>

                            {{-- ============ STEP 1: PERIODE + JENIS ============ --}}
                            <div class="wizard-step" :class="{active: step===1}">
                                <h5 class="mb-1">1. Pilih Periode & Jenis</h5>
                                <p class="text-muted small mb-3">Pilih gelombang yang sedang dibuka.</p>

                                <div class="row g-2 mb-3">
                                    <template x-for="tahun in periodeTerbuka" :key="tahun.id">
                                        <div class="col-12">
                                            <button type="button"
                                                    class="periode-btn w-100 text-start border rounded-3 p-3 bg-white"
                                                    :class="tahunAjaranId === tahun.id ? 'selected' : ''"
                                                    @click="pilihTahunId(tahun.id)">
                                                <div class="d-flex justify-content-between">
                                                    <strong x-text="tahun.nama"></strong>
                                                    <span class="badge" :class="tahunAjaranId===tahun.id?'bg-success':'bg-light text-success border'"
                                                          x-text="tahunAjaranId===tahun.id?'Dipilih':'Pilih'"></span>
                                                </div>
                                                <div class="small text-muted mt-1" x-text="ringkasanGelombangTahun(tahun)"></div>
                                                <div class="small mt-1 d-flex flex-wrap gap-2 align-items-center">
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="bi bi-people me-1"></i>Kuota <span x-text="tahun.kuota.kuota_label"></span>
                                                    </span>
                                                    <span class="badge"
                                                          :class="tahun.kuota.penuh ? 'bg-warning text-dark' : 'bg-success-subtle text-success border border-success-subtle'">
                                                        <template x-if="tahun.kuota.penuh"><span>Kuota penuh — Waiting List</span></template>
                                                        <template x-if="!tahun.kuota.penuh"><span>Sisa <span x-text="tahun.kuota.sisa_label"></span></span></template>
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <template x-if="selectedTahun">
                                    <div class="mb-3">
                                        {{-- Rincian kuota periode terpilih: total & per jenis kelamin --}}
                                        <div class="border rounded-3 p-3 mb-3 bg-light">
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                                <span class="small fw-semibold">
                                                    <i class="bi bi-clipboard-data me-1"></i>Kuota Penerimaan
                                                </span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2"
                                                            data-bs-toggle="modal" data-bs-target="#modalPenjelasanKuota">
                                                        <i class="bi bi-patch-question me-1"></i>Cara masuk kuota
                                                    </button>
                                                    <span class="badge"
                                                          :class="selectedTahun.kuota.penuh ? 'bg-warning text-dark' : 'bg-success'">
                                                        <template x-if="selectedTahun.kuota.penuh"><span>Penuh</span></template>
                                                        <template x-if="!selectedTahun.kuota.penuh"><span>Tersedia</span></template>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="row g-2 text-center mb-2">
                                                <div class="col-4">
                                                    <div class="bg-white border rounded py-2">
                                                        <div class="fw-bold" x-text="selectedTahun.kuota.kuota_label"></div>
                                                        <div class="text-muted" style="font-size:.7rem">Kuota</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="bg-white border rounded py-2">
                                                        <div class="fw-bold text-success" x-text="selectedTahun.kuota.dalam_kuota"></div>
                                                        <div class="text-muted" style="font-size:.7rem">Terisi</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="bg-white border rounded py-2">
                                                        <div class="fw-bold" x-text="selectedTahun.kuota.sisa_label"></div>
                                                        <div class="text-muted" style="font-size:.7rem">Sisa</div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Per jenis kelamin --}}
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="bg-white border rounded p-2">
                                                        <div class="d-flex justify-content-between small">
                                                            <span><i class="bi bi-gender-male me-1 text-primary"></i>Laki-laki</span>
                                                            <span class="fw-semibold">
                                                                <span x-text="selectedTahun.kuota.laki_laki.dalam_kuota"></span>/<span x-text="selectedTahun.kuota.kuota_laki_laki_label"></span>
                                                            </span>
                                                        </div>
                                                        <div class="progress mt-1" style="height:5px">
                                                            <div class="progress-bar bg-primary"
                                                                 :style="'width:' + persenKuota(selectedTahun.kuota.laki_laki.dalam_kuota, selectedTahun.kuota.kuota_laki_laki) + '%'"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="bg-white border rounded p-2">
                                                        <div class="d-flex justify-content-between small">
                                                            <span><i class="bi bi-gender-female me-1 text-danger"></i>Perempuan</span>
                                                            <span class="fw-semibold">
                                                                <span x-text="selectedTahun.kuota.perempuan.dalam_kuota"></span>/<span x-text="selectedTahun.kuota.kuota_perempuan_label"></span>
                                                            </span>
                                                        </div>
                                                        <div class="progress mt-1" style="height:5px">
                                                            <div class="progress-bar bg-danger"
                                                                 :style="'width:' + persenKuota(selectedTahun.kuota.perempuan.dalam_kuota, selectedTahun.kuota.kuota_perempuan) + '%'"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <template x-if="selectedTahun.kuota.penuh">
                                                <div class="alert alert-warning py-2 px-2 small mt-2 mb-0">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    Kuota periode ini sudah penuh. Pendaftaran tetap diterima dan masuk <strong>Waiting List</strong>.
                                                </div>
                                            </template>

                                            <div class="text-muted mt-2" style="font-size:.72rem">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Pendaftaran menempati kuota setelah Formulir Biodata lengkap dan Pembayaran Pendaftaran (Tahap 3) diverifikasi Tim SPMB.
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#modalPenjelasanKuota" class="text-success fw-semibold">Selengkapnya</a>
                                            </div>
                                        </div>

                                        <label class="form-label small text-muted">Gelombang</label>
                                        <div class="row g-2">
                                            <template x-for="g in gelombangTersedia" :key="g.id">
                                                <div class="col-12">
                                                    <button type="button"
                                                            class="periode-btn w-100 text-start border rounded-3 p-3 bg-white"
                                                            :class="{'selected': gelombangId===g.id, 'opacity-50': !g.dibuka}"
                                                            :disabled="!g.dibuka"
                                                            @click="selectGelombang(g)">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="fw-medium" x-text="g.nama"></span>
                                                            <span class="badge" :class="'bg-'+g.status_class" x-text="g.status_label"></span>
                                                        </div>
                                                        <div class="small text-muted" x-text="g.periode"></div>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <div class="mb-3">
                                    <label class="form-label d-block">Daftar Sebagai <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="jenis_pendaftaran" id="jbaru" value="siswa_baru" x-model="jenisPendaftaran" @change="ubahJenis()">
                                        <label class="btn btn-outline-success" for="jbaru"><i class="bi bi-person-plus me-1"></i>Siswa Baru</label>
                                        <input type="radio" class="btn-check" name="jenis_pendaftaran" id="jpindah" value="pindahan" x-model="jenisPendaftaran" @change="ubahJenis()">
                                        <label class="btn btn-outline-primary" for="jpindah"><i class="bi bi-arrow-left-right me-1"></i>Pindahan</label>
                                    </div>
                                </div>
                                <div class="mb-3" x-show="jenisPendaftaran==='pindahan'">
                                    <label class="form-label">Kelas Tujuan</label>
                                    <select name="kelas_tujuan" class="form-select" x-model="kelasTujuan">
                                        <option value="10">Kelas 10</option>
                                        <option value="11">Kelas 11</option>
                                    </select>
                                </div>
                                <input type="hidden" name="kelas_tujuan" :value="kelasTujuan" x-show="jenisPendaftaran==='siswa_baru'">

                                <button type="button" class="btn btn-success w-100 btn-lg mt-2" :disabled="!gelombangId" @click="next()">
                                    Lanjut <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>

                            {{-- ============ STEP 2: BIODATA ============ --}}
                            <div class="wizard-step" :class="{active: step===2}">
                                <h5 class="mb-1">2. Biodata Calon Siswa</h5>
                                <p class="text-muted small mb-3">Data ini menjadi biodata pendaftaran Anda.</p>

                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" x-model="nama" value="{{ old('nama') }}" placeholder="Sesuai akta kelahiran">
                                </div>
                                <div class="row g-2">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                        <select name="jenis_kelamin" class="form-select" x-model="jenisKelamin">
                                            <option value="">- Pilih -</option>
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Tanggal Lahir</label>
                                        <input type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir') }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir') }}" placeholder="Kota kelahiran">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Asal Sekolah SMP/MTs <span class="text-danger">*</span></label>
                                    <input type="text" name="asal_sekolah" class="form-control" value="{{ old('asal_sekolah') }}">
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Nama Ayah/Wali</label>
                                        <input type="text" name="nama_ayah" class="form-control" value="{{ old('nama_ayah') }}">
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Nama Ibu/Wali</label>
                                        <input type="text" name="nama_ibu" class="form-control" value="{{ old('nama_ibu') }}">
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">Kota/Kabupaten</label>
                                        <input type="text" name="alamat_kota" class="form-control" value="{{ old('alamat_kota') }}">
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">NISN</label>
                                        <input type="text" name="nisn" class="form-control" inputmode="numeric" value="{{ old('nisn') }}">
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-outline-secondary" @click="prev()"><i class="bi bi-arrow-left"></i></button>
                                    <button type="button" class="btn btn-success flex-fill btn-lg" @click="next()">Lanjut <i class="bi bi-arrow-right ms-1"></i></button>
                                </div>
                            </div>

                            {{-- ============ STEP 3: NO HP (AKUN) + SETUJU ============ --}}
                            <div class="wizard-step" :class="{active: step===3}">
                                <h5 class="mb-1">3. Nomor HP untuk Akun</h5>
                                <p class="text-muted small mb-3">
                                    No HP ini menjadi <strong>username sekaligus password</strong> akun Anda untuk login ke dashboard.
                                    Jika No HP siswa kosong, No HP orang tua akan dipakai.
                                </p>

                                <div class="mb-3">
                                    <label class="form-label">No HP/WA Siswa</label>
                                    <input type="tel" name="telepon" class="form-control" x-model="telSiswa" inputmode="numeric" placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">No HP/WA Ayah</label>
                                        <input type="tel" name="telepon_ayah" class="form-control" x-model="telAyah" inputmode="numeric" placeholder="08xxxxxxxxxx">
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label class="form-label">No HP/WA Ibu</label>
                                        <input type="tel" name="telepon_ibu" class="form-control" x-model="telIbu" inputmode="numeric" placeholder="08xxxxxxxxxx">
                                    </div>
                                </div>

                                <div class="alert alert-success py-2" x-show="noHpAkun()">
                                    <i class="bi bi-key me-1"></i>
                                    Akun Anda: username & password = <strong x-text="noHpAkun()"></strong>
                                </div>
                                <div class="alert alert-warning py-2" x-show="!noHpAkun()">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Isi minimal satu No HP (siswa atau orang tua).
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="setuju" name="setuju" x-model="setuju">
                                    <label class="form-check-label small" for="setuju">
                                        Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#modalSK" class="text-success">syarat & ketentuan</a> pendaftaran.
                                    </label>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary" @click="prev()"><i class="bi bi-arrow-left"></i></button>
                                    <button type="submit" class="btn btn-success flex-fill btn-lg" :disabled="!noHpAkun() || !setuju || loading">
                                        <span x-show="!loading"><i class="bi bi-check-circle me-1"></i>Daftar & Masuk Dashboard</span>
                                        <span x-show="loading"><span class="spinner-border spinner-border-sm me-1"></span>Memproses...</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <hr class="my-4">
                        <div class="text-center">
                            <p class="text-muted small mb-2">Sudah punya akun?</p>
                            <a href="{{ route('peserta.login') }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login dengan No HP
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modal Syarat & Ketentuan --}}
<div class="modal fade" id="modalSK" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Syarat & Ketentuan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @foreach($syaratKetentuan ?? [] as $bagian)
                <h6 class="fw-bold text-success mt-3"><i class="{{ $bagian['ikon'] ?? 'bi-circle' }} me-2"></i>{{ $bagian['judul'] }}</h6>
                {!! $bagian['konten'] !!}
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Saya Mengerti</button>
            </div>
        </div>
    </div>
</div>

@include('partials.modal-penjelasan-kuota')
@endsection

@push('scripts')
<script>
function wizardDaftar(periode, tahunDefault, gelombangLama, jenisLama, kelasLama, jkLama, telS, telA, telI) {
    return {
        step: 1,
        periode,
        tahunAjaranId: tahunDefault,
        gelombangId: gelombangLama,
        jenisPendaftaran: jenisLama,
        kelasTujuan: kelasLama || '10',
        jenisKelamin: jkLama || '',
        nama: @js(old('nama', '')),
        telSiswa: telS || '',
        telAyah: telA || '',
        telIbu: telI || '',
        setuju: false,
        loading: false,
        get selectedTahun() { return this.periode.find(t => t.id === this.tahunAjaranId) ?? null; },
        get periodeTerbuka() {
            const t = this.periode.filter(x => this.tahunTerbuka(x));
            return t.length ? t : this.periode;
        },
        get gelombangTersedia() { return this.selectedTahun?.gelombang ?? []; },
        get gelombangTerbuka() { return this.gelombangTersedia.filter(g => g.dibuka); },
        init() {
            const daftar = this.periodeTerbuka;
            if (!daftar.some(t => t.id === this.tahunAjaranId) && daftar.length) this.tahunAjaranId = daftar[0].id;
            this.pilihTahun(true);
            this.ubahJenis();
        },
        tahunTerbuka(t){ return (t.gelombang ?? []).some(g => g.dibuka); },
        ringkasanGelombangTahun(t){
            const n = (t.gelombang ?? []).filter(g => g.dibuka).length;
            return n === 0 ? 'Belum ada gelombang terbuka' : (n===1 ? '1 gelombang terbuka' : n+' gelombang terbuka');
        },
        // Persen terisi untuk progress bar kuota per jenis kelamin.
        // Kuota 0 = tidak dibatasi -> bar dibiarkan kosong agar tidak menyesatkan.
        persenKuota(terisi, kuota){
            const k = Number(kuota) || 0;
            if (k <= 0) return 0;
            return Math.min(100, Math.round((Number(terisi) || 0) / k * 100));
        },
        pilihTahunId(id){ this.tahunAjaranId = id; this.pilihTahun(); },
        pilihTahun(keep=false){
            const t = this.gelombangTerbuka;
            const valid = t.some(g => g.id === this.gelombangId);
            if(!keep || !valid) this.gelombangId = t.length===1 ? t[0].id : '';
        },
        selectGelombang(g){ if(g.dibuka) this.gelombangId = g.id; },
        ubahJenis(){ if(this.jenisPendaftaran==='siswa_baru') this.kelasTujuan='10'; else if(!['10','11'].includes(this.kelasTujuan)) this.kelasTujuan='10'; },
        normHp(v){
            let n = (v||'').replace(/[^0-9+]/g,'');
            if(!n) return '';
            if(n.startsWith('+62')) n='0'+n.slice(3);
            else if(n.startsWith('62')) n='0'+n.slice(2);
            else if(!n.startsWith('0')) n='0'+n;
            return n;
        },
        noHpAkun(){ return this.normHp(this.telSiswa) || this.normHp(this.telAyah) || this.normHp(this.telIbu); },
        next(){
            if(this.step===1 && !this.gelombangId){ alert('Pilih gelombang dulu.'); return; }
            if(this.step===2){
                if(!this.nama.trim()){ alert('Nama wajib diisi.'); return; }
                if(!this.jenisKelamin){ alert('Jenis kelamin wajib dipilih.'); return; }
            }
            if(this.step<3) this.step++;
            window.scrollTo({top:0,behavior:'smooth'});
        },
        prev(){ if(this.step>1) this.step--; window.scrollTo({top:0,behavior:'smooth'}); },
        onSubmit(e){
            if(!this.noHpAkun() || !this.setuju){ e.preventDefault(); return; }
            this.loading = true;
        },
    }
}
</script>
@endpush
