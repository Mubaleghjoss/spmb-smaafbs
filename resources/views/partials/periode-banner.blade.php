{{-- Banner konteks periode aktif untuk halaman-halaman admin.
     Menunjukkan data periode mana yang sedang ditampilkan, agar jelas
     "tahun lalu vs tahun ini". Otomatis tampil bila $periodeAktifLabel tersedia
     (di-share oleh ViewServiceProvider untuk layout admin). --}}
@isset($periodeAktifLabel)
<div class="alert d-flex align-items-center gap-2 py-2 px-3 mb-3
            {{ !empty($periodeAktifSemua) ? 'alert-info' : 'alert-success' }}"
     style="border-left-width:4px;">
    <i class="bi {{ !empty($periodeAktifSemua) ? 'bi-collection' : 'bi-calendar3-range' }} fs-5"></i>
    <div class="small">
        Menampilkan data periode:
        <strong>{{ $periodeAktifLabel }}</strong>
        @if(!empty($periodeAktifSemua))
            <span class="badge bg-info text-white ms-1">Agregat lintas tahun</span>
        @endif
    </div>
    <span class="ms-auto text-muted small d-none d-md-inline">
        Ganti lewat pemilih “Periode” di kanan atas.
    </span>
</div>
@endisset
