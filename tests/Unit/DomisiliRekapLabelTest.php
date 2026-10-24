<?php

namespace Tests\Unit;

use App\Services\PesertaService;
use PHPUnit\Framework\TestCase;

class DomisiliRekapLabelTest extends TestCase
{
    public function test_domisili_rekap_uses_simple_public_labels(): void
    {
        self::assertSame('Tangerang Kota', PesertaService::labelDomisiliRekap('dalam_tangerang_kota'));
        self::assertSame('Luar Tangerang Kota (Lainnya)', PesertaService::labelDomisiliRekap('luar_tangerang_kota'));
        self::assertSame('Belum Diisi', PesertaService::labelDomisiliRekap(null));
        self::assertSame('Belum Diisi', PesertaService::labelDomisiliRekap(''));
    }
}
