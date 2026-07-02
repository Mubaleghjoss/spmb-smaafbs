<?php

namespace App\Enums;

/**
 * Enum Status Verifikasi
 * Kebutuhan: 15.2
 */
enum StatusVerifikasi: string
{
    case DRAFT = 'draft';
    case MENUNGGU = 'menunggu';
    case TERVERIFIKASI = 'terverifikasi';
    case DITOLAK = 'ditolak';

    /**
     * Ambil label status dalam bahasa Indonesia
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::MENUNGGU => 'Menunggu Verifikasi',
            self::TERVERIFIKASI => 'Terverifikasi',
            self::DITOLAK => 'Ditolak',
        };
    }

    /**
     * Ambil warna badge untuk status
     */
    public function warnaBadge(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::MENUNGGU => 'warning',
            self::TERVERIFIKASI => 'success',
            self::DITOLAK => 'danger',
        };
    }

    /**
     * Ambil icon untuk status
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'bi-file-earmark',
            self::MENUNGGU => 'bi-hourglass-split',
            self::TERVERIFIKASI => 'bi-check-circle',
            self::DITOLAK => 'bi-x-circle',
        };
    }
}
