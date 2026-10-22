<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PesertaDashboardSectionsContractTest extends TestCase
{
    private function dashboardView(): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/resources/views/peserta/dashboard.blade.php');
    }

    public function test_informasi_biaya_spmb_dan_akun_saya_collapsed_secara_default(): void
    {
        $view = $this->dashboardView();

        $this->assertStringContainsString('id="informasi-biaya-spmb"', $view);
        $this->assertStringContainsString('data-bs-target="#informasi-biaya-spmb"', $view);
        $this->assertStringContainsString('aria-expanded="false"', $view);
        $this->assertStringContainsString('id="akun-saya"', $view);
        $this->assertStringContainsString('data-bs-target="#akun-saya"', $view);
    }

    public function test_progress_tahapan_diletakkan_setelah_panel_ringkas(): void
    {
        $view = $this->dashboardView();

        $this->assertStringContainsString('Progress Tahapan SPMB', $view);
        $this->assertLessThan(
            strpos($view, 'Progress Tahapan SPMB'),
            strpos($view, 'id="akun-saya"'),
            'Progress harus berada setelah panel Akun Saya agar menjadi fokus utama setelah login.'
        );
    }
}
