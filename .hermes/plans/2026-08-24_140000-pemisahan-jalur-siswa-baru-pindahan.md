# Rancangan: Pemisahan Jalur Siswa Baru vs Pindahan (revisi 2)

> **Untuk Hermes:** revisi setelah keputusan user — TIDAK ada mode "Semua Jalur" sebagai konteks. Default adalah keadaan **belum memilih** dengan label "Pilih Jalur Pendaftaran". Pindahan hanya kelas 10 & 11 (kelas 12 tidak diizinkan). Fase 1–2 aman; Fase 3 mengubah arti kuota → wajib simulasi + konfirmasi.

**Permintaan user:** jalur pindahan tidak tercampur dengan siswa baru. Tahapan sama, pengelolaan terpisah. Model seperti switcher periode: di bawah periode ada pilihan jalur. Default **"Pilih Jalur Pendaftaran"** berisi 1. Siswa Baru, 2. Siswa Pindahan. Pindahan **hanya 2 rombel: kelas 10 dan kelas 11**; kelas 12 belum diizinkan.

---

## Audit Produksi (fakta)

| Temuan | Bukti |
|---|---|
| **Belum ada satu pun peserta pindahan** | 2026-2027: 51 peserta semua `siswa_baru` kelas 10. 2027-2028: 1 peserta `siswa_baru`. Pindahan lintas periode = **0**. |
| Pembeda pindahan saat ini hanya 2 hal | kolom `peserta.jenis_pendaftaran` dan `kelas_tujuan`. Tidak ada perbedaan alur, tes, kuota, atau berkas. |
| **Aturan kelas 10/11 SUDAH ADA** | `PeriodePendaftaranService::validasiKategori()`: `siswa_baru` dipaksa kelas 10; pindahan divalidasi `in_array($kelas, [10, 11])` dengan pesan "Peserta pindahan hanya dapat memilih kelas 10 atau kelas 11." Kelas 12 sudah otomatis ditolak. |
| Kuota tidak mengenal jenis | `tahun_ajaran` hanya punya `kuota_peserta`, `kuota_laki_laki`, `kuota_perempuan`. Pindahan & siswa baru berebut kursi yang sama. |
| Gelombang tidak mengenal jenis | `gelombang_pendaftaran`: `id, tahun_ajaran_id, nama, tanggal_buka, waktu_buka, tanggal_tutup, waktu_tutup, aktif`. |
| Switcher periode matang & bisa ditiru | `PeriodeContextService` (session `periode_aktif_tahun_ajaran_id`, nilai khusus `semua`), dirender `layouts/admin.blade.php` ~633, `POST admin/periode-aktif`. |
| Sebaran `jenis_pendaftaran` | 38 kemunculan di 11 berkas. |

**Implikasi:** karena pindahan masih 0, ini waktu terbaik memisahkan — tanpa migrasi data dan tanpa risiko merusak riwayat.

---

## Keputusan Desain (revisi)

### Jalur WAJIB dipilih, tidak ada mode "Semua Jalur"

Konteks jalur punya tiga keadaan, tetapi **hanya dua yang bisa dipilih**:

| Keadaan | Label | Arti |
|---|---|---|
| `null` (awal) | **Pilih Jalur Pendaftaran** | Belum memilih. Halaman berbasis peserta menampilkan *pengarah pilihan*, bukan data campur. |
| `siswa_baru` | Siswa Baru | Data disaring ke siswa baru. |
| `pindahan` | Siswa Pindahan | Data disaring ke pindahan. |

Keadaan awal **bukan** "tampilkan semua" — itu justru yang ingin dihindari user. Keadaan awal adalah *belum memilih*, dan halaman meminta admin memilih dulu.

### Bagaimana kebutuhan angka gabungan dipenuhi?

Ini kekhawatiran saya pada rancangan sebelumnya, dan sekarang saya selesaikan **tanpa** mengembalikan mode "Semua Jalur":

- **Dashboard adalah satu-satunya halaman lintas-jalur.** Dashboard menampilkan **dua kartu kuota berdampingan** (Siswa Baru | Pindahan) plus satu baris total gabungan. Jadi admin tetap melihat gambaran menyeluruh tanpa perlu mode "semua", dan setiap angka jelas milik jalur mana.
- **Halaman kerja (peserta, verifikasi, monitoring, hasil, alur) selalu satu jalur.** Inilah yang membuat pekerjaan "clean" — tidak pernah ada daftar campur.
- **Ekspor CSV** mencantumkan jalur pada nama berkas dan baris judul, sehingga rekap manual tidak pernah tertukar.

Dengan pola ini tidak ada satu pun halaman yang menampilkan daftar peserta campur aduk, tetapi Anda juga tidak kehilangan angka total.

### Pindahan: 2 rombel, kelas 12 ditolak

Aturan ini **sudah berjalan** di `validasiKategori()`. Yang perlu ditambahkan hanya penegasan agar tidak bisa ditembus dari sisi lain:

