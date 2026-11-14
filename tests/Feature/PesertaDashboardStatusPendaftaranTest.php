<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Models\Pembayaran;
use App\Models\TahapanSpmb;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesertaDashboardStatusPendaftaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_pindahan_menampilkan_jalur_tahun_ajaran_dan_saran_status_belum_lengkap(): void
    {
        $tahun = TahunAjaran::create([
            'nama' => '2027-2028',
            'aktif' => true,
            'default' => true,
        ]);

        $peserta = Peserta::factory()->create([
            'nama' => 'Ina',
            'tahun_ajaran_id' => $tahun->id,
            'jenis_pendaftaran' => Peserta::JENIS_PINDAHAN,
            'status_kuota' => Peserta::STATUS_KUOTA_BELUM_LENGKAP,
        ]);

        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 3,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => true,
        ]);

        $this->withSession(['peserta_id' => $peserta->id])
            ->get(route('peserta.dashboard'))
            ->assertOk()
            ->assertSee('Jalur Pendaftaran')
            ->assertSee('Siswa Pindahan')
            ->assertSee('Tahun Ajaran')
            ->assertSee('2027-2028')
            ->assertSee('Status Kuota')
            ->assertSee('Belum Lengkap')
            ->assertSee('Tahap 3 belum selesai')
            ->assertSee('unggah bukti pembayaran formulir');
    }

    public function test_dashboard_mengarahkan_pembayaran_terkirim_ke_status_verifikasi(): void
    {
        $tahun = TahunAjaran::create([
            'nama' => '2027-2028',
            'aktif' => true,
            'default' => true,
        ]);

        $peserta = Peserta::factory()->create([
            'nama' => 'Budi',
            'tahun_ajaran_id' => $tahun->id,
            'status_kuota' => Peserta::STATUS_KUOTA_BELUM_LENGKAP,
        ]);

        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 3,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => true,
        ]);

        Pembayaran::create([
            'peserta_id' => $peserta->id,
            'jenis' => 'formulir',
            'bukti_file' => 'pembayaran/formulir/bukti.jpg',
            'nominal' => 150000,
            'status' => 'menunggu',
        ]);

        $this->withSession(['peserta_id' => $peserta->id])
            ->get(route('peserta.dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi', false)
            ->assertSee(route('peserta.pembayaran.status-formulir'), false)
            ->assertDontSee('>Upload Bukti Bayar</h6>', false);
    }
}
