<?php

namespace Tests\Feature;

use App\Models\GayaBelajarConfig;
use App\Models\HasilGayaBelajar;
use App\Models\HasilPsikotesKepribadian;
use App\Models\Pengguna;
use App\Models\Peserta;
use App\Models\PsikotesKepribadianConfig;
use App\Models\SesiTes;
use App\Models\Tes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RekapSeleksiImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_import_rekap_seleksi_dan_import_ulang_tidak_duplikat(): void
    {
        $this->actingAs(Pengguna::factory()->admin()->create(), 'pengguna');
        $tes = $this->buatTesRekap();

        $this->post(route('admin.peserta.impor-rekap-seleksi.proses'), [
            'file_rekap' => $this->uploadedRekapFile('rekap-1.xlsx'),
        ])->assertRedirect()
            ->assertSessionHas('success');

        $peserta = Peserta::query()->where('nama', 'Muhammad Baihaqi Asshiddiqi')->firstOrFail();
        $this->assertStringEndsWith('@import.spmb.local', $peserta->email);
        $this->assertStringStartsWith('IMP', $peserta->telepon);
        $this->assertTrue(Hash::check('password123', $peserta->password));
        $this->assertSame('password123', $peserta->password_temp);
        $this->assertSame('SMP NEGERI 9 KOTA TANGERANG', $peserta->asal_sekolah);
        $this->assertSame('X1', $peserta->kelas_penempatan);
        $this->assertSame(10, $peserta->kelas_tujuan);

        $this->assertDatabaseHas('formulir_spmb', [
            'peserta_id' => $peserta->id,
            'jenis_kelamin' => 'L',
            'asal_sekolah' => 'SMP NEGERI 9 KOTA TANGERANG',
            'status_verifikasi' => 'terverifikasi',
        ]);

        $this->assertDatabaseHas('tahapan_spmb', [
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 7,
            'tahap_7_selesai' => true,
            'status_kelulusan' => 'lulus',
        ]);

        $this->assertSame(6, SesiTes::query()->where('peserta_id', $peserta->id)->count());
        $this->assertDatabaseHas('sesi_tes', [
            'peserta_id' => $peserta->id,
            'tes_id' => $tes['indo']->id,
            'status' => 'selesai',
            'nilai' => 70,
        ]);
        $this->assertDatabaseHas('sesi_tes', [
            'peserta_id' => $peserta->id,
            'tes_id' => $tes['mtk']->id,
            'status' => 'selesai',
            'nilai' => 90,
        ]);

        $this->assertSame('plegmatis', HasilPsikotesKepribadian::query()->firstOrFail()->hasil_kepribadian);
        $this->assertSame('visual & auditori', HasilGayaBelajar::query()->firstOrFail()->hasil_gaya_belajar);

        $this->post(route('admin.peserta.impor-rekap-seleksi.proses'), [
            'file_rekap' => $this->uploadedRekapFile('rekap-2.xlsx'),
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, Peserta::query()->where('nama', 'Muhammad Baihaqi Asshiddiqi')->count());
        $this->assertSame(6, SesiTes::query()->where('peserta_id', $peserta->id)->count());
    }

    public function test_command_reset_login_pengguna_membersihkan_lockout_dan_hash_password(): void
    {
        $email = 'operator-reset@example.test';
        $pengguna = Pengguna::factory()->create([
            'email' => $email,
            'password' => 'password-lama',
            'aktif' => false,
            'dikunci_sampai' => now()->addMinutes(30),
        ]);
        Cache::put('login_attempts_' . md5($email), 2, now()->addMinutes(30));

        $this->artisan('pengguna:reset-login', [
            'email' => $email,
            '--password' => 'password-baru',
            '--nama' => 'Operator Reset',
            '--peran' => 'admin',
        ])->assertSuccessful();

        $pengguna->refresh();
        $this->assertSame('Operator Reset', $pengguna->nama);
        $this->assertSame('admin', $pengguna->peran);
        $this->assertTrue($pengguna->aktif);
        $this->assertNull($pengguna->dikunci_sampai);
        $this->assertTrue(Hash::check('password-baru', $pengguna->password));
        $this->assertFalse(Cache::has('login_attempts_' . md5($email)));
    }

    /**
     * @return array<string,Tes>
     */
    private function buatTesRekap(): array
    {
        $kepribadian = Tes::factory()->create(['nama' => 'A. TES PERSONALITY PLUS', 'nilai_lulus' => 0]);
        PsikotesKepribadianConfig::query()->create([
            'tes_id' => $kepribadian->id,
            'tipe_kepribadian' => 'plegmatis',
            'nomor_soal' => [1],
        ]);

        $gayaBelajar = Tes::factory()->create(['nama' => 'B. TES MODALITAS', 'nilai_lulus' => 0]);
        GayaBelajarConfig::query()->create([
            'tes_id' => $gayaBelajar->id,
            'aktif' => true,
            'mapping_jawaban' => GayaBelajarConfig::defaultMapping(),
        ]);

        return [
            'kepribadian' => $kepribadian,
            'gaya_belajar' => $gayaBelajar,
            'indo' => Tes::factory()->create(['nama' => 'C. TES B.INDONESIA', 'nilai_lulus' => 60]),
            'ingg' => Tes::factory()->create(['nama' => 'C. TES B. INGGRIS', 'nilai_lulus' => 60]),
            'mtk' => Tes::factory()->create(['nama' => 'C. TES MTK', 'nilai_lulus' => 60]),
            'ipa' => Tes::factory()->create(['nama' => 'C. TES IPA', 'nilai_lulus' => 60]),
        ];
    }

    private function uploadedRekapFile(string $name): UploadedFile
    {
        $path = storage_path('framework/testing/' . $name);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['NO', 'NAMA', 'JK', 'ASAL SMP', 'PERSONALITY PLUS', 'MODALITAS', 'INDO', 'INGG', 'MTK', 'IPA', 'JML', 'KLS'],
            [1, 'Muhammad Baihaqi Asshiddiqi', 'L', 'SMP NEGERI 9 KOTA TANGERANG', 'Plegmatis', 'Visual dan Auditori', 70, 80, 90, 90, 330, 'X1'],
        ]);

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            $name,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
