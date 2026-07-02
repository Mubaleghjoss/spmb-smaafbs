<?php

namespace Tests\Property;

use App\Models\Peserta;
use App\Models\Pengguna;
use App\Models\FormulirSpmb;
use App\Models\TahapanSpmb;
use App\Services\FormulirSpmbService;
use App\Services\SpmbService;
use Eris\Generators;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FormulirSpmbPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    private FormulirSpmbService $formulirService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formulirService = new FormulirSpmbService(new SpmbService());
    }

    /**
     * Property: Simpan formulir harus menyimpan semua field dengan benar
     */
    public function test_simpan_formulir_menyimpan_semua_field(): void
    {
        $this->forAll(
            Generators::string(),
            Generators::string(),
            Generators::elements(['L', 'P']),
            Generators::elements(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha'])
        )->then(function ($nama, $tempat, $jenisKelamin, $agama) {
            $peserta = Peserta::factory()->create();
            
            $data = [
                'nama_lengkap' => $nama ?: 'Test',
                'tempat_lahir' => $tempat ?: 'Jakarta',
                'tanggal_lahir' => '2010-01-15',
                'jenis_kelamin' => $jenisKelamin,
                'agama' => $agama,
                'alamat' => 'Jl. Test No. 1',
                'telepon' => '08123456789',
                'nama_ayah' => 'Ayah Test',
                'nama_ibu' => 'Ibu Test',
                'asal_sekolah' => 'SMP Test',
            ];
            
            $formulir = $this->formulirService->simpan($peserta, $data);
            
            $this->assertEquals($peserta->id, $formulir->peserta_id);
            $this->assertEquals($data['nama_lengkap'], $formulir->nama_lengkap);
            $this->assertEquals($data['jenis_kelamin'], $formulir->jenis_kelamin);
            $this->assertEquals($data['agama'], $formulir->agama);
            $this->assertEquals('draft', $formulir->status_verifikasi);
            
            // Cleanup
            $formulir->delete();
            $peserta->forceDelete();
        });
    }

    /**
     * Property: Update formulir harus memperbarui data yang ada
     */
    public function test_update_formulir_memperbarui_data(): void
    {
        $peserta = Peserta::factory()->create();
        
        // Simpan pertama
        $dataAwal = [
            'nama_lengkap' => 'Nama Awal',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2010-01-15',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Alamat Awal',
            'telepon' => '08123456789',
            'nama_ayah' => 'Ayah',
            'nama_ibu' => 'Ibu',
            'asal_sekolah' => 'SMP Awal',
        ];
        
        $formulir = $this->formulirService->simpan($peserta, $dataAwal);
        $this->assertEquals('Nama Awal', $formulir->nama_lengkap);
        
        // Update
        $dataBaru = array_merge($dataAwal, [
            'nama_lengkap' => 'Nama Baru',
            'alamat' => 'Alamat Baru',
        ]);
        
        $formulirUpdated = $this->formulirService->simpan($peserta, $dataBaru);
        
        $this->assertEquals($formulir->id, $formulirUpdated->id);
        $this->assertEquals('Nama Baru', $formulirUpdated->nama_lengkap);
        $this->assertEquals('Alamat Baru', $formulirUpdated->alamat);
    }

    /**
     * Property: Verifikasi formulir mengubah status dan tahapan SPMB
     * **Memvalidasi: Kebutuhan 0.3, 15.3**
     * Tahap 2 = Isi Formulir, jadi verifikasi formulir menyelesaikan tahap 2
     */
    public function test_verifikasi_formulir_mengubah_status_dan_tahapan(): void
    {
        $peserta = Peserta::factory()->create();
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 2,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => false,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        $data = [
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
        ];
        
        $formulir = $this->formulirService->simpan($peserta, $data);
        $this->formulirService->submit($peserta);
        
        $formulir->refresh();
        $this->assertEquals('menunggu', $formulir->status_verifikasi);
        
        $this->formulirService->verifikasi($formulir, $admin);
        
        $formulir->refresh();
        $peserta->refresh();
        
        $this->assertEquals('terverifikasi', $formulir->status_verifikasi);
        $this->assertEquals($admin->id, $formulir->diverifikasi_oleh);
        $this->assertNotNull($formulir->diverifikasi_pada);
        // Verifikasi formulir menyelesaikan tahap 2 (Isi Formulir)
        $this->assertTrue($peserta->tahapanSelesai(2));
    }

    /**
     * Property: Penolakan formulir tidak mengubah tahapan SPMB
     */
    public function test_penolakan_formulir_tidak_mengubah_tahapan(): void
    {
        $peserta = Peserta::factory()->create();
        $tahapan = TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 2,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => true,
        ]);
        
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        
        $data = [
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
        ];
        
        $formulir = $this->formulirService->simpan($peserta, $data);
        $this->formulirService->submit($peserta);
        
        $this->formulirService->tolak($formulir, 'Data tidak lengkap', $admin);
        
        $formulir->refresh();
        $tahapan->refresh();
        
        $this->assertEquals('ditolak', $formulir->status_verifikasi);
        $this->assertEquals('Data tidak lengkap', $formulir->catatan_verifikasi);
        $this->assertFalse($tahapan->tahap_3_selesai);
    }

    /**
     * Property: Cek kelengkapan mendeteksi field kosong
     * Field wajib: nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, asal_sekolah, nama_ayah, nama_ibu, telepon
     */
    public function test_cek_kelengkapan_mendeteksi_field_kosong(): void
    {
        $peserta = Peserta::factory()->create();
        
        // Formulir tidak lengkap - field wajib yang kosong
        $dataKurang = [
            'nama_lengkap' => 'Test',
            'tempat_lahir' => '',  // kosong - wajib
            'tanggal_lahir' => '2010-01-15',
            'jenis_kelamin' => 'L',
            'agama' => '',  // kosong - tapi tidak wajib
            'alamat' => 'Jl. Test',
            'telepon' => '08123456789',
            'nama_ayah' => '',  // kosong - wajib
            'nama_ibu' => 'Ibu',
            'asal_sekolah' => 'SMP Test',
        ];
        
        $formulir = $this->formulirService->simpan($peserta, $dataKurang);
        $cek = $this->formulirService->cekKelengkapan($formulir);
        
        $this->assertFalse($cek['lengkap']);
        $this->assertContains('Kota Kelahiran', $cek['kosong']);  // tempat_lahir -> Kota Kelahiran
        $this->assertContains('Nama Ayah', $cek['kosong']);
        // Agama tidak termasuk field wajib di service
    }

    /**
     * Property: Formulir yang sudah disubmit tidak bisa diedit
     */
    public function test_formulir_sudah_submit_tidak_bisa_diedit(): void
    {
        $peserta = Peserta::factory()->create();
        
        $data = [
            'nama_lengkap' => 'Test',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2010-01-15',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Jl. Test',
            'telepon' => '08123456789',
            'nama_ayah' => 'Ayah',
            'nama_ibu' => 'Ibu',
            'asal_sekolah' => 'SMP Test',
        ];
        
        $formulir = $this->formulirService->simpan($peserta, $data);
        
        // Draft bisa diedit
        $this->assertTrue($this->formulirService->bisaDiedit($formulir));
        
        // Submit
        $this->formulirService->submit($peserta);
        $formulir->refresh();
        
        // Menunggu tidak bisa diedit
        $this->assertFalse($this->formulirService->bisaDiedit($formulir));
        
        // Ditolak bisa diedit lagi
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $this->formulirService->tolak($formulir, 'Perbaiki', $admin);
        $formulir->refresh();
        
        $this->assertTrue($this->formulirService->bisaDiedit($formulir));
    }
}
