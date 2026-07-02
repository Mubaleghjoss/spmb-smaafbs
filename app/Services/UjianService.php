<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\Token;
use App\Models\Peserta;
use App\Models\SesiTes;
use App\Models\JawabanPeserta;
use App\Models\Soal;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Service untuk pelaksanaan ujian
 * Kebutuhan: 5.1, 5.2, 5.5, 5.6
 */
class UjianService
{
    public function __construct(
        private TesService $tesService,
        private TokenService $tokenService
    ) {}

    /**
     * Mulai sesi ujian baru
     * Kebutuhan: 5.1
     */
    public function mulaiSesi(Tes $tes, Peserta $peserta, ?Token $token = null, ?Request $request = null): SesiTes
    {
        return DB::transaction(function () use ($tes, $peserta, $token, $request) {
            // Cek apakah ada sesi aktif
            $sesiAktif = $this->ambilSesiAktif($peserta, $tes);
            if ($sesiAktif) {
                return $sesiAktif;
            }

            // Generate urutan soal (acak jika diaktifkan)
            $urutanSoal = $this->tesService->acakSoal($tes, $peserta->id);

            // Buat sesi baru
            $sesi = SesiTes::create([
                'tes_id' => $tes->id,
                'peserta_id' => $peserta->id,
                'token_id' => $token?->id,
                'waktu_mulai' => now(),
                'status' => 'berlangsung',
                'urutan_soal' => $urutanSoal,
                'soal_saat_ini' => 1,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);

            // Gunakan token jika ada
            if ($token) {
                $this->tokenService->gunakan($token, $peserta);
            }

            // Inisialisasi jawaban kosong untuk semua soal
            $this->inisialisasiJawaban($sesi, $urutanSoal);

            return $sesi;
        });
    }

    /**
     * Inisialisasi jawaban kosong untuk semua soal
     */
    private function inisialisasiJawaban(SesiTes $sesi, array $soalIds): void
    {
        foreach ($soalIds as $soalId) {
            JawabanPeserta::create([
                'sesi_tes_id' => $sesi->id,
                'soal_id' => $soalId,
                'ragu' => false,
            ]);
        }
    }

    /**
     * Simpan jawaban peserta (idempoten)
     * Kebutuhan: 5.2
     */
    public function simpanJawaban(SesiTes $sesi, int $soalId, array $data): JawabanPeserta
    {
        // Cek apakah sesi masih berlangsung
        if ($sesi->sudahSelesai()) {
            throw new \Exception('Sesi ujian sudah berakhir.');
        }

        // Cek apakah waktu masih ada
        if ($sesi->waktuHabis()) {
            $this->selesaikanSesi($sesi, 'timeout');
            throw new \Exception('Waktu ujian sudah habis.');
        }

        // Ambil atau buat jawaban
        $jawaban = JawabanPeserta::firstOrCreate(
            ['sesi_tes_id' => $sesi->id, 'soal_id' => $soalId],
            ['ragu' => false]
        );

        // Update jawaban berdasarkan tipe soal
        $soal = Soal::find($soalId);
        $updateData = ['ragu' => $data['ragu'] ?? false];

        switch ($soal->tipe) {
            case 'pilihan_ganda':
            case 'benar_salah':
                $updateData['jawaban_id'] = $data['jawaban_id'] ?? null;
                $updateData['jawaban_ganda'] = null;
                $updateData['jawaban_esai'] = null;
                break;

            case 'jawaban_ganda':
                $updateData['jawaban_id'] = null;
                $updateData['jawaban_ganda'] = $data['jawaban_ganda'] ?? [];
                $updateData['jawaban_esai'] = null;
                break;

            case 'esai':
                $updateData['jawaban_id'] = null;
                $updateData['jawaban_ganda'] = null;
                $updateData['jawaban_esai'] = $data['jawaban_esai'] ?? null;
                break;
        }

        $jawaban->update($updateData);

        return $jawaban->fresh();
    }

    /**
     * Selesaikan sesi ujian
     * Kebutuhan: 5.5
     */
    public function selesaikanSesi(SesiTes $sesi, string $status = 'selesai'): SesiTes
    {
        if ($sesi->sudahSelesai()) {
            return $sesi;
        }

        return DB::transaction(function () use ($sesi, $status) {
            // Hitung nilai
            $nilai = $this->hitungNilai($sesi);
            $tes = $sesi->tes;
            
            // Cek apakah ini psikotes kepribadian
            $psikotesService = app(\App\Services\PsikotesKepribadianService::class);
            $isPsikotes = $psikotesService->isPsikotesKepribadian($tes);
            
            // Cek apakah ini tes gaya belajar
            $gayaBelajarService = app(\App\Services\GayaBelajarService::class);
            $isGayaBelajar = $gayaBelajarService->isGayaBelajar($tes);
            
            // Cek apakah ini tes MBTI
            $mbtiService = app(\App\Services\MbtiService::class);
            $isMbti = $mbtiService->isMbti($tes);
            
            // Cek apakah ini tes Profiling
            $profilingService = app(\App\Services\ProfilingService::class);
            $isProfiling = $profilingService->isProfiling($tes);
            
            $lulus = $nilai >= $tes->nilai_lulus;

            // Update sesi
            $updateData = [
                'waktu_selesai' => now(),
                'status' => $status,
                'nilai' => $nilai,
            ];
            
            // Jika psikotes, gaya belajar, MBTI, atau Profiling, tidak perlu verifikasi (selalu dianggap selesai)
            // Jika lulus, tidak perlu verifikasi
            // Jika tidak lulus (tes akademik), set status menunggu verifikasi admin
            if (!$isPsikotes && !$isGayaBelajar && !$isMbti && !$isProfiling && !$lulus) {
                $updateData['status_verifikasi_tes'] = 'menunggu';
            }
            
            $sesi->update($updateData);
            $sesi->refresh();
            
            // Jika psikotes, hitung dan simpan hasil kepribadian
            if ($isPsikotes) {
                $psikotesService->hitungHasil($sesi);
            }
            
            // Jika gaya belajar, hitung dan simpan hasil
            if ($isGayaBelajar) {
                $gayaBelajarService->hitungHasil($sesi);
            }
            
            // Jika MBTI, hitung dan simpan hasil
            if ($isMbti) {
                $mbtiService->hitungHasil($sesi);
            }
            
            // Jika Profiling, hitung dan simpan hasil
            if ($isProfiling) {
                $profilingService->hitungHasil($sesi);
            }
            
            // Jika lulus atau psikotes atau gaya belajar atau MBTI atau Profiling, cek apakah semua tes sudah selesai
            if ($lulus || $isPsikotes || $isGayaBelajar || $isMbti || $isProfiling) {
                $this->cekDanSelesaikanTahap4($sesi->peserta);
            }

            return $sesi;
        });
    }
    
    /**
     * Cek apakah semua tes sudah selesai dan selesaikan tahap 4 jika ya
     */
    public function cekDanSelesaikanTahap4(Peserta $peserta): bool
    {
        $tesTersedia = $this->tesService->ambilTesTersediaUntukPeserta($peserta);
        
        if ($tesTersedia->isEmpty()) {
            return false;
        }
        
        $psikotesService = app(\App\Services\PsikotesKepribadianService::class);
        $gayaBelajarService = app(\App\Services\GayaBelajarService::class);
        $mbtiService = app(\App\Services\MbtiService::class);
        
        foreach ($tesTersedia as $tes) {
            $sesiTes = SesiTes::where('peserta_id', $peserta->id)
                ->where('tes_id', $tes->id)
                ->whereIn('status', ['selesai', 'timeout'])
                ->first();
            
            if (!$sesiTes) {
                // Tes belum dikerjakan
                return false;
            }
            
            // Cek apakah ini psikotes (psikotes selalu dianggap selesai)
            $isPsikotes = $psikotesService->isPsikotesKepribadian($tes);
            if ($isPsikotes) {
                continue; // Psikotes tidak perlu cek lulus/tidak
            }
            
            // Cek apakah ini gaya belajar (gaya belajar selalu dianggap selesai)
            $isGayaBelajar = $gayaBelajarService->isGayaBelajar($tes);
            if ($isGayaBelajar) {
                continue; // Gaya belajar tidak perlu cek lulus/tidak
            }
            
            // Cek apakah ini MBTI (MBTI selalu dianggap selesai)
            $isMbti = $mbtiService->isMbti($tes);
            if ($isMbti) {
                continue; // MBTI tidak perlu cek lulus/tidak
            }
            
            // Cek apakah ini Profiling (Profiling selalu dianggap selesai)
            $profilingService = app(\App\Services\ProfilingService::class);
            $isProfiling = $profilingService->isProfiling($tes);
            if ($isProfiling) {
                continue; // Profiling tidak perlu cek lulus/tidak
            }
            
            // Cek apakah lulus atau diloloskan
            $lulus = $sesiTes->nilai >= $tes->nilai_lulus;
            $diloloskan = $sesiTes->status_verifikasi_tes === 'diloloskan';
            
            if (!$lulus && !$diloloskan) {
                // Masih ada tes yang belum lulus/diloloskan
                return false;
            }
        }
        
        // Semua tes selesai, lanjut ke tahap 5
        $spmbService = app(SpmbService::class);
        $spmbService->selesaikanTahapan($peserta, 4);
        return true;
    }

    /**
     * Hitung nilai ujian
     */
    public function hitungNilai(SesiTes $sesi): float
    {
        $totalBobot = 0;
        $nilaiDiperoleh = 0;

        $tes = $sesi->tes;
        $jawabanList = $sesi->jawabanPeserta()->with(['soal.jawaban'])->get();

        foreach ($jawabanList as $jawaban) {
            $soal = $jawaban->soal;
            $bobotSoal = $tes->soal()
                ->where('soal.id', $soal->id)
                ->first()
                ?->pivot->bobot_custom ?? $soal->bobot;

            $totalBobot += $bobotSoal;

            // Cek kebenaran jawaban
            $benar = $this->cekJawabanBenar($jawaban, $soal);
            $jawaban->update(['benar' => $benar]);

            if ($benar) {
                $nilaiDiperoleh += $bobotSoal;
            }
        }

        if ($totalBobot === 0) {
            return 0;
        }

        return round(($nilaiDiperoleh / $totalBobot) * 100, 2);
    }

    /**
     * Cek apakah jawaban benar
     */
    private function cekJawabanBenar(JawabanPeserta $jawaban, Soal $soal): bool
    {
        switch ($soal->tipe) {
            case 'pilihan_ganda':
            case 'benar_salah':
                if (!$jawaban->jawaban_id) {
                    return false;
                }
                $jawabanBenar = $soal->jawaban()->where('benar', true)->first();
                return $jawabanBenar && $jawaban->jawaban_id === $jawabanBenar->id;

            case 'jawaban_ganda':
                if (empty($jawaban->jawaban_ganda)) {
                    return false;
                }
                $jawabanBenarIds = $soal->jawaban()
                    ->where('benar', true)
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->toArray();
                $jawabanPesertaIds = collect($jawaban->jawaban_ganda)
                    ->sort()
                    ->values()
                    ->toArray();
                return $jawabanBenarIds === $jawabanPesertaIds;

            case 'esai':
                // Esai perlu dinilai manual
                return false;

            default:
                return false;
        }
    }


    /**
     * Pulihkan sesi yang terputus
     * Kebutuhan: 5.6
     */
    public function pulihkanSesi(Peserta $peserta, ?Tes $tes = null): ?SesiTes
    {
        $query = SesiTes::where('peserta_id', $peserta->id)
            ->where('status', 'berlangsung');

        if ($tes) {
            $query->where('tes_id', $tes->id);
        }

        $sesi = $query->with(['tes', 'jawabanPeserta'])->first();

        if (!$sesi) {
            return null;
        }

        // Cek apakah waktu sudah habis
        if ($sesi->waktuHabis()) {
            $this->selesaikanSesi($sesi, 'timeout');
            return null;
        }

        return $sesi;
    }

    /**
     * Ambil sesi aktif peserta untuk tes tertentu
     */
    public function ambilSesiAktif(Peserta $peserta, Tes $tes): ?SesiTes
    {
        return SesiTes::where('peserta_id', $peserta->id)
            ->where('tes_id', $tes->id)
            ->where('status', 'berlangsung')
            ->first();
    }

    /**
     * Ambil soal untuk ditampilkan
     */
    public function ambilSoal(SesiTes $sesi, int $nomorSoal): ?array
    {
        $urutanSoal = $sesi->urutan_soal;
        
        if ($nomorSoal < 1 || $nomorSoal > count($urutanSoal)) {
            return null;
        }

        $soalId = $urutanSoal[$nomorSoal - 1];
        $soal = Soal::with('jawaban')->find($soalId);

        if (!$soal) {
            return null;
        }

        // Ambil jawaban peserta jika ada
        $jawabanPeserta = JawabanPeserta::where('sesi_tes_id', $sesi->id)
            ->where('soal_id', $soalId)
            ->first();

        // Acak jawaban jika diaktifkan
        $jawaban = $soal->jawaban;
        if ($sesi->tes->acak_jawaban) {
            $jawaban = $jawaban->shuffle();
        }

        return [
            'nomor' => $nomorSoal,
            'soal' => $soal,
            'jawaban' => $jawaban,
            'jawaban_peserta' => $jawabanPeserta,
            'total_soal' => count($urutanSoal),
        ];
    }

    /**
     * Update posisi soal saat ini
     */
    public function updatePosisiSoal(SesiTes $sesi, int $nomorSoal): void
    {
        $sesi->update(['soal_saat_ini' => $nomorSoal]);
    }

    /**
     * Ambil ringkasan jawaban untuk navigasi
     */
    public function ambilRingkasanJawaban(SesiTes $sesi): array
    {
        $urutanSoal = $sesi->urutan_soal;
        $jawabanList = $sesi->jawabanPeserta()
            ->get()
            ->keyBy('soal_id');

        $ringkasan = [];
        foreach ($urutanSoal as $index => $soalId) {
            $jawaban = $jawabanList->get($soalId);
            $ringkasan[] = [
                'nomor' => $index + 1,
                'soal_id' => $soalId,
                'dijawab' => $jawaban?->sudah_dijawab ?? false,
                'ragu' => $jawaban?->ragu ?? false,
            ];
        }

        return $ringkasan;
    }

    /**
     * Ambil statistik sesi
     */
    public function ambilStatistikSesi(SesiTes $sesi): array
    {
        $ringkasan = $this->ambilRingkasanJawaban($sesi);
        
        $dijawab = collect($ringkasan)->where('dijawab', true)->count();
        $ragu = collect($ringkasan)->where('ragu', true)->count();
        $belumDijawab = collect($ringkasan)->where('dijawab', false)->count();

        return [
            'total_soal' => count($ringkasan),
            'dijawab' => $dijawab,
            'belum_dijawab' => $belumDijawab,
            'ragu' => $ragu,
            'waktu_tersisa' => $sesi->waktuTersisa(),
            'persentase_progres' => $sesi->persentase_progres,
        ];
    }

    /**
     * Cek apakah peserta bisa mengakses tes
     * Hanya cek status tes aktif dan apakah sudah pernah dikerjakan
     * Tidak mengecek waktu mulai/selesai - siswa bebas memilih kapan mengerjakan
     */
    public function bisaMengaksesTes(Peserta $peserta, Tes $tes): array
    {
        // Cek status tes harus aktif
        if ($tes->status !== 'aktif') {
            return [
                'bisa' => false,
                'pesan' => 'Tes belum dibuka.',
            ];
        }

        // Cek apakah sudah pernah selesai (dan belum dihapus untuk mengulang)
        $sesiSelesai = SesiTes::where('peserta_id', $peserta->id)
            ->where('tes_id', $tes->id)
            ->whereIn('status', ['selesai', 'timeout'])
            ->first();

        if ($sesiSelesai) {
            // Cek apakah ini psikotes
            $psikotesService = app(\App\Services\PsikotesKepribadianService::class);
            $isPsikotes = $psikotesService->isPsikotesKepribadian($tes);
            
            // Cek apakah ini gaya belajar
            $gayaBelajarService = app(\App\Services\GayaBelajarService::class);
            $isGayaBelajar = $gayaBelajarService->isGayaBelajar($tes);
            
            // Cek apakah ini MBTI
            $mbtiService = app(\App\Services\MbtiService::class);
            $isMbti = $mbtiService->isMbti($tes);
            
            if ($isPsikotes) {
                return [
                    'bisa' => false,
                    'pesan' => 'Anda sudah mengerjakan psikotes ini.',
                ];
            }
            
            if ($isGayaBelajar) {
                return [
                    'bisa' => false,
                    'pesan' => 'Anda sudah mengerjakan tes gaya belajar ini.',
                ];
            }
            
            if ($isMbti) {
                return [
                    'bisa' => false,
                    'pesan' => 'Anda sudah mengerjakan tes MBTI ini.',
                ];
            }
            
            // Cek apakah ini Profiling
            $profilingService = app(\App\Services\ProfilingService::class);
            $isProfiling = $profilingService->isProfiling($tes);
            
            if ($isProfiling) {
                return [
                    'bisa' => false,
                    'pesan' => 'Anda sudah mengerjakan tes Profiling ini.',
                ];
            }
            
            // Cek apakah lulus atau diloloskan
            $lulus = $sesiSelesai->nilai >= $tes->nilai_lulus;
            $diloloskan = $sesiSelesai->status_verifikasi_tes === 'diloloskan';
            
            if ($lulus) {
                return [
                    'bisa' => false,
                    'pesan' => 'Anda sudah lulus tes ini.',
                ];
            }
            
            if ($diloloskan) {
                return [
                    'bisa' => false,
                    'pesan' => 'Anda sudah diloloskan pada tes ini.',
                ];
            }
            
            // Jika tidak lulus dan belum diloloskan, masih menunggu verifikasi
            return [
                'bisa' => false,
                'pesan' => 'Menunggu keputusan admin.',
            ];
        }

        return [
            'bisa' => true,
            'pesan' => 'OK',
        ];
    }

    /**
     * Ambil hasil ujian peserta
     */
    public function ambilHasil(SesiTes $sesi): array
    {
        $tes = $sesi->tes;
        $lulus = $sesi->nilai >= $tes->nilai_lulus;

        $hasil = [
            'sesi' => $sesi,
            'tes' => $tes,
            'nilai' => $sesi->nilai,
            'nilai_lulus' => $tes->nilai_lulus,
            'lulus' => $lulus,
            'waktu_mulai' => $sesi->waktu_mulai,
            'waktu_selesai' => $sesi->waktu_selesai,
            'durasi_menit' => $sesi->durasi_menit_bulat,
        ];

        // Cek apakah ini psikotes kepribadian
        $psikotesService = app(\App\Services\PsikotesKepribadianService::class);
        if ($psikotesService->isPsikotesKepribadian($tes)) {
            // Hitung dan simpan hasil psikotes
            $hasilPsikotes = $psikotesService->hitungHasil($sesi);
            $hasil['psikotes_kepribadian'] = $hasilPsikotes;
            $hasil['is_psikotes'] = true;
        } else {
            $hasil['is_psikotes'] = false;
        }
        
        // Cek apakah ini tes gaya belajar
        $gayaBelajarService = app(\App\Services\GayaBelajarService::class);
        if ($gayaBelajarService->isGayaBelajar($tes)) {
            // Hitung dan simpan hasil gaya belajar
            $hasilGayaBelajar = $gayaBelajarService->hitungHasil($sesi);
            $hasil['gaya_belajar'] = $hasilGayaBelajar;
            $hasil['is_gaya_belajar'] = true;
        } else {
            $hasil['is_gaya_belajar'] = false;
        }
        
        // Cek apakah ini tes MBTI
        $mbtiService = app(\App\Services\MbtiService::class);
        if ($mbtiService->isMbti($tes)) {
            // Hitung dan simpan hasil MBTI
            $hasilMbti = $mbtiService->hitungHasil($sesi);
            $hasil['mbti'] = $hasilMbti;
            $hasil['is_mbti'] = true;
            
            // Ambil deskripsi tipe
            if ($hasilMbti) {
                $tipeDeskripsi = $mbtiService->getDeskripsiTipe($tes, $hasilMbti->tipe_mbti);
                $hasil['mbti_deskripsi'] = $tipeDeskripsi;
            }
        } else {
            $hasil['is_mbti'] = false;
        }
        
        // Cek apakah ini tes Profiling
        $profilingService = app(\App\Services\ProfilingService::class);
        if ($profilingService->isProfiling($tes)) {
            // Hitung dan simpan hasil Profiling
            $hasilProfiling = $profilingService->hitungHasil($sesi);
            $hasil['profiling'] = $hasilProfiling;
            $hasil['is_profiling'] = true;
            
            // Ambil deskripsi pilar
            if ($hasilProfiling) {
                $pilarDeskripsi = $profilingService->getDeskripsiPilar($tes, $hasilProfiling->pilar_dominan);
                $hasil['profiling_deskripsi'] = $pilarDeskripsi;
            }
        } else {
            $hasil['is_profiling'] = false;
        }

        // Tambahkan detail jawaban jika diizinkan
        if ($tes->tampilkan_pembahasan) {
            $hasil['detail_jawaban'] = $this->ambilDetailJawaban($sesi);
        }

        return $hasil;
    }

    /**
     * Ambil detail jawaban untuk pembahasan
     */
    private function ambilDetailJawaban(SesiTes $sesi): array
    {
        $jawabanList = $sesi->jawabanPeserta()
            ->with(['soal.jawaban', 'jawaban'])
            ->get();

        $detail = [];
        foreach ($jawabanList as $jawaban) {
            $soal = $jawaban->soal;
            $jawabanBenar = $soal->jawaban()->where('benar', true)->first();

            $detail[] = [
                'soal' => $soal,
                'jawaban_peserta' => $jawaban,
                'jawaban_benar' => $jawabanBenar,
                'benar' => $jawaban->benar,
            ];
        }

        return $detail;
    }

    /**
     * Batalkan sesi ujian
     */
    public function batalkanSesi(SesiTes $sesi, string $alasan = ''): SesiTes
    {
        if ($sesi->sudahSelesai()) {
            throw new \Exception('Sesi sudah selesai, tidak bisa dibatalkan.');
        }

        $sesi->update([
            'waktu_selesai' => now(),
            'status' => 'dibatalkan',
        ]);

        return $sesi->fresh();
    }

    /**
     * Perpanjang waktu sesi
     */
    public function perpanjangWaktu(SesiTes $sesi, int $menitTambahan): SesiTes
    {
        if ($sesi->sudahSelesai()) {
            throw new \Exception('Sesi sudah selesai, tidak bisa diperpanjang.');
        }

        // Update waktu mulai untuk menambah durasi efektif
        $waktuMulaiBaru = $sesi->waktu_mulai->addMinutes($menitTambahan);
        $sesi->update(['waktu_mulai' => $waktuMulaiBaru]);

        return $sesi->fresh();
    }
}
