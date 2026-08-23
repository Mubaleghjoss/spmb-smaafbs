{{--
    Info: data identitas surat pernyataan SELALU dibaca dari Formulir Biodata
    (Tahap 2) — tidak disimpan sebagai salinan. Jadi memperbaiki formulir
    otomatis memperbaiki surat pernyataan beserta PDF-nya.

    Parameter: $formulir (App\Models\FormulirSpmb|null)
--}}
<div class="alert alert-info d-flex flex-wrap align-items-start gap-2 py-2">
    <i class="bi bi-link-45deg mt-1"></i>
    <div class="flex-grow-1" style="min-width:12rem;">
        <div class="fw-semibold" style="font-size:.9rem;">Mengikuti Formulir Biodata</div>
        <div style="font-size:.82rem;">
            Nama, tempat &amp; tanggal lahir, alamat, dan data orang tua/wali di bawah diambil langsung dari
            formulir Tahap 2, jadi tidak perlu ditulis ulang.
            @if(empty($formulir))
                <span class="text-danger fw-semibold">Formulir biodata belum terisi</span>, sehingga kolom di bawah masih kosong — silakan isi formulirnya lebih dulu.
            @else
                Bila ada yang salah, ubah di formulir; surat pernyataan dan PDF-nya otomatis ikut diperbarui.
            @endif
        </div>
    </div>
    <a href="{{ route('peserta.formulir.review') }}" class="btn btn-sm btn-outline-primary flex-shrink-0">
        <i class="bi bi-pencil-square me-1"></i>Ubah di Formulir
    </a>
</div>
