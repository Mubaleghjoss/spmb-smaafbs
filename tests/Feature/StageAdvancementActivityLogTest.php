<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\Peserta;
use App\Services\PeriodePendaftaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageAdvancementActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Pengguna::factory()->admin()->create(), 'pengguna');
    }

    public function test_admin_stage_advance_creates_a_general_activity_log(): void
    {
        $peserta = Peserta::factory()->create([
            ...app(PeriodePendaftaranService::class)->kategoriDefault(),
        ]);

        $this->post(route('admin.peserta.update-tahap', $peserta), [
            'tahap_baru' => 4,
        ])->assertRedirect();

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'tahapan.pindahkan_manual',
            'kategori' => LogAktivitas::KAT_PESERTA,
            'subjek_tipe' => Peserta::class,
            'subjek_id' => $peserta->id,
            'tahun_ajaran_id' => $peserta->tahun_ajaran_id,
        ]);
    }

    public function test_admin_bulk_stage_advance_creates_one_general_activity_log(): void
    {
        $kategori = app(PeriodePendaftaranService::class)->kategoriDefault();
        $peserta = Peserta::factory()->count(2)->create($kategori);

        $this->post(route('admin.peserta.bulk-update-tahap'), [
            'peserta_ids' => $peserta->modelKeys(),
            'tahap_baru' => 4,
        ])->assertRedirect();

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'tahapan.pindahkan_massal',
            'kategori' => LogAktivitas::KAT_PESERTA,
        ]);

        $this->assertSame(1, LogAktivitas::query()
            ->where('aksi', 'tahapan.pindahkan_massal')
            ->count());
    }
}
