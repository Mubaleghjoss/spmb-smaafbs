<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\SesiTes;
use App\Models\PsikotesKepribadianConfig;
use App\Models\PsikotesNilaiJawaban;
use App\Models\HasilPsikotesKepribadian;
use Illuminate\Support\Collection;

class PsikotesKepribadianService
{
    /**
     * Cek apakah tes adalah psikotes kepribadian
     */
    public function isPsikotesKepribadian(Tes $tes): bool
    {
        return PsikotesKepribadianConfig::where('tes_id', $tes->id)->exists();
    }

    /**
     * Ambil konfigurasi psikotes untuk tes
     */
    public function getConfig(Tes $tes): Collection
    {
        return PsikotesKepribadianConfig::where('tes_id', $tes->id)->get();
    }

    /**
     * Ambil nilai jawaban untuk tes
     */
    public function getNilaiJawaban(Tes $tes): Collection
    {
        $nilai = PsikotesNilaiJawaban::where('tes_id', $tes->id)->get();
        
        // Jika belum ada, gunakan default
        if ($nilai->isEmpty()) {
            return collect(PsikotesNilaiJawaban::defaultNilai())->map(function ($val, $key) {
                return (object) ['kode_jawaban' => $key, 'nilai' => $val];
            });
        }
        
        return $nilai;
    }

    /**
     * Simpan konfigurasi psikotes
     */
    public function simpanConfig(Tes $tes, array $config): void
    {
        // Hapus config lama
        PsikotesKepribadianConfig::where('tes_id', $tes->id)->delete();
        
        // Simpan config baru
        foreach ($config as $tipe => $data) {
            if (!empty($data['nomor_soal'])) {
                PsikotesKepribadianConfig::create([
                    'tes_id' => $tes->id,
                    'tipe_kepribadian' => $tipe,
                    'nomor_soal' => $data['nomor_soal'],
                    'deskripsi' => $data['deskripsi'] ?? null,
                ]);
            }
        }
    }

    /**
     * Simpan nilai jawaban
     */
    public function simpanNilaiJawaban(Tes $tes, array $nilai): void
    {
        // Hapus nilai lama
        PsikotesNilaiJawaban::where('tes_id', $tes->id)->delete();
        
        // Simpan nilai baru
        foreach ($nilai as $kode => $val) {
            PsikotesNilaiJawaban::create([
                'tes_id' => $tes->id,
                'kode_jawaban' => $kode,
                'nilai' => $val,
            ]);
        }
    }

    /**
     * Hitung hasil psikotes kepribadian
     */
    public function hitungHasil(SesiTes $sesi): ?HasilPsikotesKepribadian
    {
        $tes = $sesi->tes;
        
        if (!$this->isPsikotesKepribadian($tes)) {
            return null;
        }

        if ($sesi->status !== 'selesai') {
            return null;
        }

        $config = $this->getConfig($tes);
        $nilaiJawaban = $this->getNilaiJawaban($tes)->keyBy('kode_jawaban');
        
        // Ambil jawaban peserta dengan urutan soal
        $jawabanPeserta = $sesi->jawabanPeserta()
            ->with(['soal', 'jawaban'])
            ->get();

        if (!$jawabanPeserta->contains(fn($jawaban) => !empty($jawaban->jawaban_id))) {
            return null;
        }

        // Buat mapping nomor urut soal -> jawaban
        $soalDiTes = $tes->soal()->orderBy('tes_soal.urutan')->get();
        $jawabanByNomor = [];
        
        foreach ($soalDiTes as $index => $soal) {
            $nomorSoal = $index + 1;
            $jawaban = $jawabanPeserta->firstWhere('soal_id', $soal->id);
            
            if ($jawaban && $jawaban->jawaban) {
                // Ambil kode jawaban (A, B, C, D) dari urutan jawaban
                $jawabanSoal = $soal->jawaban()->orderBy('urutan')->get();
                $indexJawaban = $jawabanSoal->search(fn($j) => (int) $j->id === (int) $jawaban->jawaban_id);
                
                if ($indexJawaban !== false) {
                    $kodeJawaban = chr(65 + $indexJawaban); // 0=A, 1=B, 2=C, 3=D
                    $jawabanByNomor[$nomorSoal] = $kodeJawaban;
                }
            }
        }

        if (empty($jawabanByNomor)) {
            return null;
        }

        // Hitung total nilai per tipe kepribadian
        $detailNilai = [];
        
        foreach ($config as $cfg) {
            $total = 0;
            foreach ($cfg->nomor_soal as $nomor) {
                if (isset($jawabanByNomor[$nomor])) {
                    $kode = $jawabanByNomor[$nomor];
                    $nilai = $nilaiJawaban->get($kode)?->nilai ?? 0;
                    $total += $nilai;
                }
            }
            $detailNilai[$cfg->tipe_kepribadian] = $total;
        }

        if (array_sum($detailNilai) === 0) {
            return null;
        }

        // Tentukan 2 hasil tertinggi
        arsort($detailNilai); // Sort descending by value
        $sortedKeys = array_keys($detailNilai);
        
        // Ambil 2 tipe dengan nilai tertinggi
        $hasilKepribadianArray = array_slice($sortedKeys, 0, 2);
        
        // Gabungkan dengan " & "
        $hasilKepribadian = implode(' & ', $hasilKepribadianArray);

        // Simpan atau update hasil
        return HasilPsikotesKepribadian::updateOrCreate(
            ['sesi_tes_id' => $sesi->id],
            [
                'hasil_kepribadian' => $hasilKepribadian,
                'detail_nilai' => $detailNilai,
            ]
        );
    }

    /**
     * Ambil hasil psikotes untuk sesi
     */
    public function getHasil(SesiTes $sesi): ?HasilPsikotesKepribadian
    {
        return HasilPsikotesKepribadian::where('sesi_tes_id', $sesi->id)->first();
    }

    /**
     * Inisialisasi config default untuk tes baru
     */
    public function initDefaultConfig(Tes $tes): void
    {
        $defaultMapping = PsikotesKepribadianConfig::defaultMapping();
        $deskripsi = [
            'koleris' => 'Tipe kepribadian yang kuat, tegas, dan berorientasi pada tujuan.',
            'sanguin' => 'Tipe kepribadian yang ceria, optimis, dan suka bersosialisasi.',
            'plegmatis' => 'Tipe kepribadian yang tenang, damai, dan mudah bergaul.',
            'melankolis' => 'Tipe kepribadian yang analitis, detail, dan perfeksionis.',
        ];

        foreach ($defaultMapping as $tipe => $nomor) {
            PsikotesKepribadianConfig::create([
                'tes_id' => $tes->id,
                'tipe_kepribadian' => $tipe,
                'nomor_soal' => $nomor,
                'deskripsi' => $deskripsi[$tipe] ?? null,
            ]);
        }

        // Simpan nilai jawaban default
        foreach (PsikotesNilaiJawaban::defaultNilai() as $kode => $nilai) {
            PsikotesNilaiJawaban::create([
                'tes_id' => $tes->id,
                'kode_jawaban' => $kode,
                'nilai' => $nilai,
            ]);
        }
    }
}
