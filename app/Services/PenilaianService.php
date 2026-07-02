<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\Soal;
use App\Models\SesiTes;
use App\Models\JawabanPeserta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk penilaian dan analisis hasil ujian
 * Kebutuhan: 6.1, 6.2, 6.5
 */
class PenilaianService
{
    /**
     * Hitung nilai sesi ujian (deterministik)
     * Kebutuhan: 6.1
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
    public function cekJawabanBenar(JawabanPeserta $jawaban, Soal $soal): bool
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
                    ->map(fn($id) => (int) $id)
                    ->sort()
                    ->values()
                    ->toArray();
                return $jawabanBenarIds === $jawabanPesertaIds;

            case 'esai':
                // Esai perlu dinilai manual, return nilai yang sudah ada
                return $jawaban->benar ?? false;

            default:
                return false;
        }
    }

    /**
     * Hitung ulang semua nilai untuk tes tertentu
     * Kebutuhan: 6.2
     */
    public function hitungUlangNilaiTes(Tes $tes): int
    {
        $sesiList = $tes->sesiTes()
            ->whereIn('status', ['selesai', 'timeout'])
            ->get();

        $count = 0;
        foreach ($sesiList as $sesi) {
            $nilai = $this->hitungNilai($sesi);
            $sesi->update(['nilai' => $nilai]);
            $count++;
        }

        return $count;
    }


    /**
     * Ambil statistik tes
     * Kebutuhan: 6.2
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
                'standar_deviasi' => 0,
                'jumlah_lulus' => 0,
                'jumlah_tidak_lulus' => 0,
                'persentase_lulus' => 0,
                'distribusi_nilai' => [],
            ];
        }

        $nilaiList = $sesiList->pluck('nilai')->filter()->values();
        $totalPeserta = $sesiList->count();
        $rataRata = $nilaiList->avg() ?? 0;
        $nilaiTertinggi = $nilaiList->max() ?? 0;
        $nilaiTerendah = $nilaiList->min() ?? 0;

        // Hitung standar deviasi
        $standarDeviasi = $this->hitungStandarDeviasi($nilaiList->toArray());

        // Hitung lulus/tidak lulus
        $jumlahLulus = $sesiList->where('nilai', '>=', $tes->nilai_lulus)->count();
        $jumlahTidakLulus = $totalPeserta - $jumlahLulus;
        $persentaseLulus = $totalPeserta > 0 ? round(($jumlahLulus / $totalPeserta) * 100, 2) : 0;

        // Distribusi nilai
        $distribusiNilai = $this->hitungDistribusiNilai($nilaiList->toArray());

        return [
            'total_peserta' => $totalPeserta,
            'rata_rata' => round($rataRata, 2),
            'nilai_tertinggi' => round($nilaiTertinggi, 2),
            'nilai_terendah' => round($nilaiTerendah, 2),
            'standar_deviasi' => round($standarDeviasi, 2),
            'jumlah_lulus' => $jumlahLulus,
            'jumlah_tidak_lulus' => $jumlahTidakLulus,
            'persentase_lulus' => $persentaseLulus,
            'distribusi_nilai' => $distribusiNilai,
        ];
    }

    /**
     * Hitung standar deviasi
     */
    private function hitungStandarDeviasi(array $nilai): float
    {
        $count = count($nilai);
        if ($count < 2) {
            return 0;
        }

        $mean = array_sum($nilai) / $count;
        $sumSquaredDiff = 0;

        foreach ($nilai as $n) {
            $sumSquaredDiff += pow($n - $mean, 2);
        }

        return sqrt($sumSquaredDiff / ($count - 1));
    }

    /**
     * Hitung distribusi nilai dalam rentang
     */
    private function hitungDistribusiNilai(array $nilai): array
    {
        $distribusi = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0,
        ];

        foreach ($nilai as $n) {
            if ($n <= 20) {
                $distribusi['0-20']++;
            } elseif ($n <= 40) {
                $distribusi['21-40']++;
            } elseif ($n <= 60) {
                $distribusi['41-60']++;
            } elseif ($n <= 80) {
                $distribusi['61-80']++;
            } else {
                $distribusi['81-100']++;
            }
        }

