<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Services\PembayaranService;
use App\Services\PengaturanService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    public function __construct(
        private PembayaranService $pembayaranService,
        private PengaturanService $pengaturanService
    ) {}

    public function uploadBuktiFormulir(): View
    {
        $peserta = Peserta::find(session('peserta_id'));
        $pembayaran = $this->pembayaranService->ambilPembayaranPeserta($peserta, 'formulir');
        $spmb = $this->pengaturanService->ambilSpmb();
        return view('peserta.pembayaran.formulir', compact('peserta', 'pembayaran', 'spmb'));
    }

    public function simpanBuktiFormulir(Request $request): RedirectResponse
    {
        $request->validate([
            'bukti' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'bukti.required' => 'Bukti pembayaran wajib diupload',
            'bukti.image' => 'File harus berupa gambar',
            'bukti.mimes' => 'Format file harus jpeg, png, atau jpg',
            'bukti.max' => 'Ukuran file maksimal 2MB',
        ]);

        $peserta = Peserta::find(session('peserta_id'));
        $this->pembayaranService->uploadBukti($peserta, 'formulir', $request->file('bukti'));

        return redirect()->route('peserta.pembayaran.status-formulir')
            ->with('success', 'Bukti pembayaran berhasil diupload. Tunggu verifikasi dari admin.');
    }

    public function statusFormulir(): View
    {
        $peserta = Peserta::find(session('peserta_id'));
        $pembayaran = $this->pembayaranService->ambilPembayaranPeserta($peserta, 'formulir');
        
        // Ambil data kwitansi jika pembayaran sudah terverifikasi
        $kwitansi = null;
        if ($pembayaran && $pembayaran->status === 'terverifikasi') {
            $kwitansiService = app(\App\Services\KwitansiService::class);
            $kwitansi = $kwitansiService->ambilKwitansi($pembayaran);
        }
        
        return view('peserta.pembayaran.status-formulir', compact('peserta', 'pembayaran', 'kwitansi'));
    }

    /**
     * Halaman upload bukti pelunasan (Tahap 6)
     */
    public function uploadBuktiPelunasan(): View|RedirectResponse
    {
        $peserta = Peserta::find(session('peserta_id'));
        
        // Cek apakah tahap 5 sudah selesai
        if (!$peserta->tahapanSelesai(5)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Selesaikan tahap wawancara terlebih dahulu');
        }
        
        $pembayaran = $this->pembayaranService->ambilPembayaranPeserta($peserta, 'pertama');
        
        // Jika sudah upload, redirect ke status
        if ($pembayaran) {
            return redirect()->route('peserta.pembayaran.status-pelunasan');
        }
        
        $spmb = $this->pengaturanService->ambilSpmb();
        return view('peserta.pembayaran.pelunasan', compact('peserta', 'spmb'));
    }

    /**
     * Simpan bukti pelunasan
     */
    public function simpanBuktiPelunasan(Request $request): RedirectResponse
    {
        $request->validate([
            'bukti' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'nominal' => 'required|numeric|min:0',
        ], [
            'bukti.required' => 'Bukti pembayaran wajib diupload',
            'bukti.image' => 'File harus berupa gambar',
            'bukti.mimes' => 'Format file harus jpeg, png, atau jpg',
            'bukti.max' => 'Ukuran file maksimal 2MB',
            'nominal.required' => 'Nominal pembayaran wajib diisi',
            'nominal.numeric' => 'Nominal harus berupa angka',
        ]);

        $peserta = Peserta::find(session('peserta_id'));
        $this->pembayaranService->uploadBukti($peserta, 'pertama', $request->file('bukti'), $request->nominal);

        return redirect()->route('peserta.pembayaran.status-pelunasan')
            ->with('success', 'Bukti pembayaran berhasil diupload. Tunggu verifikasi dari admin.');
    }

    /**
     * Halaman status pelunasan
     */
    public function statusPelunasan(): View
    {
        $peserta = Peserta::find(session('peserta_id'));
        $pembayaran = $this->pembayaranService->ambilPembayaranPeserta($peserta, 'pertama');
        
        // Ambil data kwitansi jika pembayaran sudah terverifikasi
        $kwitansi = null;
        if ($pembayaran && $pembayaran->status === 'terverifikasi') {
            $kwitansiService = app(\App\Services\KwitansiService::class);
            $kwitansi = $kwitansiService->ambilKwitansi($pembayaran);
        }
        
        return view('peserta.pembayaran.status-pelunasan', compact('peserta', 'pembayaran', 'kwitansi'));
    }

    /**
     * Cetak kwitansi pembayaran peserta
     */
    public function cetakKwitansi(\App\Models\Pembayaran $pembayaran): View
    {
        $peserta = Peserta::find(session('peserta_id'));
        
        // Validasi pembayaran milik peserta yang login
        if ($pembayaran->peserta_id !== $peserta->id) {
            abort(403, 'Akses ditolak');
        }
        
        // Hanya bisa cetak jika sudah terverifikasi
        if ($pembayaran->status !== 'terverifikasi') {
            abort(404, 'Kwitansi tidak tersedia');
        }
        
        $kwitansiService = app(\App\Services\KwitansiService::class);
        $kwitansi = $kwitansiService->ambilKwitansi($pembayaran);
        
        return view('admin.verifikasi.cetak-kwitansi', compact('kwitansi', 'pembayaran'));
    }
}
