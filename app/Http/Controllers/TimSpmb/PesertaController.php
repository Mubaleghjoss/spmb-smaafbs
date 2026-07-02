<?php

namespace App\Http\Controllers\TimSpmb;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Models\Grup;
use App\Services\PesertaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PesertaController extends Controller
{
    public function __construct(
        private PesertaService $pesertaService
    ) {}

    public function index(Request $request)
    {
        $query = Peserta::with(['tahapanSpmb', 'grup']);

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$cari}%")
                  ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('tahap')) {
            $query->whereHas('tahapanSpmb', function ($q) use ($request) {
                $q->where('tahap_saat_ini', $request->tahap);
            });
        }

        $peserta = $query->orderBy('created_at', 'desc')->paginate(20);
        $grup = Grup::all();

        return view('tim-spmb.peserta.index', compact('peserta', 'grup'));
    }

    public function create()
    {
        $grup = Grup::all();
        return view('tim-spmb.peserta.create', compact('grup'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:peserta,email',
            'telepon' => 'nullable|string|max:20',
            'asal_sekolah' => 'nullable|string|max:255',
            'grup_id' => 'nullable|array',
        ]);

        $password = Str::random(8);
        
        $peserta = $this->pesertaService->buatPeserta([
            'nama' => $request->nama,
            'email' => $request->email,
            'telepon' => $request->telepon,
            'asal_sekolah' => $request->asal_sekolah,
            'password' => $password,
        ]);

        if ($request->filled('grup_id')) {
            $peserta->grup()->sync($request->grup_id);
        }

        return redirect()->route('tim-spmb.peserta.index')
            ->with('sukses', "Peserta {$peserta->nama} berhasil ditambahkan. Password: {$password}");
    }

    public function show(Peserta $peserta)
    {
        $peserta->load(['tahapanSpmb', 'formulirSpmb', 'pembayaran', 'sesiTes.tes', 'grup', 'wawancara']);
        return view('tim-spmb.peserta.show', compact('peserta'));
    }
}
