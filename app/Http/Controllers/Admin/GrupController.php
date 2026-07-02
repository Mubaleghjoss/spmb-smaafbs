<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grup;
use App\Models\Tes;
use App\Services\GrupService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controller untuk manajemen grup peserta
 * Kebutuhan: 3.3
 */
class GrupController extends Controller
{
    public function __construct(
        private GrupService $grupService
    ) {}

    /**
     * Tampilkan daftar grup
     */
    public function index(): View
    {
        $grup = $this->grupService->ambilDenganPaginasi(15);
        $statistik = $this->grupService->ambilStatistik();

        return view('admin.peserta.grup.index', compact('grup', 'statistik'));
    }

    /**
     * Tampilkan form tambah grup
     */
    public function create(): View
    {
        return view('admin.peserta.grup.create');
    }

    /**
     * Simpan grup baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $this->grupService->buat($validated);

        return redirect()
            ->route('admin.peserta.grup.index')
            ->with('success', 'Grup berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail grup dengan peserta
     */
    public function show(Grup $grup): View
    {
        $grup->load('peserta');
        return view('admin.peserta.grup.show', compact('grup'));
    }

    /**
     * Tampilkan form edit grup
     */
    public function edit(Grup $grup): View
    {
        return view('admin.peserta.grup.edit', compact('grup'));
    }

    /**
     * Perbarui grup
     */
    public function update(Request $request, Grup $grup): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $this->grupService->perbarui($grup, $validated);

        return redirect()
            ->route('admin.peserta.grup.index')
            ->with('success', 'Grup berhasil diperbarui.');
    }

    /**
     * Hapus grup
     */
    public function destroy(Grup $grup): RedirectResponse
    {
        $this->grupService->hapus($grup);

        return redirect()
            ->route('admin.peserta.grup.index')
            ->with('success', 'Grup berhasil dihapus.');
    }

    /**
     * Tampilkan halaman pengaturan tes untuk grup
     * Kebutuhan: 2.1, 2.2
     */
    public function tesGrup(Grup $grup): View
    {
        $tesTerpilih = $this->grupService->ambilTesYangDiassign($grup);
        $semuaTes = Tes::withCount('soal')->orderBy('nama')->get();

        return view('admin.peserta.grup.tes', [
            'grup' => $grup,
            'tesTerpilih' => $tesTerpilih,
            'semuaTes' => $semuaTes,
        ]);
    }

    /**
     * Simpan pengaturan tes untuk grup
     * Kebutuhan: 2.3
     */
    public function simpanTesGrup(Request $request, Grup $grup): RedirectResponse
    {
        $validated = $request->validate([
            'tes_ids' => 'nullable|array',
            'tes_ids.*' => 'exists:tes,id',
        ]);

        $tesIds = $validated['tes_ids'] ?? [];
        $this->grupService->assignTes($grup, $tesIds);

        return redirect()
            ->route('admin.peserta.grup.show', $grup)
            ->with('success', 'Pengaturan tes berhasil disimpan.');
    }
}
