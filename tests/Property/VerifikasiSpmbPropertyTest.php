<?php

namespace Tests\Property;

use App\Models\Peserta;
use App\Models\Pengguna;
use App\Models\Pembayaran;
use App\Models\FormulirSpmb;
use App\Models\TahapanSpmb;
use App\Services\VerifikasiSpmbService;
use App\Services\SpmbService;
use App\Services\PembayaranService;
use App\Services\FormulirSpmbService;
use App\Enums\StatusPembayaran;
use Eris\Generators;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VerifikasiSpmbPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    private VerifikasiSpmbService $verifikasiService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        $spmbService = new SpmbService();
        $pembayaranService = new PembayaranService($spmbService);
        $formulirService = new FormulirSpmbService($spmbService);
        
        $this->verifikasiService = new VerifikasiSpmbService(
            $spmbService,
            $pembayaranService,
            $formulirService
        );
    }

    /**
     * Property 16: Penolakan Mengirim Notifikasi
     * Untuk setiap penolakan (pembayaran/formulir), sistem harus mencatat log notifikasi.
     * **Memvalidasi: Kebutuhan 15.7**
     */
    public function test_penolakan_mengirim_notifikasi(): void
    {
        $this->forAll(
            Generators::string(),
            Generators::elements(['pembayaran_formulir', 'formulir', 'pelunasan'])
        )->then(function ($alasan, $jenis) {
            $alasan = $alasan ?: 'Alasan default';
            
            $peserta = Peserta::factory()->create();
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 1,
                'tahap_1_selesai' => true,
            ]);
            
            // Capture log
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message) use ($peserta, $jenis, $alasan) {
                    return str_contains($message, $jenis) 
                        && str_contains($message, $peserta->nomor_pendaftaran)
                        && str_contains($message, $alasan);
                });
            
            $this->verifikasiService->kirimNotifikasiPenolakan($peserta, $jenis, $alasan);
            
            // Cleanup
            $peserta->forceDelete();
        });
    }

    /**
     * Property: Verifikasi pembayaran formulir mengubah status dan tahapan
     * Tahap 3 = Bayar Formulir
     */
    public function test_verifikasi_pembayaran_formulir_mengubah_status(): void
    {
        $peserta = Peserta::factory()->create();
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 3,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => true,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        $file = UploadedFile::fake()->image('bukti.jpg');
        $pembayaran = Pembayaran::create([
            'peserta_id' => $peserta->id,
            'jenis' => 'formulir',
            'bukti_file' => $file->store('pembayaran/formulir', 'public'),
            'status' => StatusPembayaran::MENUNGGU->value,
        ]);
        
        $this->assertEquals(StatusPembayaran::MENUNGGU->value, $pembayaran->status);
        
        $this->verifikasiService->verifikasiPembayaranFormulir($pembayaran, $admin);
        
        $pembayaran->refresh();
        $peserta->refresh();
        
        $this->assertEquals(StatusPembayaran::TERVERIFIKASI->value, $pembayaran->status);
        // Verifikasi pembayaran formulir menyelesaikan tahap 3 (Bayar Formulir)
        $this->assertTrue($peserta->tahapanSelesai(3));
    }

    /**
     * Property: Penolakan pembayaran formulir mengirim notifikasi
     */
    public function test_penolakan_pembayaran_formulir_mengirim_notifikasi(): void
    {
        $peserta = Peserta::factory()->create();
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 1,
            'tahap_1_selesai' => true,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        $file = UploadedFile::fake()->image('bukti.jpg');
        $pembayaran = Pembayaran::create([
            'peserta_id' => $peserta->id,
            'jenis' => 'formulir',
            'bukti_file' => $file->store('pembayaran/formulir', 'public'),
            'status' => StatusPembayaran::MENUNGGU->value,
        ]);
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) use ($peserta) {
                return str_contains($message, 'pembayaran_formulir')
                    && str_contains($message, $peserta->nomor_pendaftaran);
            });
        
        $this->verifikasiService->tolakPembayaranFormulir($pembayaran, 'Bukti tidak valid', $admin);
        
        $pembayaran->refresh();
        
        $this->assertEquals(StatusPembayaran::DITOLAK->value, $pembayaran->status);
        $this->assertEquals('Bukti tidak valid', $pembayaran->catatan);
    }

    /**
     * Property: Verifikasi formulir mengubah status dan tahapan
     * Tahap 2 = Isi Formulir
     */
    public function test_verifikasi_formulir_mengubah_status(): void
    {
        $peserta = Peserta::factory()->create();
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 2,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => false,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        $formulir = FormulirSpmb::create([
            'peserta_id' => $peserta->id,
            'nama_lengkap' => 'Test Peserta',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2010-01-15',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Jl. Test',
            'telepon' => '08123456789',
            'nama_ayah' => 'Ayah',
            'nama_ibu' => 'Ibu',
            'asal_sekolah' => 'SMP Test',
            'status_verifikasi' => 'menunggu',
        ]);
        
        $this->verifikasiService->verifikasiFormulir($formulir, $admin);
        
        $formulir->refresh();
        $peserta->refresh();
        
        $this->assertEquals('terverifikasi', $formulir->status_verifikasi);
        // Verifikasi formulir menyelesaikan tahap 2 (Isi Formulir)
        $this->assertTrue($peserta->tahapanSelesai(2));
    }

    /**
     * Property: Verifikasi pelunasan membuat peserta resmi diterima
     */
    public function test_verifikasi_pelunasan_membuat_peserta_diterima(): void
    {
        $peserta = Peserta::factory()->create();
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 6,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => true,
            'tahap_3_selesai' => true,
            'tahap_4_selesai' => true,
            'tahap_5_selesai' => true,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        $file = UploadedFile::fake()->image('bukti.jpg');
        $pembayaran = Pembayaran::create([
            'peserta_id' => $peserta->id,
            'jenis' => 'pertama',
            'bukti_file' => $file->store('pembayaran/pertama', 'public'),
            'nominal' => 5000000,
            'status' => StatusPembayaran::MENUNGGU->value,
        ]);
        
        $this->verifikasiService->verifikasiPelunasan($pembayaran, $admin);
        
        $pembayaran->refresh();
        $peserta->refresh();
        
        $this->assertEquals(StatusPembayaran::TERVERIFIKASI->value, $pembayaran->status);
        $this->assertTrue($peserta->tahapanSelesai(6));
        $this->assertTrue($peserta->tahapanSelesai(7));
    }

    /**
     * Property: Statistik verifikasi menghitung dengan benar
     */
    public function test_statistik_verifikasi_menghitung_dengan_benar(): void
    {
        // Buat beberapa peserta dengan pembayaran menunggu
        for ($i = 0; $i < 3; $i++) {
            $peserta = Peserta::factory()->create();
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 1,
                'tahap_1_selesai' => true,
            ]);
            
            $file = UploadedFile::fake()->image("bukti{$i}.jpg");
            Pembayaran::create([
                'peserta_id' => $peserta->id,
                'jenis' => 'formulir',
                'bukti_file' => $file->store('pembayaran/formulir', 'public'),
                'status' => StatusPembayaran::MENUNGGU->value,
            ]);
        }
        
        // Buat beberapa formulir menunggu
        for ($i = 0; $i < 2; $i++) {
            $peserta = Peserta::factory()->create();
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 2,
                'tahap_1_selesai' => true,
                'tahap_2_selesai' => true,
            ]);
            
            FormulirSpmb::create([
                'peserta_id' => $peserta->id,
                'nama_lengkap' => "Test {$i}",
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '2010-01-15',
                'jenis_kelamin' => 'L',
                'agama' => 'Islam',
                'alamat' => 'Jl. Test',
                'telepon' => '08123456789',
                'nama_ayah' => 'Ayah',
                'nama_ibu' => 'Ibu',
                'asal_sekolah' => 'SMP Test',
                'status_verifikasi' => 'menunggu',
            ]);
        }
        
        $statistik = $this->verifikasiService->ambilStatistik();
        
        $this->assertEquals(3, $statistik['pembayaran_menunggu']);
        $this->assertEquals(2, $statistik['formulir_menunggu']);
        $this->assertEquals(5, $statistik['total_peserta']);
    }
}
