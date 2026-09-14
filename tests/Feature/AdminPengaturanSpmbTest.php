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

    public function test_admin_dapat_menyimpan_keterangan_kuota_publik(): void
    {
        $this->withoutMiddleware();

        $this->post(route('admin.pengaturan.spmb.simpan'), [
            'keterangan_kuota_publik' => 'Lengkapi formulir lalu unggah bukti pembayaran formulir untuk memperoleh urutan kuota.',
        ])->assertRedirect(route('admin.pengaturan.spmb'));

        $this->assertDatabaseHas('pengaturan', [
            'kunci' => 'keterangan_kuota_publik',
            'nilai' => 'Lengkapi formulir lalu unggah bukti pembayaran formulir untuk memperoleh urutan kuota.',
        ]);
    }
}
