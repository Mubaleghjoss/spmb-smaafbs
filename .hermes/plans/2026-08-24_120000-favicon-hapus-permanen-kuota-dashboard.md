# Rancangan: Favicon PDF, Fix Hapus Permanen, Kuota Publik & Dashboard Kuota

> **Untuk Hermes:** Fase A sudah selesai/terdiagnosis. Fase B–D belum dikerjakan. Fase C mengubah ARTI data (status kuota) — wajib konfirmasi user sebelum dijalankan di produksi.

**Tujuan:** (1) ikon tab tidak berubah saat membuka SK PDF, (2) Hapus Permanen tidak lagi gagal untuk peserta yang sudah lulus, (3) halaman /daftar menampilkan kuota + rincian L/P, (4) status "masuk kuota" ditentukan setelah formulir diisi **dan** pembayaran formulir (Tahap 3) terverifikasi, dengan penjelasan di halaman alur, (5) dashboard admin menampilkan kuota penerimaan periode aktif beserta rincian L/P, asal SMP, kelompok, desa, daerah.

---

## Hasil Audit Produksi (fakta, bukan dugaan)

| Temuan | Bukti |
|---|---|
| **Bug hapus permanen** | `ringkasan()` memakai kolom `sesi_id`, kolom sebenarnya `sesi_tes_id`. Error produksi: `SQLSTATE[42S22] Unknown column 'sesi_id' in 'WHERE'`. Muncul hanya pada peserta yang PUNYA sesi tes (peserta lulus selalu punya) — karena itu terlihat seperti "khusus peserta lulus". |
| **Penyebab favicon berubah** | `public/favicon.ico` berukuran **0 byte**. Saat membuka PDF/gambar langsung tidak ada tag `<link rel="icon">` (bukan halaman HTML), sehingga browser jatuh ke `/favicon.ico` yang kosong → ikon tab jadi ikon bawaan browser. Favicon branding ada di DB: `branding/T8YBWaGBZVqJflchOGW7UklYM3KTxKPSziao5utq.png`. |
| **Docroot sebenarnya** | `~/public_html/web/www.seleksi` (BUKAN `~/spmb-app/public`). Terbukti: marker di `~/spmb-app/public/_marker.txt` → HTTP 404. `favicon.ico` di docroot itu juga 0 byte. |
| **Kuota belum tampil di /daftar** | `$periodePayload` SUDAH memuat `kuota` lengkap (`kuota_label`, `sisa_label`, `laki_laki`, `perempuan`, `penuh`), tetapi view `public/daftar.blade.php` tidak pernah merendernya — tidak ada satu pun kata "kuota" di view. Data ada, tampilan tidak ada. |
| **Logika kuota saat ini** | `KuotaPendaftaranService::rekalkulasiTahun()` mengurutkan SEMUA peserta periode berdasarkan `urutan_kuota`/`created_at`, lalu mengisi kuota total & per gender. **Tidak melihat formulir maupun pembayaran.** Jadi begitu akun dibuat, peserta sudah menempati kuota. |
| **Data kuota produksi** | 2026-2027: kuota 46 (L 26 / P 20), total 51, dalam kuota 46, waiting 5, PENUH. 2027-2028 (default): kuota 70 (L 35 / P 35), total 1, dalam 1. |
| **Dampak perubahan aturan** | 2026-2027: punya formulir 50, **bayar formulir terverifikasi hanya 6**, tahap 3 selesai 50. Artinya bila aturan diubah ke "harus bayar terverifikasi", peserta dalam kuota turun drastis dari 46 → 6. **Ini keputusan besar, wajib dikonfirmasi.** |

---

## FASE A — Favicon (sebagian sudah dikerjakan)

### A1. SELESAI: favicon di `~/spmb-app/public/favicon.ico`
Diisi dari favicon branding (508 KB). Tetapi **tidak berefek** karena bukan docroot.

### A2. BELUM: favicon di docroot sebenarnya
```bash
D=/home/sman5479/public_html/web/www.seleksi
SRC=/home/sman5479/spmb-app/storage/app/public/branding/T8YBWaGBZVqJflchOGW7UklYM3KTxKPSziao5utq.png
cp -f "$D/favicon.ico" "$D/favicon.ico.bak-0byte"
cp -f "$SRC" "$D/favicon.ico"
cp -f "$SRC" "$D/favicon.png"
chmod 644 "$D/favicon.ico" "$D/favicon.png"
rm -f /home/sman5479/spmb-app/public/_marker.txt
```
**Verifikasi:** `curl -s -o /dev/null -w "%{http_code} %{size_download}" https://seleksi.smaafbs.sch.id/favicon.ico` → harus `200` dengan size > 0.

