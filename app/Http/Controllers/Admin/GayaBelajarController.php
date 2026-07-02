<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\GayaBelajarConfig;
use App\Services\GayaBelajarService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GayaBelajarController extends Controller
{
    public function __construct(
        private GayaBelajarService $gayaBelajarService
    ) {}

    /**
     * Halaman pengaturan gaya belajar
     */
    public function pengaturan(Tes $tes): View
    {
        $config = $this->gayaBelajarService->getConfig($tes);
        $tipeGayaBelajar = GayaBelajarConfig::tipeGayaBelajarList();
        $defaultMapping = GayaBelajarConfig::defaultMapping();
        $defaultDeskripsi = GayaBelajarConfig::defaultDeskripsi();
        
        // Ambil soal yang ada di tes ini
        $soalTes = $tes->soal()->with('jawaban')->orderBy('tes_soal.urutan')->get();
        
        return view('admin.tes.gaya-belajar', compact(
            'tes', 
            'config', 
            'tipeGayaBelajar',
            'defaultMapping',
            'defaultDeskripsi',
            'soalTes'
        ));
    }

    /**
     * Simpan pengaturan gaya belajar
     */
    public function simpan(Request $request, Tes $tes): RedirectResponse
    {
        $request->validate([
            'aktif' => 'nullable|boolean',
            'mapping_jawaban' => 'required|array',
            'mapping_jawaban.A' => 'required|in:visual,auditori,kinestetik',
            'mapping_jawaban.B' => 'required|in:visual,auditori,kinestetik',
            'mapping_jawaban.C' => 'required|in:visual,auditori,kinestetik',
            'deskripsi_tipe' => 'nullable|array',
            'deskripsi_tipe.visual' => 'nullable|string|max:1000',
            'deskripsi_tipe.auditori' => 'nullable|string|max:1000',
            'deskripsi_tipe.kinestetik' => 'nullable|string|max:1000',
        ]);

        $this->gayaBelajarService->simpanConfig($tes, [
            'aktif' => $request->boolean('aktif'),
            'mapping_jawaban' => $request->mapping_jawaban,
            'deskripsi_tipe' => $request->deskripsi_tipe ?? GayaBelajarConfig::defaultDeskripsi(),
        ]);

        return redirect()
            ->route('admin.tes.gaya-belajar', $tes)
            ->with('success', 'Pengaturan gaya belajar berhasil disimpan.');
    }

    /**
     * Inisialisasi config default
     */
    public function initDefault(Tes $tes): RedirectResponse
    {
        $this->gayaBelajarService->initDefaultConfig($tes);

        return redirect()
            ->route('admin.tes.gaya-belajar', $tes)
            ->with('success', 'Konfigurasi default berhasil diterapkan.');
    }

    /**
     * Preview perhitungan (simulasi)
     */
    public function preview(Request $request, Tes $tes): JsonResponse
    {
        $jawabanSimulasi = $request->input('jawaban', []);
        $hasil = $this->gayaBelajarService->preview($tes, $jawabanSimulasi);

        return response()->json($hasil);
    }
}
