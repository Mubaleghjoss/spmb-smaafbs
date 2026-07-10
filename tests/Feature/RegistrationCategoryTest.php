<?php

namespace Tests\Feature;

use App\Models\GelombangPendaftaran;
use App\Models\Peserta;
use App\Models\TahunAjaran;
use App\Services\PengaturanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PengaturanService::class)->simpanBanyak([
            'pendaftaran_buka' => '1',
            'tanggal_buka' => now()->subDay()->toDateString(),
            'tanggal_tutup' => now()->addDay()->toDateString(),
        ]);

        GelombangPendaftaran::query()->update([
            'tanggal_buka' => now()->subDay(),
            'tanggal_tutup' => now()->addDay(),
            'aktif' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_form_menampilkan_periode_yang_sedang_dibuka(): void
    {
        $this->get('/daftar')
            ->assertOk()
            ->assertSee('2026-2027')
            ->assertSee('Gelombang 1')
            ->assertSee('Siswa Baru')
            ->assertSee('Pindahan');
    }

    public function test_siswa_baru_selalu_disimpan_sebagai_kelas_10(): void
    {
        $tahun = TahunAjaran::query()->where('default', true)->firstOrFail();
        $gelombang = $tahun->gelombangPendaftaran()->firstOrFail();

        $response = $this->post('/daftar', [
            'nama' => 'Peserta Baru',
            'telepon' => '081234567890',
            'asal_sekolah' => 'SMP Contoh',
            'tahun_ajaran_id' => $tahun->id,
            'gelombang_pendaftaran_id' => $gelombang->id,
            'jenis_pendaftaran' => 'siswa_baru',
            'kelas_tujuan' => 11,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'setuju' => '1',
        ]);

        $response->assertRedirect(route('peserta.login'));

        $peserta = Peserta::query()->where('telepon', '081234567890')->firstOrFail();
        $this->assertSame('siswa_baru', $peserta->jenis_pendaftaran);
        $this->assertSame(10, $peserta->kelas_tujuan);
        $this->assertSame($tahun->id, $peserta->tahun_ajaran_id);
        $this->assertSame($gelombang->id, $peserta->gelombang_pendaftaran_id);
    }

    public function test_peserta_di_luar_kuota_otomatis_masuk_waiting_list(): void
    {
        $tahun = TahunAjaran::query()->where('default', true)->firstOrFail();
        $tahun->update(['kuota_peserta' => 1]);
        $gelombang = $tahun->gelombangPendaftaran()->firstOrFail();

        $payload = [
            'asal_sekolah' => 'SMP Contoh',
            'tahun_ajaran_id' => $tahun->id,
            'gelombang_pendaftaran_id' => $gelombang->id,
            'jenis_pendaftaran' => 'siswa_baru',
            'kelas_tujuan' => 10,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'setuju' => '1',
        ];

        $this->post('/daftar', [
            ...$payload,
            'nama' => 'Peserta Kuota Pertama',
            'telepon' => '081234567893',
        ])->assertRedirect(route('peserta.login'));

        $this->post('/daftar', [
            ...$payload,
            'nama' => 'Peserta Waiting List',
            'telepon' => '081234567894',
        ])->assertRedirect(route('peserta.login'));

        $this->assertDatabaseHas('peserta', [
            'telepon' => '081234567893',
            'status_kuota' => Peserta::STATUS_KUOTA_DALAM,
            'urutan_kuota' => 1,
        ]);
        $this->assertDatabaseHas('peserta', [
            'telepon' => '081234567894',
            'status_kuota' => Peserta::STATUS_KUOTA_WAITING,
            'urutan_kuota' => 2,
        ]);
    }

    public function test_gelombang_dari_tahun_lain_ditolak(): void
    {
        $tahun = TahunAjaran::query()->where('default', true)->firstOrFail();
        $tahunLain = TahunAjaran::query()->create([
            'nama' => '2027-2028',
            'aktif' => true,
            'default' => false,
        ]);
        $gelombangLain = $tahunLain->gelombangPendaftaran()->create([
            'nama' => 'Gelombang 1',
            'tanggal_buka' => now()->subDay(),
            'tanggal_tutup' => now()->addDay(),
            'aktif' => true,
        ]);

        $this->from('/daftar')
            ->post('/daftar', [
                'nama' => 'Manipulasi Gelombang',
                'telepon' => '081234567891',
                'asal_sekolah' => 'SMP Contoh',
                'tahun_ajaran_id' => $tahun->id,
                'gelombang_pendaftaran_id' => $gelombangLain->id,
                'jenis_pendaftaran' => 'pindahan',
                'kelas_tujuan' => 11,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'setuju' => '1',
            ])
            ->assertRedirect('/daftar')
            ->assertSessionHasErrors('gelombang_pendaftaran_id');

        $this->assertDatabaseMissing('peserta', ['telepon' => '081234567891']);
    }

    public function test_gelombang_kedaluwarsa_tidak_dapat_dipakai(): void
    {
        $tahun = TahunAjaran::query()->where('default', true)->firstOrFail();
        $gelombang = $tahun->gelombangPendaftaran()->firstOrFail();
        $gelombang->update([
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDay(),
        ]);

        $this->post('/daftar', [
            'nama' => 'Peserta Terlambat',
            'telepon' => '081234567892',
            'asal_sekolah' => 'SMP Contoh',
            'tahun_ajaran_id' => $tahun->id,
            'gelombang_pendaftaran_id' => $gelombang->id,
            'jenis_pendaftaran' => 'pindahan',
            'kelas_tujuan' => 10,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'setuju' => '1',
        ])->assertSessionHasErrors('gelombang_pendaftaran_id');

        $this->assertDatabaseMissing('peserta', ['telepon' => '081234567892']);
    }

    public function test_form_publik_mengikuti_jam_buka_gelombang(): void
    {
        Carbon::setTestNow('2026-07-10 08:30:00');

        GelombangPendaftaran::query()->update([
            'tanggal_buka' => '2026-07-10',
            'waktu_buka' => '09:00:00',
            'tanggal_tutup' => '2026-07-10',
            'waktu_tutup' => '17:00:00',
            'aktif' => true,
        ]);

        $this->get('/daftar')
            ->assertOk()
            ->assertSee('Belum ada gelombang pendaftaran yang sedang dibuka.')
            ->assertSee('Belum dibuka')
            ->assertDontSee('Daftar Sekarang');

        Carbon::setTestNow('2026-07-10 09:01:00');

        $this->get('/daftar')
            ->assertOk()
            ->assertSee('Gelombang 1')
            ->assertSee('Daftar Sekarang');
    }
}
