<?php

namespace Tests\Property;

use App\Models\Peserta;
use App\Models\Pengguna;
use App\Models\TahapanSpmb;
use App\Models\LogTahapanSpmb;
use App\Services\MonitoringSpmbService;
use App\Services\SpmbService;
use Eris\Generators;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MonitoringSpmbPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    private MonitoringSpmbService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitoringService = new MonitoringSpmbService(new SpmbService());
    }

    /**
     * Property 17: Log Perubahan Tahapan Lengkap
     * Untuk setiap perubahan tahapan, sistem harus mencatat log dengan informasi lengkap.
     * **Memvalidasi: Kebutuhan 16.7**
     */
    public function test_log_perubahan_tahapan_lengkap(): void
    {
        $this->forAll(
            Generators::choose(1, 7),
            Generators::bool()
        )->then(function ($tahap, $selesai) {
            $peserta = Peserta::factory()->create();
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 1,
                'tahap_1_selesai' => true,
            ]);
            
            $admin = Pengguna::factory()->create(['peran' => 'admin']);
            
            $this->monitoringService->updateStatusTahapan($peserta, $tahap, $selesai, $admin);
            
            // Cek log dibuat
            $log = LogTahapanSpmb::where('peserta_id', $peserta->id)
                ->where('tahap', $tahap)
                ->latest()
                ->first();
            
            $this->assertNotNull($log);
            $this->assertEquals($peserta->id, $log->peserta_id);
            $this->assertEquals($tahap, $log->tahap);
            $this->assertEquals('manual_update', $log->aksi);
            $this->assertEquals($selesai, $log->status_baru);
            $this->assertEquals($admin->id, $log->admin_id);
            
            // Cleanup
            $log->delete();
            $peserta->tahapanSpmb->delete();
            $peserta->forceDelete();
            $admin->delete();
        });
    }

    /**
     * Property 18: Bulk Update Konsisten
     * Untuk setiap bulk update, semua peserta yang dipilih harus terupdate dengan status yang sama.
     * **Memvalidasi: Kebutuhan 16.6**
     */
    public function test_bulk_update_konsisten(): void
    {
        $this->forAll(
            Generators::choose(1, 7),
            Generators::bool()
        )->then(function ($tahap, $selesai) {
            // Buat beberapa peserta
            $pesertaIds = [];
            for ($i = 0; $i < 3; $i++) {
                $peserta = Peserta::factory()->create();
                TahapanSpmb::create([
                    'peserta_id' => $peserta->id,
                    'tahap_saat_ini' => 1,
                    'tahap_1_selesai' => true,
                ]);
                $pesertaIds[] = $peserta->id;
            }
            
            $admin = Pengguna::factory()->create(['peran' => 'admin']);
            
            $count = $this->monitoringService->bulkUpdateTahapan($pesertaIds, $tahap, $selesai, $admin);
            
            $this->assertEquals(3, $count);
            
            // Verifikasi semua peserta terupdate
            foreach ($pesertaIds as $id) {
                $peserta = Peserta::find($id);
                $kolom = "tahap_{$tahap}_selesai";
                $this->assertEquals($selesai, $peserta->tahapanSpmb->$kolom);
            }
            
            // Cleanup
            foreach ($pesertaIds as $id) {
                $peserta = Peserta::find($id);
                $peserta->tahapanSpmb->delete();
                $peserta->forceDelete();
            }
            $admin->delete();
        });
    }

    /**
     * Property: Statistik dashboard menghitung dengan benar
     */
    public function test_statistik_dashboard_menghitung_dengan_benar(): void
    {
        // Buat peserta di berbagai tahap
        for ($tahap = 1; $tahap <= 3; $tahap++) {
            for ($i = 0; $i < $tahap; $i++) {
                $peserta = Peserta::factory()->create();
                TahapanSpmb::create([
                    'peserta_id' => $peserta->id,
                    'tahap_saat_ini' => $tahap,
                    'tahap_1_selesai' => true,
                    'tahap_2_selesai' => $tahap >= 2,
                    'tahap_3_selesai' => $tahap >= 3,
                ]);
            }
        }
        
        $statistik = $this->monitoringService->ambilStatistikDashboard();
        
        // Total: 1 + 2 + 3 = 6 peserta
        $this->assertEquals(6, $statistik['total_peserta']);
        $this->assertEquals(1, $statistik['peserta_per_tahap'][1]);
        $this->assertEquals(2, $statistik['peserta_per_tahap'][2]);
        $this->assertEquals(3, $statistik['peserta_per_tahap'][3]);
    }

    /**
     * Property: Update tahapan mengubah tahap_saat_ini dengan benar
     */
    public function test_update_tahapan_mengubah_tahap_saat_ini(): void
    {
        $peserta = Peserta::factory()->create();
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 1,
            'tahap_1_selesai' => true,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        // Update tahap 2 selesai
        $this->monitoringService->updateStatusTahapan($peserta, 2, true, $admin);
        $peserta->refresh();
        
        $this->assertTrue($peserta->tahapanSpmb->tahap_2_selesai);
        $this->assertEquals(3, $peserta->tahapanSpmb->tahap_saat_ini);
        
        // Update tahap 3 selesai
        $this->monitoringService->updateStatusTahapan($peserta, 3, true, $admin);
        $peserta->refresh();
        
        $this->assertTrue($peserta->tahapanSpmb->tahap_3_selesai);
        $this->assertEquals(4, $peserta->tahapanSpmb->tahap_saat_ini);
    }

    /**
     * Property: Ekspor data menghasilkan format yang benar
     */
    public function test_ekspor_data_format_benar(): void
    {
        $peserta = Peserta::factory()->create([
            'nomor_pendaftaran' => 'SPMB-2025-00001',
            'nama' => 'Test Peserta',
        ]);
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 2,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => false,
        ]);
        
        $data = $this->monitoringService->eksporDataPeserta();
        
        $this->assertCount(1, $data);
        $row = $data->first();
        
        $this->assertEquals('SPMB-2025-00001', $row['nomor_pendaftaran']);
        $this->assertEquals('Test Peserta', $row['nama']);
        $this->assertEquals(2, $row['tahap_saat_ini']);
        $this->assertEquals('Ya', $row['tahap_1']);
        $this->assertEquals('Tidak', $row['tahap_2']);
    }
}
