<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * KompresGambarService
 *
 * Menyimpan file upload ke disk 'public' dengan KOMPRESI otomatis untuk GAMBAR
 * (JPEG/PNG) menggunakan ekstensi GD bawaan PHP — tanpa dependensi tambahan.
 *
 * Perilaku:
 *  - Gambar: auto-orient (EXIF), resize bila sisi terpanjang > maxDimensi,
 *    lalu re-encode JPEG kualitas ~75. Hasil biasanya turun 70-90% ukuran.
 *  - PDF / non-gambar: disimpan apa adanya (tidak dikompres).
 *  - FALLBACK AMAN: bila GD tidak ada atau proses gagal, file asli tetap
 *    disimpan utuh sehingga upload tidak pernah gagal karena kompresi.
 */
class KompresGambarService
{
    private int $maxDimensi = 1600;   // px, sisi terpanjang
    private int $kualitas = 75;       // kualitas JPEG hasil

    /**
     * Simpan file upload (dengan kompresi bila gambar) ke folder pada disk public.
     * Mengembalikan path relatif (sama seperti UploadedFile::store()).
     */
    public function simpan(UploadedFile $file, string $folder): string
    {
        $folder = trim($folder, '/');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $mime = (string) $file->getMimeType();

        $adalahGambar = str_starts_with($mime, 'image/')
            && in_array($ext, ['jpg', 'jpeg', 'png'], true)
            && extension_loaded('gd');

        // Non-gambar (PDF dll) atau GD tidak tersedia -> simpan apa adanya.
        if (! $adalahGambar) {
            return $file->store($folder, 'public');
        }

        try {
            $binari = $this->kompres($file->getRealPath(), $mime);
            if ($binari === null) {
                return $file->store($folder, 'public'); // fallback
            }

            $namaFile = $folder . '/' . Str::random(40) . '.jpg';
            Storage::disk('public')->put($namaFile, $binari);

            // Jaga-jaga: bila hasil kompresi malah lebih besar dari asli, pakai asli.
            $ukuranAsli = $file->getSize();
            if ($ukuranAsli && strlen($binari) > $ukuranAsli) {
                Storage::disk('public')->delete($namaFile);
                return $file->store($folder, 'public');
            }

            return $namaFile;
        } catch (\Throwable $e) {
            report($e);
            return $file->store($folder, 'public'); // fallback aman
        }
    }

    /**
     * Kompres gambar dari path sumber -> string biner JPEG. Null bila gagal.
     */
    private function kompres(string $sumber, string $mime): ?string
    {
        if (! $sumber || ! is_file($sumber)) {
            return null;
        }

        $img = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($sumber),
            'image/png' => @imagecreatefrompng($sumber),
            default => false,
        };
        if (! $img) {
            return null;
        }

        // Auto-orient dari EXIF (hanya JPEG yang punya EXIF orientation)
        if (in_array($mime, ['image/jpeg', 'image/jpg'], true) && function_exists('exif_read_data')) {
            $img = $this->autoOrient($img, $sumber);
        }

        $lebar = imagesx($img);
        $tinggi = imagesy($img);
        if ($lebar < 1 || $tinggi < 1) {
            imagedestroy($img);
            return null;
        }

        // Resize bila melebihi batas
        $maks = max($lebar, $tinggi);
        if ($maks > $this->maxDimensi) {
            $skala = $this->maxDimensi / $maks;
            $lebarBaru = (int) round($lebar * $skala);
            $tinggiBaru = (int) round($tinggi * $skala);
            $baru = imagecreatetruecolor($lebarBaru, $tinggiBaru);
            // Latar putih (PNG transparan -> putih, cocok utk dokumen/bukti)
            $putih = imagecolorallocate($baru, 255, 255, 255);
            imagefilledrectangle($baru, 0, 0, $lebarBaru, $tinggiBaru, $putih);
            imagecopyresampled($baru, $img, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
            imagedestroy($img);
            $img = $baru;
        } elseif ($mime === 'image/png') {
            // PNG tak diresize: tetap flatten ke latar putih agar aman jadi JPEG
            $baru = imagecreatetruecolor($lebar, $tinggi);
            $putih = imagecolorallocate($baru, 255, 255, 255);
            imagefilledrectangle($baru, 0, 0, $lebar, $tinggi, $putih);
            imagecopy($baru, $img, 0, 0, 0, 0, $lebar, $tinggi);
            imagedestroy($img);
            $img = $baru;
        }

        ob_start();
        imagejpeg($img, null, $this->kualitas);
        $data = ob_get_clean();
        imagedestroy($img);

        return $data !== false && $data !== '' ? $data : null;
    }

    /**
     * Putar gambar sesuai EXIF orientation (agar tidak miring/terbalik).
     * @param \GdImage $img
     * @return \GdImage
     */
    private function autoOrient($img, string $sumber)
    {
        try {
            $exif = @exif_read_data($sumber);
        } catch (\Throwable $e) {
            return $img;
        }
        if (empty($exif['Orientation'])) {
            return $img;
        }
        switch ((int) $exif['Orientation']) {
            case 3: $img = imagerotate($img, 180, 0); break;
            case 6: $img = imagerotate($img, -90, 0); break;
            case 8: $img = imagerotate($img, 90, 0); break;
        }
        return $img;
    }
}
