<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class VerifikasiTesLinkContractTest extends TestCase
{
    private function viewSource(): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/verifikasi/index.blade.php');
    }

    public function test_tombol_verifikasi_tes_membuka_detail_hasil_peserta(): void
    {
        $source = $this->viewSource();

        self::assertStringContainsString('route(\'admin.hasil.detail-peserta-rekap\', $p->id)', $source);
        self::assertStringContainsString('{{ $sesiTes ? \'Verifikasi Tes\' : \'Lihat Hasil Tes\' }}', $source);
    }

    public function test_tombol_verifikasi_tes_hanya_muncul_untuk_sesi_yang_masih_menunggu(): void
    {
        $source = $this->viewSource();

        self::assertStringContainsString("status_verifikasi_tes", $source);
        self::assertStringContainsString("'menunggu'", $source);
        self::assertStringContainsString('$s->status_verifikasi_tes', $source);
    }
}
