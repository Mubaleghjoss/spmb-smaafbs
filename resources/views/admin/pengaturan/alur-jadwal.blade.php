@extends('layouts.admin')

@section('title', 'Alur & Jadwal SPMB')

@push('styles')
<style>
    .aj-head { background: linear-gradient(135deg, #10b981, #059669); color:#fff; border-radius:1rem; }
    .aj-card { border:0; border-radius:1rem; overflow:hidden; transition:transform .2s, box-shadow .2s; }
    .aj-card:hover { transform:translateY(-3px); box-shadow:0 12px 28px rgba(0,0,0,.10); }
    .aj-card-head { display:flex; align-items:center; gap:.75rem; padding:.9rem 1rem; border-bottom:1px solid #f1f5f9; background:#f8fafc; }
    .aj-num { width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center;
              font-weight:800; color:#fff; flex-shrink:0; font-size:1.1rem; background:linear-gradient(135deg,#10b981,#059669); }
    .aj-card.locked .aj-num { background:#cbd5e1; }
    .aj-title { font-weight:700; line-height:1.2; font-size:1rem; }
    .aj-desc { font-size:.78rem; color:#64748b; line-height:1.25; }
    .aj-badge-fixed { font-size:.68rem; font-weight:700; color:#6b7280; background:#eef2f7; border:1px solid #e5e7eb;
                      padding:.25rem .55rem; border-radius:999px; white-space:nowrap; }
    .aj-slot { border:1px solid #eef2f7; border-radius:.75rem; padding:.6rem .7rem; background:#fbfdff; }
    .aj-slot-title { font-size:.72rem; font-weight:700; letter-spacing:.03em; text-transform:uppercase; margin-bottom:.4rem;
                     display:flex; align-items:center; gap:.35rem; }
    .aj-slot-buka .aj-slot-title { color:#059669; }
    .aj-slot-tutup .aj-slot-title { color:#dc2626; }
    .aj-card .form-label.sm { font-size:.72rem; font-weight:600; color:#64748b; margin-bottom:.15rem; }
    .aj-preview { font-size:.8rem; color:#475569; background:#f0fdf4; border:1px dashed #86efac; border-radius:.6rem; padding:.5rem .75rem; }
    .aj-switch { display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #eef2f7;
                 border-radius:.75rem; padding:.5rem .75rem; margin-bottom:.85rem; }
    .aj-switch-label { font-size:.85rem; font-weight:600; }
    .gel-pill .badge { font-weight:600; }
    [x-cloak] { display:none !important; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="aj-head p-3 p-md-4 mb-4 shadow-sm">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-calendar-week-fill me-2"></i>Alur &amp; Jadwal SPMB</h4>
                <p class="mb-0 opacity-90 small">
                    Atur waktu buka/tutup tiap tahap &amp; catatan untuk pendaftar. Per tahun ajaran &amp; per gelombang.
                </p>
            </div>
            <div class="text-md-end">
                <div class="small opacity-75">Sedang diatur</div>
                <div class="fs-5 fw-bold">
                    <i class="bi bi-calendar3-range me-1"></i>{{ $tahunAjaran->nama ?? '—' }}
                    @if($gelombang)<span class="opacity-75">›</span> {{ $gelombang->nama }}@endif
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

    {{-- Pemilih Tahun & Gelombang (dalam card) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="text-muted small fw-semibold" style="min-width:110px;"><i class="bi bi-calendar3 me-1"></i>Tahun Ajaran:</span>
                @foreach($daftarTahun as $ta)
                    <form action="{{ route('admin.periode-aktif.ganti') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="tahun_ajaran_id" value="{{ $ta->id }}">
                        <button type="submit" class="btn btn-sm {{ (int)$tahunAjaranId === (int)$ta->id ? 'btn-success' : 'btn-outline-secondary' }}">
                            {{ $ta->nama }}
                            @if($ta->default)<i class="bi bi-star-fill ms-1" title="Tahun aktif"></i>@endif
                        </button>
                    </form>
                @endforeach
                <a href="{{ route('admin.pengaturan.spmb.periode') }}" class="btn btn-sm btn-outline-primary ms-auto">
                    <i class="bi bi-plus-circle me-1"></i>Kelola Tahun / Gelombang
                </a>
            </div>

            @if($tahunAjaranId)
            <hr class="my-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-semibold" style="min-width:110px;"><i class="bi bi-layers-half me-1"></i>Gelombang:</span>
                @forelse($daftarGelombang as $g)
                    @php $st = $g->statusPendaftaran(); @endphp
                    <a href="{{ route('admin.alur-jadwal.index', ['gelombang' => $g->id]) }}"
                       class="btn btn-sm gel-pill {{ (int)$gelombangId === (int)$g->id ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $g->nama }}
                        <span class="badge bg-{{ $st['class'] }} ms-1">{{ $st['label'] }}</span>
                    </a>
                @empty
                    <span class="text-muted small">Belum ada gelombang.
                        <a href="{{ route('admin.pengaturan.spmb.periode') }}">Tambah gelombang</a>.
                    </span>
                @endforelse
            </div>
            @endif
        </div>
    </div>

    @if(!$tahunAjaranId)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>
            Belum ada Tahun Ajaran. Silakan buat dulu di <a href="{{ route('admin.pengaturan.spmb.periode') }}" class="alert-link">Kelola Periode</a>.
        </div>
    @else
    <form action="{{ route('admin.pengaturan.alur-jadwal.simpan') }}" method="POST" x-data="alurJadwal()">
        @csrf
        <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
        <input type="hidden" name="gelombang_pendaftaran_id" value="{{ $gelombangId }}">

        <div class="row g-3">
            @foreach($jadwal as $tahap => $j)
            <div class="col-12 col-xl-6">
                <div class="card aj-card shadow-sm h-100" :class="{ 'locked': !t{{ $tahap }}.dibuka && {{ $j['berjadwal'] ? 'true' : 'false' }} }">
                    <div class="aj-card-head">
                        <div class="aj-num"><i class="bi bi-{{ $j['icon'] }}"></i></div>
                        <div class="flex-grow-1">
                            <div class="aj-title">Tahap {{ $tahap }}. {{ $j['judul'] }}</div>
                            <div class="aj-desc">{{ $j['deskripsi'] }}</div>
                        </div>
                        @if(!$j['berjadwal'])
                            <span class="aj-badge-fixed"><i class="bi bi-lightning-charge-fill me-1"></i>Selalu terbuka</span>
                        @endif
                    </div>

                    <div class="card-body">
                        @if(!$j['berjadwal'])
                            <p class="text-muted small mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Titik masuk pendaftar (daftar = isi biodata). Tidak memerlukan jadwal.
                            </p>
                        @else
                            {{-- Toggle buka/tutup --}}
                            <div class="aj-switch form-check form-switch">
                                <label class="form-check-label aj-switch-label" for="dibuka{{ $tahap }}">
                                    <span x-show="t{{ $tahap }}.dibuka" class="text-success"><i class="bi bi-unlock-fill me-1"></i>Tahap Dibuka</span>
                                    <span x-show="!t{{ $tahap }}.dibuka" class="text-danger" style="display:none"><i class="bi bi-lock-fill me-1"></i>Tahap Ditutup</span>
                                </label>
                                <input type="hidden" name="tahap[{{ $tahap }}][dibuka]" :value="t{{ $tahap }}.dibuka ? 1 : 0">
                                <input class="form-check-input ms-0" type="checkbox" role="switch"
                                       id="dibuka{{ $tahap }}" x-model="t{{ $tahap }}.dibuka" style="width:2.6em;height:1.3em;">
                            </div>

                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <div class="aj-slot aj-slot-buka h-100">
                                        <div class="aj-slot-title"><i class="bi bi-box-arrow-in-right"></i>Buka</div>
                                        <div class="row g-2">
                                            <div class="col-7">
                                                <label class="form-label sm">Tanggal</label>
                                                <input type="date" class="form-control form-control-sm"
                                                       name="tahap[{{ $tahap }}][tanggal_buka]" x-model="t{{ $tahap }}.tanggal_buka">
                                            </div>
                                            <div class="col-5">
                                                <label class="form-label sm">Jam</label>
                                                <input type="time" class="form-control form-control-sm"
                                                       name="tahap[{{ $tahap }}][waktu_mulai]" x-model="t{{ $tahap }}.waktu_mulai">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="aj-slot aj-slot-tutup h-100">
                                        <div class="aj-slot-title"><i class="bi bi-box-arrow-right"></i>Tutup</div>
                                        <div class="row g-2">
                                            <div class="col-7">
                                                <label class="form-label sm">Tanggal</label>
                                                <input type="date" class="form-control form-control-sm"
                                                       name="tahap[{{ $tahap }}][tanggal_tutup]" x-model="t{{ $tahap }}.tanggal_tutup">
                                            </div>
                                            <div class="col-5">
                                                <label class="form-label sm">Jam</label>
                                                <input type="time" class="form-control form-control-sm"
                                                       name="tahap[{{ $tahap }}][waktu_selesai]" x-model="t{{ $tahap }}.waktu_selesai">
                                            </div>
                                        </div>
                                    </div>
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
                                    <span class="text-muted fw-normal">(tampil di dashboard peserta)</span>
                                </label>
                                <textarea class="form-control form-control-sm" rows="2"
                                          name="tahap[{{ $tahap }}][keterangan]" x-model="t{{ $tahap }}.keterangan"
                                          placeholder="mis. Bawa berkas asli saat wawancara / batas pembayaran ..."></textarea>
                            </div>

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
