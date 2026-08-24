# Rancangan: Log Aktivitas Tim & Hapus Peserta Permanen

> **Untuk Hermes:** eksekusi bertahap. Fase 1 dulu (log aktivitas), lalu Fase 2 (hapus permanen). Jangan gabung — Fase 2 menyentuh data & storage produksi.

**Tujuan:** (1) Setiap aksi penting Tim SPMB terekam siapa-melakukan-apa-kapan, dapat dilihat & difilter admin. (2) Admin dapat menghapus peserta secara permanen beserta seluruh berkasnya, dengan konfirmasi yang menampilkan lengkap data & berkas yang akan hilang.

**Prinsip:** tidak ada dependensi baru; ikut pola kode yang sudah ada (Service + Controller + Blade); aman untuk data produksi.

---

## Temuan Audit Kondisi Saat Ini

Sudah dicek langsung di repo & produksi:

| Kebutuhan | Status sekarang |
|---|---|
| Akun per anggota tim | **SUDAH ADA.** Tabel `pengguna` (nama, email, password, `peran`, `menu_akses`, `aktif`, `percobaan_login`, `dikunci_sampai`). CRUD lengkap di `/admin/pengguna` (index/create/store/edit/update/destroy/toggle-aktif). Hak akses per menu sudah ada via `menu_akses` + middleware `CekAksesMenu`. |
| Log aktivitas | **BELUM ADA.** Hanya `log_tahapan_spmb` (khusus perubahan tahapan, punya `admin_id`) dan `token_global_log`. Tidak ada halaman admin untuk melihat log; `LogTahapanSpmb` cuma dipakai untuk `count()` di halaman pengaturan. |
| Hapus peserta | Soft delete ada (`PesertaController::destroy` → `PesertaService::hapus`), restore ada. `PesertaService::hapusPermanen()` **sudah ada tapi tidak punya route/UI**, dan **tidak menghapus berkas di storage**. |
| Berkas peserta | Tersebar di `storage/app/public/formulir/{peserta_id}/`, `wawancara/pegon`, `wawancara/voice`, `pembayaran/`, `kwitansi/`. Kolom file: `formulir_spmb` (file_kk, file_akta, file_ijazah, file_bpjs, file_ktp_ayah, file_ktp_ibu, foto), `wawancara` (file_tes_pegon, file_voice_quran), `pembayaran` (bukti_file). TTD tersimpan base64 di DB (ikut terhapus bersama baris). |

**Kesimpulan:** kebutuhan #1 tinggal menambah *log aktivitas* (akun tim sudah jalan). Kebutuhan #2 perlu route + UI + pembersihan storage.

---

## FASE 1 — Log Aktivitas Tim SPMB

### Arsitektur

Satu tabel `log_aktivitas` + satu service `LogAktivitasService` yang dipanggil dari titik-titik aksi penting. **Tidak** memakai observer model global, supaya yang tercatat hanya aksi bermakna (bukan setiap update kolom) dan pesannya bisa dalam bahasa manusia.

### Task 1.1: Migrasi tabel `log_aktivitas`

**File:** Create `database/migrations/2026_08_23_000010_create_log_aktivitas_table.php`

```php
Schema::create('log_aktivitas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pengguna_id')->nullable()->constrained('pengguna')->nullOnDelete();
    $table->string('nama_pengguna');          // disimpan tetap, agar tetap terbaca bila akun dihapus
    $table->string('peran')->nullable();
    $table->string('aksi', 60);               // mis. peserta.hapus_permanen, kelulusan.loloskan
    $table->string('kategori', 40);           // peserta | formulir | pembayaran | ujian | wawancara | kelulusan | pengaturan | akun
    $table->string('subjek_tipe')->nullable();// App\Models\Peserta
    $table->unsignedBigInteger('subjek_id')->nullable();
    $table->string('subjek_label')->nullable();// "Rizkiana (SPMB-2026-00052)"
    $table->text('keterangan');               // kalimat siap baca
    $table->json('data')->nullable();         // detail tambahan (nilai lama/baru, jumlah menit, dsb)
    $table->string('ip', 45)->nullable();
    $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajaran')->nullOnDelete();
    $table->timestamps();

    $table->index(['kategori', 'created_at']);
    $table->index(['pengguna_id', 'created_at']);
    $table->index(['subjek_tipe', 'subjek_id']);
});
```

