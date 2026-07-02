<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Soal;
use App\Services\SoalService;
use App\Services\TopikService;
use App\Services\ImporEksporSoalService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller untuk manajemen bank soal
 * Kebutuhan: 2.1, 2.7
 */
class SoalController extends Controller
{
    public function __construct(
        private SoalService $soalService,
        private TopikService $topikService,
        private ImporEksporSoalService $imporEksporService
    ) {}

    /**
     * Tampilkan daftar soal
     */
    public function index(Request $request): View
    {
        // Filter aktif: null = semua, true = aktif saja, false = nonaktif saja
        $aktifFilter = null;
        if ($request->filled('aktif')) {
            $aktifFilter = $request->get('aktif') === '1';
        }

        $filter = [
            'topik_id' => $request->get('topik_id'),
            'tipe' => $request->get('tipe'),
            'aktif' => $aktifFilter,
            'cari' => $request->get('cari'),
        ];

        $soal = $this->soalService->ambilDenganFilter($filter, $request->integer('per_page', 15));
        $topik = $this->topikService->ambilSemuaFlat();
        $statistik = $this->soalService->ambilStatistik();

        return view('admin.soal.index', compact('soal', 'topik', 'statistik', 'filter'));
    }

    /**
     * Tampilkan semua soal hasil filter tanpa pagination
     */
    public function lihatSemua(Request $request): View
    {
        $aktifFilter = null;
        if ($request->filled('aktif')) {
            $aktifFilter = $request->get('aktif') === '1';
        }

        $filter = [
            'topik_id' => $request->get('topik_id'),
            'tipe' => $request->get('tipe'),
            'aktif' => $aktifFilter,
            'cari' => $request->get('cari'),
        ];

        $soal = $this->soalService->ambilDenganFilterTanpaPagination($filter);
        $topik = $this->topikService->ambilSemuaFlat();
        $statistik = $this->soalService->ambilStatistik();

        return view('admin.soal.lihat-semua', compact('soal', 'topik', 'statistik', 'filter'));
    }

    /**
     * Update urutan soal via drag & drop
     */
    public function updateUrutan(Request $request): JsonResponse
    {
        $request->validate([
            'urutan' => 'required|array',
            'urutan.*.id' => 'required|exists:soal,id',
            'urutan.*.urutan' => 'required|integer|min:0',
        ]);

        foreach ($request->urutan as $item) {
            Soal::where('id', $item['id'])->update(['urutan' => $item['urutan']]);
        }

        return response()->json([
            'sukses' => true,
            'pesan' => 'Urutan soal berhasil diperbarui.',
        ]);
    }

    /**
     * Tampilkan form tambah soal
     */
    public function create(): View
    {
        $topik = $this->topikService->ambilSemuaFlat();
        return view('admin.soal.create', compact('topik'));
    }

    /**
     * Simpan soal baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'topik_id' => 'nullable|exists:topik,id',
            'pertanyaan' => 'required|string',
            'tipe' => 'required|in:pilihan_ganda,jawaban_ganda,esai,benar_salah',
            'bobot' => 'required|integer|min:1',
            'pembahasan' => 'nullable|string',
            'aktif' => 'boolean',
            'jawaban' => 'required_unless:tipe,esai|array|min:2',
            'jawaban.*.isi' => 'required|string',
            'jawaban.*.benar' => 'boolean',
        ]);

        $this->soalService->buat($validated, auth()->id());

        return redirect()
            ->route('admin.soal.index')
            ->with('success', 'Soal berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail soal
     */
    public function show(Soal $soal): View
    {
        $soal->load(['topik', 'jawaban', 'pembuat', 'riwayat.pengubah']);
        return view('admin.soal.show', compact('soal'));
    }

    /**
     * Tampilkan form edit soal
     */
    public function edit(Soal $soal): View
    {
        $soal->load('jawaban');
        $topik = $this->topikService->ambilSemuaFlat();
        return view('admin.soal.edit', compact('soal', 'topik'));
    }


    /**
     * Perbarui soal
     */
    public function update(Request $request, Soal $soal): RedirectResponse
    {
        $validated = $request->validate([
            'topik_id' => 'nullable|exists:topik,id',
            'pertanyaan' => 'required|string',
            'tipe' => 'required|in:pilihan_ganda,jawaban_ganda,esai,benar_salah',
            'bobot' => 'required|integer|min:1',
            'pembahasan' => 'nullable|string',
            'aktif' => 'boolean',
            'jawaban' => 'required_unless:tipe,esai|array|min:2',
            'jawaban.*.isi' => 'required|string',
            'jawaban.*.benar' => 'boolean',
        ]);

        $this->soalService->perbarui($soal, $validated, auth()->id());

        return redirect()
            ->route('admin.soal.index')
            ->with('success', 'Soal berhasil diperbarui.');
    }

    /**
     * Hapus soal
     */
    public function destroy(Soal $soal): RedirectResponse
    {
        $this->soalService->hapus($soal);

        return redirect()
            ->route('admin.soal.index')
            ->with('success', 'Soal berhasil dihapus.');
    }

