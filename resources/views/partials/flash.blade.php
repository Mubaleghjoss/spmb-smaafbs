{{--
    Notifikasi melayang (flash message) — SATU tempat untuk seluruh aplikasi.

    Dipakai sekali di tiap layout. Menggantikan blok @if(session('sukses')) ...
    yang sebelumnya ditulis ulang di banyak view (menyebabkan notifikasi dobel).

    Sifat:
    - Melayang (fixed) di kanan atas, tidak perlu scroll ke atas halaman.
    - Bisa ditutup manual (tombol x).
    - Hilang otomatis setelah 10 detik.
    - Menumpuk rapi bila ada lebih dari satu pesan.
--}}
@php
    $flashItems = [];

    foreach ([
        ['kunci' => ['success', 'sukses', 'status'], 'tipe' => 'success', 'ikon' => 'check-circle-fill'],
        ['kunci' => ['error'],                        'tipe' => 'danger',  'ikon' => 'exclamation-octagon-fill'],
        ['kunci' => ['warning'],                      'tipe' => 'warning', 'ikon' => 'exclamation-triangle-fill'],
        ['kunci' => ['info'],                         'tipe' => 'info',    'ikon' => 'info-circle-fill'],
    ] as $grup) {
        foreach ($grup['kunci'] as $kunci) {
            $pesan = session($kunci);
            if (is_string($pesan) && trim($pesan) !== '') {
                $flashItems[] = [
                    'tipe' => $grup['tipe'],
                    'ikon' => $grup['ikon'],
                    'pesan' => $pesan,
                ];
                break; // satu pesan per kelompok, hindari dobel success/sukses
            }
        }
    }
@endphp

@if(!empty($flashItems))
<div class="hf-flash-wrap" id="hfFlashWrap" aria-live="polite" aria-atomic="true">
    @foreach($flashItems as $item)
        <div class="hf-flash alert alert-{{ $item['tipe'] }} shadow-sm" role="alert">
            <i class="bi bi-{{ $item['ikon'] }} hf-flash-icon"></i>
            <div class="hf-flash-msg">{!! nl2br(e($item['pesan'])) !!}</div>
            <button type="button" class="btn-close hf-flash-close" aria-label="Tutup"></button>
            <span class="hf-flash-bar"></span>
        </div>
    @endforeach
</div>

<style>
    .hf-flash-wrap {
        position: fixed;
        top: 1rem;
        right: 1rem;
        left: auto;
        z-index: 1090;
        width: min(24rem, calc(100vw - 2rem));
        display: flex;
        flex-direction: column;
        gap: .5rem;
        pointer-events: none;
    }
    .hf-flash {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        margin: 0;
        padding: .7rem 2.1rem .7rem .8rem;
        border-radius: .6rem;
        overflow: hidden;
        pointer-events: auto;
        opacity: 0;
        transform: translateY(-.5rem);
        transition: opacity .25s ease, transform .25s ease;
        word-break: break-word;
        overflow-wrap: anywhere;
        /* Latar SOLID (bukan transparan) agar teks tidak menyatu dengan isi halaman */
        border-width: 1px;
        border-style: solid;
        box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .18), 0 .125rem .35rem rgba(0, 0, 0, .12);
        backdrop-filter: none;
    }
    /* Warna solid per tipe — menimpa alert-* Bootstrap yang memakai warna lembut/tembus */
    .hf-flash.alert-success { background-color: #eaf7ef !important; border-color: #198754 !important; color: #0f5132 !important; }
    .hf-flash.alert-danger  { background-color: #fdecec !important; border-color: #dc3545 !important; color: #842029 !important; }
    .hf-flash.alert-warning { background-color: #fff6e5 !important; border-color: #ffc107 !important; color: #664d03 !important; }
    .hf-flash.alert-info    { background-color: #e9f4fb !important; border-color: #0dcaf0 !important; color: #055160 !important; }
    .hf-flash.hf-show { opacity: 1; transform: translateY(0); }
    .hf-flash.hf-hide { opacity: 0; transform: translateY(-.5rem); }
    .hf-flash-icon { flex-shrink: 0; margin-top: .1rem; }
    .hf-flash-msg { flex: 1 1 auto; font-size: .875rem; line-height: 1.4; }
    .hf-flash-close {
        position: absolute;
        top: .5rem;
        right: .5rem;
        padding: .35rem;
        font-size: .65rem;
    }
    .hf-flash-bar {
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 100%;
        background: currentColor;
        opacity: .35;
        transform-origin: left center;
        animation: hfFlashBar 10s linear forwards;
    }
    @keyframes hfFlashBar { from { transform: scaleX(1); } to { transform: scaleX(0); } }

    @media (max-width: 575.98px) {
        .hf-flash-wrap { top: .5rem; right: .5rem; left: .5rem; width: auto; }
    }
    @media print { .hf-flash-wrap { display: none !important; } }
</style>

<script>
(function () {
    var wrap = document.getElementById('hfFlashWrap');
    if (!wrap) return;

    var items = wrap.querySelectorAll('.hf-flash');

    function tutup(el) {
        if (!el || el.dataset.hfClosing === '1') return;
        el.dataset.hfClosing = '1';
        el.classList.remove('hf-show');
        el.classList.add('hf-hide');
        setTimeout(function () {
            el.remove();
            if (!wrap.querySelector('.hf-flash')) wrap.remove();
        }, 300);
    }

    items.forEach(function (el) {
        // animasi masuk
        requestAnimationFrame(function () { el.classList.add('hf-show'); });

        var btn = el.querySelector('.hf-flash-close');
        if (btn) btn.addEventListener('click', function () { tutup(el); });

        // hilang otomatis setelah 10 detik
        var timer = setTimeout(function () { tutup(el); }, 10000);

        // tahan hitungan saat kursor di atas notifikasi
        el.addEventListener('mouseenter', function () {
            clearTimeout(timer);
            var bar = el.querySelector('.hf-flash-bar');
            if (bar) bar.style.animationPlayState = 'paused';
        });
        el.addEventListener('mouseleave', function () {
            timer = setTimeout(function () { tutup(el); }, 4000);
            var bar = el.querySelector('.hf-flash-bar');
            if (bar) bar.style.animationPlayState = 'running';
        });
    });
})();
</script>
@endif
