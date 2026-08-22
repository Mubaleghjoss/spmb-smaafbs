<?php

/**
 * Generator ikon PWA untuk SPMB.
 * Membuat public/icons/icon-192.png, icon-512.png, dan maskable-512.png.
 *
 * Prioritas sumber:
 *   1. Logo branding (storage/app/public/<logo>) bila ada -> ditempel di tengah.
 *   2. Fallback: kotak warna tema + inisial nama singkat.
 *
 * Pemakaian: php scripts/generate-pwa-icons.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PengaturanService;

$svc = app(PengaturanService::class);
$branding = $svc->ambilBranding();

$warna = ltrim($branding['warna_primer'] ?? '#1a5f2a', '#');
if (strlen($warna) === 3) {
    $warna = $warna[0].$warna[0].$warna[1].$warna[1].$warna[2].$warna[2];
}
$r = hexdec(substr($warna, 0, 2));
$g = hexdec(substr($warna, 2, 2));
$b = hexdec(substr($warna, 4, 2));

$inisial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $branding['nama_singkat'] ?? 'SPMB'), 0, 4)) ?: 'SPMB';

$logoPath = null;
if (!empty($branding['logo'])) {
    $cand = storage_path('app/public/' . $branding['logo']);
    if (is_file($cand)) {
        $logoPath = $cand;
    }
}

$outDir = __DIR__ . '/../public/icons';
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

/**
 * Buat satu ikon ukuran $size. $padding untuk maskable (safe zone).
 */
function buatIkon(int $size, int $r, int $g, int $b, string $inisial, ?string $logoPath, string $file, float $padding = 0.0): void
{
    $im = imagecreatetruecolor($size, $size);
    imagealphablending($im, true);
    imagesavealpha($im, true);

    // Latar gradient sederhana (vertikal) dari warna tema ke sedikit lebih gelap
    for ($y = 0; $y < $size; $y++) {
        $t = $y / max(1, $size - 1);
        $rr = (int) max(0, $r * (1 - 0.25 * $t));
        $gg = (int) max(0, $g * (1 - 0.25 * $t));
        $bb = (int) max(0, $b * (1 - 0.25 * $t));
        $col = imagecolorallocate($im, $rr, $gg, $bb);
        imageline($im, 0, $y, $size, $y, $col);
    }

    if ($logoPath) {
        $src = @imagecreatefromstring(file_get_contents($logoPath));
        if ($src) {
            $sw = imagesx($src);
            $sh = imagesy($src);
            $area = (int) ($size * (1 - 2 * max(0.0, $padding)) * 0.72);
            $scale = min($area / $sw, $area / $sh);
            $dw = (int) ($sw * $scale);
            $dh = (int) ($sh * $scale);
            $dx = (int) (($size - $dw) / 2);
            $dy = (int) (($size - $dh) / 2);
            imagealphablending($src, true);
            imagecopyresampled($im, $src, $dx, $dy, 0, 0, $dw, $dh, $sw, $sh);
            imagedestroy($src);
        } else {
            $logoPath = null;
        }
    }

    if (!$logoPath) {
        // Tulis inisial di tengah
        $white = imagecolorallocate($im, 255, 255, 255);
        $fontSize = (int) ($size * 0.30);
        $font = 5; // built-in font, akan diperbesar manual
        // Gunakan imagestring diperbesar via temporary
        $tw = imagefontwidth($font) * strlen($inisial);
        $th = imagefontheight($font);
        $tmp = imagecreatetruecolor($tw, $th);
        imagesavealpha($tmp, true);
        $trans = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagefill($tmp, 0, 0, $trans);
        imagestring($tmp, $font, 0, 0, $inisial, $white);
        $scale = ($size * 0.6) / $tw;
        $dw = (int) ($tw * $scale);
        $dh = (int) ($th * $scale);
        imagecopyresampled($im, $tmp, (int) (($size - $dw) / 2), (int) (($size - $dh) / 2), 0, 0, $dw, $dh, $tw, $th);
        imagedestroy($tmp);
    }

    imagepng($im, $file);
    imagedestroy($im);
}

buatIkon(192, $r, $g, $b, $inisial, $logoPath, $outDir . '/icon-192.png');
buatIkon(512, $r, $g, $b, $inisial, $logoPath, $outDir . '/icon-512.png');
buatIkon(512, $r, $g, $b, $inisial, $logoPath, $outDir . '/maskable-512.png', 0.12);

echo "OK: ikon PWA dibuat di public/icons/\n";
echo "  - icon-192.png\n  - icon-512.png\n  - maskable-512.png\n";
echo "Sumber: " . ($logoPath ? "logo branding" : "warna tema + inisial '{$inisial}'") . "\n";