**Verifikasi:** `php artisan migrate` → DONE; `Schema::hasTable('log_aktivitas')` true.
**Aman produksi:** CREATE TABLE saja.

### Task 1.2: Model `LogAktivitas`

**File:** Create `app/Models/LogAktivitas.php`

- `$fillable` semua kolom di atas; cast `data` => array.
- Relasi `pengguna()` (BelongsTo), `subjek()` (morphTo).
- Konstanta kategori: `KAT_PESERTA`, `KAT_FORMULIR`, `KAT_PEMBAYARAN`, `KAT_UJIAN`, `KAT_WAWANCARA`, `KAT_KELULUSAN`, `KAT_PENGATURAN`, `KAT_AKUN`.
- Accessor `ikon` & `warna` per kategori (untuk badge di UI).

### Task 1.3: `LogAktivitasService`

**File:** Create `app/Services/LogAktivitasService.php`

```php
public function catat(
    string $aksi,
    string $kategori,
    string $keterangan,
    ?Model $subjek = null,
    array $data = [],
    ?int $tahunAjaranId = null,
): LogAktivitas
```

Isi otomatis: `pengguna_id`/`nama_pengguna`/`peran` dari `auth('pengguna')->user()` (fallback "Sistem"), `ip` dari request, `subjek_label` dari `$subjek->nama ?? $subjek->id`.

**Penting:** dibungkus `try/catch` + `Log::warning` — kegagalan pencatatan **tidak boleh** membatalkan aksi utama.

Helper query: `filter(array $filter)` (kategori, pengguna, tanggal_dari, tanggal_sampai, cari, subjek) → paginate.

### Task 1.4: Pasang pencatatan di titik aksi

Panggil `$this->logAktivitas->catat(...)` pada:

| File | Method | aksi / kategori |
|---|---|---|
| `Admin/VerifikasiSpmbController.php` | `verifikasiFormulir`, `tolakFormulir` | `formulir.verifikasi` / `formulir.tolak` — formulir |
| | `verifikasiPembayaranFormulir`, `tolakPembayaranFormulir`, `uploadBuktiFormulir` | `pembayaran.*` — pembayaran |
| | `loloskanWawancara`, `tidakLolosWawancara`, `simpanWawancara` | `wawancara.*` — wawancara |
| | `luluskanPeserta`, `tidakLuluskanPeserta`, `luluskanSemua`, `luluskanBatchKelulusan` | `kelulusan.*` — kelulusan (sertakan `sk_gelombang` di `data`) |
| | `setujuiPerpanjanganTimeout`, `setujuiUlangTimeout`, `tolakPermohonanUlang` | `ujian.tambah_waktu` / `ujian.ulang` / `ujian.tolak_permohonan` — ujian (sertakan menit di `data`) |
| `Admin/MonitoringUjianController.php` | `resetSesi`, `perpanjangWaktu`, `paksaSelesai` | `ujian.*` — ujian |
| `Admin/PesertaController.php` | `store`, `update`, `destroy`, `restore`, `hapusPermanen` | `peserta.*` — peserta |
| `Admin/AlurPesertaController.php` | `ubahTahapan` | `tahapan.ubah_manual` — peserta |
| `Admin/PenggunaController.php` | `store`, `update`, `destroy`, `toggleAktif` | `akun.*` — akun |
| `Admin/TesController.php` | `store`, `update`, `destroy`, `bulkDurasiJadwal` | `ujian.tes_*` — ujian |
| `Admin/PengaturanController.php` | `simpanSpmb` (termasuk SK kelulusan) | `pengaturan.simpan` — pengaturan |

