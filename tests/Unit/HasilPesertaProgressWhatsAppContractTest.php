<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HasilPesertaProgressWhatsAppContractTest extends TestCase
{
    public function test_detail_hasil_menyiapkan_tes_belum_dikerjakan_dan_tautan_whatsapp(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/hasil/detail-peserta-rekap.blade.php');
        $controller = file_get_contents(dirname(__DIR__, 2) . '/app/Http/Controllers/Admin/HasilController.php');

        self::assertStringContainsString("'tesBelumDikerjakan'", $controller);
        self::assertStringContainsString("'tesSudahDikerjakan'", $controller);
        self::assertStringContainsString('$tesBelumDikerjakan', $view);
        self::assertStringContainsString('$tesSudahDikerjakan', $view);
        self::assertStringContainsString('https://wa.me/', $view);
        self::assertStringContainsString('Tes yang sudah dikerjakan', $view);
        self::assertStringContainsString('Tes yang belum dikerjakan', $view);
        self::assertStringContainsString('Jika ada kendala, silakan kabari kami', $view);
    }
}
