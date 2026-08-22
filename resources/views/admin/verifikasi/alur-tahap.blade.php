@extends('layouts.admin')

@section('title', 'Alur & Tahap SPMB')

@push('styles')
<style>
    .tahap-card{border:0;border-radius:1rem;overflow:hidden;transition:transform .2s,box-shadow .2s;}
    .tahap-card:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(0,0,0,.1);}
    .tahap-num{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;flex-shrink:0;font-size:1.15rem;}
    .tahap-badge-antre{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;font-size:.72rem;padding:.15rem .5rem;font-weight:700;}
    .tahap-badge-ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:8px;font-size:.72rem;padding:.15rem .5rem;font-weight:600;}
    .peran-chip{font-size:.72rem;padding:.12rem .5rem;border-radius:6px;font-weight:600;display:inline-flex;align-items:center;gap:.25rem;}
    .chip-peserta{background:#eff6ff;color:#1d4ed8;}
    .chip-admin{background:#f0fdf4;color:#15803d;}
    /* Tombol aksi berwarna solid & jelas (bukan outline polos) */
    .tahap-card .btn{font-size:.82rem;font-weight:600;border:0;color:#fff;box-shadow:0 3px 8px rgba(15,23,42,.16);}
    .tahap-card .btn:hover{transform:translateY(-1px);color:#fff;box-shadow:0 5px 14px rgba(15,23,42,.22);}
    .btn-verif{background:linear-gradient(135deg,#10b981,#059669);}
    .btn-atur{background:linear-gradient(135deg,#6366f1,#4f46e5);}
    .btn-extra{background:linear-gradient(135deg,#0ea5e9,#0284c7);}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-signpost-2 me-2 text-success"></i>Alur &amp; Tahap SPMB</h4>
            <p class="text-muted mb-0 small">Peta 7 tahap pendaftaran. Tiap kartu menjelaskan yang dikerjakan peserta, yang diverifikasi admin, dan tombol langsung ke halamannya.</p>
        </div>
        <div class="d-flex gap-2 align-items-center small flex-wrap">
            <span class="peran-chip chip-peserta"><i class="bi bi-person"></i>Peserta</span><span class="text-muted">= dikerjakan peserta</span>
            <span class="peran-chip chip-admin"><i class="bi bi-shield-check"></i>Admin</span><span class="text-muted">= diverifikasi admin</span>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div class="text-muted small">Total Peserta</div>
                <div class="h4 mb-0">{{ $statistik['total_peserta'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10"><div class="card-body py-3">
                <div class="text-muted small">Menunggu Verifikasi</div>
                @php $totalAntre = ($statistik['pembayaran_menunggu']??0)+($statistik['formulir_menunggu']??0)+($statistik['hasil_tes_menunggu']??0)+($statistik['pelunasan_menunggu']??0); @endphp
                <div class="h4 mb-0 text-warning">{{ $totalAntre }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-info bg-opacity-10"><div class="card-body py-3">
                <div class="text-muted small">Sedang Tes (Tahap 4)</div>
                <div class="h4 mb-0 text-info">{{ $statistik['peserta_per_tahap'][4] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10"><div class="card-body py-3">
                <div class="text-muted small">Diterima (Tahap 7)</div>
                <div class="h4 mb-0 text-success">{{ $statistik['peserta_per_tahap'][7] ?? 0 }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">

        {{-- Tahap 1 --}}
        <div class="col-12 col-xl-6">
            <div class="card tahap-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="tahap-num" style="background:#64748b">1</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1"><i class="bi bi-person-plus me-1"></i>Buat Akun</h6>
                                <span class="tahap-badge-ok">Otomatis</span>
                            </div>
                            <div class="mb-2 small"><span class="peran-chip chip-peserta"><i class="bi bi-person"></i>Peserta</span> mengisi biodata &amp; No HP saat mendaftar. Akun langsung aktif (auto-login).</div>
                            <div class="small text-muted">Tidak perlu verifikasi admin.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tahap 2 --}}
        <div class="col-12 col-xl-6">
            <div class="card tahap-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="tahap-num" style="background:#0ea5e9">2</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1"><i class="bi bi-file-earmark-text me-1"></i>Isi Formulir</h6>
                                @if(($statistik['formulir_menunggu']??0)>0)<span class="tahap-badge-antre">{{ $statistik['formulir_menunggu'] }} menunggu</span>@else<span class="tahap-badge-ok">Aman</span>@endif
                            </div>
                            <div class="mb-3 small"><span class="peran-chip chip-peserta"><i class="bi bi-person"></i>Peserta</span> melengkapi biodata &amp; unggah berkas. <span class="peran-chip chip-admin"><i class="bi bi-shield-check"></i>Admin</span> verifikasi formulir.</div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.verifikasi.formulir') }}" class="btn btn-verif"><i class="bi bi-clipboard-check me-1"></i>Verifikasi Formulir</a>
                                <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap2" class="btn btn-atur"><i class="bi bi-gear me-1"></i>Atur Jadwal/Teks</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tahap 3 --}}
        <div class="col-12 col-xl-6">
            <div class="card tahap-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="tahap-num" style="background:#f59e0b">3</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1"><i class="bi bi-credit-card me-1"></i>Pembayaran Formulir</h6>
                                @if(($statistik['pembayaran_menunggu']??0)>0)<span class="tahap-badge-antre">{{ $statistik['pembayaran_menunggu'] }} menunggu</span>@else<span class="tahap-badge-ok">Aman</span>@endif
                            </div>
                            <div class="mb-3 small"><span class="peran-chip chip-peserta"><i class="bi bi-person"></i>Peserta</span> upload bukti transfer. <span class="peran-chip chip-admin"><i class="bi bi-shield-check"></i>Admin</span> verifikasi pembayaran.</div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.verifikasi.pembayaran-formulir') }}" class="btn btn-verif"><i class="bi bi-clipboard-check me-1"></i>Verifikasi Pembayaran</a>
                                <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap3" class="btn btn-atur"><i class="bi bi-gear me-1"></i>Rekening &amp; Biaya</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tahap 4 --}}
        <div class="col-12 col-xl-6">
            <div class="card tahap-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="tahap-num" style="background:#eab308">4</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1"><i class="bi bi-laptop me-1"></i>Tes Online</h6>
                                @if(($statistik['hasil_tes_menunggu']??0)>0)<span class="tahap-badge-antre">{{ $statistik['hasil_tes_menunggu'] }} hasil menunggu</span>@else<span class="tahap-badge-ok">Aman</span>@endif
                            </div>
                            <div class="mb-3 small"><span class="peran-chip chip-peserta"><i class="bi bi-person"></i>Peserta</span> ikut tes CBT pakai token. <span class="peran-chip chip-admin"><i class="bi bi-shield-check"></i>Admin</span> siapkan token &amp; verifikasi hasil.</div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.verifikasi.hasil-tes') }}" class="btn btn-verif"><i class="bi bi-clipboard-check me-1"></i>Verifikasi Hasil Tes</a>
                                <a href="{{ route('admin.monitoring-ujian.index') }}" class="btn btn-extra"><i class="bi bi-display me-1"></i>Monitoring</a>
                                <a href="{{ route('admin.tes.index') }}" class="btn btn-atur"><i class="bi bi-gear me-1"></i>Atur Tes</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tahap 5 --}}
        <div class="col-12 col-xl-6">
            <div class="card tahap-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="tahap-num" style="background:#8b5cf6">5</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1"><i class="bi bi-people me-1"></i>Wawancara &amp; Berkas</h6>
                                @if(($wawancaraMenunggu??0)>0)<span class="tahap-badge-antre">{{ $wawancaraMenunggu }} menunggu</span>@else<span class="tahap-badge-ok">Aman</span>@endif
                            </div>
                            <div class="mb-3 small"><span class="peran-chip chip-peserta"><i class="bi bi-person"></i>Peserta</span> isi jawaban &amp; surat pernyataan. <span class="peran-chip chip-admin"><i class="bi bi-shield-check"></i>Admin</span> loloskan wawancara.</div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.verifikasi.wawancara') }}" class="btn btn-verif"><i class="bi bi-clipboard-check me-1"></i>Verifikasi Wawancara</a>
                                <a href="{{ route('admin.pengaturan.wawancara') }}" class="btn btn-atur"><i class="bi bi-gear me-1"></i>Atur Pertanyaan</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tahap 6 --}}
        <div class="col-12 col-xl-6">
            <div class="card tahap-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="tahap-num" style="background:#0891b2">6</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1"><i class="bi bi-wallet2 me-1"></i>Pembayaran Pertama</h6>
                                @if(($statistik['pelunasan_menunggu']??0)>0)<span class="tahap-badge-antre">{{ $statistik['pelunasan_menunggu'] }} menunggu</span>@else<span class="tahap-badge-ok">Aman</span>@endif
                            </div>
                            <div class="mb-3 small"><span class="peran-chip chip-peserta"><i class="bi bi-person"></i>Peserta</span> upload bukti pelunasan awal. <span class="peran-chip chip-admin"><i class="bi bi-shield-check"></i>Admin</span> verifikasi &amp; terbitkan kwitansi.</div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.verifikasi.pelunasan') }}" class="btn btn-verif"><i class="bi bi-clipboard-check me-1"></i>Verifikasi Pelunasan</a>
                                <a href="{{ route('admin.pengaturan.template-kwitansi') }}" class="btn btn-atur"><i class="bi bi-gear me-1"></i>Template Kwitansi</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tahap 7 --}}
        <div class="col-12 col-xl-6">
            <div class="card tahap-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div class="tahap-num" style="background:#16a34a">7</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1"><i class="bi bi-mortarboard me-1"></i>Kelulusan / Diterima</h6>
                                @if(($kelulusanMenunggu??0)>0)<span class="tahap-badge-antre">{{ $kelulusanMenunggu }} perlu keputusan</span>@else<span class="tahap-badge-ok">Aman</span>@endif
                            </div>
                            <div class="mb-3 small"><span class="peran-chip chip-admin"><i class="bi bi-shield-check"></i>Admin</span> tetapkan lulus/tidak &amp; unggah SK. <span class="peran-chip chip-peserta"><i class="bi bi-person"></i>Peserta</span> lihat pengumuman + unduh SK.</div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.verifikasi.kelulusan') }}" class="btn btn-verif"><i class="bi bi-award me-1"></i>Verifikasi Kelulusan</a>
                                <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap7" class="btn btn-atur"><i class="bi bi-gear me-1"></i>Atur Pengumuman &amp; SK</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <p class="text-muted small mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>Nomor &amp; label tahap di sini sama persis dengan yang dilihat peserta di dashboard. Badge merah = jumlah antrean yang butuh tindakan admin (data real).</p>
</div>
@endsection
