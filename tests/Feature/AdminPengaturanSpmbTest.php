<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPengaturanSpmbTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_membuka_pengaturan_spmb_pada_tab_tahap_tiga(): void
    {
        $this->actingAs(Pengguna::factory()->admin()->create(), 'pengguna');

        $this->get(route('admin.pengaturan.spmb', ['tab' => 'tahap3']))
            ->assertOk()
            ->assertSee('Biaya Berdasarkan Domisili')
            ->assertSee('biaya_formulir_dalam_kota', false)
            ->assertSee('biaya_formulir_luar_kota', false);
    }
}