**Catatan:** PNG 508 KB terlalu besar untuk favicon. Idealnya dibuat versi 32×32 (`.ico`/`.png` < 20 KB) memakai GD (sudah tersedia, dipakai kompresi upload). Opsional, bukan penghalang.

### A3. Perbaikan agar tidak terulang saat deploy
- Tambah langkah salin favicon ke docroot pada prosedur deploy, atau
- Simpan favicon 32×32 di repo sebagai `public/favicon.ico` dan pastikan deploy menyalin `public/` ke docroot.

**File:** `public/favicon.ico` (repo), catatan di dokumen deploy.

---

## FASE B — Fix Hapus Permanen (kode sudah dipatch lokal, belum deploy)

### B1. SELESAI (lokal): kolom yang benar
`app/Services/PesertaPembersihService.php`
- `ringkasan()`: `whereIn('sesi_id', …)` → `whereIn('sesi_tes_id', …)`
- `hapusPermanen()`: `JawabanPeserta::whereIn('sesi_id', …)` → `sesi_tes_id`

Ini juga memperbaiki bug tersembunyi: penghapusan jawaban tes sebelumnya **selalu gagal** dengan error kolom, artinya hapus permanen tidak pernah benar-benar berhasil untuk peserta yang punya sesi tes.

### B2. SELESAI (lokal): pesan error jujur
- `PesertaController::pratinjauHapus()` dibungkus `try/catch` → kembalikan pesan sebab + `Log::error`, bukan gagal 500 tanpa keterangan.
- Modal: `fetch` membaca teks dulu lalu `JSON.parse` dengan penjaga, sehingga respons non-JSON (halaman error) tetap menampilkan HTTP status + judul halaman, bukan "Gagal memuat data peserta." generik.

### B3. BELUM: uji ulang + deploy
1. Jalankan ulang `_ujihapus.php` (12 skenario) — pastikan jumlah jawaban tes ikut terhitung & terhapus.
2. Tambah skenario baru: peserta dengan sesi tes + jawaban → `ringkasan()` menampilkan jumlah jawaban > 0, dan setelah hapus `JawabanPeserta` = 0.
3. `php -l`, compile blade, commit, deploy, lalu **uji langsung di produksi** dengan peserta uji (bukan peserta asli).

---

## FASE C — Logika Kuota Baru (PERLU KONFIRMASI USER)

### Aturan yang diminta
Peserta menempati kuota **hanya jika**: formulir biodata sudah diisi **DAN** pembayaran formulir (Tahap 3) sudah terverifikasi.

### C1. Status kuota bertingkat
Tambah status ketiga agar keadaan peserta jelas — jangan paksakan semua ke `waiting_list`:

| Status | Arti |
|---|---|
| `belum_lengkap` (BARU) | Belum isi formulir atau belum bayar/verifikasi → **tidak menempati kuota** |
| `dalam_kuota` | Syarat lengkap & masih ada kursi |
| `waiting_list` | Syarat lengkap tetapi kursi (total atau per gender) sudah penuh |

**File:** `app/Models/Peserta.php` (konstanta `STATUS_KUOTA_BELUM_LENGKAP`), migrasi bila kolom `status_kuota` bertipe enum (**cek dulu**; bila `varchar`, tidak perlu migrasi).

### C2. `rekalkulasiTahun()` menghormati syarat
**File:** `app/Services/KuotaPendaftaranService.php`

```php
$peserta = Peserta::query()
    ->with(['formulirSpmb:id,peserta_id,jenis_kelamin', 'tahapanSpmb:id,peserta_id,tahap_3_selesai'])
    ->withExists(['pembayaran as bayar_formulir_ok' => fn($q) => $q
        ->where('jenis', 'formulir')->where('status', 'terverifikasi')])
    ->where('tahun_ajaran_id', $tahun->id)
    // Urutan kuota: yang lebih dulu MEMENUHI SYARAT dapat kursi lebih dulu
    ->orderByRaw('CASE WHEN urutan_kuota IS NULL THEN 1 ELSE 0 END')
    ->orderBy('urutan_kuota')->orderBy('created_at')->orderBy('id')
    ->get();

$layak = fn($p) => $p->formulirSpmb !== null
    && ($p->bayar_formulir_ok || ($p->tahapanSpmb?->tahap_3_selesai ?? false));
```
Peserta tidak layak → `belum_lengkap`, **tidak** menambah `$dalamKuota` dan **tidak** menambah `$urutanGender`.