    /**
     * Hapus massal soal yang dipilih
     */
    public function hapusMassal(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:soal,id',
        ]);

        $jumlahDihapus = 0;
        foreach ($request->ids as $id) {
            $soal = Soal::find($id);
            if ($soal) {
                $this->soalService->hapus($soal);
                $jumlahDihapus++;
            }
        }

        return response()->json([
            'sukses' => true,
            'pesan' => "{$jumlahDihapus} soal berhasil dihapus.",
        ]);
    }

    /**
     * Toggle status aktif soal
     */
    public function toggleAktif(Soal $soal): JsonResponse
    {
        $soal = $this->soalService->toggleAktif($soal);

        return response()->json([
            'sukses' => true,
            'aktif' => $soal->aktif,
            'pesan' => $soal->aktif ? 'Soal diaktifkan.' : 'Soal dinonaktifkan.',
        ]);
    }

    /**
     * Duplikat soal
     */
    public function duplikat(Soal $soal): RedirectResponse
    {
        $this->soalService->duplikat($soal, auth()->id());

        return redirect()
            ->route('admin.soal.index')
            ->with('success', 'Soal berhasil diduplikat.');
    }

    /**
     * Upload media untuk soal
     */
    public function uploadMedia(Request $request, Soal $soal): RedirectResponse
    {
        $validated = $request->validate([
            'media' => 'required|file|mimes:jpg,jpeg,png,gif,mp3,wav,mp4|max:10240',
            'tipe_media' => 'required|in:gambar,audio,video',
        ]);

        $this->soalService->uploadMedia($soal, $request->file('media'), $validated['tipe_media']);

        return redirect()
            ->back()
            ->with('success', 'Media berhasil diupload.');
    }

    /**
     * Hapus media dari soal
     */
    public function hapusMedia(Soal $soal): RedirectResponse
    {
        $this->soalService->hapusMedia($soal);

        return redirect()
            ->back()
            ->with('success', 'Media berhasil dihapus.');
    }

    /**
     * Tampilkan halaman impor soal
     */
    public function impor(): View
    {
        $topik = $this->topikService->ambilSemuaFlat();
        return view('admin.soal.impor', compact('topik'));
    }

    /**
     * Proses impor soal dari Excel
     */
    public function prosesImporExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $hasil = $this->imporEksporService->imporDariExcel(
            $request->file('file')->getRealPath(),
            auth()->id()
        );

        $pesan = "Impor selesai: {$hasil['sukses']} berhasil, {$hasil['gagal']} gagal.";

        if (!empty($hasil['errors'])) {
            return redirect()
                ->back()
                ->with('success', $pesan)
                ->with('errors_impor', $hasil['errors']);
        }

        return redirect()
            ->route('admin.soal.index')
            ->with('success', $pesan);
    }

    /**
     * Proses impor soal dari Word
     */
    public function prosesImporWord(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:docx,doc|max:5120',
            'topik_id' => 'nullable|exists:topik,id',
        ]);

        $hasil = $this->imporEksporService->imporDariWord(
            $request->file('file')->getRealPath(),
            auth()->id(),
            $request->get('topik_id')
        );

        $pesan = "Impor selesai: {$hasil['sukses']} berhasil, {$hasil['gagal']} gagal.";

        if (!empty($hasil['errors'])) {
            return redirect()
                ->back()
                ->with('success', $pesan)
                ->with('errors_impor', $hasil['errors']);
        }

        return redirect()
            ->route('admin.soal.index')
            ->with('success', $pesan);
    }

    /**
     * Ekspor soal ke Excel
     */
    public function ekspor(Request $request): BinaryFileResponse
    {
        $topikId = $request->get('topik_id');
        $path = $this->imporEksporService->eksporKeExcel($topikId);

        return response()->download($path)->deleteFileAfterSend();
    }

    /**
     * Preview soal seperti tampilan ujian
     */
    public function preview(Request $request): View
    {
        $filter = [
            'topik_id' => $request->get('topik_id'),
            'tipe' => $request->get('tipe'),
            'aktif' => $request->filled('aktif') ? $request->get('aktif') === '1' : null,
            'cari' => $request->get('cari'),
            'tes_id' => $request->get('tes_id'),
        ];

        $topik = $this->topikService->ambilSemuaFlat();
        $tes = null;
        
        // Jika ada tes_id, ambil soal dari tes dengan urutan yang sama
        if (!empty($filter['tes_id'])) {
            $tes = \App\Models\Tes::with(['soal' => function ($query) {
                $query->with('jawaban')->orderBy('tes_soal.urutan');
            }])->find($filter['tes_id']);
            
            if (!$tes) {
                return redirect()->route('admin.soal.index', $filter)
                    ->with('error', 'Tes tidak ditemukan.');
            }
            
            $soalList = $tes->soal;
        } else {
            // Ambil semua soal tanpa pagination untuk preview
            $soalList = $this->soalService->ambilDenganFilterTanpaPagination($filter);
        }
        
        $nomor = max(1, (int) $request->get('nomor', 1));
        $totalSoal = $soalList->count();
        
        if ($totalSoal === 0) {
            return redirect()->route('admin.soal.index', $filter)
                ->with('error', 'Tidak ada soal yang ditemukan untuk preview.');
        }
        
        // Pastikan nomor tidak melebihi total
        $nomor = min($nomor, $totalSoal);
        
        // Ambil soal berdasarkan nomor (index 0-based)
        $soalSaatIni = $soalList->values()->get($nomor - 1);
        $soalSaatIni->load('jawaban');

        return view('admin.soal.preview', compact('soalList', 'soalSaatIni', 'nomor', 'totalSoal', 'topik', 'filter', 'tes'));
    }

    /**
     * Download template impor Excel
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $path = $this->imporEksporService->generateTemplate();
        return response()->download($path)->deleteFileAfterSend();
    }
}
