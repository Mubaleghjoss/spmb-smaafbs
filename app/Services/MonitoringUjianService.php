<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\SesiTes;
use App\Models\Peserta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service untuk monitoring ujian real-time
 * Kebutuhan: 7.1, 7.2, 7.3, 7.4
 */
class MonitoringUjianService
{
    /**
     * Ambil statistik dashboard admin
     * Kebutuhan: 7.1
     */
    public function ambilStatistikDashboard(): array
    {
        $today = Carbon::today();

        return [
            'tes_aktif' => Tes::where('status', 'aktif')->count(),
            'tes_draft' => Tes::where('status', 'draft')->count(),
            'tes_selesai' => Tes::where('status', 'selesai')->count(),
            'total_tes' => Tes::count(),
            'peserta_online' => SesiTes::where('status', 'berlangsung')->count(),
            'sesi_hari_ini' => SesiTes::whereDate('waktu_mulai', $today)->count(),
            'sesi_selesai_hari_ini' => SesiTes::whereDate('waktu_selesai', $today)
                ->whereIn('status', ['selesai', 'timeout'])
                ->count(),
            'rata_rata_nilai_hari_ini' => SesiTes::whereDate('waktu_selesai', $today)
                ->whereIn('status', ['selesai', 'timeout'])
                ->avg('nilai') ?? 0,
        ];
    }

    /**
     * Ambil daftar tes aktif dengan statistik
     * Kebutuhan: 7.1
     */
    public function ambilTesAktif(): Collection
    {
        return Tes::where('status', 'aktif')
            ->withCount([
                'sesiTes as peserta_online' => function ($q) {
                    $q->where('status', 'berlangsung');
                },
                'sesiTes as peserta_selesai' => function ($q) {
                    $q->whereIn('status', ['selesai', 'timeout']);
                },
            ])
            ->get();
    }

    /**
     * Ambil peserta online untuk tes tertentu
     * Kebutuhan: 7.2
     */
    public function ambilPesertaOnline(Tes $tes): Collection
    {
        return SesiTes::where('tes_id', $tes->id)
            ->where('status', 'berlangsung')
            ->with('peserta')
            ->get()
            ->map(function ($sesi) {
                $totalSoal = count($sesi->urutan_soal ?? []);
                $dijawab = $sesi->jawabanPeserta()
                    ->whereNotNull('jawaban_id')
                    ->orWhereNotNull('jawaban_ganda')
                    ->orWhereNotNull('jawaban_esai')
                    ->count();

                return [
                    'sesi_id' => $sesi->id,
                    'peserta' => $sesi->peserta,
                    'waktu_mulai' => $sesi->waktu_mulai,
                    'waktu_tersisa' => $sesi->waktuTersisa(),
                    'soal_saat_ini' => $sesi->soal_saat_ini,
                    'total_soal' => $totalSoal,
                    'dijawab' => $dijawab,
                    'progres' => $totalSoal > 0 ? round(($dijawab / $totalSoal) * 100) : 0,
                    'ip_address' => $sesi->ip_address,
                ];
            });
    }

    /**
     * Ambil riwayat sesi untuk tes
     * Kebutuhan: 7.3
     */
    public function ambilRiwayatSesi(Tes $tes, array $filter = []): Collection
    {
        $query = SesiTes::where('tes_id', $tes->id)
            ->with('peserta')
            ->orderBy('waktu_mulai', 'desc');

        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        if (!empty($filter['tanggal'])) {
            $query->whereDate('waktu_mulai', $filter['tanggal']);
        }

        return $query->get();
    }

    /**
     * Perpanjang waktu sesi peserta
     * Kebutuhan: 7.4
     */
    public function perpanjangWaktu(SesiTes $sesi, int $menitTambahan): SesiTes
    {
        if ($sesi->sudahSelesai()) {
            throw new \Exception('Sesi sudah selesai, tidak bisa diperpanjang.');
        }

        // Simpan waktu mulai asli untuk log
        $waktuMulaiLama = $sesi->waktu_mulai->copy();

        // Perpanjang dengan menggeser waktu mulai ke belakang
        // Ini efektif menambah durasi tersisa
        $sesi->update([
            'waktu_mulai' => $sesi->waktu_mulai->addMinutes($menitTambahan),
        ]);

        return $sesi->fresh();
    }

