<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

class UjianHasilMbtiTest extends TestCase
{
    public function test_hasil_mbti_tanpa_data_hasil_menampilkan_pesan_bukan_error(): void
    {
        $view = View::make('ujian.hasil', [
            'hasil' => [
                'is_mbti' => true,
                'mbti' => null,
                'mbti_deskripsi' => null,
            ],
            'tes' => (object) [
                'nama' => 'Tes MBTI',
                'tampilkan_nilai' => false,
                'tampilkan_pembahasan' => false,
            ],
            'peserta' => (object) [
                'nama' => 'Peserta Uji',
                'nomor_pendaftaran' => 'SPMB-TEST-00001',
                'tahapanSpmb' => null,
            ],
        ]);

        $this->assertStringContainsString(
            'Hasil MBTI belum tersedia',
            $view->render(),
        );
    }
}
