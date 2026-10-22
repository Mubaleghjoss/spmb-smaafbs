<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PesertaDashboardInterviewStepLinksContractTest extends TestCase
{
    public function test_stage_five_progress_items_link_to_the_matching_interview_step(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/peserta/dashboard.blade.php');

        $this->assertStringContainsString("route('peserta.wawancara.info') . '#step'", $view);
        $this->assertStringContainsString('href="{{ route(\'peserta.wawancara.info\') . \'#step\' . $nomorLangkah }}"', $view);
        $this->assertStringContainsString('aria-label="Buka {{ $langkah[\'label\'] }}"', $view);
    }

    public function test_interview_page_exposes_step_targets(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/peserta/wawancara-info.blade.php');

        foreach (range(1, 6) as $step) {
            $this->assertStringContainsString('id="step' . $step . '"', $view);
        }
    }
}
