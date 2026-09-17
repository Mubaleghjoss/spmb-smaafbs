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

    public function test_admin_dapat_menyimpan_kontak_bendahara_terpisah_dari_tim_spmb(): void
    {
        $this->withoutMiddleware();

        $this->post(route('admin.pengaturan.spmb.simpan'), [
            'nama_bendahara_spmb' => 'M. Herianto, S.E.',
            'whatsapp_bendahara_spmb' => '082299507730',
        ])->assertRedirect(route('admin.pengaturan.spmb'));

        $this->assertDatabaseHas('pengaturan', ['kunci' => 'nama_bendahara_spmb', 'nilai' => 'M. Herianto, S.E.']);
        $this->assertDatabaseHas('pengaturan', ['kunci' => 'whatsapp_bendahara_spmb', 'nilai' => '082299507730']);
        $this->assertDatabaseMissing('pengaturan', ['kunci' => 'kontak_tim_spmb', 'nilai' => json_encode([['nama' => 'M. Herianto, S.E.', 'whatsapp' => '082299507730']])]);
    }

    public function test_admin_dapat_mengatur_popup_persetujuan_pendaftaran(): void
    {
        $this->withoutMiddleware();

        $this->post(route('admin.pengaturan.spmb.simpan'), [
            'popup_persetujuan_aktif' => '1',
            'popup_persetujuan_judul' => 'Komitmen Calon Peserta Didik',
            'popup_persetujuan_teks' => 'Saya bersedia mengikuti peraturan sekolah.',
        ])->assertRedirect(route('admin.pengaturan.spmb'));

        $this->assertDatabaseHas('pengaturan', ['kunci' => 'popup_persetujuan_aktif', 'nilai' => '1']);
        $this->assertDatabaseHas('pengaturan', ['kunci' => 'popup_persetujuan_judul', 'nilai' => 'Komitmen Calon Peserta Didik']);
        $this->assertDatabaseHas('pengaturan', ['kunci' => 'popup_persetujuan_teks', 'nilai' => 'Saya bersedia mengikuti peraturan sekolah.']);
    }
}
