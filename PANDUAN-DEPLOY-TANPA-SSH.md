# Panduan Deploy Laravel ke Shared Hosting (Tanpa SSH)

## Target: seleksi.smaafbs.sch.id
## Directory: public_html/web/www.seleksi

---

## LANGKAH 1: Persiapan di Komputer Lokal

### A. Export Database
1. Buka phpMyAdmin lokal (http://localhost/phpmyadmin)
2. Pilih database `spmb_alfurqon` atau nama database Anda
3. Klik tab **Export**
4. Method: **Quick**
5. Format: **SQL**
6. Klik **Go** dan simpan file `.sql`

### B. Siapkan File Project
1. Buka folder `spmb-alfurqon`
2. **HAPUS** folder berikut (untuk mengecilkan ukuran):
   - `node_modules/` (jika ada)
   - `vendor/` (akan di-install ulang)
   - `.git/` (jika ada)
3. Rename file `.env.production` menjadi `.env`
4. Compress semua file menjadi `spmb-alfurqon.zip`

---

## LANGKAH 2: Setup di cPanel

### A. Buat Database
1. Login ke cPanel
2. Buka **MySQL Databases**
3. Create New Database: `spmb_seleksi` (atau nama lain)
4. Create New User dengan password kuat
5. Add User to Database → pilih **ALL PRIVILEGES**
6. Catat:
   - Nama database: `cpanelusername_spmb_seleksi`
   - Username: `cpanelusername_dbuser`
   - Password: `password_anda`

### B. Import Database
1. Buka **phpMyAdmin** di cPanel
2. Pilih database yang baru dibuat
3. Klik tab **Import**
4. Pilih file `.sql` yang sudah di-export
5. Klik **Go**

### C. Upload File Project
1. Buka **File Manager** di cPanel
2. Masuk ke folder `public_html/web/www.seleksi`
3. Upload file `spmb-alfurqon.zip`
4. Klik kanan → **Extract**
5. Pindahkan semua file dari folder hasil extract ke `www.seleksi` (root)

**Struktur akhir harus seperti ini:**
```
public_html/web/www.seleksi/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── setup-hosting.php
│   └── ...
├── resources/
├── routes/
├── storage/
├── vendor/  (kosong dulu)
├── .env
└── ...
```

---

## LANGKAH 3: Install Vendor (Composer)

Karena tidak ada SSH, gunakan salah satu cara berikut:

### Opsi A: Upload vendor dari lokal (RECOMMENDED)
1. Di komputer lokal, jalankan: `composer install --no-dev`
2. Compress folder `vendor/` menjadi `vendor.zip`
3. Upload `vendor.zip` ke hosting
4. Extract di folder project

### Opsi B: Gunakan Composer di cPanel (jika tersedia)
1. Cari menu **Setup PHP** atau **Select PHP Version** di cPanel
2. Aktifkan extension yang diperlukan
3. Beberapa hosting menyediakan Composer via Softaculous

---

## LANGKAH 4: Konfigurasi .env

1. Di File Manager, buka file `.env`
2. Edit bagian berikut:

```env
APP_KEY=base64:GENERATE_NEW_KEY
APP_DEBUG=false
APP_URL=https://seleksi.smaafbs.sch.id

DB_DATABASE=cpanelusername_spmb_seleksi
DB_USERNAME=cpanelusername_dbuser
DB_PASSWORD=password_anda_yang_sebenarnya
```

3. Simpan file

---

## LANGKAH 5: Setup Document Root

### Opsi A: Jika subdomain mengarah ke www.seleksi/public
Tidak perlu ubah apa-apa, langsung lanjut ke langkah 6.

### Opsi B: Jika subdomain mengarah ke www.seleksi (tanpa /public)
Edit file `public_html/web/www.seleksi/index.php`:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader
require __DIR__.'/vendor/autoload.php';

// Bootstrap
(require_once __DIR__.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
```

Dan copy semua isi folder `public/` ke root `www.seleksi/`:
- index.php (sudah ada, edit seperti di atas)
- .htaccess
- setup-hosting.php
- storage-proxy.php
- css/, js/, images/, dll

---

## LANGKAH 6: Jalankan Setup Script

1. Buka browser
2. Akses: `https://seleksi.smaafbs.sch.id/setup-hosting.php?key=setup123`
3. Script akan:
   - Generate APP_KEY
   - Membuat folder yang diperlukan
   - Set permissions
   - Test koneksi database
4. Pastikan semua ✅ hijau

---

## LANGKAH 7: Test Aplikasi

1. Akses: `https://seleksi.smaafbs.sch.id`
2. Cek halaman utama tampil
3. Test login admin
4. Test upload file (foto, bukti pembayaran)

---

## LANGKAH 8: Cleanup (PENTING!)

**HAPUS file-file berikut setelah selesai:**
- `setup-hosting.php` (keamanan!)
- File `.zip` yang di-upload

---

## TROUBLESHOOTING

### Error 500 Internal Server Error
1. Cek file `.htaccess` sudah benar
2. Cek PHP version minimal 8.1
3. Cek permission folder `storage/` dan `bootstrap/cache/` (775)

### Gambar/Upload tidak tampil
1. Pastikan `storage-proxy.php` ada di folder public
2. Cek `.htaccess` sudah ada rule untuk storage proxy

### Database Error
1. Cek nama database, username, password di `.env`
2. Pastikan user database punya ALL PRIVILEGES

### Session/Login Error
1. Cek folder `storage/framework/sessions/` ada dan writable
2. Cek `SESSION_DRIVER=file` di `.env`

---

## KONTAK SUPPORT

Jika ada masalah, hubungi developer dengan informasi:
- Screenshot error
- Isi file `.env` (sensor password)
- PHP version di hosting
