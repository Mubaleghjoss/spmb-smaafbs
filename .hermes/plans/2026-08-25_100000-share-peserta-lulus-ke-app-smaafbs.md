# Rancangan: Share Data Peserta Lulus ke app.smaafbs.sch.id

> **Untuk Hermes:** rancangan, belum dieksekusi. Fase 1–2 aman (kolom baru + UI). Fase 3 menyentuh sistem lain (app.smaafbs.sch.id) → butuh koordinasi & konfirmasi. Jangan kirim data ke sistem luar tanpa persetujuan eksplisit user.

**Permintaan user:** setelah peserta lulus, datanya bisa di-share ke `app.smaafbs.sch.id` dengan menentukan masuk ke kelas mana, atau ditempatkan di luar kelas.

---

## Audit: Yang SUDAH ADA (dan ini kabar baik)

Integrasi ini **sebagian besar sudah dibangun**. Temuan verifikasi:

| Komponen | Status | Bukti |
|---|---|---|
| **Endpoint API** | ADA | `GET /api/v1/integrations/akses/graduated-students` di `routes/api.php` |
| **Controller** | ADA | `app/Http/Controllers/Api/GraduatedStudentController.php` — menyaring `status_kelulusan=lulus` DAN `tahap_7_selesai=true`, paginasi 1–100, eager-load formulir/tahapan/tahun/gelombang/sesi tes + hasil psikotes |
| **Resource** | ADA | `app/Http/Resources/GraduatedStudentResource.php` — sudah mengirim `kelas_tujuan` DAN `kelas_penempatan` |
| **Autentikasi** | ADA | middleware `akses.sync` → `AuthenticateAksesSync`, wajib HTTPS + token, throttle 30/menit |
| **Token produksi** | TERPASANG | `config('services.akses_sync.token')` = ADA, `require_https` = true |
| **Kolom penempatan** | ADA | `peserta.kelas_penempatan` (dipakai ekspor & impor Excel) |
| **Header keamanan** | ADA | `Cache-Control: no-store, private` |

### Yang BELUM ada

| Kekurangan | Dampak |
|---|---|
| **`kelas_penempatan` kosong untuk semua 50 peserta lulus** | app.smaafbs.sch.id menerima data tanpa tahu peserta masuk kelas mana |
| **Tidak ada UI admin untuk menetapkan kelas penempatan** | satu-satunya cara mengisi saat ini adalah impor Excel — tidak praktis untuk penempatan per peserta |
| **Tidak ada pilihan "di luar kelas"** | `kelas_penempatan` bertipe teks bebas; tidak ada nilai baku untuk peserta yang belum/tidak ditempatkan di rombel |
| **Tidak ada penanda sudah di-share** | tidak ada kolom `*_sync/*_share/*_kirim` di tabel peserta → tidak bisa tahu siapa yang sudah tersalin ke app, dan tidak ada jejak audit |
| **Model tarik, bukan dorong** | app.smaafbs.sch.id harus memanggil endpoint (pull). Belum ada tombol "Kirim" dari sisi SPMB (push) |
| **Belum memisahkan jalur** | endpoint mengirim semua peserta lulus tanpa membedakan siswa baru / pindahan |

---

## Keputusan Desain

### 1. Tetap pakai model TARIK (pull), tambahkan penanda

Saya sarankan **tidak** membangun pengiriman push dari SPMB ke app.smaafbs.sch.id. Alasannya:

- Endpoint pull sudah ada, sudah diamankan, dan sudah diuji bentuk datanya.
- Push berarti SPMB harus menyimpan kredensial app, menangani percobaan-ulang, kegagalan jaringan, dan status "separuh terkirim" — jauh lebih rapuh.
- Dengan pull, app yang menentukan kapan menarik; SPMB hanya perlu memastikan data **siap** dan **ditandai**.

Yang ditambahkan: **status kesiapan share** di SPMB, sehingga admin tahu peserta mana yang sudah lengkap penempatannya dan siap ditarik.

### 2. `kelas_penempatan`: dua bentuk nilai + satu nilai baku "di luar kelas"

Nilai `kelas_penempatan` dibakukan menjadi:

