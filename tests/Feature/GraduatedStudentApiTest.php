<?php

namespace Tests\Feature;

use App\Models\FormulirSpmb;
use App\Models\HasilPsikotesKepribadian;
use App\Models\Peserta;
use App\Models\SesiTes;
use App\Models\TahapanSpmb;
use App\Models\Tes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraduatedStudentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.akses_sync.token' => 'test-integration-token',
            'services.akses_sync.require_https' => false,
        ]);
    }

    public function test_endpoint_requires_a_valid_bearer_token(): void
    {
        $this->getJson('/api/v1/integrations/akses/graduated-students')
            ->assertUnauthorized();

        $this->withToken('wrong-token')
            ->getJson('/api/v1/integrations/akses/graduated-students')
            ->assertUnauthorized();
    }

    public function test_endpoint_only_returns_students_with_completed_graduation(): void
    {
        $graduated = $this->createPeserta('SPMB-2026-00001', 'Siswa Lulus', true);
        $this->createPeserta('SPMB-2026-00002', 'Masih Menunggu', false);

        FormulirSpmb::query()->create([
            'peserta_id' => $graduated->id,
            'nama_lengkap' => 'Siswa Lulus Lengkap',
            'jenis_kelamin' => 'L',
            'nisn' => '1234567890',
            'tanggal_lahir' => '2010-01-15',
            'nama_ayah' => 'Ayah Siswa',
            'telepon_ayah' => '081234567890',
            'asal_sekolah' => 'SMP Asal',
            'status_verifikasi' => 'terverifikasi',
        ]);

        $tes = Tes::factory()->create();
        $sesi = SesiTes::query()->create([
            'tes_id' => $tes->id,
            'peserta_id' => $graduated->id,
            'waktu_mulai' => now()->subHour(),
            'waktu_selesai' => now(),
            'nilai' => 90,
            'status' => 'selesai',
        ]);
        HasilPsikotesKepribadian::query()->create([
            'sesi_tes_id' => $sesi->id,
            'hasil_kepribadian' => 'plegmatis',
            'detail_nilai' => [],
        ]);

        $response = $this->withToken('test-integration-token')
            ->getJson('/api/v1/integrations/akses/graduated-students?per_page=1');

        $response
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nomor_pendaftaran', 'SPMB-2026-00001')
            ->assertJsonPath('data.0.biodata.nama', 'Siswa Lulus Lengkap')
            ->assertJsonPath('data.0.biodata.nisn', '1234567890')
            ->assertJsonPath('data.0.hasil_tes.kepribadian', 'Plegmatis')
            ->assertJsonStructure([
                'data' => [[
                    'source_id',
                    'nomor_pendaftaran',
                    'source_updated_at',
                    'checksum',
                    'biodata',
                    'orang_tua',
                    'sekolah_asal',
                    'fisik',
                    'hasil_tes',
                ]],
                'links',
                'meta',
                'api_version',
                'generated_at',
            ]);

        $payload = $response->json('data.0');
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('pembayaran', $payload);
        $this->assertArrayNotHasKey('dokumen', $payload);
        $this->assertArrayNotHasKey('wawancara', $payload);
    }

    private function createPeserta(string $nomor, string $nama, bool $lulus): Peserta
    {
        $peserta = Peserta::factory()->create([
            'nomor_pendaftaran' => $nomor,
            'nama' => $nama,
            'email' => strtolower(str_replace(' ', '.', $nama)).'@example.test',
        ]);

        TahapanSpmb::query()->create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 7,
            'tahap_7_selesai' => $lulus,
            'status_kelulusan' => $lulus ? 'lulus' : 'menunggu',
        ]);

        return $peserta;
    }
}
