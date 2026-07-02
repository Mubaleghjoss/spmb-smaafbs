# Panduan Deployment ke cPanel

## Persyaratan Sistem

- PHP >= 8.2
- MySQL >= 5.7 atau MariaDB >= 10.3
- Ekstensi PHP: PDO, PDO_MySQL, Mbstring, OpenSSL, Tokenizer, XML, Ctype, JSON, Fileinfo, GD, Zip
- Disk space minimal 500MB
- Memory limit minimal 256MB

## Langkah-langkah Deployment

### 1. Persiapan File

1. Download/clone repository aplikasi
2. Jalankan `composer install --no-dev --optimize-autoloader`
3. Jalankan `npm install && npm run build`
4. Hapus folder yang tidak diperlukan:
   - `node_modules/`
   - `tests/`
   - `.git/`

### 2. Upload ke cPanel

1. Login ke cPanel
2. Buka File Manager
3. Upload semua file ke folder `public_html/` atau subdomain yang diinginkan
4. Pastikan struktur folder:
   ```
   public_html/
   ├── app/
   ├── bootstrap/
   ├── config/
   ├── database/
   ├── public/
   │   ├── index.php
   │   ├── .htaccess
   │   └── ...
   ├── resources/
   ├── routes/
   ├── storage/
   ├── vendor/
   ├── .env
   └── ...
   ```

### 3. Konfigurasi Document Root

Untuk shared hosting, ada 2 opsi:

#### Opsi A: Pindahkan isi folder public ke root
1. Pindahkan semua isi folder `public/` ke `public_html/`
2. Edit `index.php`, ubah path:
   ```php
   require __DIR__.'/../vendor/autoload.php';
   // menjadi
   require __DIR__.'/vendor/autoload.php';
   
   $app = require_once __DIR__.'/../bootstrap/app.php';
   // menjadi
   $app = require_once __DIR__.'/bootstrap/app.php';
   ```

#### Opsi B: Gunakan subdomain (Rekomendasi)
1. Buat subdomain di cPanel
2. Arahkan document root ke folder `public/`

### 4. Buat Database

1. Di cPanel, buka MySQL Databases
2. Buat database baru (contoh: `username_spmb`)
3. Buat user database baru
4. Assign user ke database dengan ALL PRIVILEGES

### 5. Konfigurasi Environment

1. Rename `.env.example` menjadi `.env`
2. Edit file `.env`:
   ```env
   APP_NAME="SPMB Al-Furqon"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-anda.com
   
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=username_spmb
   DB_USERNAME=username_dbuser
   DB_PASSWORD=password_database
   
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   ```

### 6. Set Permissions

Via SSH atau File Manager:
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 7. Jalankan Wizard Instalasi

1. Buka browser: `https://domain-anda.com/instalasi`
2. Ikuti langkah-langkah wizard:
   - Cek requirements sistem
   - Konfigurasi database
   - Buat akun admin
   - Proses instalasi

### 8. Setup Cron Job

1. Di cPanel, buka Cron Jobs
2. Tambahkan cron job baru:
   - Common Settings: Once Per Minute
   - Command:
     ```
     /usr/local/bin/php /home/username/public_html/artisan schedule:run >> /dev/null 2>&1
     ```

### 9. Konfigurasi SSL

1. Di cPanel, buka SSL/TLS
2. Aktifkan Let's Encrypt atau upload sertifikat SSL
3. Force HTTPS di `.htaccess`:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

## Troubleshooting

### Error 500
- Cek file `storage/logs/laravel.log`
- Pastikan permissions folder storage dan bootstrap/cache

### Halaman Blank
- Aktifkan `APP_DEBUG=true` sementara untuk melihat error
- Cek PHP error log di cPanel

### Session/Cache Error
- Jalankan: `php artisan config:clear`
- Jalankan: `php artisan cache:clear`

### Upload Gagal
- Cek `upload_max_filesize` dan `post_max_size` di `.user.ini`
- Pastikan folder `storage/app/public` writable

## Backup

### Manual Backup
```bash
php artisan spmb:backup-database
```

### Otomatis via Cron
Backup otomatis berjalan setiap hari jam 02:00 jika cron sudah dikonfigurasi.

File backup tersimpan di: `storage/app/backups/`

## Update Aplikasi

1. Backup database terlebih dahulu
2. Upload file baru (kecuali `.env` dan `storage/`)
3. Jalankan via SSH atau cPanel Terminal:
   ```bash
   php artisan migrate --force
   php artisan optimize
   ```