| Nilai | Arti |
|---|---|
| `null` / kosong | **Belum ditempatkan** — tidak akan ikut ditarik app |
| nama rombel (mis. `10-A`, `11-IPA-1`) | Ditempatkan di rombel tersebut |
| `LUAR_KELAS` | **Di luar kelas** (sesuai permintaan user) — sudah diputuskan tidak masuk rombel, tetap dikirim ke app dengan penanda ini |

Alasan memakai nilai baku `LUAR_KELAS` alih-alih membiarkan teks bebas: app.smaafbs.sch.id perlu membedakan "belum diputuskan" dari "sudah diputuskan di luar kelas". Kalau keduanya sama-sama kosong, app tidak bisa membedakan.

### 3. Daftar rombel dikelola, bukan diketik bebas

Teks bebas akan melahirkan `10-A`, `10 A`, `X-A`, `10a` untuk rombel yang sama — dan app akan menganggapnya empat kelas berbeda. Jadi daftar rombel disimpan di pengaturan (satu sumber), lalu admin memilih dari daftar.

---

## FASE 1 — Kesiapan Data (aman)

### 1.1 Migrasi (ADD COLUMN nullable — aman)
**File baru:** `database/migrations/..._add_penempatan_share_to_peserta_table.php`
```
peserta.penempatan_ditetapkan_pada   (timestamp, nullable)
peserta.penempatan_ditetapkan_oleh   (unsignedBigInteger, nullable) -> pengguna
peserta.disinkron_pada               (timestamp, nullable)  // diisi saat app menarik
```
`kelas_penempatan` **sudah ada**, tidak perlu kolom baru untuk itu.

### 1.2 Daftar rombel di pengaturan
**File:** `app/Services/PengaturanService.php` — kunci baru `rombel_penempatan` (JSON), mis.
```json
[{"nama":"10-A","kelas":10},{"nama":"10-B","kelas":10},{"nama":"11-IPA-1","kelas":11}]
```
**File:** `resources/views/admin/pengaturan/spmb.blade.php` — tab pengelolaan rombel (tambah/hapus/urut), mirip pola SK gelombang yang sudah ada.

### 1.3 Konstanta & helper
**File:** `app/Models/Peserta.php`
```php
public const PENEMPATAN_LUAR_KELAS = 'LUAR_KELAS';
public function getKelasPenempatanLabelAttribute(): string; // 'Belum ditempatkan' | 'Di luar kelas' | nama rombel
public function getSiapDishareAttribute(): bool;            // lulus + tahap7 + kelas_penempatan terisi
```

---

## FASE 2 — UI Penempatan Kelas (aman)

### 2.1 Halaman baru: Penempatan Kelas
**Route:** `GET /admin/penempatan-kelas`, `POST /admin/penempatan-kelas/{peserta}`, `POST /admin/penempatan-kelas/massal`
**Controller baru:** `app/Http/Controllers/Admin/PenempatanKelasController.php`
**View baru:** `resources/views/admin/penempatan-kelas/index.blade.php`

Isi halaman:
- Hanya menampilkan peserta **lulus & tahap 7 selesai** (kandidat share)
- Menghormati konteks **periode** dan **jalur** (siswa baru / pindahan) yang sudah dibangun
- Kolom: nama, nomor pendaftaran, jalur, kelas tujuan, jenis kelamin, asal sekolah, **penempatan saat ini**, status sinkron
- Aksi per baris: pilih rombel dari daftar, atau tandai **Di luar kelas**
- Aksi massal: pilih beberapa peserta → tetapkan ke satu rombel (untuk mengisi 50 peserta sekaligus)
- Ringkasan: jumlah per rombel + berapa yang belum ditempatkan
- Peringatan bila rombel dipilih tidak sesuai `kelas_tujuan` peserta (mis. peserta kelas 10 ditempatkan di rombel 11) — **peringatan, bukan larangan**, karena bisa saja disengaja

Setiap perubahan penempatan dicatat ke **log aktivitas** (kategori peserta) memakai `LogAktivitasService` yang sudah ada.

### 2.2 Kolom di halaman peserta & detail
Tambahkan kolom/baris "Penempatan" agar terlihat tanpa membuka halaman khusus.