- `PesertaController` store/update/impor: validasi `kelas_tujuan` menjadi bergantung jenis — `siswa_baru` → harus 10; `pindahan` → harus 10 atau 11. Saat ini ketiganya memakai `in:10,11` datar tanpa mengaitkan jenis.
- Form admin & publik: opsi kelas untuk pindahan dibatasi 10 dan 11 saja, dengan keterangan "Kelas 12 belum dibuka".
- `ImporEksporPesertaService`: baris impor pindahan berkelas 12 ditolak dengan pesan jelas, bukan dibiarkan masuk.

---

## FASE 1 — Konteks Jalur (switcher)

### 1.1 Service konteks
**File baru:** `app/Services/JalurContextService.php`

```php
class JalurContextService
{
    public const SESSION_KEY = 'jalur_aktif_jenis_pendaftaran';

    /** Jalur aktif: 'siswa_baru' | 'pindahan' | null (belum memilih). */
    public function jenis(): ?string;
    public function belumMemilih(): bool;
    public function set(?string $jenis): void;   // hanya siswa_baru|pindahan|null
    public function label(): string;             // 'Pilih Jalur Pendaftaran' | 'Siswa Baru' | 'Siswa Pindahan'
    public function pilihan(): array;            // [['nilai'=>'siswa_baru','label'=>'Siswa Baru','ikon'=>'person-plus'], ...]

    /** Kelas tujuan yang diizinkan untuk jalur tertentu. */
    public static function kelasDiizinkan(string $jenis): array; // siswa_baru => [10]; pindahan => [10, 11]
}
```
**Tidak ada konstanta SEMUA.** `null` berarti belum memilih, bukan "semua".

`kelasDiizinkan()` menjadi **satu sumber kebenaran** aturan rombel — dipakai oleh validasi controller, form admin, form publik, dan impor. Ini mencegah aturan kelas 12 tersebar di banyak tempat lalu tidak konsisten.

### 1.2 Route + controller
**File baru:** `app/Http/Controllers/Admin/JalurAktifController.php`
**File:** `routes/web.php` — `POST /admin/jalur-aktif` → `admin.jalur-aktif.ganti`, diletakkan tepat di bawah route `periode-aktif`.

### 1.3 Berbagi ke semua view
Tambahkan `$jalurPilihan`, `$jalurAktifLabel`, `$jalurAktifJenis`, `$jalurBelumMemilih` melalui mekanisme yang sama dengan `$periodePilihan` (cek `AppServiceProvider` / middleware yang sudah men-share konteks periode).

### 1.4 Tampilan switcher
**File:** `resources/views/layouts/admin.blade.php` (~baris 633)

Dropdown kedua di bawah switcher periode:
- Belum memilih → tombol **berwarna peringatan** dengan teks "Pilih Jalur Pendaftaran" + ikon `bi-signpost-2`, supaya jelas ada yang harus dipilih
- Terpilih → teks jalur + ikon (`bi-person-plus` untuk Siswa Baru, `bi-arrow-left-right` untuk Siswa Pindahan)
- Isi dropdown: **1. Siswa Baru**, **2. Siswa Pindahan** (bernomor, sesuai permintaan)
- Tidak ada opsi "Semua Jalur"
- Di HP dua switcher ditumpuk

### 1.5 Pengarah saat belum memilih
**File baru:** `resources/views/admin/partials/pilih-jalur.blade.php`

Kartu di tengah halaman: judul "Pilih Jalur Pendaftaran", keterangan singkat mengapa perlu memilih, dan dua tombol besar (Siswa Baru / Siswa Pindahan) yang langsung menyetel konteks. Disertakan di halaman kerja saat `$jalurBelumMemilih` bernilai true — menggantikan daftar, bukan menumpuknya.

---

## FASE 2 — Penerapan Filter di Halaman Admin

Pola: `if ($jenis = app(JalurContextService::class)->jenis()) $query->where('jenis_pendaftaran', $jenis);`

| Berkas | Perlakuan saat belum memilih |
|---|---|
| `PesertaService::daftar()` (~60), `queryRekapFormulir()` (~183) | halaman peserta menampilkan pengarah pilih jalur |
| `VerifikasiSpmbController` (formulir/pembayaran/wawancara/kelulusan) | pengarah pilih jalur |
| `AlurPesertaController::index()` + `eksporCsv()` | pengarah; ekspor menolak bila jalur belum dipilih |
| `MonitoringUjianController` | pengarah |
| `HasilController` | pengarah |
| `DashboardController` + `RingkasanKuotaDashboardService` | **tetap tampil**: dua kartu jalur berdampingan + total gabungan |
| `LogAktivitasController` | **TIDAK difilter** — log adalah catatan audit, harus utuh |

**Sinkron dengan filter halaman:** dropdown `jenis_pendaftaran` yang sudah ada di `admin/peserta/index.blade.php:153` **dihapus** (bukan dinonaktifkan), karena jalur kini ditentukan switcher. Ini sesuai preferensi user: logika lama yang tumpang tindih dihapus, bukan dilapisi.

---

## FASE 3 — Kuota Per Jalur (PERLU KONFIRMASI + SIMULASI)

