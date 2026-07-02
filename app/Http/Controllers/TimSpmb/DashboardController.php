<?php

namespace App\Http\Controllers\TimSpmb;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Models\Pembayaran;
use App\Models\FormulirSpmb;
use App\Models\SesiTes;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_peserta' => Peserta::count(),
            'menunggu_verifikasi_pembayaran' => Pembayaran::where('status', 'menunggu')->count(),
            'menunggu_verifikasi_formulir' => FormulirSpmb::where('status', 'diajukan')->count(),
            'sudah_tes' => SesiTes::where('status', 'selesai')->distinct('peserta_id')->count(),
        ];

        return view('tim-spmb.dashboard', compact('stats'));
    }
}
