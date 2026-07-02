<?php

namespace Tests\Property;

use Tests\TestCase;
use App\Models\Soal;
use App\Models\Topik;
use App\Models\Jawaban;
use App\Models\Pengguna;
use App\Services\SoalService;
use App\Services\ImporEksporSoalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based tests untuk manajemen soal
 * Kebutuhan: 2.1, 2.4, 2.6
 */
class SoalPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    private SoalService $soalService;
    private ImporEksporSoalService $imporEksporService;
    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();
        $this->soalService = app(SoalService::class);
        $this->imporEksporService = app(ImporEksporSoalService::class);
        
        $this->pengguna = Pengguna::factory()->create([
            'peran' => 'admin',
        ]);
    }

    /**
     * Property 7: Impor-Ekspor Soal Round-Trip
     * Soal yang diekspor dan diimpor kembali harus memiliki data yang sama
     * Memvalidasi: Kebutuhan 2.4, 2.6
     */
    public function testImporEksporSoalRoundTrip(): void
    {
        $this
            ->forAll(
                Generator\choose(1, 10), // jumlah soal
                Generator\elements('pilihan_ganda', 'jawaban_ganda', 'benar_salah')
            )
            ->then(function (int $jumlahSoal, string $tipe) {
                // Buat topik
                $topik = Topik::create(['nama' => 'Topik Test ' . uniqid()]);

                // Buat soal dengan jawaban
                $soalAsli = [];
                for ($i = 0; $i < $jumlahSoal; $i++) {
                    $soal = Soal::create([
                        'topik_id' => $topik->id,
                        'pertanyaan' => "Pertanyaan test {$i} " . uniqid(),
                        'tipe' => $tipe,
                        'bobot' => rand(1, 5),
                        'aktif' => true,
                        'dibuat_oleh' => $this->pengguna->id,
                    ]);

                    // Buat jawaban
                    $jawabanBenarIndex = rand(0, 3);
                    for ($j = 0; $j < 4; $j++) {
                        Jawaban::create([
                            'soal_id' => $soal->id,
                            'isi_jawaban' => "Jawaban " . chr(65 + $j) . " untuk soal {$i}",
                            'benar' => $j === $jawabanBenarIndex,
                            'urutan' => $j,
                        ]);
                    }

                    $soalAsli[] = $soal->fresh(['jawaban']);
                }

                // Ekspor ke Excel
                $pathEkspor = $this->imporEksporService->eksporKeExcel($topik->id);
                $this->assertFileExists($pathEkspor);

                // Hapus soal asli
                Soal::where('topik_id', $topik->id)->delete();
                $this->assertEquals(0, Soal::where('topik_id', $topik->id)->count());

                // Impor kembali
                $hasil = $this->imporEksporService->imporDariExcel($pathEkspor, $this->pengguna->id);

                // Verifikasi jumlah soal yang berhasil diimpor
                $this->assertEquals($jumlahSoal, $hasil['sukses']);
                $this->assertEquals(0, $hasil['gagal']);

                // Cleanup
                @unlink($pathEkspor);
            });
    }

    /**
     * Property: CRUD Soal Konsisten
     * Operasi CRUD pada soal harus konsisten
     */
    public function testCrudSoalKonsisten(): void
    {
        $this
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) >= 10,
                    Generator\string()
                ),
                Generator\elements('pilihan_ganda', 'jawaban_ganda', 'esai', 'benar_salah'),
                Generator\choose(1, 10)
            )
            ->then(function (string $pertanyaan, string $tipe, int $bobot) {
                // Buat topik
                $topik = Topik::create(['nama' => 'Topik ' . uniqid()]);

                // Buat soal
                $data = [
                    'topik_id' => $topik->id,
                    'pertanyaan' => $pertanyaan,
                    'tipe' => $tipe,
                    'bobot' => $bobot,
                    'aktif' => true,
                ];

                if ($tipe !== 'esai') {
                    $data['jawaban'] = [
                        ['isi' => 'Jawaban A', 'benar' => true],
                        ['isi' => 'Jawaban B', 'benar' => false],
                        ['isi' => 'Jawaban C', 'benar' => false],
                        ['isi' => 'Jawaban D', 'benar' => false],
                    ];
                }

                $soal = $this->soalService->buat($data, $this->pengguna->id);

                // Verifikasi create
                $this->assertNotNull($soal->id);
                $this->assertEquals($pertanyaan, $soal->pertanyaan);
                $this->assertEquals($tipe, $soal->tipe);
                $this->assertEquals($bobot, $soal->bobot);

                // Update
                $pertanyaanBaru = $pertanyaan . ' (updated)';
                $dataUpdate = array_merge($data, ['pertanyaan' => $pertanyaanBaru]);
                $soalUpdated = $this->soalService->perbarui($soal, $dataUpdate, $this->pengguna->id);

                // Verifikasi update
                $this->assertEquals($pertanyaanBaru, $soalUpdated->pertanyaan);

                // Delete
                $soalId = $soal->id;
                $this->soalService->hapus($soal);

                // Verifikasi delete
                $this->assertNull(Soal::find($soalId));
            });
    }

    /**
     * Property: Toggle Aktif Idempoten
     * Toggle aktif dua kali harus kembali ke status awal
     */
    public function testToggleAktifIdempoten(): void
    {
        $this
            ->forAll(Generator\bool())
            ->then(function (bool $statusAwal) {
                $soal = Soal::create([
                    'pertanyaan' => 'Test pertanyaan ' . uniqid(),
                    'tipe' => 'pilihan_ganda',
                    'bobot' => 1,
                    'aktif' => $statusAwal,
                    'dibuat_oleh' => $this->pengguna->id,
                ]);

                // Toggle pertama
                $soal = $this->soalService->toggleAktif($soal);
                $this->assertEquals(!$statusAwal, $soal->aktif);

                // Toggle kedua
                $soal = $this->soalService->toggleAktif($soal);
                $this->assertEquals($statusAwal, $soal->aktif);
            });
    }

    /**
     * Property: Duplikat Soal Menghasilkan Soal Baru
     * Duplikat soal harus menghasilkan soal baru dengan ID berbeda
     */
    public function testDuplikatSoalMenghasilkanSoalBaru(): void
    {
        $this
            ->forAll(Generator\choose(1, 5)) // jumlah jawaban
            ->then(function (int $jumlahJawaban) {
                $jumlahJawaban = max(2, $jumlahJawaban); // minimal 2 jawaban

                $soalAsli = Soal::create([
                    'pertanyaan' => 'Pertanyaan asli ' . uniqid(),
                    'tipe' => 'pilihan_ganda',
                    'bobot' => 1,
                    'aktif' => true,
                    'dibuat_oleh' => $this->pengguna->id,
                ]);

                // Buat jawaban
                for ($i = 0; $i < $jumlahJawaban; $i++) {
                    Jawaban::create([
                        'soal_id' => $soalAsli->id,
                        'isi_jawaban' => "Jawaban " . chr(65 + $i),
                        'benar' => $i === 0,
                        'urutan' => $i,
                    ]);
                }

                $soalAsli->load('jawaban');

                // Duplikat
                $soalDuplikat = $this->soalService->duplikat($soalAsli, $this->pengguna->id);

                // Verifikasi
                $this->assertNotEquals($soalAsli->id, $soalDuplikat->id);
                $this->assertStringContainsString('[DUPLIKAT]', $soalDuplikat->pertanyaan);
                $this->assertEquals($soalAsli->tipe, $soalDuplikat->tipe);
                $this->assertEquals($soalAsli->bobot, $soalDuplikat->bobot);
                $this->assertEquals($jumlahJawaban, $soalDuplikat->jawaban->count());
            });
    }

    /**
     * Property: Filter Soal Konsisten
     * Filter soal harus mengembalikan hasil yang sesuai kriteria
     */
    public function testFilterSoalKonsisten(): void
    {
        $this
            ->forAll(
                Generator\choose(5, 20), // jumlah soal
                Generator\elements('pilihan_ganda', 'jawaban_ganda', 'esai', 'benar_salah')
            )
            ->then(function (int $jumlahSoal, string $tipeFilter) {
                // Buat soal dengan berbagai tipe
                $tipeList = ['pilihan_ganda', 'jawaban_ganda', 'esai', 'benar_salah'];
                
                for ($i = 0; $i < $jumlahSoal; $i++) {
                    Soal::create([
                        'pertanyaan' => "Pertanyaan {$i} " . uniqid(),
                        'tipe' => $tipeList[$i % 4],
                        'bobot' => 1,
                        'aktif' => true,
                        'dibuat_oleh' => $this->pengguna->id,
                    ]);
                }

                // Filter berdasarkan tipe
                $hasil = $this->soalService->ambilDenganFilter(['tipe' => $tipeFilter], 100);

                // Verifikasi semua hasil sesuai filter
                foreach ($hasil as $soal) {
                    $this->assertEquals($tipeFilter, $soal->tipe);
                }
            });
    }
}
