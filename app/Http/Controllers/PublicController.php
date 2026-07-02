<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Services\PengaturanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicController extends Controller
{
    public function __construct(
        private PengaturanService $pengaturanService
    ) {}

    /**
     * Halaman beranda
     */
    public function beranda(): View
    {
        return view('public.beranda');
    }

    /**
     * Halaman alur SPMB
     */
    public function alurSpmb(): View
    {
        $alurSpmb = $this->pengaturanService->ambilAlurSpmb();
        $branding = $this->pengaturanService->ambilBranding();
        
        return view('public.alur-spmb', compact('alurSpmb', 'branding'));
    }

    /**
     * Halaman jadwal
     */
    public function jadwal(): View
    {
        $jadwal = $this->pengaturanService->ambilJadwal();
        $catatan = $this->pengaturanService->ambilCatatanJadwal();
        $branding = $this->pengaturanService->ambilBranding();
        
        return view('public.jadwal', compact('jadwal', 'catatan', 'branding'));
    }

    /**
     * Halaman kontak
     */
    public function kontak(): View
    {
        return view('public.kontak');
    }

    /**
     * Cek Status Kelulusan SPMB
     */
    public function cekStatus(Request $request): View
    {
        $hasil = null;
        $nomorPendaftaran = $request->input('nomor_pendaftaran');

        if ($request->isMethod('post') && $nomorPendaftaran) {
            $peserta = Peserta::with(['tahapanSpmb', 'formulirSpmb'])
                ->where('nomor_pendaftaran', trim($nomorPendaftaran))
                ->first();

            if ($peserta) {
                $tahap = $peserta->tahapanSpmb?->tahap_saat_ini ?? 1;
                $statusKelulusan = $peserta->tahapanSpmb?->status_kelulusan;
                $tahap7Selesai = $peserta->tahapanSpmb?->tahap_7_selesai ?? false;
                $skKelulusan = null;
                $downloadSkUrl = null;

                $tahapLabels = [
                    1 => 'Pendaftaran',
                    2 => 'Pembayaran Formulir',
                    3 => 'Pengisian Formulir',
                    4 => 'Tes Masuk',
                    5 => 'Wawancara',
                    6 => 'Pelunasan',
                    7 => 'Kelulusan',
                ];

                if ($tahap7Selesai && $statusKelulusan === 'lulus') {
                    $status = 'lulus';
                    $statusLabel = 'LULUS / DITERIMA';
                    $keterangan = 'Selamat! Anda dinyatakan lulus dan diterima sebagai peserta didik baru.';

                    $skKelulusan = $this->pengaturanService->ambilSuratKelulusanUntukGelombang(
                        $peserta->tahapanSpmb?->sk_gelombang_kelulusan
                    );

                    if ($skKelulusan && !empty($skKelulusan['file']) && Storage::disk('public')->exists($skKelulusan['file'])) {
                        $downloadSkUrl = URL::signedRoute('cek-status.download-sk', [
                            'peserta' => $peserta->getKey(),
                        ]);
                    }
                } elseif ($statusKelulusan === 'tidak_lulus') {
                    $status = 'tidak_lulus';
                    $statusLabel = 'TIDAK LULUS';
                    $keterangan = 'Mohon maaf, Anda dinyatakan tidak lulus pada seleksi penerimaan murid baru.';
                } else {
                    $status = 'proses';
                    $statusLabel = 'DALAM PROSES';
                    $keterangan = "Proses SPMB Anda masih berjalan. Saat ini berada di Tahap {$tahap}: {$tahapLabels[$tahap]}.";
                }

                $hasil = [
                    'ditemukan' => true,
                    'nama' => $this->maskedName($peserta->nama),
                    'nomor_pendaftaran' => $peserta->nomor_pendaftaran,
                    'asal_sekolah' => $peserta->formulirSpmb?->asal_sekolah ?? '-',
                    'tahap' => $tahap,
                    'tahap_label' => $tahapLabels[$tahap],
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'keterangan' => $keterangan,
                    'progres' => $peserta->tahapanSpmb?->persentase_progres ?? 0,
                    'sk_gelombang' => $skKelulusan['nama'] ?? null,
                    'download_sk_url' => $downloadSkUrl,
                    'sk_tersedia' => !empty($downloadSkUrl),
                ];
            } else {
                $hasil = [
                    'ditemukan' => false,
                ];
            }
        }

        return view('public.cek-status', compact('hasil', 'nomorPendaftaran'));
    }

    /**
     * Download SK kelulusan dari halaman cek status.
     */
    public function downloadSk(Peserta $peserta): StreamedResponse
    {
        $peserta->loadMissing('tahapanSpmb');

        if ($peserta->tahapanSpmb?->status_kelulusan !== 'lulus' || !($peserta->tahapanSpmb?->tahap_7_selesai ?? false)) {
            abort(403, 'SK kelulusan belum tersedia.');
        }

        $skKelulusan = $this->pengaturanService->ambilSuratKelulusanUntukGelombang(
            $peserta->tahapanSpmb?->sk_gelombang_kelulusan
        );

        if (!$skKelulusan || empty($skKelulusan['file']) || !Storage::disk('public')->exists($skKelulusan['file'])) {
            abort(404, 'File SK kelulusan belum tersedia.');
        }

        $extension = pathinfo($skKelulusan['file'], PATHINFO_EXTENSION) ?: 'pdf';
        $namaPeserta = Str::upper(Str::slug($peserta->nama, '-')) ?: 'PESERTA';
        $filename = "SK-SPMB-SMAAFBS-{$namaPeserta}.{$extension}";

        return Storage::disk('public')->download($skKelulusan['file'], $filename);
    }

    /**
     * Mask nama untuk privasi (tampilkan 3 huruf pertama + ***) 
     */
    private function maskedName(string $name): string
    {
        $parts = explode(' ', $name);
        return collect($parts)->map(function ($part) {
            if (strlen($part) <= 2) return $part;
            return substr($part, 0, 3) . str_repeat('*', max(0, strlen($part) - 3));
        })->implode(' ');
    }
}
