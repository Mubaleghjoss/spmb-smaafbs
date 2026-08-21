<?php

/**
 * Seeder konten beranda SPMB.
 *
 * Mengisi konten awal yang profesional untuk halaman beranda ke tabel pengaturan.
 * Semua nilai tetap bisa diedit dari Admin > Pengaturan > Konten Beranda.
 *
 * Pemakaian:
 *   php scripts/seed-beranda.php          # isi hanya jika key masih kosong (aman)
 *   php scripts/seed-beranda.php --force  # timpa nilai yang sudah ada
 *
 * Catatan: angka statistik & testimoni adalah contoh siap-pakai — silakan
 * sesuaikan dengan data resmi sekolah dari panel admin.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Pengaturan;
use App\Services\PengaturanService;

$force = in_array('--force', $argv, true);
$svc = app(PengaturanService::class);

/** Ambil nama institusi untuk teks yang kontekstual. */
$branding = $svc->ambilBranding();
$institusi = $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School';

$konten = [
    // --- HERO ---
    'beranda_hero_badge' => 'Penerimaan Murid Baru ' . ($branding['tahun_ajaran'] ?? date('Y')),
    'beranda_hero_judul' => 'Wujudkan Generasi Qurani yang Berakhlak & Berprestasi',
    'beranda_hero_subjudul' => 'Bergabunglah bersama ' . $institusi . ' — pendidikan berbasis Al-Quran, pembinaan karakter di lingkungan asrama, dan keunggulan akademik untuk masa depan yang gemilang.',
    'beranda_hero_tombol1_teks' => 'Daftar Sekarang',
    'beranda_hero_tombol2_teks' => 'Lihat Alur SPMB',

    // --- TEKS BAGIAN ---
    'beranda_keunggulan_judul' => 'Kenapa Memilih Kami?',
    'beranda_keunggulan_subjudul' => 'Sistem pendaftaran modern yang memudahkan Anda di setiap langkah seleksi.',
    'beranda_program_judul' => 'Program Unggulan',
    'beranda_program_subjudul' => 'Beragam program untuk mengembangkan potensi terbaik setiap santri.',
    'beranda_faq_judul' => 'Pertanyaan yang Sering Diajukan',
    'beranda_testimoni_judul' => 'Apa Kata Mereka',

    // --- FLAG ---
    'beranda_statistik_aktif' => true,
    'beranda_tahapan_aktif' => true,

    // --- STATISTIK (angka real dari data sekolah; "auto" = jumlah pendaftar SPMB) ---
    'beranda_statistik' => [
        ['icon' => 'people-fill', 'angka' => '162', 'suffix' => '', 'label' => 'Siswa Aktif'],
        ['icon' => 'mortarboard-fill', 'angka' => '80', 'suffix' => '+', 'label' => 'Alumni'],
        ['icon' => 'person-badge-fill', 'angka' => '27', 'suffix' => '', 'label' => 'Guru & Tenaga Pendidik'],
        ['icon' => 'door-open-fill', 'angka' => '7', 'suffix' => '', 'label' => 'Rombongan Belajar'],
    ],

    // --- KEUNGGULAN ---
    'beranda_keunggulan' => [
        ['icon' => 'calendar-check', 'judul' => 'Pendaftaran Online', 'deskripsi' => 'Daftar kapan saja dan di mana saja lewat sistem online yang mudah dan cepat.'],
        ['icon' => 'laptop', 'judul' => 'Tes Online (CBT)', 'deskripsi' => 'Ikuti seleksi secara online dengan sistem ujian berbasis komputer yang aman dan terpercaya.'],
        ['icon' => 'shield-check', 'judul' => 'Proses Transparan', 'deskripsi' => 'Pantau status pendaftaran dan hasil seleksi secara real-time melalui dashboard peserta.'],
    ],

    // --- PROGRAM UNGGULAN ---
    'beranda_program' => [
        ['icon' => 'book-half', 'judul' => 'Tahfizh Al-Quran', 'deskripsi' => 'Program menghafal Al-Quran dengan bimbingan ustadz berpengalaman.'],
        ['icon' => 'building', 'judul' => 'Boarding School', 'deskripsi' => 'Lingkungan asrama yang kondusif untuk pembinaan karakter 24 jam.'],
        ['icon' => 'translate', 'judul' => 'Bahasa Arab & Inggris', 'deskripsi' => 'Penguatan dua bahasa dalam keseharian untuk membuka wawasan global.'],
        ['icon' => 'trophy', 'judul' => 'Prestasi & Ekstrakurikuler', 'deskripsi' => 'Beragam kegiatan untuk mengasah bakat, minat, dan kepemimpinan santri.'],
    ],

    // --- FAQ ---
    'beranda_faq' => [
        ['tanya' => 'Kapan pendaftaran dibuka?', 'jawab' => 'Pendaftaran dibuka sesuai jadwal gelombang yang tertera di halaman Jadwal. Silakan cek halaman pendaftaran untuk gelombang yang sedang aktif.'],
        ['tanya' => 'Apa saja syarat pendaftaran?', 'jawab' => 'Calon peserta menyiapkan data diri, data orang tua/wali, asal sekolah/NISN, dan pas foto terbaru. Detail lengkap ada di halaman Syarat & Ketentuan.'],
        ['tanya' => 'Bagaimana cara mengikuti tes seleksi?', 'jawab' => 'Tes dilaksanakan secara online (CBT) menggunakan token yang diberikan panitia sesuai jadwal masing-masing peserta.'],
        ['tanya' => 'Berapa biaya pendaftaran?', 'jawab' => 'Rincian biaya formulir dan pembayaran ditampilkan pada tahap pendaftaran setelah Anda membuat akun. Silakan hubungi Tim SPMB untuk informasi lebih lanjut.'],
        ['tanya' => 'Bagaimana cara mengetahui hasil seleksi?', 'jawab' => 'Hasil seleksi dapat dilihat melalui dashboard peserta dan halaman Cek Status menggunakan nomor pendaftaran Anda.'],
    ],

    // --- TESTIMONI (contoh — ganti dengan testimoni asli dari panel admin) ---
    'beranda_testimoni' => [
        ['nama' => 'Wali Santri', 'peran' => 'Orang Tua Peserta Didik', 'isi' => 'Proses pendaftarannya sangat mudah dan transparan. Anak kami berkembang pesat dalam hafalan Al-Quran maupun akhlaknya.', 'foto' => ''],
        ['nama' => 'Alumni', 'peran' => 'Lulusan Terbaik', 'isi' => 'Pembinaan di sini membekali saya bukan hanya ilmu akademik, tetapi juga karakter, kemandirian, dan kecintaan pada Al-Quran.', 'foto' => ''],
    ],
];

$keys = array_keys($konten);
$sudahAda = Pengaturan::whereIn('kunci', $keys)->pluck('kunci')->all();

if (!empty($sudahAda) && !$force) {
    echo "Sebagian konten beranda sudah ada di database:\n  - " . implode("\n  - ", $sudahAda) . "\n";
    echo "Jalankan ulang dengan --force untuk menimpa, atau edit dari panel admin.\n";
    exit(0);
}

$svc->simpanKontenBeranda($konten);
$svc->hapusCache();

echo "OK: konten beranda berhasil di-seed" . ($force ? " (mode --force)" : "") . ".\n";
echo "Institusi: {$institusi}\n";
echo "Edit kapan saja di: Admin > Pengaturan > Konten Beranda\n";