> Catatan penting: `tahap_3_selesai` disertakan sebagai alternatif karena di produksi 50 peserta punya `tahap_3_selesai = true` sementara hanya 6 punya pembayaran terverifikasi (sisanya kemungkinan diverifikasi manual/diloloskan admin). Tanpa ini, 44 peserta yang sudah berjalan sampai tahap akhir akan terlempar keluar kuota.

### C3. Pemicu rekalkulasi
Tambahkan `rekalkulasiPeserta()` setelah:
- Formulir disimpan/diverifikasi/ditolak — `VerifikasiSpmbController::verifikasiFormulir/tolakFormulir`, `Peserta\FormulirController::simpan`
- Pembayaran formulir diverifikasi/ditolak — `verifikasiPembayaranFormulir`, `tolakPembayaranFormulir`, `uploadBuktiFormulir`
- Tahap 3 diubah manual — `AlurPesertaController::ubahTahapan`

### C4. Ringkasan kuota menyertakan `belum_lengkap`
`ringkasanTahun()` tambah kunci `belum_lengkap` (total, L, P) agar tampil di publik & dashboard.

### C5. Penjelasan di halaman alur
**File:** `resources/views/public/alur.blade.php` (+ kartu Tahap 3 di dashboard peserta)

Teks (ringkas, tanpa menjanjikan lebih dari yang dibuktikan):
> **Cara masuk hitungan kuota:** Pendaftaran Anda baru menempati kuota setelah (1) Formulir Biodata terisi lengkap dan (2) Pembayaran Biaya Pendaftaran pada Tahap 3 diverifikasi Tim SPMB. Sebelum keduanya selesai, pendaftaran tercatat tetapi belum mengambil kursi. Bila kuota sudah penuh saat syarat Anda lengkap, pendaftaran masuk **Waiting List**.

### C6. Rekalkulasi sekali-jalan di produksi
Skrip `_rekal.php`: jalankan `rekalkulasiTahun()` untuk semua tahun ajaran, cetak perbandingan sebelum/sesudah per periode. **Tampilkan dulu hasil simulasinya ke user sebelum menyimpan.**

---

## FASE D — Dashboard Admin: Kuota Penerimaan Periode Ini

### D1. Service ringkasan dashboard
**File baru:** `app/Services/RingkasanKuotaDashboardService.php`

Metode `untukPeriode(?int $tahunAjaranId): array` mengembalikan:
- `kuota`: hasil `KuotaPendaftaranService::ringkasanTahun()` (total, dalam kuota, waiting, belum lengkap, sisa, penuh)
- `gender`: kuota vs terisi untuk L dan P + jumlah belum isi gender
- `rekap`: 4 kelompok — Asal Sekolah SMP, Kelompok, Desa, Daerah — masing-masing top 10 dengan kolom `jumlah`, `dalam_kuota`, `waiting_list`

Implementasi rekap: pakai ulang pola `PesertaService::rekapFormulir()` (sudah terbukti) dengan filter `tahun_ajaran_id` = periode aktif, ditambah kolom `laki_laki` dan `perempuan` per baris agar rincian gender terlihat per sekolah/desa.

### D2. Controller dashboard
**File:** `app/Http/Controllers/Admin/DashboardController.php`

Saat ini hanya mengirim 4 angka (`total_peserta`, `peserta_baru`, `total_tes`, `total_soal`) — perlu ditambah:
```php
$periode = app(PeriodeContextService::class);
$ringkasanKuota = app(RingkasanKuotaDashboardService::class)->untukPeriode($periode->tahunAjaranId());
```
Kirim `ringkasanKuota` + `periodeLabel` ke view.

### D3. Tampilan
**File:** `resources/views/admin/dashboard.blade.php`

Bagian baru **"Kuota Penerimaan — {periode}"**:
1. Empat kartu angka: Kuota, Dalam Kuota, Waiting List, Belum Lengkap (+ sisa kursi)
2. Kartu gender: L (kuota vs terisi + progress bar), P (idem), badge "belum isi gender"
3. Empat kartu rekap (Asal SMP / Kelompok / Desa / Daerah) — meniru gaya "Rekap Data Formulir" di halaman peserta, tiap baris menampilkan jumlah + dalam kuota + waiting, tautan ke daftar peserta dengan filter terkait
4. Responsif HP: `col-12 col-lg-6 col-xl-3`, nama panjang dipotong dengan `text-truncate`

Hormati switcher periode: kalau admin memilih "Semua Periode", tampilkan pesan agar memilih satu periode (kuota tidak bermakna lintas periode).

---

## FASE E — Kuota di Halaman /daftar

