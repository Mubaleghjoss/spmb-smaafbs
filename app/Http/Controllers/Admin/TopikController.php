<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topik;
use App\Services\TopikService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controller untuk manajemen topik/kategori soal
 * Kebutuhan: 2.5
 */
class TopikController extends Controller
{
    public function __construct(
        private TopikService $topikService
    ) {}

    /**
     * Tampilkan daftar topik
     */
    public function index(): View
    {
        $topik = $this->topikService->ambilDenganPaginasi(15);
        $statistik = $this->topikService->ambilStatistik();

        return view('admin.soal.topik.index', compact('topik', 'statistik'));
    }

    /**
     * Tampilkan form tambah topik
     */
    public function create(): View
    {
        $parentTopik = $this->topikService->ambilSemuaFlat();
        return view('admin.soal.topik.create', compact('parentTopik'));
    }

    /**
     * Simpan topik baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'parent_id' => 'nullable|exists:topik,id',
        ]);

        $this->topikService->buat($validated);

        return redirect()
            ->route('admin.soal.topik.index')
            ->with('success', 'Topik berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit topik
     */
    public function edit(Topik $topik): View
    {
        $parentTopik = $this->topikService->ambilSemuaFlat()
            ->where('id', '!=', $topik->id);

        return view('admin.soal.topik.edit', compact('topik', 'parentTopik'));
    }

    /**
     * Perbarui topik
     */
    public function update(Request $request, Topik $topik): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'parent_id' => 'nullable|exists:topik,id|not_in:' . $topik->id,
        ]);

        $this->topikService->perbarui($topik, $validated);

        return redirect()
            ->route('admin.soal.topik.index')
            ->with('success', 'Topik berhasil diperbarui.');
    }

    /**
     * Hapus topik
     */
    public function destroy(Topik $topik): RedirectResponse
    {
        $this->topikService->hapus($topik);

        return redirect()
            ->route('admin.soal.topik.index')
            ->with('success', 'Topik berhasil dihapus.');
    }
}
