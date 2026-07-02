<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\PsikotesKepribadianConfig;
use App\Services\PsikotesKepribadianService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PsikotesKepribadianController extends Controller
{
    public function __construct(
        private PsikotesKepribadianService $psikotesService
    ) {}

    /**
     * Halaman pengaturan psikotes kepribadian
     */
    public function pengaturan(Tes $tes): View
    {
        $config = $this->psikotesService->getConfig($tes);
        $nilaiJawaban = $this->psikotesService->getNilaiJawaban($tes);
        $tipeKepribadian = PsikotesKepribadianConfig::tipeKepribadianList();
        $defaultMapping = PsikotesKepribadianConfig::defaultMapping();
        
        // Ambil soal yang ada di tes ini
        $soalTes = $tes->soal()->orderBy('tes_soal.urutan')->get();
        
        return view('admin.tes.psikotes-kepribadian', compact(
            'tes', 
            'config', 
            'nilaiJawaban', 
            'tipeKepribadian',
            'defaultMapping',
            'soalTes'
        ));
    }

    /**
     * Simpan pengaturan psikotes
     */
    public function simpan(Request $request, Tes $tes): RedirectResponse
    {
        $request->validate([
            'nilai_jawaban' => 'required|array',
            'nilai_jawaban.*' => 'required|integer|min:1|max:10',
            'config' => 'required|array',
            'config.*.nomor_soal' => 'nullable|string',
            'config.*.deskripsi' => 'nullable|string|max:500',
        ]);

        // Simpan nilai jawaban
        $this->psikotesService->simpanNilaiJawaban($tes, $request->nilai_jawaban);

        // Parse dan simpan config
        $configData = [];
        foreach ($request->config as $tipe => $data) {
            $nomorSoal = [];
            if (!empty($data['nomor_soal'])) {
                // Parse string "1, 5, 9, 13" menjadi array [1, 5, 9, 13]
                $nomorSoal = array_map('intval', array_filter(
                    array_map('trim', explode(',', $data['nomor_soal']))
                ));
            }
            
            $configData[$tipe] = [
                'nomor_soal' => $nomorSoal,
                'deskripsi' => $data['deskripsi'] ?? null,
            ];
        }
        
        $this->psikotesService->simpanConfig($tes, $configData);

        return redirect()
            ->route('admin.tes.psikotes-kepribadian', $tes)
            ->with('success', 'Pengaturan psikotes kepribadian berhasil disimpan.');
    }

    /**
     * Inisialisasi config default
     */
    public function initDefault(Tes $tes): RedirectResponse
    {
        $this->psikotesService->initDefaultConfig($tes);

        return redirect()
            ->route('admin.tes.psikotes-kepribadian', $tes)
            ->with('success', 'Konfigurasi default berhasil diterapkan.');
    }

    /**
     * Preview perhitungan (simulasi)
     */
    public function preview(Request $request, Tes $tes): JsonResponse
    {
        $config = $this->psikotesService->getConfig($tes);
        $nilaiJawaban = $this->psikotesService->getNilaiJawaban($tes)->keyBy('kode_jawaban');
        
        // Simulasi jawaban dari request
        $jawabanSimulasi = $request->input('jawaban', []);
        
        // Hitung total nilai per tipe
        $detailNilai = [];
        foreach ($config as $cfg) {
            $total = 0;
            foreach ($cfg->nomor_soal as $nomor) {
                if (isset($jawabanSimulasi[$nomor])) {
                    $kode = $jawabanSimulasi[$nomor];
                    $nilai = $nilaiJawaban->get($kode)?->nilai ?? 0;
                    $total += $nilai;
                }
            }
            $detailNilai[$cfg->tipe_kepribadian] = $total;
        }

        // Tentukan hasil
        $hasilKepribadian = !empty($detailNilai) 
            ? array_keys($detailNilai, max($detailNilai))[0] 
            : 'tidak_diketahui';

        return response()->json([
            'hasil' => $hasilKepribadian,
            'detail' => $detailNilai,
            'label' => ucfirst($hasilKepribadian),
        ]);
    }
}
