@extends('layouts.admin')

@section('title', 'Alur & Jadwal SPMB')

@push('styles')
<style>
    .aj-head {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff; border-radius: 16px;
    }
    .aj-stage {
        border: 1px solid #e5e7eb; border-radius: 16px; background: #fff;
        overflow: hidden; transition: box-shadow .2s ease;
    }
    .aj-stage:hover { box-shadow: 0 6px 18px rgba(15,23,42,.07); }
    .aj-stage-head {
        display: flex; align-items: center; gap: .75rem;
        padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }
    .aj-num {
        width: 40px; height: 40px; flex-shrink: 0;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.1rem;
        background: linear-gradient(135deg, #10b981, #059669);
    }
    .aj-stage.locked .aj-num { background: #cbd5e1; }
    .aj-stage-title { font-weight: 700; line-height: 1.2; }
    .aj-stage-desc { font-size: .8rem; color: #64748b; }
    .aj-body { padding: 1rem; }
    .aj-badge-fixed {
        font-size: .68rem; font-weight: 700; color: #6b7280;
        background: #f1f5f9; border: 1px solid #e5e7eb;
        padding: .2rem .5rem; border-radius: 999px;
    }
    .aj-preview {
        font-size: .8rem; color: #475569; background: #f0fdf4;
        border: 1px dashed #86efac; border-radius: 10px; padding: .5rem .75rem;
    }
    .form-label.sm { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .2rem; }
    .aj-switch-label { font-size: .85rem; font-weight: 600; }
    @media (max-width: 575px) {
        .aj-stage-head { padding: .7rem .8rem; }
        .aj-body { padding: .8rem; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="aj-head p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-signpost-2-fill me-2"></i>Alur &amp; Jadwal SPMB</h4>
                <p class="mb-0 opacity-90 small">
                    Atur waktu buka/tutup tiap tahap &amp; catatan yang dilihat pendaftar — semua di satu halaman, per periode.
                </p>
            </div>
            <div class="text-md-end">
                <div class="small opacity-75">Sedang diatur</div>
                <div class="fs-5 fw-bold">
                    <i class="bi bi-calendar3-range me-1"></i>{{ $tahunAjaran->nama ?? '—' }}
                    @if($gelombang)
                        <span class="opacity-75">›</span> {{ $gelombang->nama }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Pemilih periode (kalau ada >1 tahun ajaran) --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i>Tahun Ajaran:</span>
        @foreach($daftarTahun as $ta)
            <form action="{{ route('admin.periode-aktif.ganti') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="tahun_ajaran_id" value="{{ $ta->id }}">
                <button type="submit"
                        class="btn btn-sm {{ (int)$tahunAjaranId === (int)$ta->id ? 'btn-success' : 'btn-outline-secondary' }}">
                    {{ $ta->nama }}
                    @if($ta->default)<i class="bi bi-star-fill ms-1" title="Tahun aktif"></i>@endif
                </button>
            </form>
        @endforeach
        <a href="{{ route('admin.pengaturan.spmb.periode') }}" class="btn btn-sm btn-outline-primary ms-auto">
            <i class="bi bi-plus-circle me-1"></i>Kelola Tahun / Gelombang
        </a>
    </div>

    {{-- Pemilih GELOMBANG --}}
    @if($tahunAjaranId)
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4 p-2 rounded" style="background:#f1f5f9;">
        <span class="text-muted small fw-semibold"><i class="bi bi-layers-half me-1"></i>Gelombang:</span>
        @forelse($daftarGelombang as $g)
            <a href="{{ route('admin.alur-jadwal.index', ['gelombang' => $g->id]) }}"
               class="btn btn-sm {{ (int)$gelombangId === (int)$g->id ? 'btn-primary' : 'btn-outline-primary' }}">
                {{ $g->nama }}
                @php $st = $g->statusPendaftaran(); @endphp
                <span class="badge bg-{{ $st['class'] }} ms-1">{{ $st['label'] }}</span>
            </a>
        @empty
            <span class="text-muted small">Belum ada gelombang.
                <a href="{{ route('admin.pengaturan.spmb.periode') }}">Tambah gelombang</a>.
            </span>
        @endforelse
        <span class="text-muted small ms-auto">
            <i class="bi bi-info-circle me-1"></i>Jadwal yang Anda atur berlaku untuk gelombang terpilih.
        </span>
    </div>
    @endif

    @if(!$tahunAjaranId)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>
            Belum ada Tahun Ajaran. Silakan buat dulu di <a href="{{ route('admin.pengaturan.spmb.periode') }}" class="alert-link">Kelola Periode</a>.
        </div>
    @else
    <form action="{{ route('admin.pengaturan.alur-jadwal.simpan') }}" method="POST"
          x-data="alurJadwal()">
        @csrf
        <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
        <input type="hidden" name="gelombang_pendaftaran_id" value="{{ $gelombangId }}">

        <div class="row g-3">
            @foreach($jadwal as $tahap => $j)
            <div class="col-12 col-lg-6">
                <div class="aj-stage" :class="{ 'locked': !t{{ $tahap }}.dibuka && {{ $j['berjadwal'] ? 'true' : 'false' }} }"
                     x-data="{ }">
                    <div class="aj-stage-head">
                        <div class="aj-num"><i class="bi bi-{{ $j['icon'] }}"></i></div>
                        <div class="flex-grow-1">
                            <div class="aj-stage-title">Tahap {{ $tahap }}. {{ $j['judul'] }}</div>
                            <div class="aj-stage-desc">{{ $j['deskripsi'] }}</div>
                        </div>
                        @if(!$j['berjadwal'])
                            <span class="aj-badge-fixed"><i class="bi bi-lightning-charge-fill me-1"></i>Selalu terbuka</span>
                        @endif
                    </div>

                    <div class="aj-body">
                        @if(!$j['berjadwal'])
                            {{-- Tahap 1: tidak ada jadwal, hanya info --}}
                            <p class="text-muted small mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Tahap ini adalah titik masuk pendaftar (daftar = isi biodata). Tidak memerlukan jadwal.
                            </p>
                        @else
                            {{-- Toggle buka/tutup --}}
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="tahap[{{ $tahap }}][dibuka]" :value="t{{ $tahap }}.dibuka ? 1 : 0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="dibuka{{ $tahap }}" x-model="t{{ $tahap }}.dibuka">
                                <label class="form-check-label aj-switch-label" for="dibuka{{ $tahap }}">
                                    <span x-show="t{{ $tahap }}.dibuka" class="text-success"><i class="bi bi-unlock-fill me-1"></i>Tahap Dibuka</span>
                                    <span x-show="!t{{ $tahap }}.dibuka" class="text-danger" style="display:none"><i class="bi bi-lock-fill me-1"></i>Tahap Ditutup</span>
                                </label>
                            </div>

                            <div class="row g-2">
                                <div class="col-6 col-sm-3">
                                    <label class="form-label sm">Tgl Buka</label>
                                    <input type="date" class="form-control form-control-sm"
                                           name="tahap[{{ $tahap }}][tanggal_buka]" x-model="t{{ $tahap }}.tanggal_buka">
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="form-label sm">Jam Buka</label>
                                    <input type="time" class="form-control form-control-sm"
                                           name="tahap[{{ $tahap }}][waktu_mulai]" x-model="t{{ $tahap }}.waktu_mulai">
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="form-label sm">Tgl Tutup</label>
                                    <input type="date" class="form-control form-control-sm"
                                           name="tahap[{{ $tahap }}][tanggal_tutup]" x-model="t{{ $tahap }}.tanggal_tutup">
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="form-label sm">Jam Tutup</label>
                                    <input type="time" class="form-control form-control-sm"
                                           name="tahap[{{ $tahap }}][waktu_selesai]" x-model="t{{ $tahap }}.waktu_selesai">
                                </div>
                            </div>

                            @if($tahap === 5)
                            <div class="mt-2">
                                <label class="form-label sm">Lokasi (opsional)</label>
                                <input type="text" class="form-control form-control-sm"
                                       name="tahap[{{ $tahap }}][lokasi]" x-model="t{{ $tahap }}.lokasi"
                                       placeholder="mis. Aula SMA Al Furqon">
                            </div>
                            @endif

                            <div class="mt-2">
                                <label class="form-label sm">
                                    Catatan untuk Pendaftar
                                    <span class="text-muted fw-normal">(tampil di dashboard peserta pada tahap ini)</span>
                                </label>
                                <textarea class="form-control form-control-sm" rows="2"
                                          name="tahap[{{ $tahap }}][keterangan]" x-model="t{{ $tahap }}.keterangan"
                                          placeholder="mis. Bawa berkas asli saat wawancara / batas pembayaran ..."></textarea>
                            </div>

                            {{-- Preview jadwal seperti yang dilihat pendaftar --}}
                            <div class="aj-preview mt-2" x-show="previewJadwal(t{{ $tahap }})" x-cloak>
                                <i class="bi bi-eye me-1"></i><span x-text="previewJadwal(t{{ $tahap }})"></span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Bar simpan (sticky di bawah) --}}
        <div class="position-sticky bottom-0 bg-white border-top mt-4 py-3" style="z-index: 5;">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>Perubahan berlaku untuk
                    <strong>{{ $tahunAjaran->nama ?? '' }}{{ $gelombang ? ' › '.$gelombang->nama : '' }}</strong> saja.
                </span>
                <div class="d-flex gap-2">
                    <a href="{{ route('jadwal') }}" target="_blank" class="btn btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i>Lihat Halaman Publik
                    </a>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-save me-1"></i>Simpan Jadwal Gelombang Ini
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
function alurJadwal() {
    return {
        @foreach($jadwal as $tahap => $j)
        t{{ $tahap }}: {
            dibuka: {{ $j['dibuka'] ? 'true' : 'false' }},
            tanggal_buka: @js($j['tanggal_buka']),
            waktu_mulai: @js($j['waktu_mulai']),
            tanggal_tutup: @js($j['tanggal_tutup']),
            waktu_selesai: @js($j['waktu_selesai']),
            lokasi: @js($j['lokasi']),
            keterangan: @js($j['keterangan']),
        },
        @endforeach

        fmt(tgl, jam) {
            if (!tgl) return null;
            try {
                const d = new Date(tgl + 'T' + (jam || '00:00'));
                const opt = { day: 'numeric', month: 'long', year: 'numeric' };
                let s = d.toLocaleDateString('id-ID', opt);
                if (jam) s += ' ' + jam + ' WIB';
                return s;
            } catch (e) { return tgl; }
        },
        previewJadwal(t) {
            if (!t.dibuka) return 'Tahap ditutup — pendaftar tidak bisa mengakses.';
            const m = this.fmt(t.tanggal_buka, t.waktu_mulai);
            const s = this.fmt(t.tanggal_tutup, t.waktu_selesai);
            if (m && s) return 'Jadwal: ' + m + ' sampai ' + s;
            if (m) return 'Dibuka pada ' + m;
            if (s) return 'Ditutup pada ' + s;
            return 'Tanpa batas jadwal (selalu bisa diakses).';
        },
    }
}
</script>
@endpush
