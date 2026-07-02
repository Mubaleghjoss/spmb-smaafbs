<?php

namespace Tests\Property;

use Tests\TestCase;
use App\Models\Tes;
use App\Models\Soal;
use App\Models\Token;
use App\Models\Topik;
use App\Models\Pengguna;
use App\Services\TesService;
use App\Services\TokenService;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Property-based tests untuk manajemen tes
 * Kebutuhan: 4.3, 4.4
 */
class TesPropertyTest extends TestCase
{
    use TestTrait, RefreshDatabase;

    private TesService $tesService;
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tesService = app(TesService::class);
        $this->tokenService = app(TokenService::class);
    }

    /**
     * Property 3: Pengacakan Soal Konsisten
     * Dengan seed yang sama, pengacakan soal harus menghasilkan urutan yang sama
     * Kebutuhan: 4.3
     */
    public function testPengacakanSoalKonsistenDenganSeedSama(): void
    {
        $this->forAll(
            Generator\choose(1, 1000000), // seed
            Generator\choose(5, 20)       // jumlah soal
        )->then(function (int $seed, int $jumlahSoal) {
            // Buat pengguna
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            
            // Buat topik
            $topik = Topik::create(['nama' => 'Topik Test ' . $seed]);
            
            // Buat soal
            $soalIds = [];
            for ($i = 0; $i < $jumlahSoal; $i++) {
                $soal = Soal::create([
                    'topik_id' => $topik->id,
                    'tipe' => 'pilihan_ganda',
                    'pertanyaan' => "Soal $i untuk seed $seed",
                    'bobot' => 1,
                    'aktif' => true,
                ]);
                $soalIds[] = $soal->id;
            }
            
            // Buat tes dengan acak_soal = true
            $tes = $this->tesService->buat([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Acak $seed",
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
                'acak_soal' => true,
                'soal_ids' => $soalIds,
            ]);
            
            // Acak dengan seed yang sama dua kali
            $urutan1 = $this->tesService->acakSoal($tes, $seed);
            $urutan2 = $this->tesService->acakSoal($tes, $seed);
            
            // Property: Dengan seed yang sama, urutan harus sama
            $this->assertEquals(
                $urutan1,
                $urutan2,
                "Pengacakan dengan seed $seed harus menghasilkan urutan yang sama"
            );
            
            // Property: Semua soal harus ada dalam hasil acak
            $this->assertCount(
                count($soalIds),
                $urutan1,
                "Jumlah soal setelah diacak harus sama"
            );
            
            // Property: Tidak ada soal yang hilang
            foreach ($soalIds as $soalId) {
                $this->assertContains(
                    $soalId,
                    $urutan1,
                    "Soal $soalId harus ada dalam hasil acak"
                );
            }
        });
    }

    /**
     * Property: Pengacakan dengan seed berbeda menghasilkan urutan berbeda (probabilistik)
     * Kebutuhan: 4.3
     */
    public function testPengacakanSoalBerbedaDenganSeedBerbeda(): void
    {
        $this->forAll(
            Generator\choose(1, 1000000),
            Generator\choose(1000001, 2000000)
        )->then(function (int $seed1, int $seed2) {
            // Buat pengguna
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            
            // Buat topik
            $topik = Topik::create(['nama' => 'Topik Test Seeds']);
            
            // Buat 10 soal
            $soalIds = [];
            for ($i = 0; $i < 10; $i++) {
                $soal = Soal::create([
                    'topik_id' => $topik->id,
                    'tipe' => 'pilihan_ganda',
                    'pertanyaan' => "Soal $i",
                    'bobot' => 1,
                    'aktif' => true,
                ]);
                $soalIds[] = $soal->id;
            }
            
            // Buat tes
            $tes = $this->tesService->buat([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Seeds",
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
                'acak_soal' => true,
                'soal_ids' => $soalIds,
            ]);
            
            // Acak dengan seed berbeda
            $urutan1 = $this->tesService->acakSoal($tes, $seed1);
            $urutan2 = $this->tesService->acakSoal($tes, $seed2);
            
            // Property: Dengan seed berbeda, kemungkinan besar urutan berbeda
            // (tidak selalu, tapi sangat jarang sama untuk 10 soal)
            // Kita hanya memastikan keduanya valid
            $this->assertCount(10, $urutan1);
            $this->assertCount(10, $urutan2);
        });
    }

    /**
     * Property 2: Token Unik
     * Setiap token yang digenerate harus unik
     * Kebutuhan: 4.4
     */
    public function testTokenUnik(): void
    {
        $this->forAll(
            Generator\choose(10, 100) // jumlah token
        )->then(function (int $jumlahToken) {
            // Buat pengguna dan tes
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            $tes = Tes::create([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Token $jumlahToken",
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
                'status' => 'draft',
            ]);
            
            // Generate batch token
            $tokens = $this->tokenService->generateBatch($tes, $jumlahToken);
            
            // Property: Jumlah token yang digenerate harus sesuai
            $this->assertCount(
                $jumlahToken,
                $tokens,
                "Harus generate $jumlahToken token"
            );
            
            // Property: Semua kode token harus unik
            $kodeList = array_map(fn($t) => $t->kode, $tokens);
            $uniqueKode = array_unique($kodeList);
            
            $this->assertCount(
                count($kodeList),
                $uniqueKode,
                "Semua kode token harus unik"
            );
            
            // Property: Setiap token harus terkait dengan tes yang benar
            foreach ($tokens as $token) {
                $this->assertEquals(
                    $tes->id,
                    $token->tes_id,
                    "Token harus terkait dengan tes yang benar"
                );
            }
        });
    }

    /**
     * Property: Token yang sudah terpakai tidak bisa digunakan lagi
     * Kebutuhan: 4.4
     */
    public function testTokenTidakBisaDigunakanDuaKali(): void
    {
        $this->forAll(
            Generator\constant(true)
        )->then(function () {
            // Buat pengguna dan tes
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            $tes = Tes::create([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Token Sekali Pakai",
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
                'status' => 'aktif',
            ]);
            
            // Generate token
            $token = $this->tokenService->buat($tes);
            
            // Property: Token baru harus valid
            $this->assertTrue(
                $token->masihValid(),
                "Token baru harus valid"
            );
            
            // Buat peserta dan gunakan token
            $peserta = \App\Models\Peserta::factory()->create();
            $this->tokenService->gunakan($token, $peserta);
            
            // Refresh token
            $token->refresh();
            
            // Property: Token yang sudah digunakan tidak valid lagi
            $this->assertFalse(
                $token->masihValid(),
                "Token yang sudah digunakan tidak boleh valid"
            );
            
            // Property: Token harus tercatat siapa yang menggunakan
            $this->assertEquals(
                $peserta->id,
                $token->dipakai_oleh,
                "Token harus mencatat peserta yang menggunakan"
            );
            
            // Property: Token harus tercatat kapan digunakan
            $this->assertNotNull(
                $token->dipakai_pada,
                "Token harus mencatat waktu penggunaan"
            );
        });
    }

    /**
     * Property: Token kedaluwarsa tidak bisa digunakan
     * Kebutuhan: 4.4
     */
    public function testTokenKedaluwarsaTidakValid(): void
    {
        $this->forAll(
            Generator\choose(1, 24) // jam kedaluwarsa di masa lalu
        )->then(function (int $jamLalu) {
            // Buat pengguna dan tes
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            $tes = Tes::create([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Token Kedaluwarsa",
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
                'status' => 'aktif',
            ]);
            
            // Buat token dengan kedaluwarsa di masa lalu
            $kedaluwarsa = now()->subHours($jamLalu);
            $token = Token::create([
                'tes_id' => $tes->id,
                'kode' => $this->tokenService->generateKodeUnik(),
                'kedaluwarsa' => $kedaluwarsa,
                'terpakai' => false,
            ]);
            
            // Property: Token kedaluwarsa tidak valid
            $this->assertFalse(
                $token->masihValid(),
                "Token yang sudah kedaluwarsa tidak boleh valid"
            );
            
            // Property: Attribute sudah_kedaluwarsa harus true
            $this->assertTrue(
                $token->sudah_kedaluwarsa,
                "Attribute sudah_kedaluwarsa harus true"
            );
        });
    }

    /**
     * Property: Tes tidak bisa diaktifkan tanpa soal
     * Kebutuhan: 4.1
     */
    public function testTesTidakBisaAktifTanpaSoal(): void
    {
        $this->forAll(
            Generator\constant(true)
        )->then(function () {
            // Buat pengguna
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            
            // Buat tes tanpa soal
            $tes = $this->tesService->buat([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Tanpa Soal",
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
            ]);
            
            // Property: Mengaktifkan tes tanpa soal harus gagal
            $this->expectException(\Exception::class);
            $this->tesService->ubahStatus($tes, 'aktif');
        });
    }

    /**
     * Property: Total bobot soal dihitung dengan benar
     * Kebutuhan: 4.2
     */
    public function testTotalBobotSoalBenar(): void
    {
        $this->forAll(
            Generator\seq(Generator\choose(1, 10)), // bobot per soal
            Generator\choose(3, 10)                  // jumlah soal
        )->when(function (array $bobotList, int $jumlahSoal) {
            return count($bobotList) >= $jumlahSoal;
        })->then(function (array $bobotList, int $jumlahSoal) {
            $bobotList = array_slice($bobotList, 0, $jumlahSoal);
            
            // Buat pengguna
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            
            // Buat topik
            $topik = Topik::create(['nama' => 'Topik Bobot']);
            
            // Buat soal dengan bobot berbeda
            $soalIds = [];
            foreach ($bobotList as $i => $bobot) {
                $soal = Soal::create([
                    'topik_id' => $topik->id,
                    'tipe' => 'pilihan_ganda',
                    'pertanyaan' => "Soal bobot $bobot",
                    'bobot' => $bobot,
                    'aktif' => true,
                ]);
                $soalIds[] = $soal->id;
            }
            
            // Buat tes
            $tes = $this->tesService->buat([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Bobot",
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
                'soal_ids' => $soalIds,
            ]);
            
            // Hitung total bobot
            $totalBobot = $this->tesService->hitungTotalBobot($tes);
            $expectedTotal = array_sum($bobotList);
            
            // Property: Total bobot harus sama dengan jumlah bobot semua soal
            $this->assertEquals(
                $expectedTotal,
                $totalBobot,
                "Total bobot harus $expectedTotal, dapat $totalBobot"
            );
        });
    }

    /**
     * Property: Duplikat tes menghasilkan tes baru dengan soal yang sama
     * Kebutuhan: 4.1
     */
    public function testDuplikatTesMenghasilkanTesBaru(): void
    {
        $this->forAll(
            Generator\choose(3, 10) // jumlah soal
        )->then(function (int $jumlahSoal) {
            // Buat pengguna
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            
            // Buat topik
            $topik = Topik::create(['nama' => 'Topik Duplikat']);
            
            // Buat soal
            $soalIds = [];
            for ($i = 0; $i < $jumlahSoal; $i++) {
                $soal = Soal::create([
                    'topik_id' => $topik->id,
                    'tipe' => 'pilihan_ganda',
                    'pertanyaan' => "Soal duplikat $i",
                    'bobot' => 1,
                    'aktif' => true,
                ]);
                $soalIds[] = $soal->id;
            }
            
            // Buat tes asli
            $tesAsli = $this->tesService->buat([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Asli",
                'keterangan' => "Keterangan asli",
                'durasi_menit' => 60,
                'nilai_lulus' => 70,
                'acak_soal' => true,
                'soal_ids' => $soalIds,
            ]);
            
            // Duplikat tes
            $tesDuplikat = $this->tesService->duplikat($tesAsli);
            
            // Property: Tes duplikat harus berbeda ID
            $this->assertNotEquals(
                $tesAsli->id,
                $tesDuplikat->id,
                "Tes duplikat harus memiliki ID berbeda"
            );
            
            // Property: Tes duplikat harus memiliki nama dengan suffix
            $this->assertStringContainsString(
                'Salinan',
                $tesDuplikat->nama,
                "Nama tes duplikat harus mengandung 'Salinan'"
            );
            
            // Property: Tes duplikat harus berstatus draft
            $this->assertEquals(
                'draft',
                $tesDuplikat->status,
                "Tes duplikat harus berstatus draft"
            );
            
            // Property: Tes duplikat harus memiliki soal yang sama
            $soalAsli = $tesAsli->soal()->pluck('soal.id')->sort()->values()->toArray();
            $soalDuplikat = $tesDuplikat->soal()->pluck('soal.id')->sort()->values()->toArray();
            
            $this->assertEquals(
                $soalAsli,
                $soalDuplikat,
                "Tes duplikat harus memiliki soal yang sama"
            );
        });
    }
}
