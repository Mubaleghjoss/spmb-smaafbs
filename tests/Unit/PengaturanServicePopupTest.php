<?php

namespace Tests\Unit;

use App\Services\PengaturanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengaturanServicePopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_ambil_spmb_exposes_commitment_popup_settings(): void
    {
        app(PengaturanService::class)->simpanBanyak([
            'popup_persetujuan_aktif' => 1,
            'popup_persetujuan_gambar' => 'uploads/popup-komitmen.jpg',
        ]);

        $spmb = app(PengaturanService::class)->ambilSpmb();

        $this->assertArrayHasKey('popup_persetujuan_aktif', $spmb);
        $this->assertTrue((bool) $spmb['popup_persetujuan_aktif']);
        $this->assertSame('uploads/popup-komitmen.jpg', $spmb['popup_persetujuan_gambar']);
    }
}
