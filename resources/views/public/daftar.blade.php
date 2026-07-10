@extends('layouts.public')

@section('title', 'Pendaftaran SPMB')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Pendaftaran SPMB</h2>
                            <p class="text-muted">{{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}</p>
                            <p class="text-muted small">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
                        </div>
                        
                        @if(!$pendaftaranDibuka)
                        {{-- Tampilkan pesan jika pendaftaran belum dibuka --}}
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="bi bi-calendar-x text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="text-muted mb-3">Mohon Maaf</h4>
                            <p class="text-muted mb-4">{{ $pesanTutup }}</p>
                            
                            @if($jadwalBerikutnya)
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Gelombang berikutnya:
                                <strong>{{ $jadwalBerikutnya->tahunAjaran?->nama }} - {{ $jadwalBerikutnya->nama }}</strong><br>
                                <span>{{ $jadwalBerikutnya->labelPeriodePendaftaran() }}</span>
                            </div>
                            @endif

                            @if(!empty($periodePayload))
                            <div class="text-start border rounded-3 p-3 bg-light mb-4">
                                <h6 class="fw-semibold mb-3">Periode Pendaftaran</h6>
                                <div class="row g-3">
                                    @foreach($periodePayload as $tahun)
                                        <div class="col-md-6">
                                            <div class="bg-white border rounded-3 p-3 h-100">
                                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                    <div>
                                                        <div class="fw-semibold">{{ $tahun['nama'] }}</div>
                                                        <small class="text-muted">
                                                            Kuota: {{ $tahun['kuota']['kuota_label'] }} &middot;
                                                            Total: {{ $tahun['kuota']['total'] }} &middot;
                                                            Waiting: {{ $tahun['kuota']['waiting_list'] }}
                                                        </small>
                                                    </div>
                                                </div>
                                                @foreach($tahun['gelombang'] as $gelombang)
                                                    <div class="border-top pt-2 mt-2">
                                                        <div class="d-flex justify-content-between gap-2">
                                                            <span class="fw-medium">{{ $gelombang['nama'] }}</span>
                                                            <span class="badge bg-{{ $gelombang['status_class'] }}">{{ $gelombang['status_label'] }}</span>
                                                        </div>
                                                        <small class="text-muted">{{ $gelombang['periode'] }}</small>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            
                            <div class="mt-4">
                                <a href="{{ route('beranda') }}" class="btn btn-outline-success me-2">
                                    <i class="bi bi-house me-1"></i>Kembali ke Beranda
                                </a>
                                <a href="{{ route('peserta.login') }}" class="btn btn-success">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login Peserta
                                </a>
                            </div>
                            
                            @if(!empty($spmb['whatsapp_spmb']))
                            <div class="mt-4">
                                <p class="text-muted small mb-2">Ada pertanyaan?</p>
                                <a href="https://wa.me/62{{ ltrim($spmb['whatsapp_spmb'], '0') }}" target="_blank" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-whatsapp me-1"></i>Hubungi Tim SPMB
                                </a>
                            </div>
                            @endif
                        </div>
                        @else
                        {{-- Form pendaftaran --}}
                        <form method="POST" action="{{ route('daftar.proses') }}"
                              @submit="loading = true"
                              x-data="formDaftar(
                                  @js($periodePayload),
                                  @js((string) old('tahun_ajaran_id', $tahunDefaultId)),
                                  @js((string) old('gelombang_pendaftaran_id')),
                                  @js(old('jenis_pendaftaran', 'siswa_baru')),
                                  @js((string) old('kelas_tujuan', 10))
                              )">
                            @csrf

                            <input type="hidden" name="tahun_ajaran_id" x-model="tahunAjaranId">
                            <input type="hidden" name="gelombang_pendaftaran_id" x-model="gelombangId">

                            <div class="border rounded-3 p-3 mb-4 bg-light">
                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                                    <div>
                                        <h5 class="mb-1">Pilih Periode Pendaftaran</h5>
                                        <p class="text-muted small mb-0">Pilih tahun ajaran dan gelombang yang sedang dibuka.</p>
                                    </div>
                                    <template x-if="selectedTahun">
                                        <span class="badge align-self-start"
                                              :class="selectedTahun.kuota.penuh ? 'bg-warning text-dark' : 'bg-success'"
                                              x-text="selectedTahun.kuota.penuh ? 'Kuota Penuh - Waiting List' : 'Masih Dalam Kuota'"></span>
                                    </template>
                                </div>

                                <div class="row g-2 mb-3" role="tablist" aria-label="Tahun ajaran">
                                    <template x-for="tahun in periode" :key="tahun.id">
                                        <div class="col-md-6">
                                            <button type="button"
                                                    class="w-100 h-100 text-start border rounded-3 p-3 bg-white"
                                                    :class="tahunAjaranId === tahun.id ? 'border-success shadow-sm' : ''"
                                                    @click="pilihTahunId(tahun.id)">
                                                <div class="d-flex justify-content-between gap-2 mb-2">
                                                    <strong x-text="tahun.nama"></strong>
                                                    <span class="badge"
                                                          :class="tahunAjaranId === tahun.id ? 'bg-success' : (tahunTerbuka(tahun) ? 'bg-light text-success border' : 'bg-light text-muted border')"
                                                          x-text="tahunAjaranId === tahun.id ? 'Dipilih' : (tahunTerbuka(tahun) ? 'Tersedia' : 'Tidak Dibuka')"></span>
                                                </div>
                                                <div class="small text-muted">
                                                    Kuota <span x-text="tahun.kuota.kuota_label"></span>
                                                    &middot; Total <span x-text="tahun.kuota.total"></span>
                                                    &middot; Waiting <span x-text="tahun.kuota.waiting_list"></span>
                                                </div>
                                                <div class="small mt-2" :class="tahunTerbuka(tahun) ? 'text-success' : 'text-muted'">
                                                    <i class="bi" :class="tahunTerbuka(tahun) ? 'bi-check-circle' : 'bi-lock'"></i>
                                                    <span x-text="ringkasanGelombangTahun(tahun)"></span>
                                                </div>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <template x-if="selectedTahun">
                                    <div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6 col-lg-3">
                                                <div class="bg-white border rounded p-2 h-100">
                                                    <div class="text-muted small">Kuota</div>
                                                    <strong x-text="selectedTahun.kuota.kuota_label"></strong>
                                                </div>
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <div class="bg-white border rounded p-2 h-100">
                                                    <div class="text-muted small">Total Daftar</div>
                                                    <strong x-text="selectedTahun.kuota.total"></strong>
                                                </div>
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <div class="bg-white border rounded p-2 h-100">
                                                    <div class="text-muted small">Dalam Kuota</div>
                                                    <strong x-text="selectedTahun.kuota.dalam_kuota"></strong>
                                                </div>
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <div class="bg-white border rounded p-2 h-100">
                                                    <div class="text-muted small">Sisa / Waiting</div>
                                                    <strong>
                                                        <span x-text="selectedTahun.kuota.sisa_label"></span>
                                                        <span class="text-warning" x-show="selectedTahun.kuota.waiting_list > 0">
                                                            / <span x-text="selectedTahun.kuota.waiting_list"></span>
                                                        </span>
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <template x-for="gelombang in gelombangTersedia" :key="gelombang.id">
                                                <div class="col-md-6">
                                                    <button type="button"
                                                            class="w-100 h-100 text-start border rounded-3 p-3 bg-white"
                                                            :class="{
                                                                'border-success shadow-sm': gelombangId === gelombang.id,
                                                                'opacity-75': !gelombang.dibuka
                                                            }"
                                                            @click="selectGelombang(gelombang)"
                                                            :disabled="!gelombang.dibuka">
                                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                            <div>
                                                                <div class="fw-semibold" x-text="gelombang.nama"></div>
                                                                <div class="text-muted small" x-text="gelombang.periode"></div>
                                                            </div>
                                                            <span class="badge" :class="'bg-' + gelombang.status_class" x-text="gelombang.status_label"></span>
                                                        </div>
                                                        <div class="small text-success" x-show="gelombangId === gelombang.id">
                                                            <i class="bi bi-check-circle me-1"></i>Dipilih
                                                        </div>
                                                        <div class="small text-muted" x-show="!gelombang.dibuka">
                                                            Gelombang ini belum bisa dipilih.
                                                        </div>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="alert alert-warning mt-3 mb-0" x-show="selectedTahun.kuota.penuh">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            Kuota tahun ajaran ini sudah penuh. Pendaftaran tetap diterima sebagai <strong>Waiting List</strong>.
                                        </div>
                                        <div class="alert alert-secondary mt-3 mb-0" x-show="gelombangTerbuka.length === 0">
                                            <i class="bi bi-lock me-1"></i>
                                            Belum ada gelombang yang sedang dibuka pada tahun ajaran ini.
                                        </div>
                                    </div>
                                </template>

                                @error('tahun_ajaran_id')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                                @error('gelombang_pendaftaran_id')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-block">Daftar Sebagai <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group" aria-label="Jenis pendaftaran">
                                    <input type="radio" class="btn-check" name="jenis_pendaftaran"
                                           id="jenisSiswaBaru" value="siswa_baru"
                                           x-model="jenisPendaftaran" @change="ubahJenis()" required>
                                    <label class="btn btn-outline-success" for="jenisSiswaBaru">
                                        <i class="bi bi-person-plus me-1"></i>Siswa Baru
                                    </label>
                                    <input type="radio" class="btn-check" name="jenis_pendaftaran"
                                           id="jenisPindahan" value="pindahan"
                                           x-model="jenisPendaftaran" @change="ubahJenis()" required>
                                    <label class="btn btn-outline-primary" for="jenisPindahan">
                                        <i class="bi bi-arrow-left-right me-1"></i>Pindahan
                                    </label>
                                </div>
                                @error('jenis_pendaftaran')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="kelas_tujuan" class="form-label">Kelas Tujuan <span class="text-danger">*</span></label>
                                <select id="kelas_tujuan"
                                        name="kelas_tujuan"
                                        class="form-select @error('kelas_tujuan') is-invalid @enderror"
                                        x-model="kelasTujuan"
                                        :disabled="jenisPendaftaran === 'siswa_baru'"
                                        required>
                                    <option value="10">Kelas 10</option>
                                    <option value="11">Kelas 11</option>
                                </select>
                                <input type="hidden" name="kelas_tujuan" value="10"
                                       x-show="jenisPendaftaran === 'siswa_baru'"
                                       :disabled="jenisPendaftaran !== 'siswa_baru'">
                                <div class="form-text" x-show="jenisPendaftaran === 'siswa_baru'">
                                    Siswa baru otomatis mendaftar ke kelas 10.
                                </div>
                                @error('kelas_tujuan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('nama') is-invalid @enderror" 
                                       id="nama" 
                                       name="nama" 
                                       value="{{ old('nama') }}"
                                       placeholder="Masukkan nama lengkap sesuai akta"
                                       required>
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="telepon" class="form-label">No HP / WhatsApp <span class="text-danger">*</span></label>
                                <input type="tel" 
                                       class="form-control @error('telepon') is-invalid @enderror" 
                                       id="telepon" 
                                       name="telepon" 
                                       value="{{ old('telepon') }}"
                                       placeholder="08xxxxxxxxxx"
                                       required>
                                @error('telepon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">No HP akan digunakan untuk login dan notifikasi WhatsApp</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="asal_sekolah" class="form-label">Asal Sekolah <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('asal_sekolah') is-invalid @enderror" 
                                       id="asal_sekolah" 
                                       name="asal_sekolah" 
                                       value="{{ old('asal_sekolah') }}"
                                       placeholder="Nama SMP/MTs asal"
                                       required>
                                @error('asal_sekolah')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input :type="showPassword ? 'text' : 'password'" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="Minimal 8 karakter"
                                           minlength="8"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" @click="showPassword = !showPassword">
                                        <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input :type="showPassword ? 'text' : 'password'" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation"
                                       placeholder="Ulangi password"
                                       required>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="setuju" name="setuju" required>
                                    <label class="form-check-label small" for="setuju">
                                        Saya menyetujui <a href="#" class="text-success" data-bs-toggle="modal" data-bs-target="#modalSyaratKetentuan">syarat dan ketentuan</a> pendaftaran {{ $branding['nama_singkat'] ?? 'SPMB' }} {{ $branding['nama_institusi'] ?? '' }}
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 btn-lg" :disabled="loading || !gelombangId">
                                <span x-show="!loading">
                                    <i class="bi bi-person-plus me-2"></i><span x-text="selectedTahun?.kuota?.penuh ? 'Daftar Waiting List' : 'Daftar Sekarang'"></span>
                                </span>
                                <span x-show="loading">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Memproses...
                                </span>
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="text-muted small mb-2">Sudah punya akun?</p>
                            <a href="{{ route('peserta.login') }}" class="btn btn-outline-success">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login Peserta
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modal Syarat dan Ketentuan --}}
<div class="modal fade" id="modalSyaratKetentuan" tabindex="-1" aria-labelledby="modalSyaratKetentuanLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalSyaratKetentuanLabel">
                    <i class="bi bi-file-earmark-text me-2"></i>Syarat dan Ketentuan SPMB
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-success">{{ $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL' }}</h4>
                    <p class="text-muted">Seleksi Penerimaan Murid Baru (SPMB)</p>
                    <p class="text-muted small">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Bismillahirrahmanirrahim</strong><br>
                    Dengan mendaftar di SPMB {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }}, calon peserta didik dan orang tua/wali menyatakan telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan berikut.
                </div>
                
                @foreach($syaratKetentuan ?? [] as $bagian)
                <h6 class="fw-bold text-success mt-4 mb-3">
                    <i class="{{ $bagian['ikon'] ?? 'bi-circle' }} me-2"></i>{{ $bagian['judul'] }}
                </h6>
                {!! $bagian['konten'] !!}
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="bi bi-check-circle me-2"></i>Saya Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function formDaftar(periode, tahunDefault, gelombangLama, jenisLama, kelasLama) {
    return {
        periode,
        tahunAjaranId: tahunDefault,
        gelombangId: gelombangLama,
        jenisPendaftaran: jenisLama,
        kelasTujuan: kelasLama,
        showPassword: false,
        loading: false,
        get selectedTahun() {
            return this.periode.find(tahun => tahun.id === this.tahunAjaranId) ?? null;
        },
        get gelombangTersedia() {
            return this.selectedTahun?.gelombang ?? [];
        },
        get gelombangTerbuka() {
            return this.gelombangTersedia.filter(gelombang => gelombang.dibuka);
        },
        get selectedGelombang() {
            return this.gelombangTersedia.find(gelombang => gelombang.id === this.gelombangId) ?? null;
        },
        init() {
            if (!this.tahunAjaranId && this.periode.length > 0) {
                this.tahunAjaranId = this.periode[0].id;
            }
            this.pilihTahun(true);
            this.ubahJenis();
        },
        tahunTerbuka(tahun) {
            return (tahun.gelombang ?? []).some(gelombang => gelombang.dibuka);
        },
        ringkasanGelombangTahun(tahun) {
            const jumlah = (tahun.gelombang ?? []).filter(gelombang => gelombang.dibuka).length;
            if (jumlah === 0) {
                return 'Belum ada gelombang terbuka';
            }

            return jumlah === 1 ? '1 gelombang terbuka' : `${jumlah} gelombang terbuka`;
        },
        pilihTahunId(tahunId) {
            this.tahunAjaranId = tahunId;
            this.pilihTahun();
        },
        pilihTahun(pertahankanPilihan = false) {
            const tersedia = this.gelombangTerbuka;
            const masihValid = tersedia.some(gelombang => gelombang.id === this.gelombangId);
            if (!pertahankanPilihan || !masihValid) {
                this.gelombangId = tersedia.length === 1 ? tersedia[0].id : '';
            }
        },
        selectGelombang(gelombang) {
            if (!gelombang.dibuka) {
                return;
            }

            this.gelombangId = gelombang.id;
        },
        ubahJenis() {
            if (this.jenisPendaftaran === 'siswa_baru') {
                this.kelasTujuan = '10';
            } else if (!['10', '11'].includes(this.kelasTujuan)) {
                this.kelasTujuan = '10';
            }
        }
    }
}
</script>
@endpush
@endsection