Contoh:
```php
app(LogAktivitasService::class)->catat(
    aksi: 'kelulusan.loloskan',
    kategori: LogAktivitas::KAT_KELULUSAN,
    keterangan: "Meluluskan {$peserta->nama} dengan SK {$namaSk}",
    subjek: $peserta,
    data: ['sk_gelombang_id' => $skGelombangId],
    tahunAjaranId: $peserta->tahun_ajaran_id,
);
```

### Task 1.5: Halaman Log Aktivitas

**Files:**
- Create `app/Http/Controllers/Admin/LogAktivitasController.php` (`index`, `eksporCsv`)
- Create `resources/views/admin/log-aktivitas/index.blade.php`
- Modify `routes/web.php`: `GET /admin/log-aktivitas`, `GET /admin/log-aktivitas/ekspor`
- Modify `app/Models/Pengguna.php::daftarMenu()` → tambah entri `log-aktivitas` (agar bisa dibatasi per peran)
- Modify `resources/views/layouts/admin.blade.php` → item sidebar (di grup pengaturan/sistem)

**UI:** kartu statistik (aktivitas hari ini, 7 hari, pengguna teraktif) + filter (kategori, pengguna, rentang tanggal, kata kunci) + tabel: Waktu · Pengguna+peran · Kategori (badge) · Keterangan · Subjek (tautan ke detail peserta) · IP. Responsif HP: tabel jadi daftar kartu (`d-md-none`). Tombol Ekspor CSV.

**Tambahan:** di halaman Detail Peserta, tab/kartu "Riwayat Aktivitas" memakai `subjek_tipe`+`subjek_id` → riwayat khusus peserta itu.

### Task 1.6: Retensi log

**File:** Create `app/Console/Commands/BersihkanLogAktivitas.php` + jadwalkan di `bootstrap/app.php` (atau `routes/console.php`) harian.
Hapus log > 12 bulan (`log_aktivitas_retensi_bulan`, default 12, bisa diubah admin di Pengaturan). Mencegah tabel membengkak seperti kasus `laravel.log` 24 MB.

### Verifikasi Fase 1

Skrip uji lokal `_ujilog.php`:
1. `catat()` menyimpan baris dengan `nama_pengguna` terisi walau akun dihapus setelahnya.
2. Loloskan peserta → 1 baris kategori kelulusan berisi nama SK.
3. Tambah waktu ujian → baris ujian dengan menit di `data`.
4. Ubah tahapan manual → baris peserta.
5. Filter kategori & rentang tanggal mengembalikan jumlah benar.
6. `catat()` yang gagal (DB error disimulasikan) tidak membuat aksi utama gagal.
7. `php artisan log-aktivitas:bersihkan` menghapus baris berumur > retensi.
8. Compile semua blade 0 gagal.

---

## FASE 2 — Hapus Peserta Permanen + Bersihkan Storage

### Alur yang dirancang

1. Admin klik **Hapus Permanen** (di Detail Peserta, dan di daftar peserta pada tab "Terhapus").
2. Sistem menampilkan **modal pratinjau** (data diambil via AJAX supaya selalu terkini):
   - Identitas: nama, no. pendaftaran, telepon, periode, gelombang, tahap saat ini, status kelulusan
   - Ringkasan data yang akan hilang: formulir (ada/tidak), N sesi tes + jawaban, N pembayaran (+ status verifikasi), data wawancara, N log tahapan/aktivitas
   - **Daftar berkas**: nama file, ukuran, tautan lihat, per kategori (formulir/wawancara/pembayaran/kwitansi) + total ukuran
   - Peringatan merah: tidak bisa dibatalkan
