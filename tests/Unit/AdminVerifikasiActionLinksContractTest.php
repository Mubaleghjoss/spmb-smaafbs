<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminVerifikasiActionLinksContractTest extends TestCase
{
    private function viewSource(): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/verifikasi/index.blade.php');
    }

    public function test_empty_stage_actions_are_links_to_their_verification_pages(): void
    {
        $view = $this->viewSource();

        self::assertStringContainsString("route('admin.verifikasi.formulir')", $view);
        self::assertStringContainsString("route('admin.verifikasi.pembayaran-formulir')", $view);
        self::assertStringContainsString('route(\'admin.hasil.detail-peserta-rekap\', $p->id)', $view);
        self::assertStringContainsString('route(\'admin.verifikasi.wawancara.form\', $p)', $view);
        self::assertStringContainsString("route('admin.verifikasi.pelunasan')", $view);
        self::assertStringContainsString("route('admin.verifikasi.kelulusan')", $view);
    }

    public function test_action_labels_are_not_rendered_as_non_clickable_button_spans(): void
    {
        $view = $this->viewSource();

        self::assertStringNotContainsString('><i class="bi bi-file-earmark-x me-1"></i>Belum Isi Formulir</span>', $view);
        self::assertStringNotContainsString('><i class="bi bi-credit-card me-1"></i>Belum Upload Bukti</span>', $view);
        self::assertStringNotContainsString('><i class="bi bi-journal-x me-1"></i>Belum Tes</span>', $view);
    }
}