    /**
     * Reset sesi peserta
     * Kebutuhan: 7.4
     */
    public function resetSesi(SesiTes $sesi): SesiTes
    {
        return DB::transaction(function () use ($sesi) {
            $this->hapusHasilTurunan($sesi);

            // Hapus semua jawaban
            $sesi->jawabanPeserta()->delete();

            // Reset sesi
            $sesi->update([
                'waktu_mulai' => now(),
                'waktu_selesai' => null,
                'status' => 'berlangsung',
                'nilai' => null,
                'status_verifikasi_tes' => null,
                'catatan_verifikasi' => null,
                'diverifikasi_oleh' => null,
                'diverifikasi_pada' => null,
                'soal_saat_ini' => 1,
            ]);

            // Inisialisasi ulang jawaban kosong
            foreach ($sesi->urutan_soal as $soalId) {
                $sesi->jawabanPeserta()->create([
                    'soal_id' => $soalId,
                    'ragu' => false,
                ]);
            }

            return $sesi->fresh();
        });
    }

    public function setujuiPerpanjanganTimeout(
        SesiTes $sesi,
        int $menit,
        int $adminId,
        ?string $catatan = null
    ): SesiTes {
        if ($sesi->status !== 'timeout') {
            throw new \Exception('Hanya sesi yang waktu habis yang bisa diperpanjang dari halaman verifikasi.');
        }

        return DB::transaction(function () use ($sesi, $menit, $adminId, $catatan) {
            $this->hapusHasilTurunan($sesi);

            $durasiMenit = max((int) ($sesi->tes?->durasi_menit ?? $menit), 1);
            $menitDiberikan = min($menit, $durasiMenit);
            $waktuMulaiBaru = now()->subMinutes(max($durasiMenit - $menitDiberikan, 0));

            $sesi->update([
                'waktu_mulai' => $waktuMulaiBaru,
                'waktu_selesai' => null,
                'status' => 'berlangsung',
                'nilai' => null,
                'status_verifikasi_tes' => null,
                'catatan_verifikasi' => null,
                'diverifikasi_oleh' => null,
                'diverifikasi_pada' => null,
                'permohonan_ulang_status' => SesiTes::PERMOHONAN_ULANG_DISETUJUI,
                'permohonan_ulang_tipe' => SesiTes::PERMOHONAN_TIPE_PERPANJANGAN,
                'permohonan_ulang_menit' => $menitDiberikan,
                'permohonan_ulang_catatan_admin' => $catatan,
                'permohonan_ulang_diproses_oleh' => $adminId,
                'permohonan_ulang_diproses_pada' => now(),
            ]);

            return $sesi->fresh();
        });
    }

    public function setujuiUlangDariAwalTimeout(SesiTes $sesi, int $adminId, ?string $catatan = null): SesiTes
    {
        if ($sesi->status !== 'timeout') {
            throw new \Exception('Hanya sesi yang waktu habis yang bisa diulang dari halaman verifikasi.');
        }

        return DB::transaction(function () use ($sesi, $adminId, $catatan) {
            $this->resetSesi($sesi);

            $sesi->update([
                'permohonan_ulang_status' => SesiTes::PERMOHONAN_ULANG_DISETUJUI,
                'permohonan_ulang_tipe' => SesiTes::PERMOHONAN_TIPE_ULANG_DARI_AWAL,
                'permohonan_ulang_catatan_admin' => $catatan,
                'permohonan_ulang_diproses_oleh' => $adminId,
                'permohonan_ulang_diproses_pada' => now(),
            ]);

            return $sesi->fresh();
        });
    }

    public function tolakPermohonanTimeout(SesiTes $sesi, int $adminId, ?string $catatan = null): SesiTes
    {
        if ($sesi->permohonan_ulang_status !== SesiTes::PERMOHONAN_ULANG_PENDING) {
            throw new \Exception('Permohonan ini sudah diproses atau belum diajukan.');
        }

        $sesi->update([
            'permohonan_ulang_status' => SesiTes::PERMOHONAN_ULANG_DITOLAK,
            'permohonan_ulang_catatan_admin' => $catatan,
            'permohonan_ulang_diproses_oleh' => $adminId,
            'permohonan_ulang_diproses_pada' => now(),
        ]);

        return $sesi->fresh();
    }

