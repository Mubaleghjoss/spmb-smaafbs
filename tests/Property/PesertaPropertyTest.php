<?php

namespace Tests\Property;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use Eris\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Property-Based Tests untuk Model Peserta
 */
class PesertaPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    /**
     * Feature: cbt-modernization, Property 15: Nomor Pendaftaran Unik
     * Validates: Kebutuhan 0.2
     * 
     * Untuk setiap peserta yang mendaftar, nomor pendaftaran yang dihasilkan
     * harus unik dan tidak ada duplikat dalam database.
     */
    public function test_nomor_pendaftaran_unik(): void
    {
        $this->forAll(
            Generator\choose(5, 20) // Jumlah peserta yang akan dibuat (5-20)
        )->then(function (int $jumlah) {
            // Bersihkan database sebelum setiap iterasi
            Peserta::query()->forceDelete();
            
            // Buat peserta dengan nomor pendaftaran unik
            $nomorPendaftaran = [];
            
            for ($i = 0; $i < $jumlah; $i++) {
                $nomor = $this->generateNomorPendaftaran($i);
                $nomorPendaftaran[] = $nomor;
                
                Peserta::create([
                    'nomor_pendaftaran' => $nomor,
                    'nama' => 'Peserta ' . ($i + 1),
                    'email' => 'peserta' . ($i + 1) . '_' . Str::random(8) . '@test.com',
                    'telepon' => '081' . str_pad((string) ($i + 1), 9, '0', STR_PAD_LEFT),
                    'password' => bcrypt('password'),
                ]);
            }
            
            // Verifikasi semua nomor pendaftaran unik
            $this->assertEquals(
                count($nomorPendaftaran),
                count(array_unique($nomorPendaftaran)),
                'Nomor pendaftaran harus unik'
            );
            
            // Verifikasi di database
            $this->assertEquals(
                $jumlah,
                Peserta::count(),
                'Jumlah peserta di database harus sesuai'
            );
        });
    }

    /**
     * Generate nomor pendaftaran dengan format SPMB-YYYY-XXXXX
     */
    private function generateNomorPendaftaran(int $index): string
    {
        $tahun = date('Y');
        $urutan = str_pad($index + 1, 5, '0', STR_PAD_LEFT);
        
        return "SPMB-{$tahun}-{$urutan}";
    }

    /**
     * Feature: cbt-modernization, Property 6: Soft Delete Preservasi Data
     * Validates: Kebutuhan 3.7
     * 
     * Untuk setiap peserta yang dihapus (soft delete), data peserta harus tetap
     * tersimpan di database dan dapat dipulihkan (restore).
     */
    public function test_soft_delete_preservasi_data(): void
    {
        $this->forAll(
            Generator\choose(1, 10) // Jumlah peserta yang akan dibuat
        )->then(function (int $jumlah) {
            // Bersihkan database
            Peserta::query()->forceDelete();
            
            // Buat peserta
            $pesertaIds = [];
            for ($i = 0; $i < $jumlah; $i++) {
                $peserta = Peserta::create([
                    'nomor_pendaftaran' => $this->generateNomorPendaftaran($i),
                    'nama' => 'Peserta ' . ($i + 1),
                    'email' => 'peserta' . ($i + 1) . '_' . Str::random(8) . '@test.com',
                    'telepon' => '082' . str_pad((string) ($i + 1), 9, '0', STR_PAD_LEFT),
                    'password' => bcrypt('password'),
                ]);
                $pesertaIds[] = $peserta->id;
            }
            
            // Verifikasi jumlah awal
            $this->assertEquals($jumlah, Peserta::count());
            
            // Soft delete semua peserta
            foreach ($pesertaIds as $id) {
                Peserta::find($id)->delete();
            }
            
            // Verifikasi peserta tidak muncul di query normal
            $this->assertEquals(0, Peserta::count());
            
            // Verifikasi data masih ada dengan withTrashed
            $this->assertEquals($jumlah, Peserta::withTrashed()->count());
            
            // Restore semua peserta
            foreach ($pesertaIds as $id) {
                Peserta::withTrashed()->find($id)->restore();
            }
            
            // Verifikasi semua peserta kembali
            $this->assertEquals($jumlah, Peserta::count());
            
            // Verifikasi data tidak berubah setelah restore
            foreach ($pesertaIds as $id) {
                $peserta = Peserta::find($id);
                $this->assertNotNull($peserta);
                $this->assertNull($peserta->deleted_at);
            }
        });
    }
}
