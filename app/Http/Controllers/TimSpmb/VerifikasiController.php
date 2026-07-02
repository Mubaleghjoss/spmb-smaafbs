<?php

namespace App\Http\Controllers\TimSpmb;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\FormulirSpmb;
use App\Models\Peserta;
use App\Models\SesiTes;
use App\Services\TahapanSpmbService;
use Illuminate\Http\Request;

class VerifikasiController extends Controller
{
    public function __construct(
        private TahapanSpmbService $tahapanService
    ) {}

    public function index()
    {
        $stats = [
            'pembayaran_formulir' => Pembayaran::where('jenis', 'formulir')->where('status', 'menunggu')->count(),
            'formulir' => FormulirSpmb::where('status', 'diajukan')->count(),
            'hasil_tes' => SesiTes::where('status', 'selesai')
                ->whereHas('peserta.tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 4))
                ->count(),
            'pelunasan' => Pembayaran::where('jenis', 'pertama')->where('status', 'menunggu')->count(),
        ];

        return view('tim-spmb.verifikasi.index', compact('stats'));
    }

    // Verifikasi Pembayaran Formulir
    public function pembayaranFormulir()
    {
        $pembayaran = Pembayaran::with('peserta')
            ->where('jenis', 'formulir')
            ->where('status', 'menunggu')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return view('tim-spmb.verifikasi.pembayaran-formulir', compact('pembayaran'));
    }

    public function terimaPembayaranFormulir(Pembayaran $pembayaran)
    {
        $pembayaran->update([
            'status' => 'terverifikasi',
            'diverifikasi_oleh' => auth('pengguna')->id(),
            'diverifikasi_pada' => now(),
        ]);

        $this->tahapanService->selesaikanTahap($pembayaran->peserta, 3);

        return back()->with('sukses', 'Pembayaran formulir berhasil diverifikasi');
    }

    public function tolakPembayaranFormulir(Request $request, Pembayaran $pembayaran)
    {
        $request->validate(['alasan' => 'required|string|max:500']);

        $pembayaran->update([
            'status' => 'ditolak',
            'catatan' => $request->alasan,
        ]);

        return back()->with('sukses', 'Pembayaran formulir ditolak');
    }

    // Verifikasi Formulir
    public function formulir()
    {
        $formulir = FormulirSpmb::with('peserta')
            ->where('status', 'diajukan')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return view('tim-spmb.verifikasi.formulir', compact('formulir'));
    }

    public function detailFormulir(FormulirSpmb $formulir)
    {
        $formulir->load('peserta');
        return view('tim-spmb.verifikasi.detail-formulir', compact('formulir'));
    }

    public function terimaFormulir(FormulirSpmb $formulir)
    {
        $formulir->update([
            'status' => 'diverifikasi',
            'diverifikasi_oleh' => auth('pengguna')->id(),
            'diverifikasi_pada' => now(),
        ]);

        // Lanjut ke tahap tes
        $this->tahapanService->selesaikanTahap($formulir->peserta, 2);

        return redirect()->route('tim-spmb.verifikasi.formulir')
            ->with('sukses', 'Formulir berhasil diverifikasi');
    }

    public function tolakFormulir(Request $request, FormulirSpmb $formulir)
    {
        $request->validate(['alasan' => 'required|string|max:500']);

        $formulir->update([
            'status' => 'ditolak',
            'catatan_verifikasi' => $request->alasan,
        ]);

        return redirect()->route('tim-spmb.verifikasi.formulir')
            ->with('sukses', 'Formulir ditolak');
    }

    // Verifikasi Hasil Tes
    public function hasilTes()
    {
        $sesi = SesiTes::with(['peserta', 'tes'])
            ->where('status', 'selesai')
            ->whereHas('peserta.tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 4))
            ->orderBy('selesai_pada', 'asc')
            ->paginate(20);

        return view('tim-spmb.verifikasi.hasil-tes', compact('sesi'));
    }

    public function loloskanHasilTes(SesiTes $sesi)
    {
        $this->tahapanService->selesaikanTahap($sesi->peserta, 4);

        return back()->with('sukses', 'Peserta diloloskan ke tahap wawancara');
    }

    // Verifikasi Pelunasan
    public function pelunasan()
    {
        $pembayaran = Pembayaran::with('peserta')
            ->where('jenis', 'pertama')
            ->where('status', 'menunggu')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return view('tim-spmb.verifikasi.pelunasan', compact('pembayaran'));
    }

    public function terimaPelunasan(Pembayaran $pembayaran)
    {
        $pembayaran->update([
            'status' => 'terverifikasi',
            'diverifikasi_oleh' => auth('pengguna')->id(),
            'diverifikasi_pada' => now(),
        ]);

        $this->tahapanService->selesaikanTahap($pembayaran->peserta, 6);

        return back()->with('sukses', 'Pelunasan berhasil diverifikasi');
    }

    public function tolakPelunasan(Request $request, Pembayaran $pembayaran)
    {
        $request->validate(['alasan' => 'required|string|max:500']);

        $pembayaran->update([
            'status' => 'ditolak',
            'catatan' => $request->alasan,
        ]);

        return back()->with('sukses', 'Pelunasan ditolak');
    }
}
