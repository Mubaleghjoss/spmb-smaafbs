<?php

namespace App\Http\Controllers\TimSpmb;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\SesiTes;
use App\Models\Peserta;
use Illuminate\Http\Request;

class HasilController extends Controller
{
    public function index(Request $request)
    {
        $query = Tes::withCount(['sesiTes as peserta_selesai' => function ($q) {
            $q->where('status', 'selesai');
        }]);

        $tes = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('tim-spmb.hasil.index', compact('tes'));
    }

    public function show(Tes $tes, Request $request)
    {
        $query = SesiTes::with('peserta')
            ->where('tes_id', $tes->id)
            ->where('status', 'selesai');

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->whereHas('peserta', function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$cari}%");
            });
        }

        $sesi = $query->orderBy('nilai', 'desc')->paginate(20);

        $statistik = [
            'total_peserta' => SesiTes::where('tes_id', $tes->id)->where('status', 'selesai')->count(),
            'rata_rata' => SesiTes::where('tes_id', $tes->id)->where('status', 'selesai')->avg('nilai') ?? 0,
            'tertinggi' => SesiTes::where('tes_id', $tes->id)->where('status', 'selesai')->max('nilai') ?? 0,
            'terendah' => SesiTes::where('tes_id', $tes->id)->where('status', 'selesai')->min('nilai') ?? 0,
            'lulus' => SesiTes::where('tes_id', $tes->id)->where('status', 'selesai')->where('lulus', true)->count(),
        ];

        return view('tim-spmb.hasil.show', compact('tes', 'sesi', 'statistik'));
    }

    public function detailPeserta(Tes $tes, SesiTes $sesi)
    {
        $sesi->load(['peserta', 'jawabanPeserta.soal.jawaban']);
        return view('tim-spmb.hasil.detail-peserta', compact('tes', 'sesi'));
    }
}
