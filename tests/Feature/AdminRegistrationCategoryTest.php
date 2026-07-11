<?php

namespace Tests\Feature;

use App\Models\GelombangPendaftaran;
use App\Models\FormulirSpmb;
use App\Models\Pengguna;
use App\Models\Peserta;
use App\Models\TahunAjaran;
use App\Services\ImporEksporPesertaService;
use App\Services\PeriodePendaftaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminRegistrationCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Pengguna::factory()->admin()->create(), 'pengguna');
    }

    public function test_admin_dapat_memfilter_peserta_berdasarkan_tahun_ajaran(): void
    {
        $default = app(PeriodePendaftaranService::class)->kategoriDefault();
        $tahunLain = TahunAjaran::query()->create([
            'nama' => '2027-2028',
            'aktif' => true,
            'default' => false,
        ]);
        $gelombangLain = $tahunLain->gelombangPendaftaran()->create([
            'nama' => 'Gelombang 1',
            'aktif' => true,
        ]);

        Peserta::factory()->create([
            'nama' => 'PESERTA TAHUN DEFAULT',
            ...$default,
        ]);
        Peserta::factory()->create([
            'nama' => 'PESERTA TAHUN BERIKUTNYA',
            'tahun_ajaran_id' => $tahunLain->id,
            'gelombang_pendaftaran_id' => $gelombangLain->id,
            'jenis_pendaftaran' => 'pindahan',
            'kelas_tujuan' => 11,
        ]);

        $this->get('/admin/peserta?tahun_ajaran_id=' . $default['tahun_ajaran_id'])
            ->assertOk()
            ->assertSee('PESERTA TAHUN DEFAULT')
            ->assertDontSee('PESERTA TAHUN BERIKUTNYA');
    }

    public function test_admin_dapat_memfilter_peserta_berdasarkan_status_kuota(): void
    {
        $kategori = app(PeriodePendaftaranService::class)->kategoriDefault();

        Peserta::factory()->create([
            'nama' => 'PESERTA MASUK KUOTA',
            'status_kuota' => Peserta::STATUS_KUOTA_DALAM,
            ...$kategori,
        ]);
        Peserta::factory()->create([
            'nama' => 'PESERTA WAITING LIST',
            'status_kuota' => Peserta::STATUS_KUOTA_WAITING,
            ...$kategori,
        ]);

        $this->get('/admin/peserta?status_kuota=' . Peserta::STATUS_KUOTA_WAITING)
            ->assertOk()
            ->assertSee('PESERTA WAITING LIST')
            ->assertDontSee('PESERTA MASUK KUOTA');
    }

    public function test_admin_dapat_memfilter_data_formulir_peserta(): void
    {
        $kategori = app(PeriodePendaftaranService::class)->kategoriDefault();

        $pesertaTarget = Peserta::factory()->create([
            'nama' => 'PESERTA REKAP TARGET',
            ...$kategori,
        ]);
        FormulirSpmb::query()->create([
            'peserta_id' => $pesertaTarget->id,
            'nama_lengkap' => $pesertaTarget->nama,
            'asal_sekolah' => 'SMP AL FAJAR',
            'kelompok' => 'Kelompok Melati',
            'desa' => 'Desa Harmoni',
            'daerah' => 'Daerah Timur',
        ]);

        $pesertaLain = Peserta::factory()->create([
            'nama' => 'PESERTA REKAP LAIN',
            ...$kategori,
        ]);
        FormulirSpmb::query()->create([
            'peserta_id' => $pesertaLain->id,
            'nama_lengkap' => $pesertaLain->nama,
            'asal_sekolah' => 'SMP BINA BAKTI',
            'kelompok' => 'Kelompok Mawar',
            'desa' => 'Desa Sejahtera',
            'daerah' => 'Daerah Barat',
        ]);

        $this->get('/admin/peserta?asal_sekolah_smp=AL+FAJAR&kelompok=Melati&desa=Harmoni&daerah=Timur')
            ->assertOk()
            ->assertSee('PESERTA REKAP TARGET')
            ->assertSee('SMP AL FAJAR')
            ->assertSee('Kelompok Melati')
            ->assertSee('Desa Harmoni')
            ->assertSee('Daerah Timur')
            ->assertDontSee('PESERTA REKAP LAIN');
    }

    public function test_admin_melihat_rekap_data_formulir_peserta(): void
    {
        $kategori = app(PeriodePendaftaranService::class)->kategoriDefault();

        foreach (['SATU', 'DUA'] as $suffix) {
            $peserta = Peserta::factory()->create([
                'nama' => "PESERTA KAMPUNG {$suffix}",
                'status_kuota' => Peserta::STATUS_KUOTA_DALAM,
                ...$kategori,
            ]);
            FormulirSpmb::query()->create([
                'peserta_id' => $peserta->id,
                'nama_lengkap' => $peserta->nama,
                'asal_sekolah' => 'SMP REKAP UTAMA',
                'kelompok' => 'Kelompok Cempaka',
                'desa' => 'Desa Mandiri',
                'daerah' => 'Daerah Tengah',
            ]);
        }

        $pesertaWaiting = Peserta::factory()->create([
            'nama' => 'PESERTA WAITING REKAP',
            'status_kuota' => Peserta::STATUS_KUOTA_WAITING,
            ...$kategori,
        ]);
        FormulirSpmb::query()->create([
            'peserta_id' => $pesertaWaiting->id,
            'nama_lengkap' => $pesertaWaiting->nama,
            'asal_sekolah' => 'SMP REKAP LAIN',
            'kelompok' => 'Kelompok Kenanga',
            'desa' => 'Desa Madani',
            'daerah' => 'Daerah Utara',
        ]);

        $this->get('/admin/peserta')
            ->assertOk()
            ->assertSee('Rekap Data Formulir')
            ->assertSee('Asal Sekolah SMP')
            ->assertSee('SMP REKAP UTAMA')
            ->assertSee('Kelompok Cempaka')
            ->assertSee('Desa Mandiri')
            ->assertSee('Daerah Tengah')
            ->assertSee('Waiting');
    }

    public function test_update_kuota_tahun_ajaran_merekalkulasi_waiting_list(): void
    {
        $kategori = app(PeriodePendaftaranService::class)->kategoriDefault();
        $tahun = TahunAjaran::query()->findOrFail($kategori['tahun_ajaran_id']);

        foreach (range(1, 3) as $index) {
            Peserta::factory()->create([
                'nama' => "PESERTA KUOTA {$index}",
                'urutan_kuota' => $index,
                'status_kuota' => Peserta::STATUS_KUOTA_DALAM,
                ...$kategori,
            ]);
        }

        $this->put(route('admin.pengaturan.spmb.periode.tahun.update', $tahun), [
            'nama' => $tahun->nama,
            'kuota_peserta' => 2,
            'aktif' => '1',
            'default' => '1',
        ])->assertRedirect();

        $this->assertSame(2, Peserta::query()
            ->where('tahun_ajaran_id', $tahun->id)
            ->where('status_kuota', Peserta::STATUS_KUOTA_DALAM)
            ->count());
        $this->assertSame(1, Peserta::query()
            ->where('tahun_ajaran_id', $tahun->id)
            ->where('status_kuota', Peserta::STATUS_KUOTA_WAITING)
            ->count());
    }

    public function test_bulk_kategori_memaksa_siswa_baru_ke_kelas_10(): void
    {
        $kategori = app(PeriodePendaftaranService::class)->kategoriDefault();
        $peserta = collect([
            Peserta::factory()->create([
                ...$kategori,
                'nomor_pendaftaran' => 'SPMB-2026-90001',
            ]),
            Peserta::factory()->create([
                ...$kategori,
                'nomor_pendaftaran' => 'SPMB-2026-90002',
            ]),
        ]);

        $this->post('/admin/peserta/bulk-update-kategori', [
            'peserta_ids' => $peserta->pluck('id')->all(),
            'tahun_ajaran_id' => $kategori['tahun_ajaran_id'],
            'gelombang_pendaftaran_id' => $kategori['gelombang_pendaftaran_id'],
            'jenis_pendaftaran' => 'siswa_baru',
            'kelas_tujuan' => 11,
        ])->assertRedirect();

        $this->assertSame(
            0,
            Peserta::query()
                ->whereIn('id', $peserta->pluck('id'))
                ->where('kelas_tujuan', '!=', 10)
                ->count()
        );
    }

    public function test_gelombang_yang_sudah_digunakan_tidak_dapat_dihapus(): void
    {
        $kategori = app(PeriodePendaftaranService::class)->kategoriDefault();
        Peserta::factory()->create($kategori);
        $tahun = TahunAjaran::query()->findOrFail($kategori['tahun_ajaran_id']);
        $gelombang = GelombangPendaftaran::query()->findOrFail($kategori['gelombang_pendaftaran_id']);

        $this->delete(route('admin.pengaturan.spmb.periode.gelombang.destroy', [$tahun, $gelombang]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('gelombang_pendaftaran', ['id' => $gelombang->id]);
    }

    public function test_ekspor_excel_memuat_kategori_pendaftaran(): void
    {
        $kategori = app(PeriodePendaftaranService::class)->kategoriDefault();
        Peserta::factory()->create([
            'nama' => 'PESERTA EKSPOR KATEGORI',
            ...$kategori,
        ]);

        $path = app(ImporEksporPesertaService::class)->eksporKeExcel();

        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame('Tahun Ajaran', $sheet->getCell('G1')->getValue());
            $this->assertSame('Gelombang', $sheet->getCell('H1')->getValue());
            $this->assertSame('Jenis Pendaftaran', $sheet->getCell('I1')->getValue());
            $this->assertSame('Kelas Tujuan', $sheet->getCell('J1')->getValue());
            $this->assertSame('Kelas Penempatan', $sheet->getCell('K1')->getValue());
            $this->assertSame('2026-2027', $sheet->getCell('G2')->getValue());
            $this->assertSame('Gelombang 1', $sheet->getCell('H2')->getValue());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