        return $distribusi;
    }

    /**
     * Analisis butir soal - Indeks Kesukaran
     * Kebutuhan: 6.5
     * P = B/N (B = jumlah benar, N = total peserta)
     * Interpretasi: 0.00-0.30 = Sukar, 0.31-0.70 = Sedang, 0.71-1.00 = Mudah
     */
    public function hitungIndeksKesukaran(Soal $soal, Tes $tes): array
    {
        $jawabanList = JawabanPeserta::whereHas('sesiTes', function ($q) use ($tes) {
            $q->where('tes_id', $tes->id)
              ->whereIn('status', ['selesai', 'timeout']);
        })
        ->where('soal_id', $soal->id)
        ->get();

        $totalPeserta = $jawabanList->count();
        if ($totalPeserta === 0) {
            return [
                'indeks' => 0,
                'kategori' => 'Tidak ada data',
                'jumlah_benar' => 0,
                'total_peserta' => 0,
            ];
        }

        $jumlahBenar = $jawabanList->where('benar', true)->count();
        $indeks = $jumlahBenar / $totalPeserta;

        $kategori = match (true) {
            $indeks <= 0.30 => 'Sukar',
            $indeks <= 0.70 => 'Sedang',
            default => 'Mudah',
        };

        return [
            'indeks' => round($indeks, 3),
            'kategori' => $kategori,
            'jumlah_benar' => $jumlahBenar,
            'total_peserta' => $totalPeserta,
        ];
    }

    /**
     * Analisis butir soal - Indeks Daya Beda
     * Kebutuhan: 6.5
     * D = (BA - BB) / N
     * BA = jumlah benar kelompok atas (27% tertinggi)
     * BB = jumlah benar kelompok bawah (27% terendah)
     * N = jumlah peserta per kelompok
     * Interpretasi: < 0.20 = Jelek, 0.20-0.40 = Cukup, 0.41-0.70 = Baik, > 0.70 = Sangat Baik
     */
    public function hitungIndeksDayaBeda(Soal $soal, Tes $tes): array
    {
        // Ambil semua sesi dengan nilai
        $sesiList = $tes->sesiTes()
            ->whereIn('status', ['selesai', 'timeout'])
            ->whereNotNull('nilai')
            ->orderBy('nilai', 'desc')
            ->get();

        $totalPeserta = $sesiList->count();
        if ($totalPeserta < 4) {
            return [
                'indeks' => 0,
                'kategori' => 'Data tidak cukup',
                'benar_atas' => 0,
                'benar_bawah' => 0,
                'jumlah_per_kelompok' => 0,
            ];
        }

        // Ambil 27% kelompok atas dan bawah
        $jumlahKelompok = max(1, (int) ceil($totalPeserta * 0.27));
        $kelompokAtas = $sesiList->take($jumlahKelompok)->pluck('id');
        $kelompokBawah = $sesiList->reverse()->take($jumlahKelompok)->pluck('id');

        // Hitung jumlah benar per kelompok
        $benarAtas = JawabanPeserta::whereIn('sesi_tes_id', $kelompokAtas)
            ->where('soal_id', $soal->id)
            ->where('benar', true)
            ->count();

        $benarBawah = JawabanPeserta::whereIn('sesi_tes_id', $kelompokBawah)
            ->where('soal_id', $soal->id)
            ->where('benar', true)
            ->count();

        $indeks = ($benarAtas - $benarBawah) / $jumlahKelompok;

        $kategori = match (true) {
            $indeks < 0.20 => 'Jelek',
            $indeks <= 0.40 => 'Cukup',
            $indeks <= 0.70 => 'Baik',
            default => 'Sangat Baik',
        };

        return [
            'indeks' => round($indeks, 3),
            'kategori' => $kategori,
            'benar_atas' => $benarAtas,
            'benar_bawah' => $benarBawah,
            'jumlah_per_kelompok' => $jumlahKelompok,
        ];
    }

    /**
     * Analisis lengkap butir soal untuk tes
     * Kebutuhan: 6.5
     */
    public function analisisButirSoal(Tes $tes): Collection
    {
        $soalList = $tes->soal()->with('topik')->get();

        return $soalList->map(function ($soal) use ($tes) {
            $kesukaran = $this->hitungIndeksKesukaran($soal, $tes);
            $dayaBeda = $this->hitungIndeksDayaBeda($soal, $tes);

            return [
                'soal_id' => $soal->id,
                'pertanyaan' => html_entity_decode(strip_tags(substr($soal->pertanyaan, 0, 100)), ENT_QUOTES, 'UTF-8') . '...',
                'topik' => $soal->topik?->nama ?? '-',
                'tipe' => $soal->tipe,
                'kesukaran' => $kesukaran,
                'daya_beda' => $dayaBeda,
                'rekomendasi' => $this->rekomendasiSoal($kesukaran, $dayaBeda),
            ];
        });
    }

    /**
     * Berikan rekomendasi untuk soal berdasarkan analisis
     */
    private function rekomendasiSoal(array $kesukaran, array $dayaBeda): string
    {
        if ($kesukaran['kategori'] === 'Tidak ada data' || $dayaBeda['kategori'] === 'Data tidak cukup') {
            return 'Data belum cukup untuk analisis';
        }

        $rekomendasi = [];

        // Analisis kesukaran
        if ($kesukaran['kategori'] === 'Mudah' && $kesukaran['indeks'] > 0.90) {
            $rekomendasi[] = 'Soal terlalu mudah, pertimbangkan untuk direvisi';
        } elseif ($kesukaran['kategori'] === 'Sukar' && $kesukaran['indeks'] < 0.20) {
            $rekomendasi[] = 'Soal terlalu sukar, periksa kembali materi atau opsi jawaban';
        }

        // Analisis daya beda
        if ($dayaBeda['kategori'] === 'Jelek') {
            $rekomendasi[] = 'Daya beda rendah, soal tidak efektif membedakan kemampuan peserta';
        }

        if ($dayaBeda['indeks'] < 0) {
            $rekomendasi[] = 'PERHATIAN: Daya beda negatif, kemungkinan kunci jawaban salah';
        }

        if (empty($rekomendasi)) {
            return 'Soal baik, dapat dipertahankan';
        }

        return implode('. ', $rekomendasi);
    }

    /**
     * Ambil peringkat peserta untuk tes
     */
    public function ambilPeringkat(Tes $tes, int $limit = 0): Collection
    {
        $query = $tes->sesiTes()
            ->with('peserta')
            ->whereIn('status', ['selesai', 'timeout'])
            ->whereNotNull('nilai')
            ->orderBy('nilai', 'desc')
            ->orderBy('waktu_selesai', 'asc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $sesiList = $query->get();

        return $sesiList->map(function ($sesi, $index) {
            return [
                'peringkat' => $index + 1,
                'peserta' => $sesi->peserta,
                'nilai' => $sesi->nilai,
                'waktu_selesai' => $sesi->waktu_selesai,
                'durasi_menit' => $sesi->durasi_menit_bulat,
                'lulus' => $sesi->nilai >= $sesi->tes->nilai_lulus,
            ];
        });
    }

    /**
     * Nilai esai secara manual
     */
    public function nilaiEsai(JawabanPeserta $jawaban, bool $benar, ?string $catatan = null): JawabanPeserta
    {
        $jawaban->update([
            'benar' => $benar,
        ]);

        // Hitung ulang nilai sesi
        $sesi = $jawaban->sesiTes;
        $nilai = $this->hitungNilai($sesi);
        $sesi->update(['nilai' => $nilai]);

        return $jawaban->fresh();
    }

    /**
     * Ambil daftar esai yang perlu dinilai
     */
    public function ambilEsaiBelumDinilai(Tes $tes): Collection
    {
        return JawabanPeserta::whereHas('sesiTes', function ($q) use ($tes) {
            $q->where('tes_id', $tes->id)
              ->whereIn('status', ['selesai', 'timeout']);
        })
        ->whereHas('soal', function ($q) {
            $q->where('tipe', 'esai');
        })
        ->whereNull('benar')
        ->whereNotNull('jawaban_esai')
        ->with(['sesiTes.peserta', 'soal'])
        ->get();
    }

    /**
     * Ekspor hasil tes ke array untuk Excel
     * Kebutuhan: 6.4, 6.7
     */
    public function eksporHasil(Tes $tes): array
    {
        $sesiList = $tes->sesiTes()
            ->with('peserta')
            ->whereIn('status', ['selesai', 'timeout'])
            ->orderBy('nilai', 'desc')
            ->get();

        $data = [];
        $peringkat = 1;

        foreach ($sesiList as $sesi) {
            $data[] = [
                'Peringkat' => $peringkat++,
                'Nomor Pendaftaran' => $sesi->peserta->nomor_pendaftaran ?? '-',
                'Nama' => $sesi->peserta->nama,
                'Nilai' => $sesi->nilai,
                'Status' => $sesi->nilai >= $tes->nilai_lulus ? 'Lulus' : 'Tidak Lulus',
                'Waktu Mulai' => $sesi->waktu_mulai->format('d/m/Y H:i'),
                'Waktu Selesai' => $sesi->waktu_selesai?->format('d/m/Y H:i') ?? '-',
                'Durasi (menit)' => $sesi->waktu_selesai 
                    ? $sesi->durasi_menit_bulat
                    : '-',
                'Status Sesi' => match($sesi->status) {
                    'selesai' => 'Selesai',
                    'timeout' => 'Waktu Habis',
                    default => $sesi->status,
                },
            ];
        }

        return $data;
    }
}
