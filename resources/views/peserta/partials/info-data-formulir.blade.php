{{--
    Info: data di bawah terisi otomatis dari Formulir Biodata (Tahap 2),
    plus tautan langsung untuk mengubahnya di halaman formulir.

    Parameter: $formulir (App\Models\FormulirSpmb|null)
--}}
<div class="alert alert-info d-flex flex-wrap align-items-start gap-2 py-2">
    <i class="bi bi-magic mt-1"></i>
    <div class="flex-grow-1" style="min-width:12rem;">
        <div class="fw-semibold" style="font-size:.9rem;">Terisi otomatis dari Formulir Biodata</div>
        <div style="font-size:.82rem;">
            Nama, tempat &amp; tanggal lahir, alamat, dan data orang tua/wali diambil dari formulir yang Anda isi di Tahap 2.
            @if(empty($formulir))
                <span class="text-danger fw-semibold">Formulir biodata belum terisi</span>, jadi kolom di bawah masih kosong — silakan isi formulirnya lebih dulu.
            @else
                Bila ada yang salah, ubah di formulir agar seluruh dokumen ikut benar.
            @endif
        </div>
    </div>
    <a href="{{ route('peserta.formulir.review') }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
        <i class="bi bi-pencil-square me-1"></i>Ubah di Formulir
    </a>
</div>
