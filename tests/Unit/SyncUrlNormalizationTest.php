<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\SyncController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SyncUrlNormalizationTest extends TestCase
{
    public function test_common_live_domain_typo_is_normalized(): void
    {
        $this->assertSame(
            'https://seleksi.smaafbs.sch.id',
            $this->normalize('https://selesksi.smaafbs.sch.id')
        );
    }

    public function test_sync_endpoint_path_is_removed_from_base_url(): void
    {
        $this->assertSame(
            'https://seleksi.smaafbs.sch.id',
            $this->normalize('https://selesksi.smaafbs.sch.id/api/sync/export/')
        );
    }

    public function test_endpoint_builder_uses_single_sync_path(): void
    {
        $this->assertSame(
            'https://seleksi.smaafbs.sch.id/api/sync/export',
            $this->endpoint('https://seleksi.smaafbs.sch.id', 'export')
        );
    }

    public function test_sync_tables_include_master_data_before_result_data(): void
    {
        $tables = $this->syncTables();

        foreach ([
            'pengaturan',
            'pengguna',
            'tahun_ajaran',
            'gelombang_pendaftaran',
            'grup',
            'topik',
            'soal',
            'jawaban',
            'tes',
            'tes_soal',
            'grup_tes',
            'psikotes_kepribadian_config',
            'gaya_belajar_config',
            'mbti_config',
            'profiling_config',
            'sesi_tes',
            'jawaban_peserta',
        ] as $table) {
            $this->assertContains($table, $tables);
        }

        $positions = array_flip($tables);

        $this->assertLessThan($positions['peserta'], $positions['tahun_ajaran']);
        $this->assertLessThan($positions['peserta'], $positions['gelombang_pendaftaran']);
        $this->assertLessThan($positions['tes_soal'], $positions['tes']);
        $this->assertLessThan($positions['tes_soal'], $positions['soal']);
        $this->assertLessThan($positions['sesi_tes'], $positions['tes']);
        $this->assertLessThan($positions['jawaban_peserta'], $positions['sesi_tes']);
        $this->assertLessThan($positions['jawaban_peserta'], $positions['soal']);
        $this->assertLessThan($positions['jawaban_peserta'], $positions['jawaban']);
    }

    private function normalize(string $url): string
    {
        return $this->callPrivateMethod('normalisasiSyncServerUrl', [$url]);
    }

    private function endpoint(string $serverUrl, string $aksi): string
    {
        return $this->callPrivateMethod('syncEndpoint', [$serverUrl, $aksi]);
    }

    /**
     * @return array<int, string>
     */
    private function syncTables(): array
    {
        $reflection = new \ReflectionProperty(SyncController::class, 'syncTables');
        $reflection->setAccessible(true);

        return $reflection->getValue(new SyncController());
    }

    /**
     * @param array<int, mixed> $parameters
     */
    private function callPrivateMethod(string $method, array $parameters): mixed
    {
        $reflection = new ReflectionMethod(SyncController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(new SyncController(), $parameters);
    }
}
