<?php

namespace Tests\Property;

use Tests\TestCase;
use App\Models\Tes;
use App\Models\Soal;
use App\Models\Token;
use App\Models\Topik;
use App\Models\Peserta;
use App\Models\Pengguna;
use App\Models\SesiTes;
use App\Models\Jawaban;
use App\Services\UjianService;
use App\Services\TesService;
use App\Services\TokenService;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Property-based tests untuk pelaksanaan ujian
 * Kebutuhan: 5.2, 5.4
 */
class UjianPropertyTest extends TestCase
{
    use TestTrait, RefreshDatabase;

    private UjianService $ujianService;
    private TesService $tesService;
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ujianService = app(UjianService::class);
        $this->tesService = app(TesService::class);
        $this->tokenService = app(TokenService::class);
    }

    /**
     * Helper untuk membuat tes dengan soal
     */
    private function buatTesDenganSoal(int $jumlahSoal = 5): array
    {
        $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
        $topik = Topik::create(['nama' => 'Topik Test']);
        
        $soalIds = [];
        for ($i = 0; $i < $jumlahSoal; $i++) {
            $soal = Soal::create([
                'topik_id' => $topik->id,
                'tipe' => 'pilihan_ganda',
                'pertanyaan' => "Soal $i",
                'bobot' => 1,
                'aktif' => true,
            ]);
            
            // Buat jawaban
            for ($j = 0; $j < 4; $j++) {
                Jawaban::create([
                    'soal_id' => $soal->id,
                    'isi_jawaban' => "Jawaban $j untuk soal $i",
                    'benar' => $j === 0, // Jawaban pertama benar
                    'urutan' => $j,
                ]);
            }
            
            $soalIds[] = $soal->id;
        }
        
        $tes = $this->tesService->buat([
            'pengguna_id' => $pengguna->id,
            'nama' => "Tes Ujian",
            'durasi_menit' => 60,
            'nilai_lulus' => 60,
            'status' => 'aktif',
            'soal_ids' => $soalIds,
        ]);
        
        return ['tes' => $tes, 'soalIds' => $soalIds, 'pengguna' => $pengguna];
    }

    /**
     * Property 4: Auto-Save Jawaban Idempoten
     * Menyimpan jawaban yang sama berkali-kali harus menghasilkan state yang sama
     * Kebutuhan: 5.2
     */
    public function testAutoSaveJawabanIdempoten(): void
    {
        $this->forAll(
            Generator\choose(1, 10) // jumlah kali simpan
        )->then(function (int $jumlahSimpan) {
            // Setup
            $data = $this->buatTesDenganSoal(5);
            $tes = $data['tes'];
            $peserta = Peserta::factory()->create();
            
            // Mulai sesi
            $sesi = $this->ujianService->mulaiSesi($tes, $peserta);
            
            // Ambil soal pertama dan jawabannya
            $soalId = $data['soalIds'][0];
            $soal = Soal::with('jawaban')->find($soalId);
            $jawabanBenar = $soal->jawaban()->where('benar', true)->first();
            
            // Simpan jawaban berkali-kali
            $jawabanTerakhir = null;
            for ($i = 0; $i < $jumlahSimpan; $i++) {
                $jawabanTerakhir = $this->ujianService->simpanJawaban($sesi, $soalId, [
                    'jawaban_id' => $jawabanBenar->id,
                    'ragu' => false,
                ]);
            }
            
            // Property: Hanya ada satu record jawaban untuk soal ini
            $jumlahRecord = $sesi->jawabanPeserta()
                ->where('soal_id', $soalId)
                ->count();
            
            $this->assertEquals(
                1,
                $jumlahRecord,
                "Harus hanya ada 1 record jawaban, bukan $jumlahRecord"
            );
            
            // Property: Jawaban yang tersimpan harus benar
            $this->assertEquals(
                $jawabanBenar->id,
                $jawabanTerakhir->jawaban_id,
                "Jawaban yang tersimpan harus sesuai"
            );
        });
    }

    /**
     * Property: Mengubah jawaban harus update record yang sama
     * Kebutuhan: 5.2
     */
    public function testMengubahJawabanUpdateRecordSama(): void
    {
        $this->forAll(
            Generator\choose(2, 5) // jumlah perubahan
        )->then(function (int $jumlahPerubahan) {
            // Setup
            $data = $this->buatTesDenganSoal(5);
            $tes = $data['tes'];
            $peserta = Peserta::factory()->create();
            
            // Mulai sesi
            $sesi = $this->ujianService->mulaiSesi($tes, $peserta);
            
            // Ambil soal dan semua jawabannya
            $soalId = $data['soalIds'][0];
            $soal = Soal::with('jawaban')->find($soalId);
            $jawabanList = $soal->jawaban->pluck('id')->toArray();
            
            // Ubah jawaban berkali-kali
            $jawabanTerakhirId = null;
            for ($i = 0; $i < $jumlahPerubahan; $i++) {
                $jawabanTerakhirId = $jawabanList[$i % count($jawabanList)];
                $this->ujianService->simpanJawaban($sesi, $soalId, [
                    'jawaban_id' => $jawabanTerakhirId,
                ]);
            }
            
            // Property: Hanya ada satu record
            $jumlahRecord = $sesi->jawabanPeserta()
                ->where('soal_id', $soalId)
                ->count();
            
            $this->assertEquals(1, $jumlahRecord);
            
            // Property: Jawaban terakhir yang tersimpan
            $jawabanTersimpan = $sesi->jawabanPeserta()
                ->where('soal_id', $soalId)
                ->first();
            
            $this->assertEquals(
                $jawabanTerakhirId,
                $jawabanTersimpan->jawaban_id
            );
        });
    }

    /**
     * Property 9: Timer Sesi Monoton
     * Waktu tersisa harus selalu berkurang atau tetap (tidak pernah bertambah)
     * Kebutuhan: 5.4
     */
    public function testTimerSesiMonoton(): void
    {
        $this->forAll(
            Generator\choose(1, 5) // detik delay
        )->then(function (int $delay) {
            // Setup
            $data = $this->buatTesDenganSoal(3);
            $tes = $data['tes'];
            $peserta = Peserta::factory()->create();
            
            // Mulai sesi
            $sesi = $this->ujianService->mulaiSesi($tes, $peserta);
            
            // Ambil waktu tersisa awal
            $waktuAwal = $sesi->waktuTersisa();
            
            // Tunggu sebentar (simulasi dengan sleep minimal)
            usleep($delay * 100000); // 0.1 detik per unit
            
            // Refresh sesi
            $sesi->refresh();
            
            // Ambil waktu tersisa setelah delay
            $waktuSetelah = $sesi->waktuTersisa();
            
            // Property: Waktu tersisa harus <= waktu awal
            $this->assertLessThanOrEqual(
                $waktuAwal,
                $waktuSetelah,
                "Waktu tersisa harus monoton menurun"
            );
        });
    }

    /**
     * Property: Sesi yang sudah selesai tidak bisa diubah
     * Kebutuhan: 5.5
     */
    public function testSesiSelesaiTidakBisaDiubah(): void
    {
        $this->forAll(
            Generator\constant(true)
        )->then(function () {
            // Setup
            $data = $this->buatTesDenganSoal(3);
            $tes = $data['tes'];
            $peserta = Peserta::factory()->create();
            
            // Mulai dan selesaikan sesi
            $sesi = $this->ujianService->mulaiSesi($tes, $peserta);
            $this->ujianService->selesaikanSesi($sesi);
            
            // Property: Mencoba simpan jawaban harus gagal
            $this->expectException(\Exception::class);
            $this->ujianService->simpanJawaban($sesi, $data['soalIds'][0], [
                'jawaban_id' => 1,
            ]);
        });
    }

    /**
     * Property: Perhitungan nilai harus konsisten
     * Kebutuhan: 5.5
     */
    public function testPerhitunganNilaiKonsisten(): void
    {
        $this->forAll(
            Generator\choose(3, 10) // jumlah soal
        )->then(function (int $jumlahSoal) {
            // Setup
            $data = $this->buatTesDenganSoal($jumlahSoal);
            $tes = $data['tes'];
            $peserta = Peserta::factory()->create();
            
            // Mulai sesi
            $sesi = $this->ujianService->mulaiSesi($tes, $peserta);
            
            // Jawab semua soal dengan benar
            foreach ($data['soalIds'] as $soalId) {
                $soal = Soal::with('jawaban')->find($soalId);
                $jawabanBenar = $soal->jawaban()->where('benar', true)->first();
                
                $this->ujianService->simpanJawaban($sesi, $soalId, [
                    'jawaban_id' => $jawabanBenar->id,
                ]);
            }
            
            // Selesaikan sesi
            $sesiSelesai = $this->ujianService->selesaikanSesi($sesi);
            
            // Property: Nilai harus 100 jika semua benar
            $this->assertEquals(
                100,
                $sesiSelesai->nilai,
                "Nilai harus 100 jika semua jawaban benar"
            );
        });
    }

    /**
     * Property: Nilai 0 jika tidak ada jawaban benar
     * Kebutuhan: 5.5
     */
    public function testNilaiNolJikaTidakAdaJawabanBenar(): void
    {
        $this->forAll(
            Generator\choose(3, 10) // jumlah soal
        )->then(function (int $jumlahSoal) {
            // Setup
            $data = $this->buatTesDenganSoal($jumlahSoal);
            $tes = $data['tes'];
            $peserta = Peserta::factory()->create();
            
            // Mulai sesi
            $sesi = $this->ujianService->mulaiSesi($tes, $peserta);
            
            // Jawab semua soal dengan salah
            foreach ($data['soalIds'] as $soalId) {
                $soal = Soal::with('jawaban')->find($soalId);
                $jawabanSalah = $soal->jawaban()->where('benar', false)->first();
                
                $this->ujianService->simpanJawaban($sesi, $soalId, [
                    'jawaban_id' => $jawabanSalah->id,
                ]);
            }
            
            // Selesaikan sesi
            $sesiSelesai = $this->ujianService->selesaikanSesi($sesi);
            
            // Property: Nilai harus 0 jika semua salah
            $this->assertEquals(
                0,
                $sesiSelesai->nilai,
                "Nilai harus 0 jika semua jawaban salah"
            );
        });
    }

    /**
     * Property: Pulihkan sesi mengembalikan sesi yang sama
     * Kebutuhan: 5.6
     */
    public function testPulihkanSesiMengembalikanSesiSama(): void
    {
        $this->forAll(
            Generator\constant(true)
        )->then(function () {
            // Setup
            $data = $this->buatTesDenganSoal(5);
            $tes = $data['tes'];
            $peserta = Peserta::factory()->create();
            
            // Mulai sesi
            $sesiAsli = $this->ujianService->mulaiSesi($tes, $peserta);
            
            // Jawab beberapa soal
            $soalId = $data['soalIds'][0];
            $soal = Soal::with('jawaban')->find($soalId);
            $jawabanBenar = $soal->jawaban()->where('benar', true)->first();
            
            $this->ujianService->simpanJawaban($sesiAsli, $soalId, [
                'jawaban_id' => $jawabanBenar->id,
            ]);
            
            // Pulihkan sesi
            $sesiPulih = $this->ujianService->pulihkanSesi($peserta, $tes);
            
            // Property: Sesi yang dipulihkan harus sama
            $this->assertEquals(
                $sesiAsli->id,
                $sesiPulih->id,
                "Sesi yang dipulihkan harus sama dengan sesi asli"
            );
            
            // Property: Jawaban harus tetap ada
            $jawabanTersimpan = $sesiPulih->jawabanPeserta()
                ->where('soal_id', $soalId)
                ->first();
            
            $this->assertEquals(
                $jawabanBenar->id,
                $jawabanTersimpan->jawaban_id,
                "Jawaban harus tetap tersimpan setelah pulih"
            );
        });
    }

    /**
     * Property: Urutan soal konsisten untuk sesi yang sama
     * Kebutuhan: 5.1
     */
    public function testUrutanSoalKonsistenUntukSesiSama(): void
    {
        $this->forAll(
            Generator\choose(5, 15) // jumlah soal
        )->then(function (int $jumlahSoal) {
            // Setup dengan acak_soal = true
            $pengguna = Pengguna::factory()->create(['peran' => 'admin']);
            $topik = Topik::create(['nama' => 'Topik Acak']);
            
            $soalIds = [];
            for ($i = 0; $i < $jumlahSoal; $i++) {
                $soal = Soal::create([
                    'topik_id' => $topik->id,
                    'tipe' => 'pilihan_ganda',
                    'pertanyaan' => "Soal acak $i",
                    'bobot' => 1,
                    'aktif' => true,
                ]);
                $soalIds[] = $soal->id;
            }
            
            $tes = $this->tesService->buat([
                'pengguna_id' => $pengguna->id,
                'nama' => "Tes Acak",
                'durasi_menit' => 60,
                'nilai_lulus' => 60,
                'status' => 'aktif',
                'acak_soal' => true,
                'soal_ids' => $soalIds,
            ]);
            
            $peserta = Peserta::factory()->create();
            
            // Mulai sesi
            $sesi = $this->ujianService->mulaiSesi($tes, $peserta);
            $urutanAwal = $sesi->urutan_soal;
            
            // Akses sesi beberapa kali
            for ($i = 0; $i < 5; $i++) {
                $sesi->refresh();
                
                // Property: Urutan harus tetap sama
                $this->assertEquals(
                    $urutanAwal,
                    $sesi->urutan_soal,
                    "Urutan soal harus konsisten untuk sesi yang sama"
                );
            }
        });
    }
}
