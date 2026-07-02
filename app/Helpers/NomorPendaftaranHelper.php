<?php

namespace App\Helpers;

use App\Models\Peserta;

/**
 * Helper untuk generate nomor pendaftaran
 * Kebutuhan: 0.2
 * Format: SPMB-YYYY-XXXXX
 */
class NomorPendaftaranHelper
{
    /**
     * Generate nomor pendaftaran unik
     */
    public static function generate(): string
    {
        $tahun = date('Y');
        $prefix = "SPMB-{$tahun}-";
        
        // Ambil nomor terakhir untuk tahun ini
        $terakhir = Peserta::withTrashed()
            ->where('nomor_pendaftaran', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(nomor_pendaftaran, -5) AS UNSIGNED) DESC')
            ->first();
        
        if ($terakhir) {
            // Ekstrak nomor urut dari nomor pendaftaran terakhir
            $nomorUrut = (int) substr($terakhir->nomor_pendaftaran, -5);
            $nomorUrut++;
        } else {
            $nomorUrut = 1;
        }
        
        // Format dengan padding 5 digit
        $nomorUrutFormatted = str_pad($nomorUrut, 5, '0', STR_PAD_LEFT);
        
        return $prefix . $nomorUrutFormatted;
    }

    /**
     * Validasi format nomor pendaftaran
     */
    public static function validasi(string $nomor): bool
    {
        return preg_match('/^SPMB-\d{4}-\d{5}$/', $nomor) === 1;
    }

    /**
     * Ekstrak tahun dari nomor pendaftaran
     */
    public static function ekstrakTahun(string $nomor): ?int
    {
        if (!self::validasi($nomor)) {
            return null;
        }
        
        return (int) substr($nomor, 5, 4);
    }

    /**
     * Ekstrak nomor urut dari nomor pendaftaran
     */
    public static function ekstrakNomorUrut(string $nomor): ?int
    {
        if (!self::validasi($nomor)) {
            return null;
        }
        
        return (int) substr($nomor, -5);
    }
}
