<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\SesiTes;
use App\Models\GayaBelajarConfig;
use App\Models\HasilGayaBelajar;

class GayaBelajarService
{
    /**
     * Cek apakah tes adalah tes gaya belajar
     */
    public function isGayaBelajar(Tes $tes): bool
    {
        $config = GayaBelajarConfig::where('tes_id', $tes->id)->first();
        return $config && $config->aktif;
    }

    /**
     * Ambil konfigurasi gaya belajar untuk tes
     */
    public function getConfig(Tes $tes): ?GayaBelajarConfig
    {
        return GayaBelajarConfig::where('tes_id', $tes->id)->first();
    }

    /**
     * Simpan konfigurasi gaya belajar
     */
    public function simpanConfig(Tes $tes, array $data): GayaBelajarConfig
    {
        return GayaBelajarConfig::updateOrCreate(
            ['tes_id' => $tes->id],
            [
                'aktif' => $data['aktif'] ?? false,
                'mapping_jawaban' => $data['mapping_jawaban'] ?? GayaBelajarConfig::defaultMapping(),
                'deskripsi_tipe' => $data['deskripsi_tipe'] ?? GayaBelajarConfig::defaultDeskripsi(),
            ]
        );
    }

    /**
     * Inisialisasi config default untuk tes
     */
    public function initDefaultConfig(Tes $tes): GayaBelajarConfig
    {
        return GayaBelajarConfig::updateOrCreate(
            ['tes_id' => $tes->id],
            [
                'aktif' => true,
                'mapping_jawaban' => GayaBelajarConfig::defaultMapping(),
                'deskripsi_tipe' => GayaBelajarConfig::defaultDeskripsi(),
            ]
        );
    }

    /**
     * Hitung hasil gaya belajar dari sesi tes
     */
    public function hitungHasil(SesiTes $sesi): ?HasilGayaBelajar
    {
        $tes = $sesi->tes;
        
        if (!$this->isGayaBelajar($tes)) {
            return null;
        }

        $config = $this->getConfig($tes);
        $mappingJawaban = $config->mapping_jawaban;
        
        // Ambil jawaban peserta dengan urutan soal
        $jawabanPeserta = $sesi->jawabanPeserta()
            ->with(['soal', 'jawaban'])
            ->get();

        // Buat mapping nomor urut soal -> jawaban
        $soalDiTes = $tes->soal()->orderBy('tes_soal.urutan')->get();
        
        // Hitung total per tipe gaya belajar
        $detailNilai = [
            'visual' => 0,
            'auditori' => 0,
            'kinestetik' => 0,
        ];
        
        foreach ($soalDiTes as $index => $soal) {
            $jawaban = $jawabanPeserta->firstWhere('soal_id', $soal->id);
            
            if ($jawaban && $jawaban->jawaban_id) {
                // Ambil kode jawaban (A, B, C) dari urutan jawaban
                $jawabanSoal = $soal->jawaban()->orderBy('urutan')->get();
                $indexJawaban = $jawabanSoal->search(fn($j) => $j->id === $jawaban->jawaban_id);
                
                if ($indexJawaban !== false) {
                    $kodeJawaban = chr(65 + $indexJawaban); // 0=A, 1=B, 2=C
                    
                    // Tambahkan ke tipe yang sesuai
                    if (isset($mappingJawaban[$kodeJawaban])) {
                        $tipe = $mappingJawaban[$kodeJawaban];
                        if (isset($detailNilai[$tipe])) {
                            $detailNilai[$tipe]++;
                        }
                    }
                }
            }
        }

        // Tentukan hasil (tipe dengan nilai tertinggi, jika sama tampilkan semua yang sama)
        $maxNilai = max($detailNilai);
        $hasilTipe = array_keys(array_filter($detailNilai, fn($v) => $v === $maxNilai));
        
        // Gabungkan dengan " & " jika ada lebih dari satu
        $hasilGayaBelajar = implode(' & ', $hasilTipe);

        // Simpan atau update hasil
        return HasilGayaBelajar::updateOrCreate(
            ['sesi_tes_id' => $sesi->id],
            [
                'hasil_gaya_belajar' => $hasilGayaBelajar,
                'detail_nilai' => $detailNilai,
            ]
        );
    }

    /**
     * Ambil hasil gaya belajar untuk sesi
     */
    public function getHasil(SesiTes $sesi): ?HasilGayaBelajar
    {
        return HasilGayaBelajar::where('sesi_tes_id', $sesi->id)->first();
    }

    /**
     * Preview perhitungan (simulasi)
     */
    public function preview(Tes $tes, array $jawabanSimulasi): array
    {
        $config = $this->getConfig($tes);
        
        if (!$config) {
            return [
                'hasil' => 'tidak_diketahui',
                'detail' => [],
                'label' => 'Tidak Diketahui',
            ];
        }

        $mappingJawaban = $config->mapping_jawaban;
        
        // Hitung total per tipe
        $detailNilai = [
            'visual' => 0,
            'auditori' => 0,
            'kinestetik' => 0,
        ];
        
        foreach ($jawabanSimulasi as $nomor => $kodeJawaban) {
            if (isset($mappingJawaban[$kodeJawaban])) {
                $tipe = $mappingJawaban[$kodeJawaban];
                if (isset($detailNilai[$tipe])) {
                    $detailNilai[$tipe]++;
                }
            }
        }

        // Tentukan hasil (tipe dengan nilai tertinggi, jika sama tampilkan semua yang sama)
        $maxNilai = max($detailNilai);
        $hasilTipe = array_keys(array_filter($detailNilai, fn($v) => $v === $maxNilai));
        $hasilGayaBelajar = implode(' & ', $hasilTipe);

        $tipeList = GayaBelajarConfig::tipeGayaBelajarList();
        $label = collect($hasilTipe)
            ->map(fn($h) => $tipeList[$h] ?? ucfirst($h))
            ->implode(' & ');

        return [
            'hasil' => $hasilGayaBelajar,
            'detail' => $detailNilai,
            'label' => $label,
        ];
    }
}
