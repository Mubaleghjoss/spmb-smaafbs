<?php

namespace App\Enums;

/**
 * Enum Tahapan SPMB
 * Kebutuhan: 0.3
 * Urutan: 1. Buat Akun, 2. Isi Formulir, 3. Bayar Formulir, 4. Tes Online, 5. Wawancara, 6. Bayar Pertama, 7. Diterima
 */
enum TahapanSpmb: int
{
    case BUAT_AKUN = 1;
    case ISI_FORMULIR = 2;
    case BAYAR_FORMULIR = 3;
    case TES_ONLINE = 4;
    case WAWANCARA = 5;
    case BAYAR_PERTAMA = 6;
    case RESMI_DITERIMA = 7;

    /**
     * Ambil label tahapan dalam bahasa Indonesia
     */
    public function label(): string
    {
        return match ($this) {
            self::BUAT_AKUN => 'Buat Akun & Login',
            self::ISI_FORMULIR => 'Isi Formulir SPMB',
            self::BAYAR_FORMULIR => 'Transfer dan Upload Bukti Pembayaran Formulir',
            self::TES_ONLINE => 'Tes Online',
            self::WAWANCARA => 'Wawancara & Verifikasi Berkas',
            self::BAYAR_PERTAMA => 'Upload Bukti Pembayaran Pertama',
            self::RESMI_DITERIMA => 'Pengumuman Kelulusan',
        };
    }

    /**
     * Ambil deskripsi tahapan
     */
    public function deskripsi(): string
    {
        return match ($this) {
            self::BUAT_AKUN => 'Daftar akun dan login ke sistem SPMB',
            self::ISI_FORMULIR => 'Lengkapi formulir pendaftaran peserta didik baru',
            self::BAYAR_FORMULIR => 'Transfer biaya formulir dan upload bukti pembayaran',
            self::TES_ONLINE => 'Ikuti tes seleksi online sesuai jadwal',
            self::WAWANCARA => 'Hadiri wawancara dan verifikasi berkas',
            self::BAYAR_PERTAMA => 'Upload bukti pembayaran biaya pendidikan pertama',
            self::RESMI_DITERIMA => 'Lihat hasil kelulusan seleksi SPMB',
        };
    }

    /**
     * Ambil icon untuk tahapan
     */
    public function icon(): string
    {
        return match ($this) {
            self::BUAT_AKUN => 'bi-person-plus',
            self::ISI_FORMULIR => 'bi-file-earmark-text',
            self::BAYAR_FORMULIR => 'bi-credit-card',
            self::TES_ONLINE => 'bi-laptop',
            self::WAWANCARA => 'bi-people',
            self::BAYAR_PERTAMA => 'bi-wallet2',
            self::RESMI_DITERIMA => 'bi-mortarboard',
        };
    }

    /**
     * Ambil semua tahapan sebagai array
     */
    public static function semuaTahapan(): array
    {
        return array_map(fn ($case) => [
            'nilai' => $case->value,
            'label' => $case->label(),
            'deskripsi' => $case->deskripsi(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
