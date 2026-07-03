<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\SesiTes;
use App\Models\ProfilingConfig;
use App\Models\ProfilingMapping;
use App\Models\ProfilingPilarDeskripsi;
use App\Models\HasilProfiling;
use App\Models\JawabanPeserta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfilingService
{
    /**
     * Ambil konfigurasi Profiling untuk tes
     */
    public function getConfig(Tes $tes): ?ProfilingConfig
    {
        return ProfilingConfig::where('tes_id', $tes->id)->first();
    }

    /**
     * Ambil mapping jawaban untuk tes
     */
    public function getMapping(Tes $tes): Collection
    {
        return ProfilingMapping::where('tes_id', $tes->id)
            ->orderBy('nomor_soal')
            ->get()
            ->keyBy('nomor_soal');
    }

    /**
     * Ambil deskripsi pilar untuk tes
     */
    public function getPilarDeskripsi(Tes $tes): Collection
    {
        return ProfilingPilarDeskripsi::where('tes_id', $tes->id)
            ->get()
            ->keyBy('pilar');
    }

    /**
     * Simpan konfigurasi Profiling
     */
    public function simpanConfig(Tes $tes, array $configData): void
    {
        ProfilingConfig::updateOrCreate(
            ['tes_id' => $tes->id],
            [
                'aktif' => $configData['aktif'] ?? true,
                'jumlah_soal' => $configData['jumlah_soal'] ?? 30,
            ]
        );
    }

    /**
     * Simpan mapping jawaban
     */
    public function simpanMapping(Tes $tes, array $mappingData): void
    {
        DB::transaction(function () use ($tes, $mappingData) {
            foreach ($mappingData as $nomorSoal => $data) {
                ProfilingMapping::updateOrCreate(
                    ['tes_id' => $tes->id, 'nomor_soal' => $nomorSoal],
                    [
                        'jawaban_a' => $data['a'] ?? null,
                        'jawaban_b' => $data['b'] ?? null,
                        'jawaban_c' => $data['c'] ?? null,
                        'jawaban_d' => $data['d'] ?? null,
                        'jawaban_e' => $data['e'] ?? null,
                    ]
                );
            }
        });
    }

    /**
     * Simpan deskripsi pilar
     */
    public function simpanPilarDeskripsi(Tes $tes, array $pilarData): void
    {
        DB::transaction(function () use ($tes, $pilarData) {
            foreach ($pilarData as $pilar => $data) {
                ProfilingPilarDeskripsi::updateOrCreate(
                    ['tes_id' => $tes->id, 'pilar' => $pilar],
                    [
                        'kode_qx' => $data['kode_qx'] ?? null,
                        'nama_qx' => $data['nama_qx'] ?? null,
                        'deskripsi' => $data['deskripsi'] ?? null,
                        'kekuatan' => $data['kekuatan'] ?? null,
                        'saran_pengembangan' => $data['saran_pengembangan'] ?? null,
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
        // Simpan config
        $this->simpanConfig($tes, [
            'aktif' => true,
            'jumlah_soal' => 30,
        ]);

        // Simpan mapping default
        $defaultMapping = ProfilingConfig::defaultMapping();
        $this->simpanMapping($tes, $defaultMapping);

        // Simpan deskripsi pilar default
        $pilarList = ProfilingConfig::pilarList();
        $pilarData = [];
        foreach ($pilarList as $pilar => $info) {
            $pilarData[$pilar] = [
                'kode_qx' => $info['kode_qx'],
                'nama_qx' => $info['nama_qx'],
                'deskripsi' => $info['deskripsi'],
                'kekuatan' => $info['kekuatan'],
                'saran_pengembangan' => $info['saran_pengembangan'],
            ];
        }
        $this->simpanPilarDeskripsi($tes, $pilarData);
    }

    /**
     * Hitung hasil Profiling dari jawaban peserta
     */
    public function hitungHasil(SesiTes $sesi): ?HasilProfiling
    {
        $tes = $sesi->tes;

        if ($sesi->status !== 'selesai') {
            return null;
        }

        $config = $this->getConfig($tes);

        if (!$config || !$config->aktif) {
            return null;
        }

        $mapping = $this->getMapping($tes);

        if ($mapping->isEmpty()) {
            return null;
        }

        // Ambil jawaban peserta
        $jawabanPesertaList = JawabanPeserta::where('sesi_tes_id', $sesi->id)
            ->with(['soal.jawaban', 'jawaban'])
            ->get();

        if (!$jawabanPesertaList->contains(fn($jawaban) => !empty($jawaban->jawaban_id))) {
            return null;
        }

        // Ambil urutan soal dari pivot table
        $tesSoal = DB::table('tes_soal')
            ->where('tes_id', $tes->id)
            ->orderBy('urutan')
            ->get()
            ->keyBy('soal_id');

        // Hitung skor per pilar
        $skor = [
            'kreatif' => 0,
            'emosional' => 0,
            'aksi' => 0,
            'logika' => 0,
            'spiritual' => 0,
        ];

        $detailJawaban = [];

        foreach ($jawabanPesertaList as $jp) {
            $soalId = $jp->soal_id;
            $urutan = $tesSoal->get($soalId)?->urutan ?? $soalId;

            // Ambil mapping untuk soal ini
            $soalMapping = $mapping->get($urutan);

            if (!$soalMapping || !$jp->jawaban) {
                continue;
            }

            $hurufJawaban = $this->hurufJawaban($jp);

            if (!$hurufJawaban) {
                continue;
            }

            // Ambil pilar untuk jawaban ini
            $pilar = $soalMapping->getPilarForJawaban($hurufJawaban);

            if ($pilar && isset($skor[$pilar])) {
                $skor[$pilar]++;
            }

            $detailJawaban[$urutan] = [
                'jawaban' => $hurufJawaban,
                'pilar' => $pilar,
            ];
        }

        if (array_sum($skor) === 0) {
            return null;
        }

        // Tentukan pilar dominan (skor tertinggi, jika sama tampilkan semua yang sama)
        $maxSkor = max($skor);
        $pilarTertinggi = array_keys(array_filter($skor, fn($v) => $v === $maxSkor));
        $pilarDominan = $pilarTertinggi[0];
        $pilarDominan2 = count($pilarTertinggi) > 1 ? $pilarTertinggi[1] : null;

        // Simpan hasil
        return HasilProfiling::updateOrCreate(
            ['sesi_tes_id' => $sesi->id],
            [
                'pilar_dominan' => $pilarDominan,
                'pilar_dominan_2' => $pilarDominan2,
                'skor_kreatif' => $skor['kreatif'],
                'skor_emosional' => $skor['emosional'],
                'skor_aksi' => $skor['aksi'],
                'skor_logika' => $skor['logika'],
                'skor_spiritual' => $skor['spiritual'],
                'detail_jawaban' => $detailJawaban,
            ]
        );
    }

    private function hurufJawaban(JawabanPeserta $jawabanPeserta): ?string
    {
        if (!$jawabanPeserta->jawaban_id || !$jawabanPeserta->soal) {
            return null;
        }

        $jawabanSoal = $jawabanPeserta->soal->jawaban
            ->sortBy('urutan')
            ->values();
        $indexJawaban = $jawabanSoal->search(fn($jawaban) => (int) $jawaban->id === (int) $jawabanPeserta->jawaban_id);

        if ($indexJawaban === false) {
            return null;
        }

        return chr(65 + $indexJawaban);
    }

    /**
     * Ambil hasil Profiling untuk sesi
     */
    public function getHasil(SesiTes $sesi): ?HasilProfiling
    {
        return HasilProfiling::where('sesi_tes_id', $sesi->id)->first();
    }

    /**
     * Ambil deskripsi untuk pilar tertentu
     */
    public function getDeskripsiPilar(Tes $tes, string $pilar): ?ProfilingPilarDeskripsi
    {
        $deskripsi = ProfilingPilarDeskripsi::where('tes_id', $tes->id)
            ->where('pilar', $pilar)
            ->first();

        // Jika tidak ada di database, ambil dari default
        if (!$deskripsi) {
            $pilarList = ProfilingConfig::pilarList();
            if (isset($pilarList[$pilar])) {
                $deskripsi = new ProfilingPilarDeskripsi([
                    'pilar' => $pilar,
                    'kode_qx' => $pilarList[$pilar]['kode_qx'],
                    'nama_qx' => $pilarList[$pilar]['nama_qx'],
                    'deskripsi' => $pilarList[$pilar]['deskripsi'],
                    'kekuatan' => $pilarList[$pilar]['kekuatan'],
                    'saran_pengembangan' => $pilarList[$pilar]['saran_pengembangan'],
                ]);
            }
        }

        return $deskripsi;
    }

    /**
     * Cek apakah tes ini adalah tes Profiling
     */
    public function isProfiling(Tes $tes): bool
    {
        $config = ProfilingConfig::where('tes_id', $tes->id)->first();
        return $config && $config->aktif;
    }
}