**File:** `resources/views/public/daftar.blade.php`

Data sudah tersedia di `$periodePayload[*]['kuota']`. Tambahkan:
1. Di kartu periode (Step 1), di bawah `ringkasanGelombangTahun(tahun)`:
   `Kuota <span x-text="tahun.kuota.kuota_label"></span> · Sisa <span x-text="tahun.kuota.sisa_label"></span>`
2. Panel rincian saat periode terpilih:
   - Laki-laki: `kuota_laki_laki_label` vs `laki_laki.dalam_kuota`
   - Perempuan: `kuota_perempuan_label` vs `perempuan.dalam_kuota`
   - Progress bar per gender
3. Peringatan bila `tahun.kuota.penuh`:
   > Kuota periode ini sudah penuh. Pendaftaran tetap diterima dan masuk **Waiting List**.
4. Setelah Fase C: tampilkan juga `belum_lengkap` sebagai "menunggu kelengkapan" agar calon peserta paham angkanya.

**Verifikasi:** buka `/daftar` sebagai anonim, pastikan angka cocok dengan hasil `ringkasanTahun()` di skrip audit.

---

## Berkas yang Akan Berubah

**Baru:**
```
app/Services/RingkasanKuotaDashboardService.php
```

**Diubah:**
```
app/Services/PesertaPembersihService.php        (SUDAH — kolom sesi_tes_id)
app/Http/Controllers/Admin/PesertaController.php (SUDAH — try/catch pratinjau)
resources/views/admin/peserta/partials/modal-hapus-permanen.blade.php (SUDAH — pesan error jujur)
app/Services/KuotaPendaftaranService.php        (syarat kuota + status belum_lengkap)
app/Models/Peserta.php                          (konstanta status baru)
app/Http/Controllers/Admin/DashboardController.php
app/Http/Controllers/Admin/VerifikasiSpmbController.php (pemicu rekalkulasi)
app/Http/Controllers/Admin/AlurPesertaController.php    (pemicu rekalkulasi)
app/Http/Controllers/Peserta/FormulirController.php     (pemicu rekalkulasi)
resources/views/admin/dashboard.blade.php
resources/views/public/daftar.blade.php
resources/views/public/alur.blade.php
public/favicon.ico
```

---

## Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| **Aturan kuota baru menendang 40+ peserta 2026-2027 keluar kuota** | Sertakan `tahap_3_selesai` sebagai syarat alternatif; jalankan simulasi & tunjukkan angka sebelum/sesudah ke user sebelum menyimpan |
| Status `belum_lengkap` belum dikenali laporan/filter lama | Cari semua pemakaian `STATUS_KUOTA_WAITING`/`DALAM` dan pastikan status baru ditangani (jangan sampai peserta hilang dari daftar) |
| Kolom `status_kuota` bertipe enum | Cek `Schema` dulu; bila enum, migrasi ubah ke varchar (aman, tidak menghapus data) |
| Favicon hilang lagi setelah deploy | Simpan di repo + tambahkan langkah salin ke docroot pada prosedur deploy |
| Favicon 508 KB memperlambat | Buat versi 32×32 via GD |
| Query rekap dashboard berat | Batasi top 10 per kelompok + filter periode (pola yang sama sudah dipakai di halaman peserta tanpa masalah) |

---

## Pertanyaan yang Harus Dijawab Sebelum Fase C

1. **Peserta 2026-2027 yang sudah berjalan** (50 punya formulir, 50 tahap 3 selesai, tapi hanya 6 punya pembayaran terverifikasi): apakah `tahap_3_selesai` boleh dianggap setara "sudah bayar & terverifikasi"? Kalau tidak, peserta dalam kuota akan turun 46 → 6.
2. **Aturan berlaku surut atau hanya untuk pendaftar baru?** (mempengaruhi apakah rekalkulasi dijalankan pada periode lama)
3. **Urutan kursi** dihitung dari tanggal daftar, atau dari tanggal syarat lengkap (pembayaran diverifikasi)? Rancangan saat ini memakai tanggal daftar.

---

## Urutan Eksekusi yang Disarankan

1. **Fase A2** — salin favicon ke docroot (1 perintah, langsung terlihat)
2. **Fase B3** — uji ulang + deploy fix hapus permanen (bug nyata, aman)
3. **Fase E** — kuota tampil di /daftar (hanya tampilan, data sudah ada)
4. **Fase D** — dashboard kuota + rekap (hanya baca, tanpa risiko data)
5. Jawab 3 pertanyaan di atas
6. **Fase C** — logika kuota baru: simulasi dulu, tunjukkan angkanya, baru terapkan
