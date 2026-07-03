<?php

namespace Tests\Unit;

use App\Models\JawabanPeserta;
use PHPUnit\Framework\TestCase;

class JawabanPesertaTest extends TestCase
{
    public function testForeignKeyJawabanDinormalisasiMenjadiInteger(): void
    {
        $jawaban = new JawabanPeserta();
        $jawaban->setRawAttributes([
            'sesi_tes_id' => '65',
            'soal_id' => '228',
            'jawaban_id' => '692',
        ]);

        $this->assertSame(65, $jawaban->sesi_tes_id);
        $this->assertSame(228, $jawaban->soal_id);
        $this->assertSame(692, $jawaban->jawaban_id);
    }

    public function testJawabanGandaTetapDinormalisasiSaatDinilai(): void
    {
        $jawaban = new JawabanPeserta([
            'jawaban_ganda' => ['12', '10'],
        ]);

        $ids = collect($jawaban->jawaban_ganda)
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $this->assertSame([10, 12], $ids);
    }
}
