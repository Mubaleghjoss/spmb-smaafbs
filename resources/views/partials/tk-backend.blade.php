{{-- 
    Partial: gaya frontend RINGAN untuk halaman backend (dashboard peserta, admin, tim, login).
    Hanya polesan: font premium + tombol/kartu/heading halus bertema HIJAU.
    TIDAK memuat hero besar / animasi reveal / jarak lebar (itu khusus halaman publik).
    Aman disisipkan di dalam <head> layout mana pun.
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
<style>
    :root {
        --tk-primary: {{ $branding['warna_primer'] ?? '#1a5f2a' }};
        --tk-secondary: {{ $branding['warna_sekunder'] ?? '#2e8b57' }};
        --tk-ink: #14251c;
        --tk-muted: #5b6b63;
        --tk-green-soft: rgba(46,139,87,.10);
        --tk-green-ring: rgba(46,139,87,.18);
        --tk-ease: cubic-bezier(0.22, 1, 0.36, 1);
    }

    /* Tipografi ringan — teks tetap terbaca, judul lebih berkarakter */
    body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, "Segoe UI", sans-serif; }
    h1, h2, h3, h4, .tk-serif {
        font-family: 'Fraunces', Georgia, serif;
        letter-spacing: -0.01em;
    }
    h5, h6, .fw-bold, .fw-semibold { letter-spacing: -0.005em; }

    /* Tombol: sudut pil lembut + hijau brand pada success */
    .btn { border-radius: .7rem; font-weight: 600; }
    .btn-lg { border-radius: .85rem; }
    .btn-sm { border-radius: .55rem; }
    .btn-success {
        background: linear-gradient(135deg, var(--tk-primary), var(--tk-secondary));
        border: 0;
        box-shadow: 0 8px 18px -10px rgba(16,36,26,.55);
    }
    .btn-success:hover, .btn-success:focus {
        filter: brightness(1.05);
        background: linear-gradient(135deg, var(--tk-primary), var(--tk-secondary));
    }
    .btn-outline-success { border-color: var(--tk-secondary); color: var(--tk-primary); }
    .btn-outline-success:hover { background: linear-gradient(135deg, var(--tk-primary), var(--tk-secondary)); border-color: transparent; }

    /* Kartu: sudut lebih halus + bayangan lembut (tanpa efek terangkat berat) */
    .card { border-radius: 1rem; }
    .card.border-0 { box-shadow: 0 14px 34px -26px rgba(16,36,26,.30); }
    .card.shadow-sm { box-shadow: 0 12px 30px -24px rgba(16,36,26,.30) !important; }

    /* Badge & label halus */
    .badge { font-weight: 600; letter-spacing: .01em; }

    /* Eyebrow kecil opsional untuk judul halaman backend */
    .tk-eyebrow-b {
        display: inline-flex; align-items: center; gap: .45rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: .68rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase;
        padding: .3rem .7rem; border-radius: 999px;
        background: var(--tk-green-soft); border: 1px solid var(--tk-green-ring); color: var(--tk-primary);
    }
    .tk-eyebrow-b .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--tk-secondary); }

    /* Ikon tile lembut (untuk header kartu bila dipakai) */
    .tk-ico-tile-b {
        width: 46px; height: 46px; border-radius: .8rem;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;
        color: var(--tk-secondary);
        background: radial-gradient(120% 120% at 30% 20%, var(--tk-green-ring), var(--tk-green-soft));
    }

    /* Accordion & progress kecil disamakan nuansanya */
    .accordion-button:not(.collapsed) { background: var(--tk-green-soft); color: var(--tk-ink); box-shadow: none; }
    .accordion-button:focus { box-shadow: none; }
    .progress-bar.bg-success { background: linear-gradient(135deg, var(--tk-primary), var(--tk-secondary)) !important; }
</style>