### 3.1 Migrasi (aman: ADD COLUMN nullable)
```
tahun_ajaran.kuota_pindahan_kelas_10  (int, nullable, 0 = tidak dibatasi)
tahun_ajaran.kuota_pindahan_kelas_11  (int, nullable, 0 = tidak dibatasi)
```
Karena pindahan hanya punya 2 rombel, kuota langsung dipecah **per kelas** — tidak perlu kuota pindahan total, sebab jumlah kursi kelas 10 dan 11 memang dikelola terpisah oleh sekolah. Ini lebih tepat daripada satu angka gabungan.

`kuota_peserta` / `kuota_laki_laki` / `kuota_perempuan` yang ada **tetap berarti kuota siswa baru** — tanpa rename, agar kode lain tidak pecah dan hasil untuk 52 peserta existing identik.

### 3.2 Hitungan dua kolam terpisah
**File:** `app/Services/KuotaPendaftaranService.php`

`rekalkulasiTahun()` dipecah menjadi dua kolam independen:
- **Siswa baru** → batas `kuota_peserta` + `kuota_laki_laki`/`kuota_perempuan` (perilaku sekarang, tidak berubah)
- **Pindahan** → batas `kuota_pindahan_kelas_10` untuk pendaftar kelas 10, `kuota_pindahan_kelas_11` untuk kelas 11

Aturan 3 lapis yang sudah dibangun **berlaku sama** untuk kedua jalur: syarat formulir + pembayaran Tahap 3, tiga status (belum lengkap / masuk kuota / waiting list), kursi dilepas saat tidak lulus. Yang berbeda hanya kolam kursinya.

`ringkasanTahun()` mengembalikan blok `siswa_baru`, `pindahan_kelas_10`, `pindahan_kelas_11`, dan `gabungan` (untuk dashboard).

### 3.3 Tampilan kuota
- **Pengaturan Periode Pendaftaran:** dua kolom kuota pindahan (kelas 10, kelas 11) di baris tahun ajaran
- **Dashboard:** dua kartu berdampingan; kartu pindahan dipecah per kelas
- **Halaman /daftar:** setelah calon memilih Siswa Pindahan lalu kelas tujuan, kuota yang tampil = kuota pindahan kelas tersebut
- **Popup penjelasan kuota:** tambah paragraf bahwa kuota siswa baru dan pindahan dihitung terpisah, dan pindahan hanya tersedia untuk kelas 10 & 11

---

## FASE 4 — Pembeda Alur Pindahan (perlu keputusan user)

Tahapan sama, tetapi ini kebijakan sekolah — belum saya putuskan:

1. **Berkas tambahan** — Surat Pindah/Mutasi, rapor sekolah asal, surat kelakuan baik. Diwajibkan untuk jalur pindahan?
2. **Tes** — pindahan mengerjakan 8 tes yang sama? Untuk masuk kelas 11, tes kelas 10 mungkin tidak relevan.
3. **Biaya pendaftaran** — nominal sama?
4. **Nomor pendaftaran** — dibedakan menjadi `SPMB-P-2026-000xx`?
5. **SK Kelulusan** — SK terpisah untuk pindahan, atau digabung?

**Saran saya:** nomor pendaftaran dibedakan (murah, langsung berguna untuk pengarsipan) dan berkas tambahan diwajibkan (kebutuhan nyata administrasi mutasi). Tes dan biaya tidak saya ubah tanpa keputusan Anda.

---

## Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Admin bingung karena halaman "kosong" saat belum memilih jalur | Bukan kosong — kartu pengarah dengan dua tombol besar; switcher berwarna peringatan |
| Admin lupa jalur mana yang aktif lalu salah menyimpulkan | Label jalur dicetak di header setiap halaman terfilter, pada nama berkas ekspor, dan baris judul CSV |
| Kehilangan angka lintas jalur | Dashboard menampilkan dua kartu + total gabungan |
| Kelas 12 lolos dari jalur lain (impor/admin) | `kelasDiizinkan()` sebagai sumber tunggal, dipakai validasi controller, form, dan impor |
| Kuota pindahan belum diisi → dianggap 0 | 0 = tidak dibatasi (konsisten perilaku kuota yang ada) + keterangan di form |
| Peserta lama berubah status | Kolam siswa baru memakai kolom kuota yang sama → hasil identik; dibuktikan simulasi sebelum simpan |
| Menyentuh banyak berkas | Fase 1–2 dulu, Fase 3 putaran tersendiri |

---

## Urutan Eksekusi

1. **Fase 1** — service konteks + switcher + kartu pengarah
2. **Fase 2** — filter halaman admin, hapus dropdown jenis lama, tegakkan aturan kelas per jalur
3. Jawab pertanyaan Fase 4
4. **Fase 3** — kuota per jalur: migrasi, dua kolam, simulasi, tunjukkan angka, baru simpan
5. **Fase 4** — pembeda alur yang disetujui

Fase 1–2 dapat dikerjakan & dideploy dalam satu putaran. Fase 3 putaran tersendiri karena mengubah arti data.
