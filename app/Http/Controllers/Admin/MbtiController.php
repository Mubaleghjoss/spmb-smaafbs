<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\MbtiConfig;
use App\Services\MbtiService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MbtiController extends Controller
{
    public function __construct(
        private MbtiService $mbtiService
    ) {}

    /**
     * Halaman pengaturan MBTI
     */
    public function pengaturan(Tes $tes): View
    {
        $config = $this->mbtiService->getConfig($tes);
        $tipeDeskripsi = $this->mbtiService->getTipeDeskripsi($tes);
        $dimensiList = MbtiConfig::dimensiList();
        $defaultMapping = MbtiConfig::defaultMapping();
        $tipeMbtiList = MbtiConfig::tipeMbtiList();
        
        // Ambil soal yang ada di tes ini
        $soalTes = $tes->soal()->orderBy('tes_soal.urutan')->get();
        
        return view('admin.tes.mbti', compact(
            'tes', 
            'config', 
            'tipeDeskripsi',
            'dimensiList',
            'defaultMapping',
            'tipeMbtiList',
            'soalTes'
        ));
    }

    /**
     * Simpan pengaturan MBTI
     */
    public function simpan(Request $request, Tes $tes): RedirectResponse
    {
        $request->validate([
            'config' => 'required|array',
            'config.*.soal_bagian_1' => 'nullable|string',
            'config.*.soal_bagian_2' => 'nullable|string',
            'config.*.soal_bagian_3' => 'nullable|string',
            'config.*.deskripsi_a' => 'nullable|string|max:500',
            'config.*.deskripsi_b' => 'nullable|string|max:500',
            'tipe' => 'nullable|array',
            'tipe.*.nama' => 'nullable|string|max:100',
            'tipe.*.deskripsi' => 'nullable|string|max:2000',
            'tipe.*.kekuatan' => 'nullable|string|max:500',
            'tipe.*.kelemahan' => 'nullable|string|max:500',
            'tipe.*.karir_cocok' => 'nullable|string|max:500',
        ]);

        $dimensiList = MbtiConfig::dimensiList();

        // Parse dan simpan config
        $configData = [];
        foreach ($request->config as $dimensi => $data) {
            $configData[$dimensi] = [
                'soal_bagian_1' => $this->parseNomorSoal($data['soal_bagian_1'] ?? ''),
                'soal_bagian_2' => $this->parseNomorSoal($data['soal_bagian_2'] ?? ''),
                'soal_bagian_3' => $this->parseNomorSoal($data['soal_bagian_3'] ?? ''),
                'label_a' => $dimensiList[$dimensi]['label_a'] ?? null,
                'label_b' => $dimensiList[$dimensi]['label_b'] ?? null,
                'deskripsi_a' => $data['deskripsi_a'] ?? null,
                'deskripsi_b' => $data['deskripsi_b'] ?? null,
            ];
        }
        
        $this->mbtiService->simpanConfig($tes, $configData);

        // Simpan deskripsi tipe jika ada
        if ($request->has('tipe')) {
            $this->mbtiService->simpanTipeDeskripsi($tes, $request->tipe);
        }

        return redirect()
            ->route('admin.tes.mbti', $tes)
            ->with('success', 'Pengaturan MBTI berhasil disimpan.');
    }

    /**
     * Inisialisasi config default
     */
    public function initDefault(Tes $tes): RedirectResponse
    {
        $this->mbtiService->initDefaultConfig($tes);

        return redirect()
            ->route('admin.tes.mbti', $tes)
            ->with('success', 'Konfigurasi default MBTI berhasil diterapkan.');
    }

    /**
     * Preview perhitungan (simulasi)
     */
    public function preview(Request $request, Tes $tes): JsonResponse
    {
        $config = $this->mbtiService->getConfig($tes);
        
        if ($config->isEmpty()) {
            return response()->json([
                'error' => 'Konfigurasi MBTI belum diatur',
            ], 400);
        }

        // Simulasi jawaban dari request
        $jawabanSimulasi = $request->input('jawaban', []);
        
        // Hitung skor per dimensi
        $skor = [
            'E' => 0, 'I' => 0,
            'S' => 0, 'N' => 0,
            'T' => 0, 'F' => 0,
            'J' => 0, 'P' => 0,
        ];

        $detailPerhitungan = [];

        foreach ($config as $dimensi => $cfg) {
            $labelA = $cfg->label_a;
            $labelB = $cfg->label_b;

            $skorA = 0;
            $skorB = 0;

            $semuaSoal = array_merge(
                $cfg->soal_bagian_1 ?? [],
                $cfg->soal_bagian_2 ?? [],
                $cfg->soal_bagian_3 ?? []
            );

            foreach ($semuaSoal as $nomorSoal) {
                $jawaban = strtoupper($jawabanSimulasi[$nomorSoal] ?? '');
                if ($jawaban === 'A') {
                    $skorA++;
                } elseif ($jawaban === 'B') {
                    $skorB++;
                }
            }

            $skor[$labelA] = $skorA;
            $skor[$labelB] = $skorB;

            $detailPerhitungan[$dimensi] = [
                'label_a' => $labelA,
                'label_b' => $labelB,
                'skor_a' => $skorA,
                'skor_b' => $skorB,
                'hasil' => $skorB > $skorA ? $labelB : $labelA,
            ];
        }

        // Tentukan tipe MBTI
        $tipeMbti = '';
        $tipeMbti .= $skor['I'] > $skor['E'] ? 'I' : 'E';
        $tipeMbti .= $skor['N'] > $skor['S'] ? 'N' : 'S';
        $tipeMbti .= $skor['F'] > $skor['T'] ? 'F' : 'T';
        $tipeMbti .= $skor['P'] > $skor['J'] ? 'P' : 'J';

        $tipeMbtiList = MbtiConfig::tipeMbtiList();

        return response()->json([
            'tipe' => $tipeMbti,
            'nama' => $tipeMbtiList[$tipeMbti]['nama'] ?? $tipeMbti,
            'deskripsi' => $tipeMbtiList[$tipeMbti]['deskripsi'] ?? '',
            'skor' => $skor,
            'detail' => $detailPerhitungan,
        ]);
    }

    /**
     * Parse string nomor soal menjadi array
     */
    private function parseNomorSoal(string $str): array
    {
        if (empty($str)) {
            return [];
        }
        return array_map('intval', array_filter(
            array_map('trim', explode(',', $str))
        ));
    }
}
