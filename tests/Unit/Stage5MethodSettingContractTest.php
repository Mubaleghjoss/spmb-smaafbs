<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class Stage5MethodSettingContractTest extends TestCase
{
    private function read(string $relative): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/'.$relative);
    }

    public function test_stage5_method_is_stored_and_configurable_from_admin(): void
    {
        $migration = $this->read('database/migrations/2026_10_22_000001_add_metode_pelaksanaan_to_jadwal_alur_periode_table.php');
        $model = $this->read('app/Models/JadwalAlurPeriode.php');
        $service = $this->read('app/Services/JadwalAlurService.php');
        $controller = $this->read('app/Http/Controllers/Admin/AlurJadwalController.php');
        $view = $this->read('resources/views/admin/pengaturan/alur-jadwal.blade.php');

        $this->assertStringContainsString("\$table->string('metode_pelaksanaan', 20)", $migration);
        $this->assertStringContainsString("'metode_pelaksanaan'", $model);
        $this->assertStringContainsString("'metode_pelaksanaan' => \$src->metode_pelaksanaan", $service);
        $this->assertStringContainsString("'tahap.*.metode_pelaksanaan' => 'nullable|in:online,offline'", $controller);
        $this->assertStringContainsString('name="tahap[{{ $tahap }}][metode_pelaksanaan]"', $view);
        $this->assertStringContainsString('Online', $view);
        $this->assertStringContainsString('Offline', $view);
        $this->assertStringContainsString("\$table->text('panduan_online')->nullable()", $this->read('database/migrations/2026_10_22_000002_add_panduan_online_to_jadwal_alur_periode_table.php'));
        $this->assertStringContainsString("'panduan_online'", $model);
        $this->assertStringContainsString("'panduan_online' => \$src->panduan_online", $service);
        $this->assertStringContainsString("'tahap.*.panduan_online' => 'nullable|string|max:2000'", $controller);
        $this->assertStringContainsString('name="tahap[{{ $tahap }}][panduan_online]"', $view);
    }

    public function test_participant_dashboard_renders_method_specific_stage5_guidance(): void
    {
        $service = $this->read('app/Services/JadwalAlurService.php');
        $dashboard = $this->read('resources/views/peserta/dashboard.blade.php');

        $this->assertStringContainsString("'metode_pelaksanaan' => \$src->metode_pelaksanaan", $service);
        $this->assertStringContainsString("\$hasil['metode_pelaksanaan'] = \$j['metode_pelaksanaan'] ?? 'online'", $service);
        $this->assertStringContainsString('Wawancara Tahap 5 dilaksanakan secara online', $dashboard);
        $this->assertStringContainsString('belum perlu membawa dokumen fisik', $dashboard);
        $this->assertStringContainsString('wawancara offline', $dashboard);
        $this->assertStringContainsString("\$item['panduan_online'] ?: ", $dashboard);
    }
}
