<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GrupWaPenandaTahap4ContractTest extends TestCase
{
    public function test_kontrak_penanda_grup_wa_tahap_4_tersedia(): void
    {
        $root = dirname(__DIR__, 2);

        $migrationFiles = glob($root . '/database/migrations/*grup_wa*') ?: [];
        $migration = implode("\n", array_map(static fn (string $path): string => file_get_contents($path), $migrationFiles));
        $controller = file_get_contents($root . '/app/Http/Controllers/Admin/VerifikasiSpmbController.php');
        $routes = file_get_contents($root . '/routes/web.php');
        $view = file_get_contents($root . '/resources/views/admin/verifikasi/grup-wa.blade.php');
        $model = file_get_contents($root . '/app/Models/TahapanSpmb.php');

        self::assertNotEmpty($migrationFiles, 'Migration penanda grup WA belum tersedia.');
        self::assertStringContainsString('grup_wa_status', $migration);
        self::assertStringContainsString('grup_wa_ditandai_pada', $migration);
        self::assertStringContainsString('grup_wa_ditandai_oleh', $migration);
        self::assertStringContainsString('grup_wa_catatan', $migration);
        self::assertStringContainsString("grup_wa_status", $model);
        self::assertStringContainsString("grup_wa_ditandai_oleh", $model);
        self::assertStringContainsString("verifikasi.grup-wa.tandai", $routes);
        self::assertStringContainsString("verifikasi.grup-wa", $routes);
        self::assertStringContainsString('grup_wa_status', $controller);
        self::assertStringContainsString('Sudah Masuk Grup WA', $view);
        self::assertStringContainsString('Belum Masuk Grup WA', $view);
        self::assertStringContainsString('Tandai Sudah Masuk', $view);
        self::assertStringContainsString('checkbox', file_get_contents($root . '/resources/views/admin/verifikasi/index.blade.php'));
        self::assertStringContainsString('Sudah masuk Grup WA', file_get_contents($root . '/resources/views/admin/verifikasi/index.blade.php'));
    }
}