3. Konfirmasi berlapis: centang "Saya paham data & berkas akan hilang permanen" **dan** ketik nomor pendaftaran peserta. Tombol Hapus baru aktif bila keduanya benar (juga divalidasi di server).
4. Server: transaksi DB → hapus baris anak → `forceDelete()` peserta → hapus berkas dari storage → rekalkulasi kuota → catat ke log aktivitas (log ini **tetap ada** setelah peserta hilang, memakai `subjek_label`).

### Task 2.1: `PesertaPembersihService`

**File:** Create `app/Services/PesertaPembersihService.php`

- `ringkasan(Peserta $p): array` → struktur untuk modal: `identitas`, `data` (jumlah per relasi), `berkas` (array {kategori, path, nama, ukuran, url, ada}), `total_ukuran`.
- `hapusPermanen(Peserta $p): array` → kembalikan `['berkas_terhapus' => n, 'berkas_gagal' => [...], 'byte_dibebaskan' => n]`.

Aturan berkas:
- Kumpulkan path dari kolom DB (bukan menebak nama file).
- Hapus juga direktori `formulir/{id}` bila kosong setelah pembersihan.
- **Jangan** hapus berkas milik pengaturan global (SK kelulusan, branding) — hanya berkas milik peserta.
- Cek `Storage::disk('public')->exists()` dulu; kumpulkan yang gagal ke `berkas_gagal` dan laporkan, jangan bikin exception.

Urutan hapus baris (hormati FK): `jawaban_peserta` → `sesi_tes` → `pembayaran` → `wawancara` → `peserta_wawancara` → `formulir_spmb` → `log_tahapan_spmb` → `tahapan_spmb` → pivot `grup` → `peserta` (forceDelete).

### Task 2.2: Endpoint pratinjau & hapus

**File:** Modify `app/Http/Controllers/Admin/PesertaController.php`
- `pratinjauHapus(int $id): JsonResponse` → `ringkasan()` (izinkan `withTrashed`)
- `hapusPermanen(Request $request, int $id): RedirectResponse` → validasi `konfirmasi_nomor` sama dengan `nomor_pendaftaran` + `paham=1`; jalankan service; catat log; redirect dengan ringkasan hasil.

**File:** Modify `routes/web.php`
```php
Route::get('/peserta/{id}/pratinjau-hapus', [...])->name('peserta.pratinjau-hapus');
Route::delete('/peserta/{id}/hapus-permanen', [...])->name('peserta.hapus-permanen');
```

**Pembatasan akses:** hanya `peran === 'admin'` (bukan semua yang punya menu peserta). Tambah pemeriksaan di controller + sembunyikan tombol di view.

### Task 2.3: UI modal

**Files:** Modify `resources/views/admin/peserta/show.blade.php`, `resources/views/admin/peserta/index.blade.php`
- Satu partial `resources/views/admin/peserta/partials/modal-hapus-permanen.blade.php` (di luar `<table>` — pelajaran dari modal Alur Peserta).
- Isi dimuat via `fetch` ke route pratinjau; tampilkan spinner saat memuat; tampilkan error bila gagal.
- Tombol Hapus `disabled` sampai centang + nomor pendaftaran cocok.

### Task 2.4: Opsi hapus massal (opsional, minta persetujuan user dulu)

Untuk membersihkan data testing: pilih beberapa peserta di tab "Terhapus" → hapus permanen sekaligus, dengan modal berisi daftar nama + total berkas. **Tidak dikerjakan tanpa persetujuan eksplisit.**

### Verifikasi Fase 2

