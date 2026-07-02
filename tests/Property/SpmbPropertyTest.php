<?php

namespace Tests\Property;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Services\SpmbService;
use Eris\Generators;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Property 13: Progres Tahapan SPMB Monoton
 * Memvalidasi: Kebutuhan 0.4, 0.5
 */
class SpmbPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    private SpmbService $spmbService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->spmbService = new SpmbService();
    }

    /**
     * Property 13: Progres Tahapan SPMB Monoton
     * Untuk setiap peserta, tahap saat ini harus selalu >= tahap sebelumnya
     * dan tidak pernah mundur kecuali ada reset eksplisit
     */
    public function test_tahapan_progres_monoton(): void
    {
        $this->forAll(
            Generators::choose(1, 7), // tahap awal
            Generators::choose(1, 7)  // tahap tujuan
        )->then(function ($tahapAwal, $tahapTujuan) {
            // Buat peserta dengan tahapan awal
            $peserta = Peserta::factory()->create();
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => $tahapAwal,
                'tahap_1_selesai' => true,
            ]);

            $tahapanSebelum = $peserta->fresh()->tahapanSpmb->tahap_saat_ini;

            // Selesaikan tahapan
            if ($tahapTujuan >= $tahapAwal) {
                $this->spmbService->selesaikanTahapan($peserta, $tahapTujuan);
                $tahapanSesudah = $peserta->fresh()->tahapanSpmb->tahap_saat_ini;

                // Tahapan harus monoton naik atau tetap
                $this->assertGreaterThanOrEqual(
                    $tahapanSebelum,
                    $tahapanSesudah,
                    "Tahapan harus monoton: sebelum={$tahapanSebelum}, sesudah={$tahapanSesudah}"
                );
            }

            return true;
        });
    }

    /**
     * Property: Tahapan yang sudah selesai tidak bisa di-unset
     */
    public function test_tahapan_selesai_tidak_bisa_mundur(): void
    {
        $this->forAll(
            Generators::choose(1, 6) // tahap yang akan diselesaikan
        )->then(function ($tahap) {
            $peserta = Peserta::factory()->create();
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 1,
                'tahap_1_selesai' => true,
            ]);

            // Selesaikan tahapan
            $this->spmbService->selesaikanTahapan($peserta, $tahap);
            
            // Verifikasi tahapan sudah selesai
            $this->assertTrue(
                $this->spmbService->cekTahapanSelesai($peserta->fresh(), $tahap),
                "Tahapan {$tahap} harus sudah selesai"
            );

            return true;
        });
    }

    /**
     * Property: Semua tahapan sebelumnya harus selesai untuk akses tahapan berikutnya
     */
    public function test_urutan_tahapan_sequential(): void
    {
        $peserta = Peserta::factory()->create();
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 1,
            'tahap_1_selesai' => true,
        ]);

        // Selesaikan tahapan secara berurutan
        for ($tahap = 2; $tahap <= 7; $tahap++) {
            $this->spmbService->selesaikanTahapan($peserta, $tahap);
            
            $pesertaFresh = $peserta->fresh();
            
            // Verifikasi semua tahapan sebelumnya sudah selesai
            for ($i = 1; $i <= $tahap; $i++) {
                $this->assertTrue(
                    $this->spmbService->cekTahapanSelesai($pesertaFresh, $i),
                    "Tahapan {$i} harus sudah selesai setelah menyelesaikan tahap {$tahap}"
                );
            }
        }
    }
}
