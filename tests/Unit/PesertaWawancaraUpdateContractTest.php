<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PesertaWawancaraUpdateContractTest extends TestCase
{
    private function read(string $relative): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/' . $relative);
    }

    public function test_participant_wawancara_page_explicitly_supports_updates_after_submission(): void
    {
        $view = $this->read('resources/views/peserta/wawancara-info.blade.php');
        $controller = $this->read('app/Http/Controllers/Peserta/DashboardSpmbController.php');

        $this->assertStringContainsString('Data yang sudah dikirim tetap bisa diperbarui', $view);
        $this->assertStringContainsString('Ganti Rekaman', $view);
        $this->assertStringContainsString('Simpan Perubahan Rekaman', $view);
        $this->assertStringContainsString("case 6:", $controller);
        $this->assertStringContainsString("hasFile('file_voice_quran')", $controller);
        $this->assertStringContainsString('$wawancara->file_voice_quran = $path', $controller);
    }

    public function test_participant_wawancara_update_has_no_completion_lock_guard(): void
    {
        $controller = $this->read('app/Http/Controllers/Peserta/DashboardSpmbController.php');
        $method = substr($controller, strpos($controller, 'public function simpanWawancara'));

        $this->assertStringNotContainsString('$peserta->tahapanSpmb?->tahap_5_selesai', $method);
        $this->assertStringNotContainsString("hasil_wawancara === 'lulus'", $method);
    }
}
