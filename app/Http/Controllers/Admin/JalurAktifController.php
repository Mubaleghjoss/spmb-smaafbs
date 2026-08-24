<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\JalurContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JalurAktifController extends Controller
{
    public function __construct(private JalurContextService $jalur) {}

    /**
     * Ganti jalur pendaftaran aktif (siswa baru / pindahan) untuk konteks admin.
     * Tidak ada nilai "semua" — data kedua jalur tidak pernah dicampur di
     * halaman kerja. Nilai kosong mengembalikan ke keadaan "belum memilih".
     */
    public function ganti(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_pendaftaran' => 'nullable|string|in:siswa_baru,pindahan,',
        ]);

        $jenis = $validated['jenis_pendaftaran'] ?? null;
        $this->jalur->set($jenis !== '' ? $jenis : null);

        return back()->with('success', 'Jalur pendaftaran aktif: ' . $this->jalur->label());
    }
}
