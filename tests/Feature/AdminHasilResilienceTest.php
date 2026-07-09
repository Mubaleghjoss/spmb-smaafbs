<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Peserta;
use App\Models\SesiTes;
use App\Services\PeriodePendaftaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminHasilResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_root_redirects_to_dashboard(): void
    {
        $this->actingAs(Pengguna::factory()->admin()->create(), 'pengguna');

        $this->get('/admin/')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_hasil_index_mengabaikan_sesi_tes_tanpa_data_tes(): void
    {
        $this->actingAs(Pengguna::factory()->admin()->create(), 'pengguna');

        $peserta = Peserta::factory()->create([
            ...app(PeriodePendaftaranService::class)->kategoriDefault(),
        ]);

        Schema::disableForeignKeyConstraints();
        try {
            SesiTes::query()->create([
                'tes_id' => 999999,
                'peserta_id' => $peserta->id,
                'waktu_mulai' => now()->subHour(),
                'waktu_selesai' => now(),
                'nilai' => 80,
                'status' => 'selesai',
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->get(route('admin.hasil.index'))
            ->assertOk();
    }
}
