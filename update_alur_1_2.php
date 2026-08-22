<?php
/**
 * Update terarah konten Alur SPMB di DB: HANYA langkah 1 & 2 agar sesuai flow baru
 * (Daftar = langsung isi biodata + akun otomatis + masuk dashboard).
 * Tahap 3-7 (kustomisasi sekolah) DIPERTAHANKAN apa adanya.
 *
 * Idempotent & aman: kalau langkah 1/2 sudah versi baru, tidak berubah.
 * Jalankan: php update_alur_1_2.php   (di root aplikasi)
 */
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PengaturanService;

$svc = app(PengaturanService::class);
$alur = $svc->ambilAlurSpmb();

if (count($alur) < 2) {
    echo "Alur kurang dari 2 langkah, batal.\n";
    exit(1);
}

// Simpan snapshot untuk audit
$before = json_encode($alur, JSON_UNESCAPED_UNICODE);
@file_put_contents(__DIR__ . '/storage/app/alur_spmb_backup_' . date('Ymd_His') . '.json', $before);

// Langkah 1 — Daftar & Isi Biodata
$alur[0]['nomor'] = 1;
$alur[0]['judul'] = 'Daftar & Isi Biodata';
$alur[0]['icon'] = 'person-plus-fill';
$alur[0]['deskripsi'] = 'Cukup satu langkah: isi biodata calon siswa dan nomor HP. Akun otomatis dibuat (No HP menjadi username sekaligus password), lalu Anda langsung masuk ke dashboard peserta.';
$alur[0]['detail'] = [
    'Pilih periode/gelombang dan jenis pendaftaran (siswa baru/pindahan)',
    'Isi biodata: nama, jenis kelamin, tempat/tanggal lahir, asal sekolah, data orang tua',
    'Masukkan No HP/WA (siswa atau orang tua) sebagai akun login',
    'Langsung masuk dashboard — tanpa registrasi & login terpisah',
];

// Langkah 2 — Lengkapi Formulir & Berkas
$alur[1]['nomor'] = 2;
$alur[1]['judul'] = 'Lengkapi Formulir & Berkas';
$alur[1]['icon'] = 'file-earmark-text-fill';
$alur[1]['deskripsi'] = 'Dari dashboard, lengkapi sisa data formulir pendaftaran dan unggah dokumen yang diperlukan.';
$alur[1]['detail'] = [
    'Lengkapi data diri, orang tua/wali, dan NISN',
    'Upload pas foto terbaru',
    'Periksa kembali seluruh data sebelum lanjut',
];

$svc->simpanAlurSpmb($alur);

$after = $svc->ambilAlurSpmb();
echo "=== SESUDAH UPDATE ===\n";
foreach ($after as $i => $s) {
    echo ($i + 1) . ") " . $s['judul'] . " [" . ($s['icon'] ?? '-') . "]\n";
}
echo "\nLangkah 3-7 dipertahankan. Backup tersimpan di storage/app/.\n";
