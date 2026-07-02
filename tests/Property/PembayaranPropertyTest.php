<?php

namespace Tests\Property;

use App\Models\Peserta;
use App\Models\Pengguna;
use App\Models\Pembayaran;
use App\Models\TahapanSpmb;
use App\Services\PembayaranService;
use App\Services\SpmbService;
use App\Enums\StatusPembayaran;
use Eris\Generators;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PembayaranPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    private PembayaranService $pembayaranService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->pembayaranService = new PembayaranService(new SpmbService());
    }

    /**
     * Property 14: Verifikasi Pembayaran Mengubah Status
     * Untuk setiap pembayaran yang diverifikasi, status harus berubah 
     * dari 'menunggu' menjadi 'terverifikasi' dan tahapan SPMB harus terupdate.
     * Tahap 3 = Bayar Formulir, Tahap 6 = Bayar Pertama (Pelunasan)
     * **Memvalidasi: Kebutuhan 13.2**
     */
    public function test_verifikasi_pembayaran_mengubah_status(): void
    {
        // Test untuk jenis 'formulir' -> tahap 3
        $this->verifikasiPembayaranUntukJenis('formulir', 3);
        
        // Test untuk jenis 'pertama' -> tahap 6
        $this->verifikasiPembayaranUntukJenis('pertama', 6);
    }
    
    private function verifikasiPembayaranUntukJenis(string $jenis, int $tahapExpected): void
    {
        $peserta = Peserta::factory()->create();
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 1,
            'tahap_1_selesai' => true,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        $file = UploadedFile::fake()->image('bukti.jpg');
        $pembayaran = $this->pembayaranService->uploadBukti($peserta, $jenis, $file);
        
        $this->assertEquals(StatusPembayaran::MENUNGGU->value, $pembayaran->status);
        
        $this->pembayaranService->verifikasi($pembayaran, $admin);
        
        $pembayaran->refresh();
        $peserta->refresh();
        
        $this->assertEquals(StatusPembayaran::TERVERIFIKASI->value, $pembayaran->status);
        $this->assertEquals($admin->id, $pembayaran->diverifikasi_oleh);
        $this->assertNotNull($pembayaran->diverifikasi_pada);
        $this->assertTrue($peserta->tahapanSelesai($tahapExpected));
    }

    /**
     * Property: Penolakan pembayaran tidak mengubah tahapan SPMB
     * Tahap 3 = Bayar Formulir, Tahap 6 = Bayar Pertama (Pelunasan)
     */
    public function test_penolakan_pembayaran_tidak_mengubah_tahapan(): void
    {
        // Test untuk jenis 'formulir' -> tahap 3
        $this->penolakanPembayaranUntukJenis('formulir', 3);
        
        // Test untuk jenis 'pertama' -> tahap 6
        $this->penolakanPembayaranUntukJenis('pertama', 6);
    }
    
    private function penolakanPembayaranUntukJenis(string $jenis, int $tahap): void
    {
        $peserta = Peserta::factory()->create();
        $tahapan = TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 1,
            'tahap_1_selesai' => true,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        $file = UploadedFile::fake()->image('bukti.jpg');
        $pembayaran = $this->pembayaranService->uploadBukti($peserta, $jenis, $file);
        
        $this->pembayaranService->tolak($pembayaran, 'Bukti tidak valid', $admin);
        
        $pembayaran->refresh();
        $tahapan->refresh();
        
        $this->assertEquals(StatusPembayaran::DITOLAK->value, $pembayaran->status);
        $this->assertEquals('Bukti tidak valid', $pembayaran->catatan);
        
        $kolom = "tahap_{$tahap}_selesai";
        $this->assertFalse($tahapan->$kolom);
    }
}