---

## FASE 3 — Endpoint Share (perlu koordinasi dengan app.smaafbs.sch.id)

### 3.1 Filter kesiapan
**File:** `app/Http/Controllers/Api/GraduatedStudentController.php`

Tambah parameter opsional (tanpa mengubah perilaku default agar app yang sudah jalan tidak pecah):
- `?siap_share=1` → hanya peserta yang `kelas_penempatan` sudah terisi
- `?jenis=siswa_baru|pindahan` → saring per jalur
- `?tahun_ajaran=2027-2028` → saring per periode
- `?sejak=<ISO8601>` → hanya yang berubah setelah waktu itu (sinkron bertahap, hemat)

### 3.2 Bentuk data penempatan
**File:** `app/Http/Resources/GraduatedStudentResource.php`

Ganti `kelas_penempatan` mentah menjadi objek yang tidak ambigu:
```json
"penempatan": {
  "status": "rombel" | "luar_kelas" | "belum",
  "rombel": "10-A",
  "kelas": 10,
  "ditetapkan_pada": "2026-08-25T10:00:00+07:00"
}
```
`kelas_penempatan` lama **tetap dikirim** untuk kompatibilitas, agar app yang sudah membacanya tidak rusak.

### 3.3 Penanda sudah ditarik
Setiap peserta yang berhasil dikirim melalui endpoint dicatat `disinkron_pada`. Ini memberi admin jawaban atas "sudah masuk app atau belum?" tanpa menebak.

**Catatan penting:** ini hanya mencatat bahwa SPMB **sudah menyerahkan** datanya. SPMB tidak bisa menjamin app sudah menyimpannya — jadi labelnya harus "sudah dikirim", bukan "sudah masuk app".

### 3.4 Halaman status sinkron
Kartu di halaman Penempatan Kelas: total kandidat, sudah ditempatkan, sudah ditarik app, terakhir ditarik kapan.

---

## Pertanyaan yang Perlu Dijawab User

1. **Daftar rombel** — apa saja nama rombel kelas 10 dan 11 tahun ini? (mis. 10-A, 10-B, 11-IPA-1, 11-IPS-1)
2. **Siapa yang boleh menetapkan penempatan** — admin saja, atau operator juga?
3. **"Di luar kelas" itu maksudnya apa** — peserta diterima tetapi belum dapat rombel, atau kategori khusus (mis. tahfidz, kelas khusus, mengulang)? Kalau kategori khusus, sebaiknya jadi beberapa nilai baku, bukan satu.
4. **app.smaafbs.sch.id sudah menarik data sekarang?** Kalau sudah, saya harus hati-hati agar perubahan bentuk data tidak merusaknya.
5. **Pindahan ikut dikirim?** Jalur pindahan yang lulus juga perlu masuk app, atau hanya siswa baru?

---

## Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Mengubah bentuk JSON merusak app yang sudah jalan | `kelas_penempatan` lama tetap dikirim; field baru ditambahkan, bukan mengganti |
| Nama rombel tidak konsisten | daftar rombel dikelola di pengaturan, admin memilih bukan mengetik |
| Data terkirim sebelum penempatan diputuskan | filter `siap_share=1`; peserta tanpa penempatan tidak dianggap siap |
| "Belum ditempatkan" tertukar dengan "di luar kelas" | nilai baku `LUAR_KELAS` membedakan keduanya secara tegas |
| Admin salah menempatkan kelas 10 ke rombel 11 | peringatan ketidaksesuaian, tanpa memblokir |
| Klaim palsu "sudah masuk app" | label memakai "sudah dikirim", bukan "sudah masuk" |
| Data pribadi terekspos | endpoint tetap wajib HTTPS + token + throttle; tidak ada perubahan pada lapisan keamanan |

---

## Urutan Eksekusi

1. Jawab 5 pertanyaan di atas (terutama daftar rombel & arti "di luar kelas")
2. **Fase 1** — migrasi + daftar rombel di pengaturan
3. **Fase 2** — halaman Penempatan Kelas + aksi massal + log aktivitas
4. **Fase 3** — perluasan endpoint (setelah dipastikan tidak merusak app yang sudah jalan)
