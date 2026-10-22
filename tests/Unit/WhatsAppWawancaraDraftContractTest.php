<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WhatsAppWawancaraDraftContractTest extends TestCase
{
    private function read(string $relative): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/' . $relative);
    }

    public function test_wawancara_whatsapp_service_builds_structured_drafts_for_valid_parent_contacts(): void
    {
        $service = $this->read('app/Services/WhatsAppWawancaraService.php');
        $controller = $this->read('app/Http/Controllers/Admin/VerifikasiSpmbController.php');
        $view = $this->read('resources/views/admin/verifikasi/form-wawancara.blade.php');

        $this->assertStringContainsString('class WhatsAppWawancaraService', $service);
        $this->assertStringContainsString('telepon_ayah', $service);
        $this->assertStringContainsString('telepon_ibu', $service);
        $this->assertStringContainsString('file_voice_quran', $service);
        $this->assertStringContainsString('Catatan tambahan dari Tim SPMB', $service);
        $this->assertStringContainsString('WhatsAppWawancaraService', $controller);
        $this->assertStringContainsString('whatsappDrafts', $controller);
        $this->assertStringContainsString('Kirim WA', $view);
        $this->assertStringContainsString('catatan-wa-wawancara', $view);
        $this->assertStringContainsString('wa.me', $view);
    }

    public function test_wawancara_whatsapp_draft_is_reviewable_and_not_sent_automatically(): void
    {
        $service = $this->read('app/Services/WhatsAppWawancaraService.php');
        $view = $this->read('resources/views/admin/verifikasi/form-wawancara.blade.php');

        $this->assertStringContainsString('Rekaman bacaan Al-Qur', $service);
        $this->assertStringContainsString('type="button"', $view);
        $this->assertStringContainsString('encodeURIComponent', $view);
        $this->assertStringContainsString('window.open', $view);
    }
}
