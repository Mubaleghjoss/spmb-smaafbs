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
}
