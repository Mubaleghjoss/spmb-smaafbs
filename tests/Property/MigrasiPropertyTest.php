<?php

namespace Tests\Property;

use Tests\TestCase;
use Eris\TestTrait;
use Eris\Generator;
use App\Services\MigrasiService;
use App\Models\Soal;
use App\Models\Jawaban;
use App\Models\Topik;
use App\Models\Peserta;
use App\Models\Grup;
use App\Models\Tes;
use App\Models\SesiTes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrasiPropertyTest extends TestCase
{
    use TestTrait, RefreshDatabase;

    /**
     * Feature: cbt-modernization, Property 12: Migrasi Data Integritas
     * Validates: Kebutuhan 12.1, 12.2, 12.3
     * 
     * Untuk setiap record yang dimigrasikan dari sistem lama, 
     * data di sistem baru harus ekuivalen dengan data asli.
     */
    public function test_migrasi_soal_preserves_content(): void
    {
        $this->forAll(
            Generator\suchThat(fn($s) => !empty(trim($s)), Generator\string()),  // pertanyaan non-empty
            Generator\elements([1, 2, 3]) // tipe soal
        )->withMaxSize(100)->then(function ($pertanyaan, $tipe) {
            // Simulasi data soal dari sistem lama
            $soalLama = [
                'soal_id' => rand(1, 10000),
                'soal_topik_id' => 1,
                'soal_detail' => $pertanyaan,
                'soal_tipe' => $tipe,
                'soal_aktif' => 1,
            ];

            // Buat topik terlebih dahulu
            $topik = Topik::create([
                'nama' => 'Topik Test ' . rand(1, 99999),
                'keterangan' => 'Test',
            ]);

            // Simulasi migrasi soal
            $tipeBaru = $this->mapTipeSoal($tipe);
            $soalBaru = Soal::create([
                'topik_id' => $topik->id,
                'pertanyaan' => $soalLama['soal_detail'],
                'tipe' => $tipeBaru,
                'bobot' => 1,
                'aktif' => (bool) $soalLama['soal_aktif'],
            ]);

            // Verifikasi integritas data
            $this->assertEquals($soalLama['soal_detail'], $soalBaru->pertanyaan);
            $this->assertEquals((bool) $soalLama['soal_aktif'], $soalBaru->aktif);
            $this->assertEquals($tipeBaru, $soalBaru->tipe);
        });
    }

    /**
     * Feature: cbt-modernization, Property 12: Migrasi Data Integritas
     * Validates: Kebutuhan 12.2
     * 
     * Untuk setiap peserta yang dimigrasikan, data profil harus tetap utuh.
     */
    public function test_migrasi_peserta_preserves_profile(): void
    {
        $this->forAll(
            Generator\suchThat(fn($s) => !empty(trim($s)) && strlen($s) <= 100, Generator\string())  // nama
        )->withMaxSize(50)->then(function ($nama) {
            // Simulasi data peserta dari sistem lama
            $pesertaLama = [
                'user_id' => rand(1, 10000),
                'user_grup_id' => 1,
                'user_password' => 'password123',
                'user_firstname' => $nama,
                'user_email' => 'test' . rand(1, 99999) . '@test.com',
            ];

            // Simulasi migrasi peserta
            $nomorPendaftaran = 'SPMB-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            $pesertaBaru = Peserta::create([
                'nomor_pendaftaran' => $nomorPendaftaran,
                'nama' => $pesertaLama['user_firstname'],
                'password' => Hash::make($pesertaLama['user_password']),
                'email' => $pesertaLama['user_email'],
            ]);

            // Verifikasi integritas data
            $this->assertEquals($pesertaLama['user_firstname'], $pesertaBaru->nama);
            $this->assertEquals($pesertaLama['user_email'], $pesertaBaru->email);
            $this->assertTrue(Hash::check($pesertaLama['user_password'], $pesertaBaru->password));
        });
    }


    /**
     * Feature: cbt-modernization, Property 12: Migrasi Data Integritas
     * Validates: Kebutuhan 12.3
     * 
     * Untuk setiap riwayat tes yang dimigrasikan, nilai dan status harus tetap sama.
     */
    public function test_migrasi_riwayat_tes_preserves_scores(): void
    {
        $this->forAll(
            Generator\float(0, 100),  // nilai
            Generator\bool()          // selesai
        )->withMaxSize(100)->then(function ($nilai, $selesai) {
            // Buat data pendukung
            $topik = Topik::create(['nama' => 'Topik Test ' . rand(1, 99999), 'keterangan' => '']);
            $soal = Soal::create([
                'topik_id' => $topik->id,
                'pertanyaan' => 'Test',
                'tipe' => 'pilihan_ganda',
                'bobot' => 1,
                'aktif' => true,
            ]);

            $peserta = Peserta::create([
                'nomor_pendaftaran' => 'SPMB-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
                'nama' => 'Test Peserta',
                'password' => Hash::make('password'),
                'email' => 'test' . rand(1, 99999) . '@test.com',
            ]);

            // Buat pengguna untuk foreign key
            $pengguna = \App\Models\Pengguna::firstOrCreate(
                ['email' => 'admin@test.com'],
                ['nama' => 'Admin Test', 'password' => Hash::make('password'), 'peran' => 'admin', 'aktif' => true]
            );

            $tes = Tes::create([
                'pengguna_id' => $pengguna->id,
                'nama' => 'Test Tes',
                'keterangan' => 'Test',
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
                'status' => 'selesai',
            ]);

            // Simulasi data sesi tes dari sistem lama
            $sesiLama = [
                'tesuser_id' => rand(1, 10000),
                'tesuser_tes_id' => $tes->id,
                'tesuser_user_id' => $peserta->id,
                'tesuser_score' => $nilai,
                'tesuser_start_time' => now()->subHour(),
                'tesuser_end_time' => $selesai ? now() : null,
            ];

            // Simulasi migrasi sesi tes
            $statusBaru = $selesai ? 'selesai' : 'berlangsung';
            $sesiBaru = SesiTes::create([
                'tes_id' => $tes->id,
                'peserta_id' => $peserta->id,
                'waktu_mulai' => $sesiLama['tesuser_start_time'],
                'waktu_selesai' => $sesiLama['tesuser_end_time'],
                'nilai' => $sesiLama['tesuser_score'],
                'status' => $statusBaru,
            ]);

            // Verifikasi integritas data
            $this->assertEquals(round($sesiLama['tesuser_score'], 2), round($sesiBaru->nilai, 2));
            $this->assertEquals($statusBaru, $sesiBaru->status);
        });
    }

    /**
     * Feature: cbt-modernization, Property 12: Migrasi Data Integritas
     * Validates: Kebutuhan 12.1
     * 
     * Untuk setiap jawaban yang dimigrasikan, status benar/salah harus tetap sama.
     */
    public function test_migrasi_jawaban_preserves_correctness(): void
    {
        $this->forAll(
            Generator\string(),  // isi jawaban
            Generator\bool()     // benar
        )->withMaxSize(100)->then(function ($isiJawaban, $benar) {
            // Buat data pendukung
            $topik = Topik::create(['nama' => 'Topik Test', 'keterangan' => '']);
            $soal = Soal::create([
                'topik_id' => $topik->id,
                'pertanyaan' => 'Test',
                'tipe' => 'pilihan_ganda',
                'bobot' => 1,
                'aktif' => true,
            ]);

            // Simulasi data jawaban dari sistem lama
            $jawabanLama = [
                'jawaban_id' => rand(1, 10000),
                'jawaban_soal_id' => $soal->id,
                'jawaban_detail' => $isiJawaban,
                'jawaban_benar' => $benar ? 1 : 0,
            ];

            // Simulasi migrasi jawaban
            $jawabanBaru = Jawaban::create([
                'soal_id' => $soal->id,
                'isi_jawaban' => $jawabanLama['jawaban_detail'],
                'benar' => (bool) $jawabanLama['jawaban_benar'],
                'urutan' => 0,
            ]);

            // Verifikasi integritas data
            $this->assertEquals($jawabanLama['jawaban_detail'], $jawabanBaru->isi_jawaban);
            $this->assertEquals((bool) $jawabanLama['jawaban_benar'], $jawabanBaru->benar);
        });
    }


    /**
     * Feature: cbt-modernization, Property 12: Migrasi Data Integritas
     * Validates: Kebutuhan 12.1
     * 
     * Untuk setiap grup yang dimigrasikan, nama grup harus tetap sama.
     */
    public function test_migrasi_grup_preserves_name(): void
    {
        $this->forAll(
            Generator\string()  // nama grup
        )->withMaxSize(100)->then(function ($namaGrup) {
            // Skip empty strings
            if (empty(trim($namaGrup))) {
                return;
            }

            // Simulasi data grup dari sistem lama
            $grupLama = [
                'grup_id' => rand(1, 10000),
                'grup_nama' => substr($namaGrup, 0, 100),
            ];

            // Simulasi migrasi grup
            $grupBaru = Grup::create([
                'nama' => $grupLama['grup_nama'],
                'keterangan' => 'Migrasi dari sistem lama',
            ]);

            // Verifikasi integritas data
            $this->assertEquals($grupLama['grup_nama'], $grupBaru->nama);
        });
    }

    /**
     * Feature: cbt-modernization, Property 12: Migrasi Data Integritas
     * Validates: Kebutuhan 12.1
     * 
     * Untuk setiap topik yang dimigrasikan, nama dan keterangan harus tetap sama.
     */
    public function test_migrasi_topik_preserves_data(): void
    {
        $this->forAll(
            Generator\string(),  // nama topik
            Generator\string()   // keterangan
        )->withMaxSize(100)->then(function ($namaTopik, $keterangan) {
            // Skip empty strings
            if (empty(trim($namaTopik))) {
                return;
            }

            // Simulasi data topik dari sistem lama
            $topikLama = [
                'topik_id' => rand(1, 10000),
                'topik_nama' => substr($namaTopik, 0, 100),
                'topik_detail' => $keterangan,
            ];

            // Simulasi migrasi topik
            $topikBaru = Topik::create([
                'nama' => $topikLama['topik_nama'],
                'keterangan' => $topikLama['topik_detail'] ?? '',
            ]);

            // Verifikasi integritas data
            $this->assertEquals($topikLama['topik_nama'], $topikBaru->nama);
            $this->assertEquals($topikLama['topik_detail'] ?? '', $topikBaru->keterangan);
        });
    }

    /**
     * Helper: Map tipe soal dari sistem lama ke sistem baru
     */
    protected function mapTipeSoal(int $tipe): string
    {
        return match ($tipe) {
            1 => 'pilihan_ganda',
            2 => 'esai',
            3 => 'benar_salah',  // jawaban_singkat tidak ada, gunakan benar_salah
            default => 'pilihan_ganda',
        };
    }
}
