<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\SesiTes;
use App\Models\MbtiConfig;
use App\Models\MbtiTipeDeskripsi;
use App\Models\HasilMbti;
use App\Models\JawabanPeserta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MbtiService
{
    /**
     * Ambil konfigurasi MBTI untuk tes
     */
    public function getConfig(Tes $tes): Collection
    {
        return MbtiConfig::where('tes_id', $tes->id)->get()->keyBy('dimensi');
    }

    /**
     * Ambil deskripsi tipe MBTI untuk tes
     */
    public function getTipeDeskripsi(Tes $tes): Collection
    {
        return MbtiTipeDeskripsi::where('tes_id', $tes->id)->get()->keyBy('tipe');
    }

    /**
     * Simpan konfigurasi MBTI
     */
    public function simpanConfig(Tes $tes, array $configData): void
    {
        DB::transaction(function () use ($tes, $configData) {
            foreach ($configData as $dimensi => $data) {
                MbtiConfig::updateOrCreate(
                    ['tes_id' => $tes->id, 'dimensi' => $dimensi],
                    [
                        'soal_bagian_1' => $data['soal_bagian_1'] ?? [],
                        'soal_bagian_2' => $data['soal_bagian_2'] ?? [],
                        'soal_bagian_3' => $data['soal_bagian_3'] ?? [],
                        'label_a' => $data['label_a'] ?? null,
                        'label_b' => $data['label_b'] ?? null,
                        'deskripsi_a' => $data['deskripsi_a'] ?? null,
                        'deskripsi_b' => $data['deskripsi_b'] ?? null,
                    ]
                );
            }
        });
    }

    /**
     * Simpan deskripsi tipe MBTI
     */
    public function simpanTipeDeskripsi(Tes $tes, array $tipeData): void
    {
        DB::transaction(function () use ($tes, $tipeData) {
            foreach ($tipeData as $tipe => $data) {
                MbtiTipeDeskripsi::updateOrCreate(
                    ['tes_id' => $tes->id, 'tipe' => $tipe],
                    [
                        'nama' => $data['nama'] ?? null,
                        'deskripsi' => $data['deskripsi'] ?? null,
                        'kekuatan' => $data['kekuatan'] ?? null,
                        'kelemahan' => $data['kelemahan'] ?? null,
                        'karir_cocok' => $data['karir_cocok'] ?? null,
                    ]
                );
            }
        });
    }

    /**
     * Inisialisasi konfigurasi default
     */
    public function initDefaultConfig(Tes $tes): void
    {
        $dimensiList = MbtiConfig::dimensiList();
        $defaultMapping = MbtiConfig::defaultMapping();

        $configData = [];
        foreach ($dimensiList as $dimensi => $info) {
            $configData[$dimensi] = [
                'soal_bagian_1' => $defaultMapping[$dimensi]['soal_bagian_1'] ?? [],
                'soal_bagian_2' => $defaultMapping[$dimensi]['soal_bagian_2'] ?? [],
                'soal_bagian_3' => $defaultMapping[$dimensi]['soal_bagian_3'] ?? [],
                'label_a' => $info['label_a'],
                'label_b' => $info['label_b'],
                'deskripsi_a' => $info['deskripsi_a'],
                'deskripsi_b' => $info['deskripsi_b'],
            ];
        }

        $this->simpanConfig($tes, $configData);

        // Inisialisasi deskripsi tipe
        $tipeMbtiList = MbtiConfig::tipeMbtiList();
        $tipeData = [];
        foreach ($tipeMbtiList as $tipe => $info) {
            $tipeData[$tipe] = [
                'nama' => $info['nama'],
                'deskripsi' => $info['deskripsi'],
                'kekuatan' => $info['kekuatan'] ?? null,
                'kelemahan' => $info['kelemahan'] ?? null,
                'karir_cocok' => $info['karir_cocok'] ?? null,
            ];
        }
        $this->simpanTipeDeskripsi($tes, $tipeData);
    }

    /**
     * Hitung hasil MBTI dari jawaban peserta
     * Rumus: Hitung hasil per bagian, lalu tentukan hasil akhir berdasarkan mayoritas bagian
     */
    public function hitungHasil(SesiTes $sesi): ?HasilMbti
    {
        $tes = $sesi->tes;
        $config = $this->getConfig($tes);

        if ($config->isEmpty()) {
            return null;
        }

        // Ambil jawaban peserta dengan relasi jawaban dan soal
        $jawabanPesertaList = JawabanPeserta::where('sesi_tes_id', $sesi->id)
            ->with(['soal', 'jawaban'])
            ->get();
        
        // Buat mapping berdasarkan urutan soal dalam tes
        // Ambil urutan soal dari pivot table tes_soal
        $tesSoal = DB::table('tes_soal')
            ->where('tes_id', $tes->id)
            ->orderBy('urutan')
            ->get()
            ->keyBy('soal_id');
        
        // Key jawaban by nomor urutan (1-based)
        $jawaban = [];
        foreach ($jawabanPesertaList as $jp) {
            $soalId = $jp->soal_id;
            $urutan = $tesSoal->get($soalId)?->urutan ?? $soalId;
            $jawaban[$urutan] = $jp;
        }

        // Hitung skor per dimensi per bagian
        $skorPerBagian = [];
        $hasilPerBagian = [];
        $detailPerhitungan = [];

        foreach ($config as $dimensi => $cfg) {
            $labelA = $cfg->label_a; // E, S, T, J
            $labelB = $cfg->label_b; // I, N, F, P

            // Hitung per bagian
            $bagian1 = $this->hitungSkorBagian($cfg->soal_bagian_1 ?? [], $jawaban);
            $bagian2 = $this->hitungSkorBagian($cfg->soal_bagian_2 ?? [], $jawaban);
            $bagian3 = $this->hitungSkorBagian($cfg->soal_bagian_3 ?? [], $jawaban);

            // Tentukan hasil per bagian (mana yang lebih banyak)
            $hasilBagian1 = $bagian1['b'] > $bagian1['a'] ? $labelB : $labelA;
            $hasilBagian2 = $bagian2['b'] > $bagian2['a'] ? $labelB : $labelA;
            $hasilBagian3 = $bagian3['b'] > $bagian3['a'] ? $labelB : $labelA;

            // Hitung berapa bagian yang menang untuk masing-masing label
            $jumlahA = 0;
            $jumlahB = 0;
            
            if ($hasilBagian1 === $labelA) $jumlahA++; else $jumlahB++;
            if ($hasilBagian2 === $labelA) $jumlahA++; else $jumlahB++;
            if ($hasilBagian3 === $labelA) $jumlahA++; else $jumlahB++;

            // Hasil akhir berdasarkan mayoritas bagian (2 dari 3)
            $hasilAkhir = $jumlahB > $jumlahA ? $labelB : $labelA;

            $skorPerBagian[$dimensi] = [
                'bagian_1' => $bagian1,
                'bagian_2' => $bagian2,
                'bagian_3' => $bagian3,
            ];

            $hasilPerBagian[$dimensi] = [
                'bagian_1' => $hasilBagian1,
                'bagian_2' => $hasilBagian2,
                'bagian_3' => $hasilBagian3,
                'jumlah_a' => $jumlahA,
                'jumlah_b' => $jumlahB,
            ];

            $detailPerhitungan[$dimensi] = [
                'label_a' => $labelA,
                'label_b' => $labelB,
                'skor_bagian_1' => $bagian1,
                'skor_bagian_2' => $bagian2,
                'skor_bagian_3' => $bagian3,
                'hasil_bagian_1' => $hasilBagian1,
                'hasil_bagian_2' => $hasilBagian2,
                'hasil_bagian_3' => $hasilBagian3,
                'jumlah_bagian_a' => $jumlahA,
                'jumlah_bagian_b' => $jumlahB,
                'hasil' => $hasilAkhir,
            ];
        }

        // Tentukan tipe MBTI berdasarkan hasil per dimensi
        $tipeMbti = '';
        $tipeMbti .= $detailPerhitungan['EI']['hasil'];
        $tipeMbti .= $detailPerhitungan['SN']['hasil'];
        $tipeMbti .= $detailPerhitungan['TF']['hasil'];
        $tipeMbti .= $detailPerhitungan['JP']['hasil'];

        // Hitung total skor untuk tampilan (opsional)
        $totalSkor = [
            'E' => ($skorPerBagian['EI']['bagian_1']['a'] ?? 0) + ($skorPerBagian['EI']['bagian_2']['a'] ?? 0) + ($skorPerBagian['EI']['bagian_3']['a'] ?? 0),
            'I' => ($skorPerBagian['EI']['bagian_1']['b'] ?? 0) + ($skorPerBagian['EI']['bagian_2']['b'] ?? 0) + ($skorPerBagian['EI']['bagian_3']['b'] ?? 0),
            'S' => ($skorPerBagian['SN']['bagian_1']['a'] ?? 0) + ($skorPerBagian['SN']['bagian_2']['a'] ?? 0) + ($skorPerBagian['SN']['bagian_3']['a'] ?? 0),
            'N' => ($skorPerBagian['SN']['bagian_1']['b'] ?? 0) + ($skorPerBagian['SN']['bagian_2']['b'] ?? 0) + ($skorPerBagian['SN']['bagian_3']['b'] ?? 0),
            'T' => ($skorPerBagian['TF']['bagian_1']['a'] ?? 0) + ($skorPerBagian['TF']['bagian_2']['a'] ?? 0) + ($skorPerBagian['TF']['bagian_3']['a'] ?? 0),
            'F' => ($skorPerBagian['TF']['bagian_1']['b'] ?? 0) + ($skorPerBagian['TF']['bagian_2']['b'] ?? 0) + ($skorPerBagian['TF']['bagian_3']['b'] ?? 0),
            'J' => ($skorPerBagian['JP']['bagian_1']['a'] ?? 0) + ($skorPerBagian['JP']['bagian_2']['a'] ?? 0) + ($skorPerBagian['JP']['bagian_3']['a'] ?? 0),
            'P' => ($skorPerBagian['JP']['bagian_1']['b'] ?? 0) + ($skorPerBagian['JP']['bagian_2']['b'] ?? 0) + ($skorPerBagian['JP']['bagian_3']['b'] ?? 0),
        ];

        // Simpan hasil (hanya 1 tipe MBTI)
        return HasilMbti::updateOrCreate(
            ['sesi_tes_id' => $sesi->id],
            [
                'tipe_mbti' => $tipeMbti,
                'tipe_mbti_2' => null,
                'skor_e' => $totalSkor['E'],
                'skor_i' => $totalSkor['I'],
                'skor_s' => $totalSkor['S'],
                'skor_n' => $totalSkor['N'],
                'skor_t' => $totalSkor['T'],
                'skor_f' => $totalSkor['F'],
                'skor_j' => $totalSkor['J'],
                'skor_p' => $totalSkor['P'],
                'detail_perhitungan' => $detailPerhitungan,
            ]
        );
    }

    /**
     * Hitung skor untuk satu bagian soal
     */
    private function hitungSkorBagian(array $soalList, array $jawaban): array
    {
        $skorA = 0;
        $skorB = 0;

        foreach ($soalList as $nomorSoal) {
            $jawabanPeserta = $jawaban[$nomorSoal] ?? null;
            if ($jawabanPeserta && $jawabanPeserta->jawaban) {
                $jawabanObj = $jawabanPeserta->jawaban;
                $urutanJawaban = $jawabanObj->urutan ?? 0;
                $isiJawaban = strtoupper(trim($jawabanObj->isi_jawaban ?? ''));
                
                // Tentukan apakah ini jawaban A atau B
                $isJawabanA = false;
                $isJawabanB = false;
                
                // Cek berdasarkan urutan (0=A, 1=B) - format database menggunakan 0-based index
                if ($urutanJawaban === 0 || $urutanJawaban === '0') {
                    $isJawabanA = true;
                } elseif ($urutanJawaban === 1 || $urutanJawaban === '1') {
                    $isJawabanB = true;
                }
                // Fallback: cek berdasarkan isi jawaban yang dimulai dengan A atau B
                elseif (str_starts_with($isiJawaban, 'A.') || str_starts_with($isiJawaban, 'A ') || $isiJawaban === 'A') {
                    $isJawabanA = true;
                } elseif (str_starts_with($isiJawaban, 'B.') || str_starts_with($isiJawaban, 'B ') || $isiJawaban === 'B') {
                    $isJawabanB = true;
                }
                
                if ($isJawabanA) {
                    $skorA++;
                } elseif ($isJawabanB) {
                    $skorB++;
                }
            }
        }

        return ['a' => $skorA, 'b' => $skorB];
    }

    /**
     * Ambil hasil MBTI untuk sesi
     */
    public function getHasil(SesiTes $sesi): ?HasilMbti
    {
        return HasilMbti::where('sesi_tes_id', $sesi->id)->first();
    }

    /**
     * Ambil deskripsi untuk tipe MBTI tertentu
     */
    public function getDeskripsiTipe(Tes $tes, string $tipe): ?MbtiTipeDeskripsi
    {
        $deskripsi = MbtiTipeDeskripsi::where('tes_id', $tes->id)
            ->where('tipe', $tipe)
            ->first();
        
        // Jika tidak ada di database, ambil dari default
        if (!$deskripsi) {
            $tipeMbtiList = MbtiConfig::tipeMbtiList();
            if (isset($tipeMbtiList[$tipe])) {
                $deskripsi = new MbtiTipeDeskripsi([
                    'tipe' => $tipe,
                    'nama' => $tipeMbtiList[$tipe]['nama'],
                    'deskripsi' => $tipeMbtiList[$tipe]['deskripsi'],
                    'kekuatan' => $tipeMbtiList[$tipe]['kekuatan'] ?? null,
                    'kelemahan' => $tipeMbtiList[$tipe]['kelemahan'] ?? null,
                    'karir_cocok' => $tipeMbtiList[$tipe]['karir_cocok'] ?? null,
                ]);
            }
        }
        
        return $deskripsi;
    }
    
    /**
     * Cek apakah tes ini adalah tes MBTI
     */
    public function isMbti(Tes $tes): bool
    {
        return MbtiConfig::where('tes_id', $tes->id)->exists();
    }
}
