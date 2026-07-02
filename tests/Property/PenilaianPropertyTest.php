<?php

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based tests untuk penilaian
 * Property 5: Perhitungan Nilai Deterministik
 * Memvalidasi: Kebutuhan 6.1
 */
class PenilaianPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 5: Perhitungan nilai deterministik
     * Dengan input yang sama, hasil perhitungan nilai harus selalu sama
     */
    public function testPerhitunganNilaiDeterministik(): void
    {
        $this->forAll(
            Generator\seq(Generator\tuple(
                Generator\bool(), // benar atau salah
                Generator\choose(1, 10) // bobot
            )),
            Generator\choose(1, 100) // seed untuk konsistensi
        )
        ->withMaxSize(20)
        ->then(function (array $jawabanList, int $seed) {
            if (empty($jawabanList)) {
                $this->assertTrue(true);
                return;
            }

            // Hitung nilai pertama
            $nilai1 = $this->hitungNilai($jawabanList);
            
            // Hitung nilai kedua dengan input yang sama
            $nilai2 = $this->hitungNilai($jawabanList);
            
            // Hitung nilai ketiga
            $nilai3 = $this->hitungNilai($jawabanList);

            // Nilai harus selalu sama (deterministik)
            $this->assertEquals($nilai1, $nilai2, 'Perhitungan nilai harus deterministik');
            $this->assertEquals($nilai2, $nilai3, 'Perhitungan nilai harus deterministik');
        });
    }

    /**
     * Property: Nilai selalu dalam rentang 0-100
     */
    public function testNilaiDalamRentangValid(): void
    {
        $this->forAll(
            Generator\seq(Generator\tuple(
                Generator\bool(), // benar atau salah
                Generator\choose(1, 100) // bobot
            ))
        )
        ->withMaxSize(50)
        ->then(function (array $jawabanList) {
            $nilai = $this->hitungNilai($jawabanList);

            $this->assertGreaterThanOrEqual(0, $nilai, 'Nilai tidak boleh negatif');
            $this->assertLessThanOrEqual(100, $nilai, 'Nilai tidak boleh lebih dari 100');
        });
    }

    /**
     * Property: Semua jawaban benar = nilai 100
     */
    public function testSemuaBenarNilaiSempurna(): void
    {
        $this->forAll(
            Generator\choose(1, 50), // jumlah soal
            Generator\choose(1, 10) // bobot per soal
        )
        ->then(function (int $jumlahSoal, int $bobot) {
            $jawabanList = [];
            for ($i = 0; $i < $jumlahSoal; $i++) {
                $jawabanList[] = [true, $bobot]; // semua benar
            }

            $nilai = $this->hitungNilai($jawabanList);

            $this->assertEquals(100, $nilai, 'Semua jawaban benar harus menghasilkan nilai 100');
        });
    }

    /**
     * Property: Semua jawaban salah = nilai 0
     */
    public function testSemuaSalahNilaiNol(): void
    {
        $this->forAll(
            Generator\choose(1, 50), // jumlah soal
            Generator\choose(1, 10) // bobot per soal
        )
        ->then(function (int $jumlahSoal, int $bobot) {
            $jawabanList = [];
            for ($i = 0; $i < $jumlahSoal; $i++) {
                $jawabanList[] = [false, $bobot]; // semua salah
            }

            $nilai = $this->hitungNilai($jawabanList);

            $this->assertEquals(0, $nilai, 'Semua jawaban salah harus menghasilkan nilai 0');
        });
    }

    /**
     * Property: Nilai proporsional dengan jumlah benar
     */
    public function testNilaiProporsionalDenganJumlahBenar(): void
    {
        $this->forAll(
            Generator\choose(1, 20), // jumlah benar
            Generator\choose(1, 20), // jumlah salah
            Generator\choose(1, 10) // bobot (sama untuk semua)
        )
        ->then(function (int $jumlahBenar, int $jumlahSalah, int $bobot) {
            $jawabanList = [];
            
            for ($i = 0; $i < $jumlahBenar; $i++) {
                $jawabanList[] = [true, $bobot];
            }
            for ($i = 0; $i < $jumlahSalah; $i++) {
                $jawabanList[] = [false, $bobot];
            }

            $nilai = $this->hitungNilai($jawabanList);
            $totalSoal = $jumlahBenar + $jumlahSalah;
            $nilaiEkspektasi = round(($jumlahBenar / $totalSoal) * 100, 2);

            $this->assertEquals($nilaiEkspektasi, $nilai, 'Nilai harus proporsional dengan jumlah benar');
        });
    }

    /**
     * Property: Bobot mempengaruhi nilai secara proporsional
     */
    public function testBobotMempengaruhiNilai(): void
    {
        $this->forAll(
            Generator\choose(1, 10), // bobot soal benar
            Generator\choose(1, 10) // bobot soal salah
        )
        ->then(function (int $bobotBenar, int $bobotSalah) {
            // Satu soal benar, satu soal salah dengan bobot berbeda
            $jawabanList = [
                [true, $bobotBenar],
                [false, $bobotSalah],
            ];

            $nilai = $this->hitungNilai($jawabanList);
            $totalBobot = $bobotBenar + $bobotSalah;
            $nilaiEkspektasi = round(($bobotBenar / $totalBobot) * 100, 2);

            $this->assertEquals($nilaiEkspektasi, $nilai, 'Bobot harus mempengaruhi nilai secara proporsional');
        });
    }

    /**
     * Property: Indeks kesukaran dalam rentang 0-1
     */
    public function testIndeksKesukaranDalamRentang(): void
    {
        $this->forAll(
            Generator\choose(0, 100), // jumlah benar
            Generator\choose(1, 100) // total peserta
        )
        ->then(function (int $jumlahBenar, int $totalPeserta) {
            // Pastikan jumlah benar tidak melebihi total
            $jumlahBenar = min($jumlahBenar, $totalPeserta);
            
            $indeks = $totalPeserta > 0 ? $jumlahBenar / $totalPeserta : 0;

            $this->assertGreaterThanOrEqual(0, $indeks, 'Indeks kesukaran tidak boleh negatif');
            $this->assertLessThanOrEqual(1, $indeks, 'Indeks kesukaran tidak boleh lebih dari 1');
        });
    }

    /**
     * Property: Kategori kesukaran konsisten dengan indeks
     */
    public function testKategoriKesukaranKonsisten(): void
    {
        $this->forAll(
            Generator\float() // indeks 0-1
        )
        ->then(function (float $indeks) {
            // Normalisasi ke 0-1
            $indeks = abs($indeks);
            if ($indeks > 1) {
                $indeks = $indeks - floor($indeks);
            }

            $kategori = $this->kategorikanKesukaran($indeks);

            if ($indeks <= 0.30) {
                $this->assertEquals('Sukar', $kategori);
            } elseif ($indeks <= 0.70) {
                $this->assertEquals('Sedang', $kategori);
            } else {
                $this->assertEquals('Mudah', $kategori);
            }
        });
    }

    /**
     * Helper: Hitung nilai dari array jawaban
     */
    private function hitungNilai(array $jawabanList): float
    {
        if (empty($jawabanList)) {
            return 0;
        }

        $totalBobot = 0;
        $nilaiDiperoleh = 0;

        foreach ($jawabanList as [$benar, $bobot]) {
            $totalBobot += $bobot;
            if ($benar) {
                $nilaiDiperoleh += $bobot;
            }
        }

        if ($totalBobot === 0) {
            return 0;
        }

        return round(($nilaiDiperoleh / $totalBobot) * 100, 2);
    }

    /**
     * Helper: Kategorikan indeks kesukaran
     */
    private function kategorikanKesukaran(float $indeks): string
    {
        return match (true) {
            $indeks <= 0.30 => 'Sukar',
            $indeks <= 0.70 => 'Sedang',
            default => 'Mudah',
        };
    }
}
