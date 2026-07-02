<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\ProfilingConfig;
use App\Services\ProfilingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProfilingController extends Controller
{
    public function __construct(
        private ProfilingService $profilingService
    ) {}

    /**
     * Halaman pengaturan Profiling
     */
    public function pengaturan(Tes $tes): View
    {
        $config = $this->profilingService->getConfig($tes);
        $mapping = $this->profilingService->getMapping($tes);
        $pilarDeskripsi = $this->profilingService->getPilarDeskripsi($tes);
        $pilarList = ProfilingConfig::pilarList();
        $defaultMapping = ProfilingConfig::defaultMapping();

        // Ambil soal yang ada di tes ini
        $soalTes = $tes->soal()->orderBy('tes_soal.urutan')->get();

        return view('admin.tes.profiling', compact(
            'tes',
            'config',
            'mapping',
            'pilarDeskripsi',
            'pilarList',
            'defaultMapping',
            'soalTes'
        ));
    }

    /**
     * Simpan pengaturan Profiling
     */
    public function simpan(Request $request, Tes $tes): RedirectResponse
    {
        $request->validate([
            'aktif' => 'nullable|boolean',
            'jumlah_soal' => 'nullable|integer|min:1|max:100',
            'mapping' => 'nullable|array',
            'mapping.*.a' => 'nullable|string|in:kreatif,emosional,aksi,logika,spiritual',
            'mapping.*.b' => 'nullable|string|in:kreatif,emosional,aksi,logika,spiritual',
            'mapping.*.c' => 'nullable|string|in:kreatif,emosional,aksi,logika,spiritual',
            'mapping.*.d' => 'nullable|string|in:kreatif,emosional,aksi,logika,spiritual',
            'mapping.*.e' => 'nullable|string|in:kreatif,emosional,aksi,logika,spiritual',
            'pilar' => 'nullable|array',
            'pilar.*.deskripsi' => 'nullable|string|max:2000',
            'pilar.*.kekuatan' => 'nullable|string|max:500',
            'pilar.*.saran_pengembangan' => 'nullable|string|max:500',
        ]);

        // Simpan config
        $this->profilingService->simpanConfig($tes, [
            'aktif' => $request->boolean('aktif', true),
            'jumlah_soal' => $request->input('jumlah_soal', 30),
        ]);

        // Simpan mapping jika ada
        if ($request->has('mapping')) {
            $this->profilingService->simpanMapping($tes, $request->mapping);
        }

        // Simpan deskripsi pilar jika ada
        if ($request->has('pilar')) {
            $pilarList = ProfilingConfig::pilarList();
            $pilarData = [];
            foreach ($request->pilar as $pilar => $data) {
                $pilarData[$pilar] = [
                    'kode_qx' => $pilarList[$pilar]['kode_qx'] ?? null,
                    'nama_qx' => $pilarList[$pilar]['nama_qx'] ?? null,
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'kekuatan' => $data['kekuatan'] ?? null,
                    'saran_pengembangan' => $data['saran_pengembangan'] ?? null,
                ];
            }
            $this->profilingService->simpanPilarDeskripsi($tes, $pilarData);
        }

        return redirect()
            ->route('admin.tes.profiling', $tes)
            ->with('success', 'Pengaturan Profiling berhasil disimpan.');
    }

    /**
     * Inisialisasi config default
     */
    public function initDefault(Tes $tes): RedirectResponse
    {
        $this->profilingService->initDefaultConfig($tes);

        return redirect()
            ->route('admin.tes.profiling', $tes)
            ->with('success', 'Konfigurasi default Profiling berhasil diterapkan.');
    }

    /**
     * Preview perhitungan (simulasi)
     */
    public function preview(Request $request, Tes $tes): JsonResponse
    {
        $config = $this->profilingService->getConfig($tes);

        if (!$config || !$config->aktif) {
            return response()->json([
                'error' => 'Konfigurasi Profiling belum diatur',
            ], 400);
        }

        $mapping = $this->profilingService->getMapping($tes);

        // Simulasi jawaban dari request
        $jawabanSimulasi = $request->input('jawaban', []);

        // Hitung skor per pilar
        $skor = [
            'kreatif' => 0,
            'emosional' => 0,
            'aksi' => 0,
            'logika' => 0,
            'spiritual' => 0,
        ];

        foreach ($jawabanSimulasi as $nomorSoal => $jawaban) {
            $soalMapping = $mapping->get((int)$nomorSoal);
            if (!$soalMapping) continue;

            $pilar = $soalMapping->getPilarForJawaban($jawaban);
            if ($pilar && isset($skor[$pilar])) {
                $skor[$pilar]++;
            }
        }

        // Tentukan pilar dominan
        $pilarDominan = array_keys($skor, max($skor))[0];
        $pilarList = ProfilingConfig::pilarList();

        return response()->json([
            'pilar_dominan' => $pilarDominan,
            'nama' => $pilarList[$pilarDominan]['nama'] ?? ucfirst($pilarDominan),
            'kode_qx' => $pilarList[$pilarDominan]['kode_qx'] ?? '',
            'deskripsi' => $pilarList[$pilarDominan]['deskripsi'] ?? '',
            'skor' => $skor,
        ]);
    }

    /**
     * Hapus konfigurasi Profiling
     */
    public function hapus(Tes $tes): RedirectResponse
    {
        ProfilingConfig::where('tes_id', $tes->id)->delete();

        return redirect()
            ->route('admin.tes.show', $tes)
            ->with('success', 'Konfigurasi Profiling berhasil dihapus.');
    }
}