Skrip uji lokal `_ujihapus.php` (buat peserta uji + berkas dummy di storage):
1. `ringkasan()` mendaftar semua berkas & jumlah relasi dengan benar.
2. `hapusPermanen()` → baris peserta & semua anak hilang (`Peserta::withTrashed()->find()` null; `JawabanPeserta`/`SesiTes`/`Pembayaran`/`Wawancara`/`FormulirSpmb`/`TahapanSpmb` 0).
3. Semua berkas dummy benar-benar hilang dari `storage/app/public`; direktori `formulir/{id}` terhapus.
4. Berkas milik peserta LAIN dan berkas pengaturan (SK) **tetap ada**.
5. Konfirmasi nomor pendaftaran salah → ditolak (422/redirect error), data tetap utuh.
6. Log aktivitas tetap ada setelah peserta hilang, `subjek_label` masih terbaca.
7. Kuota periode direkalkulasi (jumlah peserta turun).
8. Peran non-admin → 403.

---

## Berkas yang Akan Berubah

**Baru:**
```
database/migrations/2026_08_23_000010_create_log_aktivitas_table.php
app/Models/LogAktivitas.php
app/Services/LogAktivitasService.php
app/Services/PesertaPembersihService.php
app/Http/Controllers/Admin/LogAktivitasController.php
app/Console/Commands/BersihkanLogAktivitas.php
resources/views/admin/log-aktivitas/index.blade.php
resources/views/admin/peserta/partials/modal-hapus-permanen.blade.php
```

**Diubah:**
```
routes/web.php
app/Models/Pengguna.php                       (daftarMenu + relasi logAktivitas)
app/Http/Controllers/Admin/PesertaController.php
app/Http/Controllers/Admin/VerifikasiSpmbController.php
app/Http/Controllers/Admin/MonitoringUjianController.php
app/Http/Controllers/Admin/AlurPesertaController.php
app/Http/Controllers/Admin/PenggunaController.php
app/Http/Controllers/Admin/TesController.php
app/Http/Controllers/Admin/PengaturanController.php
app/Services/PesertaService.php               (hapusPermanen → delegasi ke PesertaPembersihService)
resources/views/layouts/admin.blade.php       (menu sidebar)
resources/views/admin/peserta/show.blade.php
resources/views/admin/peserta/index.blade.php
```

---

## Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Salah hapus peserta asli | Konfirmasi berlapis (centang + ketik nomor pendaftaran), pratinjau lengkap, hanya peran admin, tercatat di log |
| Berkas peserta lain ikut terhapus | Hanya path dari kolom DB peserta itu; uji khusus memastikan berkas peserta lain & SK tetap ada |
| Tabel log membengkak | Indeks + perintah retensi harian (default 12 bulan) |
| Pencatatan log menggagalkan aksi utama | `try/catch` + `Log::warning`, tidak pernah melempar exception |
| FK constraint saat hapus | Urutan hapus eksplisit di dalam transaksi DB |
| Log hilang saat akun tim dihapus | `nama_pengguna`/`peran`/`subjek_label` disimpan sebagai teks, FK `nullOnDelete` |

---

## Pertanyaan untuk Diputuskan

1. **Peran yang boleh hapus permanen** — hanya `admin`, atau `admin` + peran tertentu?
2. **Retensi log** — 12 bulan cukup, atau simpan selamanya?
3. **Hapus massal** (Task 2.4) — dibuat sekarang atau nanti?
4. **Cakupan pencatatan** — apakah aksi baca/ekspor juga perlu dicatat, atau cukup aksi yang mengubah data?
5. **Peserta yang sudah lulus** — boleh dihapus permanen, atau dilarang oleh sistem?

---

## Urutan Eksekusi yang Disarankan

1. Fase 1 Task 1.1–1.3 (fondasi log) → uji → commit
2. Fase 1 Task 1.4 (pasang di titik aksi) → uji → commit
3. Fase 1 Task 1.5–1.6 (UI + retensi) → uji → commit → **deploy** (aman: hanya CREATE TABLE)
4. Jawab pertanyaan di atas
5. Fase 2 Task 2.1–2.3 → uji lokal menyeluruh → **konfirmasi user** → deploy
6. Fase 2 Task 2.4 hanya bila diminta