    /**
     * Paksa selesaikan sesi
     * Kebutuhan: 7.4
     */
    public function paksaSelesai(SesiTes $sesi): SesiTes
    {
        if (in_array($sesi->status, ['selesai', 'timeout'])) {
            return $sesi;
        }

        // Hitung nilai (jika ada jawaban)
        $penilaianService = app(PenilaianService::class);
        $nilai = $penilaianService->hitungNilai($sesi);

        $sesi->update([
            'waktu_selesai' => now(),
            'status' => 'selesai',
            'nilai' => $nilai,
        ]);

        return $sesi->fresh();
    }

    private function hapusHasilTurunan(SesiTes $sesi): void
    {
        $sesi->hasilGayaBelajar()->delete();
        $sesi->hasilPsikotesKepribadian()->delete();
        $sesi->hasilMbti()->delete();
        $sesi->hasilProfiling()->delete();
    }

    /**
     * Batalkan sesi
     */
    public function batalkanSesi(SesiTes $sesi, string $alasan = ''): SesiTes
    {
        if (in_array($sesi->status, ['selesai', 'timeout'])) {
            throw new \Exception('Sesi sudah selesai, tidak bisa dibatalkan.');
        }

        $sesi->update([
            'waktu_selesai' => now(),
            'status' => 'dibatalkan',
        ]);

        return $sesi->fresh();
    }

    /**
     * Ambil statistik per tes
     */
    public function ambilStatistikTes(Tes $tes): array
    {
        $sesiList = $tes->sesiTes()
            ->whereIn('status', ['selesai', 'timeout'])
            ->get();

        if ($sesiList->isEmpty()) {
            return [
                'total_peserta' => 0,
                'rata_rata' => 0,
                'nilai_tertinggi' => 0,
                'nilai_terendah' => 0,
                'lulus' => 0,
                'tidak_lulus' => 0,
            ];
        }

        $nilaiList = $sesiList->pluck('nilai')->filter();

        return [
            'total_peserta' => $sesiList->count(),
            'rata_rata' => round($nilaiList->avg() ?? 0, 2),
            'nilai_tertinggi' => round($nilaiList->max() ?? 0, 2),
            'nilai_terendah' => round($nilaiList->min() ?? 0, 2),
            'lulus' => $sesiList->where('nilai', '>=', $tes->nilai_lulus)->count(),
            'tidak_lulus' => $sesiList->where('nilai', '<', $tes->nilai_lulus)->count(),
        ];
    }

    /**
     * Ambil grafik aktivitas per jam
     */
    public function ambilGrafikAktivitas(Carbon $tanggal): array
    {
        $data = [];
        for ($jam = 0; $jam < 24; $jam++) {
            $mulai = $tanggal->copy()->setHour($jam)->startOfHour();
            $selesai = $tanggal->copy()->setHour($jam)->endOfHour();

            $data[] = [
                'jam' => sprintf('%02d:00', $jam),
                'sesi_mulai' => SesiTes::whereBetween('waktu_mulai', [$mulai, $selesai])->count(),
                'sesi_selesai' => SesiTes::whereBetween('waktu_selesai', [$mulai, $selesai])->count(),
            ];
        }

        return $data;
    }

    /**
     * Ambil statistik per grup
     */
    public function ambilStatistikPerGrup(Tes $tes): Collection
    {
        return DB::table('sesi_tes')
            ->join('peserta', 'sesi_tes.peserta_id', '=', 'peserta.id')
            ->join('grup_peserta', 'peserta.id', '=', 'grup_peserta.peserta_id')
            ->join('grup', 'grup_peserta.grup_id', '=', 'grup.id')
            ->where('sesi_tes.tes_id', $tes->id)
            ->whereIn('sesi_tes.status', ['selesai', 'timeout'])
            ->groupBy('grup.id', 'grup.nama')
            ->select([
                'grup.id',
                'grup.nama',
                DB::raw('COUNT(*) as total_peserta'),
                DB::raw('AVG(sesi_tes.nilai) as rata_rata'),
                DB::raw('MAX(sesi_tes.nilai) as nilai_tertinggi'),
                DB::raw('MIN(sesi_tes.nilai) as nilai_terendah'),
            ])
            ->get();
    }
}
