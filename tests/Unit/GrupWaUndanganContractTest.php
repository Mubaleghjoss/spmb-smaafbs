<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GrupWaUndanganContractTest extends TestCase
{
    public function test_pengaturan_menyediakan_kontrak_grup_wa(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root.'/app/Services/PengaturanService.php');
        $controller = file_get_contents($root.'/app/Http/Controllers/Admin/PengaturanController.php');
        $view = file_get_contents($root.'/resources/views/admin/pengaturan/spmb.blade.php');

        self::assertStringContainsString("'grup_wa_peserta_aktif'", $service);
        self::assertStringContainsString("'grup_wa_peserta_link'", $service);
        self::assertStringContainsString("'grup_wa_peserta_pesan'", $service);
        self::assertStringContainsString("'grup_wa_peserta_link'", $controller);
        self::assertStringContainsString('grup_wa_peserta_aktif', $view);
        self::assertStringContainsString('grup_wa_peserta_link', $view);
    }

    public function test_status_pembayaran_menampilkan_undangan_hanya_saat_terverifikasi_dan_aktif(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root.'/resources/views/peserta/pembayaran/status-formulir.blade.php');

        self::assertStringContainsString("$pembayaran->status === 'terverifikasi'", $view);
        self::assertStringContainsString('grup_wa_peserta_aktif', $view);
        self::assertStringContainsString('grup_wa_peserta_link', $view);
        self::assertStringContainsString('Salin Pesan WhatsApp', $view);
        self::assertStringContainsString('Gabung Grup WhatsApp', $view);
    }
}
