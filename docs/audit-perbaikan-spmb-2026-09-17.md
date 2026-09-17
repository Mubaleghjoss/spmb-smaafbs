# Audit Perbaikan SPMB — 17 September 2026

Status berikut dibedakan antara **selesai/live**, **sudah diperbaiki tetapi belum dapat diuji end-to-end**, dan **belum selesai**. Tidak ada data peserta atau konfigurasi produksi yang diubah saat audit ini.

## Ringkasan produksi

- Source aktif produksi: `7a1b77a` (`fix(spmb): verifikasi saldo tahap pembayaran`).
- Migration sampai `2026_09_17_100000_update_popup_persetujuan_ldii_asrama_copy` berstatus sudah dijalankan.
- Beranda, `/daftar`, dan login mengembalikan HTTP 200.
- URL admin mengarahkan pengunjung tanpa autentikasi ke login, sehingga tetap terlindungi.
- Tabel `log_aktivitas` ada di produksi dan memiliki 14 catatan saat audit.

## Daftar perbaikan

| Area | Status | Bukti / hasil audit | Tindak lanjut |
|---|---|---|---|
| Popup persetujuan sebelum form `/daftar` | SELESAI / LIVE | HTML produksi memuat tombol persetujuan, judul, dan teks LDII/asrama. Popup bersifat statis (`backdrop` tidak dapat ditutup dengan klik luar/ESC). | Uji visual/browser membutuhkan persetujuan remote-debugging Chrome dari pengguna. |
| Teks, judul, aktif/nonaktif, dan gambar popup dapat diatur admin | SELESAI / LIVE | Pengaturan di `/admin/pengaturan/spmb`, panel **Popup Persetujuan /daftar**. Field: `popup_persetujuan_aktif`, judul, pernyataan, dan gambar. Upload PNG/JPG/WEBP maksimal 3 MB. | Tidak ada. |
| Hasil MBTI tanpa data tidak menghasilkan 500 | SELESAI / LIVE | Source produksi memiliki fallback `Hasil MBTI belum tersedia`; commit live memuat view guard dan test regresi. | Uji akun/sesi asli tidak dilakukan karena data sesi historis yang dilaporkan tidak lagi ada dan endpoint peserta terlindungi. |
| Pembayaran Tahap 6: saldo berdasarkan transaksi terverifikasi | SELESAI / LIVE | `TahapEnamPembayaranService` menjumlahkan status terverifikasi saja. Uji regresi menetapkan tagihan Rp1.950.000, terverifikasi Rp500.000, sisa Rp1.450.000. | Uji PHPUnit lokal diblokir secret `SPMB_TEST_DB_PASSWORD`; butuh test DB yang memang disediakan. |
| Pembayaran cicilan dan pelunasan | SELESAI / LIVE | Controller membatasi nominal ke sisa tagihan; form menyediakan pilihan lunas/cicilan. | End-to-end dengan akun uji perlu dilakukan di lingkungan staging/test, bukan pada data produksi. |
| Upload bantuan admin pembayaran formulir masuk antrean | SELESAI / LIVE | Source produksi memuat `StatusPembayaran::MENUNGGU` pada controller upload bantuan. UI tidak lagi menjanjikan verifikasi otomatis. | Uji aksi admin perlu akun admin/operator dan bukti uji non-produksi. |
| Tahap 6 selesai hanya setelah tagihan lunas terverifikasi | SELESAI / LIVE | `PembayaranService` hanya menyelesaikan Tahap 6 bila ringkasan menyatakan lunas. | Perlu test DB untuk menjalankan unit test otomatis. |
| Verifikasi Tahap 6 tidak otomatis menyelesaikan Tahap 7 | SELESAI / LIVE | Tidak ada pemanggilan `selesaikanTahapan(..., 7)` dalam `VerifikasiSpmbService` produksi. | End-to-end dengan data uji tetap diperlukan. |
| Tahap 7 dapat dibuka untuk menunggu pengumuman/SK | SELESAI / LIVE | Route konfirmasi peserta tidak lagi dipagari middleware Tahap 7; dashboard mengarahkan status menunggu ke informasi pengumuman. | Perlu uji akun peserta di Tahap 6 selesai untuk memeriksa copy/UX aktual. |
| Riwayat tahap teknis (`log_tahapan_spmb`) | SELESAI | `SpmbService` dan `PesertaService` menyimpan perubahan tahap, actor, status lama/baru, dan pesan. | Tidak ada. |
| Log Aktivitas umum untuk pemindahan tahap dari halaman Peserta | SIAP DEPLOY | Jalur `admin.peserta.update-tahap` dan `admin.peserta.bulk-update-tahap` sebelumnya hanya membuat `LogTahapanSpmb`. Patch menambah log umum `tahapan.pindahkan_manual` per peserta dan satu ringkasan `tahapan.pindahkan_massal` untuk aksi bulk, tanpa menghapus riwayat teknis. | Test regresi tersedia tetapi tidak dapat menjalankan assertion tanpa `SPMB_TEST_DB_PASSWORD`; lint, Pint, dan `git diff --check` lulus. |
| Peran `tim_spmb` | SIAP DEPLOY | Schema produksi masih `enum('admin','operator')`, sedangkan aplikasi menerima `tim_spmb`. Migration baru forward-only menambah `tim_spmb` pada MySQL/MariaDB; SQLite tidak diberi DDL karena enum tersimpan sebagai teks. Rollback menolak berjalan bila akun `tim_spmb` telah ada. | Test regresi tersedia; eksekusinya dipagari secret test DB. Deploy menjalankan migration dengan backup dan schema check sesudahnya. |
| Kontak WA data formulir/wawancara | SUDAH ADA | Template surat pernyataan sudah membangun tautan `wa.me` ke nomor peserta bila tersedia; setting kontak tim tersedia di pengaturan SPMB. | Klik-to-chat khusus untuk semua alur belum diaudit sebagai requirement terpisah. |
| Teks status popup sesuai persetujuan terbaru | SELESAI / LIVE | Produksi menggunakan teks LDII/asrama dari setting aktif; teks tetap bisa diedit admin. | Tidak ada. |

## Hambatan verifikasi yang tercatat

1. `php artisan test tests/Unit/TahapEnamPembayaranServiceTest.php` tidak menjalankan assertion karena test harness menolak tanpa `SPMB_TEST_DB_PASSWORD`. Ini perlindungan secret, bukan kegagalan bisnis.
2. Verifikasi visual browser Chrome belum dapat dijalankan karena instance Chrome meminta persetujuan remote debugging. HTTP dan HTML produksi telah diperiksa tanpa aksi tulis.
3. Aksi admin/peserta yang membutuhkan autentikasi tidak dicoba dengan akun produksi untuk menghindari perubahan data peserta nyata.

## Catatan keamanan

- Tidak ada data peserta produksi dibuat, diubah, atau dihapus pada audit ini.
- Tidak ada kredensial dicatat di dokumen ini.
- File lokal `_fixfav2.sh` tetap tidak disentuh.
