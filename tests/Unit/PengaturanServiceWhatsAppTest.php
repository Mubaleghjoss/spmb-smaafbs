<?php

namespace Tests\Unit;

use App\Services\PengaturanService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PengaturanServiceWhatsAppTest extends TestCase
{
    #[DataProvider('nomorIndonesia')]
    public function test_menormalkan_nomor_whatsapp_indonesia_ke_format_wa_me(string $input): void
    {
        $this->assertSame('6281234567890', PengaturanService::nomorWhatsAppInternasional($input));
    }

    public static function nomorIndonesia(): array
    {
        return [
            'awalan nol' => ['081234567890'],
            'awalan plus 62' => ['+62 812-3456-7890'],
            'awalan 62' => ['6281234567890'],
        ];
    }

    public function test_merender_template_pengingat_pembayaran_dengan_placeholder_peserta(): void
    {
        $pesan = PengaturanService::renderTemplatePengingatPembayaran(
            'Saya {nama} ({nomor_pendaftaran}) sudah transfer {nominal} untuk {jenis_pembayaran}.',
            'Ahmad',
            'SPMB-2026-00001',
            125000,
            'Biaya Formulir'
        );

        $this->assertSame('Saya Ahmad (SPMB-2026-00001) sudah transfer Rp 125.000 untuk Biaya Formulir.', $pesan);
    }
}
