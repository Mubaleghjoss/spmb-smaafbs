<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PesertaMobileNavigationTest extends TestCase
{
    public function test_sheet_menu_lainnya_memisahkan_semua_tahap_spmb(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/peserta.blade.php');

        $this->assertIsString($layout);
        $this->assertStringContainsString('Tahap 1 — Dashboard', $layout);
        $this->assertStringContainsString('Tahap 2 — Formulir', $layout);
        $this->assertStringContainsString('Tahap 3 — Pembayaran Formulir', $layout);
        $this->assertStringContainsString('Tahap 4 — Tes Online', $layout);
        $this->assertStringContainsString('Tahap 5 — Wawancara', $layout);
        $this->assertStringContainsString('Tahap 6 — Pelunasan', $layout);
        $this->assertStringContainsString('Tahap 7 — Kelulusan', $layout);
        $this->assertStringContainsString("route('peserta.pembayaran.formulir')", $layout);
        $this->assertStringContainsString("route('peserta.pembayaran.pelunasan')", $layout);
    }
}
