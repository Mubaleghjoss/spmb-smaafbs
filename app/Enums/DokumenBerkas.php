<?php

namespace App\Enums;

/**
 * Enum Dokumen Berkas untuk Verifikasi
 * Kebutuhan: 14.4
 */
enum DokumenBerkas: string
{
    case AKTA_KELAHIRAN = 'akta_kelahiran';
    case KARTU_KELUARGA = 'kartu_keluarga';
    case IJAZAH = 'ijazah';
    case SKHUN = 'skhun';
    case RAPOR = 'rapor';
    case PAS_FOTO = 'pas_foto';
    case SURAT_KETERANGAN_SEHAT = 'surat_keterangan_sehat';
    case SURAT_KELAKUAN_BAIK = 'surat_kelakuan_baik';

    /**
     * Ambil label dokumen dalam bahasa Indonesia
     */
    public function label(): string
    {
        return match ($this) {
            self::AKTA_KELAHIRAN => 'Akta Kelahiran',
            self::KARTU_KELUARGA => 'Kartu Keluarga',
            self::IJAZAH => 'Ijazah/SKL',
            self::SKHUN => 'SKHUN/Transkrip Nilai',
            self::RAPOR => 'Rapor Semester 1-5',
            self::PAS_FOTO => 'Pas Foto 3x4',
            self::SURAT_KETERANGAN_SEHAT => 'Surat Keterangan Sehat',
            self::SURAT_KELAKUAN_BAIK => 'Surat Kelakuan Baik',
        };
    }

    /**
     * Cek apakah dokumen wajib
     */
    public function wajib(): bool
    {
        return match ($this) {
            self::AKTA_KELAHIRAN, 
            self::KARTU_KELUARGA, 
            self::IJAZAH, 
            self::PAS_FOTO => true,
            default => false,
        };
    }

    /**
     * Ambil semua dokumen sebagai array
     */
    public static function semuaDokumen(): array
    {
        return array_map(fn ($case) => [
            'kode' => $case->value,
            'label' => $case->label(),
            'wajib' => $case->wajib(),
        ], self::cases());
    }

    /**
     * Ambil dokumen wajib saja
     */
    public static function dokumenWajib(): array
    {
        return array_filter(
            self::cases(),
            fn ($case) => $case->wajib()
        );
    }
}
