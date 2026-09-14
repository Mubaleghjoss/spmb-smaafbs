<?php

namespace Tests\Unit;

use App\Models\FormulirSpmb;
use App\Services\BiayaSpmbService;
use Tests\TestCase;

class BiayaSpmbServiceTest extends TestCase
{
    public function test_memilih_tarif_dan_gambar_dalam_tangerang_kota(): void
    {
        $formulir = new FormulirSpmb(['domisili_biaya' => FormulirSpmb::DOMISILI_DALAM_TANGERANG_KOTA]);
        $spmb = [
            'biaya_formulir_dalam_kota' => 150000,
            'biaya_total_dalam_kota' => 5000000,
            'gambar_rincian_biaya_dalam_kota' => 'biaya/dalam.png',
        ];

        $biaya = app(BiayaSpmbService::class)->untukFormulir($spmb, $formulir);

        $this->assertSame(150000, $biaya['formulir']);
        $this->assertSame(5000000, $biaya['total']);
        $this->assertSame('biaya/dalam.png', $biaya['gambar']);
        $this->assertSame('Dalam Tangerang Kota', $biaya['label_domisili']);
    }

    public function test_luar_kota_memerlukan_nama_daerah_dan_memilih_tarif_luar(): void
    {
        $formulir = new FormulirSpmb([
            'domisili_biaya' => FormulirSpmb::DOMISILI_LUAR_TANGERANG_KOTA,
            'nama_daerah_luar' => 'Serang',
        ]);
        $spmb = [
            'biaya_formulir_luar_kota' => 200000,
            'biaya_total_luar_kota' => 6000000,
            'gambar_rincian_biaya_luar_kota' => 'biaya/luar.png',
        ];

        $biaya = app(BiayaSpmbService::class)->untukFormulir($spmb, $formulir);

        $this->assertSame(200000, $biaya['formulir']);
        $this->assertSame(6000000, $biaya['total']);
        $this->assertSame('biaya/luar.png', $biaya['gambar']);
        $this->assertTrue(app(BiayaSpmbService::class)->domisiliSudahLengkap($formulir));
    }

    public function test_domisili_belum_dipilih_tidak_memiliki_tarif(): void
    {
        $biaya = app(BiayaSpmbService::class)->untukFormulir([], new FormulirSpmb);

        $this->assertFalse($biaya['lengkap']);
        $this->assertNull($biaya['formulir']);
    }
}
