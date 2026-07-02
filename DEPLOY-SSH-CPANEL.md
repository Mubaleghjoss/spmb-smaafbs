# Deploy SSH cPanel - seleksi.smaafbs.sch.id

Target live:

- URL: `https://seleksi.smaafbs.sch.id`
- App root: `/home/sman5479/spmb-app`
- Public root: `/home/sman5479/public_html/web/www.seleksi`
- Database: `sman5479_spmb`
- Database user: `sman5479_spmb`

Password database tidak disimpan di GitHub. Isi hanya di file `.env` server.

## First deploy dari SSH server

```bash
cd /home/sman5479
git clone git@github.com:Mubaleghjoss/spmb-smaafbs.git spmb-app
cd spmb-app
DB_PASSWORD='ISI_PASSWORD_DATABASE_CPANEL' bash scripts/deploy-cpanel-ssh.sh
```

Script akan:

- membuat `.env` production jika belum ada
- menjalankan `composer install --no-dev --optimize-autoloader`
- menjalankan `npm ci && npm run build` jika Node.js tersedia
- menyalin isi `public/` ke `/home/sman5479/public_html/web/www.seleksi`
- melewati file helper browser `setup-hosting.php` dan `run-migration.php`
- membuat `index.php` public yang mengarah ke `/home/sman5479/spmb-app`
- menjalankan `php artisan migrate --force`
- membersihkan dan mengoptimasi cache Laravel

## Update berikutnya

```bash
cd /home/sman5479/spmb-app
git pull --ff-only
bash scripts/deploy-cpanel-ssh.sh
```

## Jika server belum punya SSH key GitHub

Jalankan di server:

```bash
ssh-keygen -t ed25519 -C "sman5479@seleksi.smaafbs.sch.id"
cat ~/.ssh/id_ed25519.pub
```

Tambahkan output public key ke GitHub repository:

`Settings -> Deploy keys -> Add deploy key -> Allow read access`

Setelah itu test:

```bash
ssh -T git@github.com
```

## Catatan penting

- `.env`, `.env.production`, `vendor`, `node_modules`, file upload, dan storage runtime tidak ikut commit.
- Aset `public/build` ikut commit supaya server tetap bisa live meskipun Node.js tidak tersedia.
- File upload lama dari lokal/server perlu dimigrasikan terpisah ke `storage/app/public` jika memang dibutuhkan.
