<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PeriodeContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PeriodeAktifController extends Controller
{
    public function __construct(private PeriodeContextService $periode) {}

    /**
     * Ganti periode aktif (tahun ajaran) untuk seluruh konteks admin.
     * Nilai 'semua' = Semua Periode (agregat lintas tahun).
     */
    public function ganti(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => 'required|string',
        ]);

        $nilai = $validated['tahun_ajaran_id'];

        if ($nilai === PeriodeContextService::SEMUA) {
            $this->periode->set(PeriodeContextService::SEMUA);
        } else {
            // Pastikan id valid; kalau tidak, kembalikan ke default.
            $request->validate([
                'tahun_ajaran_id' => 'exists:tahun_ajaran,id',
            ]);
            $this->periode->set((int) $nilai);
        }

        return back()->with('success', 'Periode aktif diganti ke: ' . $this->periode->label());
    }
}
