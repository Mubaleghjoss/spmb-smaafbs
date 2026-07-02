<?php
/**
 * File untuk menjalankan migration via browser
 * HAPUS FILE INI SETELAH SELESAI DIGUNAKAN!
 */

// Cek apakah sudah login sebagai admin
session_start();

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Cek autentikasi
if (!auth('pengguna')->check() || auth('pengguna')->user()->peran !== 'admin') {
    die('Akses ditolak. Silakan login sebagai admin terlebih dahulu.');
}

echo "<h2>Menjalankan Migration</h2>";
echo "<pre>";

try {
    // Jalankan migration
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
    
    if ($exitCode === 0) {
        echo "\n\n<strong style='color:green'>Migration berhasil!</strong>\n";
        echo "\n<strong style='color:red'>PENTING: Hapus file ini setelah selesai!</strong>";
    } else {
        echo "\n\n<strong style='color:red'>Migration gagal dengan kode: {$exitCode}</strong>";
    }
} catch (Exception $e) {
    echo "\n\n<strong style='color:red'>Error: " . $e->getMessage() . "</strong>";
}

echo "</pre>";
echo "<br><a href='/admin/dashboard'>Kembali ke Dashboard</a>";
