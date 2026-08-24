<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Models\Tes;
use App\Models\Soal;
use App\Services\PeriodeContextService;
use App\Services\RingkasanKuotaDashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard admin
     */
    public function index(): View
    {
        $stats = [
            'total_peserta' => Peserta::count(),
            'peserta_baru' => Peserta::whereDate('created_at', today())->count(),
            'total_tes' => Tes::count(),
            'total_soal' => Soal::where('aktif', true)->count(),
        ];

        // Kuota penerimaan mengikuti periode aktif pada switcher.
        $periode = app(PeriodeContextService::class);
        $periodeAktifId = $periode->tahunAjaranId();
        $semuaPeriode = $periode->semuaPeriode();

        $ringkasanKuota = app(RingkasanKuotaDashboardService::class)
            ->untukPeriode($semuaPeriode ? null : $periodeAktifId);

        return view('admin.dashboard', compact(
            'stats', 'ringkasanKuota', 'periodeAktifId', 'semuaPeriode'
        ));
    }
}
