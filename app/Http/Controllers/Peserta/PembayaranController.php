<?php

namespace App\Http\Controllers\Peserta;

use App\Enums\StatusPembayaran;
use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Services\PembayaranService;
use App\Services\PengaturanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    public function __construct(
        private PembayaranService $pembayaranService,
        private PengaturanService $pengaturanService,
        private \App\Services\BiayaSpmbService $biayaSpmbService,
    ) {}

    public function uploadBuktiFormulir(): View|RedirectResponse
    {
        $peserta = Peserta::with('tahapanSpmb')->find(session('peserta_id'));
        if (! $this->bolehAksesUploadFormulir($peserta)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Selesaikan formulir terlebih dahulu sebelum upload bukti pembayaran.');
        }

        $pembayaran = $this->pembayaranService->ambilPembayaranPeserta($peserta, 'formulir');
        $spmb = $this->pengaturanService->ambilSpmb();
        $rincianBiaya = $this->biayaSpmbService->untukFormulir($spmb, $peserta->formulirSpmb);
        if (! $rincianBiaya['lengkap']) {
            return redirect()->route('peserta.formulir.isi')
                ->with('error', 'Pilih nama daerah pada formulir sebelum upload bukti pembayaran.');
        }

        return view('peserta.pembayaran.formulir', compact('peserta', 'pembayaran', 'spmb', 'rincianBiaya'));
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

        $peserta = Peserta::with('tahapanSpmb')->find(session('peserta_id'));
        if (! $this->bolehAksesUploadFormulir($peserta)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Selesaikan formulir terlebih dahulu sebelum upload bukti pembayaran.');
        }

        $rincianBiaya = $this->biayaSpmbService->untukFormulir(
            $this->pengaturanService->ambilSpmb(),
            $peserta->formulirSpmb,
        );
        if (! $rincianBiaya['lengkap']) {
            return redirect()->route('peserta.formulir.isi')
                ->with('error', 'Pilih nama daerah pada formulir sebelum upload bukti pembayaran.');
        }

        $pembayaran = $this->pembayaranService->uploadBukti(
            $peserta,
            'formulir',
            $request->file('bukti'),
            $rincianBiaya['formulir'],
        );
        $peserta->refresh();

        $pesanKuota = $peserta->status_kuota === Peserta::STATUS_KUOTA_DALAM
            ? "Urutan kuota #{$peserta->urutan_kuota} berhasil diamankan."
            : "Anda masuk waiting list #{$peserta->urutan_kuota}.";

        return redirect()->route('peserta.pembayaran.status-formulir')
            ->with('success', 'Bukti pembayaran senilai Rp '.number_format((int) $pembayaran->nominal, 0, ',', '.')." berhasil diupload. {$pesanKuota} Tes online tetap dibuka setelah panitia memverifikasi bukti.");
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
        $peserta = Peserta::with('tahapanSpmb')->find(session('peserta_id'));

        // Peserta lulus final tetap boleh upload jika data pembayaran terlewat.
        if (! $peserta->tahapanSelesai(5) && ! $this->sudahLulusFinal($peserta)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Selesaikan tahap wawancara terlebih dahulu');
        }

        $ringkasan = app(\App\Services\TahapEnamPembayaranService::class)->ringkasan($peserta);
        $pembayaran = $this->pembayaranService->ambilPembayaranPeserta($peserta, 'pertama');
        $adaMenunggu = \App\Models\Pembayaran::where('peserta_id', $peserta->id)
            ->where('jenis', 'pertama')
            ->where('status', StatusPembayaran::MENUNGGU->value)
            ->exists();

        // Satu bukti menunggu pada satu waktu, tetapi cicilan berikutnya tetap
        // dapat diupload setelah pembayaran sebelumnya diverifikasi.
        if ($ringkasan['lunas'] || $adaMenunggu) {
            return redirect()->route('peserta.pembayaran.status-pelunasan');
        }

        $spmb = $this->pengaturanService->ambilSpmb();

        return view('peserta.pembayaran.pelunasan', compact('peserta', 'spmb', 'pembayaran', 'ringkasan'));
    }

    /**
     * Simpan bukti pelunasan
     */
    public function simpanBuktiPelunasan(Request $request): RedirectResponse
    {
        $request->validate([
            'bukti' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'metode_bayar' => 'required|in:lunas,cicilan',
            'nominal' => 'nullable|numeric|min:1',
        ], [
            'bukti.required' => 'Bukti pembayaran wajib diupload',
            'bukti.image' => 'File harus berupa gambar',
            'bukti.mimes' => 'Format file harus jpeg, png, atau jpg',
            'bukti.max' => 'Ukuran file maksimal 2MB',
            'nominal.required' => 'Nominal pembayaran wajib diisi',
            'nominal.numeric' => 'Nominal harus berupa angka',
        ]);

        $peserta = Peserta::with('tahapanSpmb')->find(session('peserta_id'));
        if (! $peserta->tahapanSelesai(5) && ! $this->sudahLulusFinal($peserta)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Selesaikan tahap wawancara terlebih dahulu');
        }

        $ringkasan = app(\App\Services\TahapEnamPembayaranService::class)->ringkasan($peserta);
        if ($ringkasan['lunas']) {
            return redirect()->route('peserta.pembayaran.status-pelunasan')
                ->with('error', 'Tagihan tahap pertama sudah lunas.');
        }
        if (\App\Models\Pembayaran::where('peserta_id', $peserta->id)->where('jenis', 'pertama')->where('status', StatusPembayaran::MENUNGGU->value)->exists()) {
            return redirect()->route('peserta.pembayaran.status-pelunasan')
                ->with('error', 'Masih ada bukti pembayaran yang menunggu verifikasi.');
        }

        $nominal = $request->metode_bayar === 'lunas'
            ? $ringkasan['sisa']
            : (float) $request->nominal;
        if ($nominal > $ringkasan['sisa']) {
            return back()->withInput()->withErrors(['nominal' => 'Nominal cicilan tidak boleh melebihi sisa tagihan Rp '.number_format($ringkasan['sisa'], 0, ',', '.').'.']);
        }

        $this->pembayaranService->uploadBukti($peserta, 'pertama', $request->file('bukti'), $nominal);

        return redirect()->route('peserta.pembayaran.status-pelunasan')
            ->with('success', 'Bukti pembayaran berhasil diupload. Tunggu verifikasi dari admin.');
    }

    /**
     * Halaman status pelunasan
     */
    public function statusPelunasan(): View
    {
        $peserta = Peserta::find(session('peserta_id'));
        $tahapEnam = app(\App\Services\TahapEnamPembayaranService::class);
        $ringkasan = $tahapEnam->ringkasan($peserta);
        $riwayat = $tahapEnam->riwayat($peserta);
        $pembayaran = $riwayat->first();

        // Kwitansi hanya terbit setelah total tagihan benar-benar lunas.
        $kwitansi = null;
        $pembayaranKwitansi = $riwayat->first(fn ($item) => $item->status === StatusPembayaran::TERVERIFIKASI->value);
        if ($ringkasan['lunas'] && $pembayaranKwitansi) {
            $kwitansiService = app(\App\Services\KwitansiService::class);
            $kwitansi = $kwitansiService->ambilKwitansi($pembayaranKwitansi);
            if ($kwitansi) {
                $kwitansi['nominal'] = $ringkasan['tagihan'];
            }
        }

        $branding = $this->pengaturanService->ambilBranding();

        return view('peserta.pembayaran.status-pelunasan', compact('peserta', 'pembayaran', 'kwitansi', 'ringkasan', 'riwayat', 'pembayaranKwitansi', 'branding'));
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
        $ringkasan = app(\App\Services\TahapEnamPembayaranService::class)->ringkasan($peserta);
        if ($pembayaran->status !== 'terverifikasi' || ! $ringkasan['lunas']) {
            abort(404, 'Kwitansi tidak tersedia');
        }

        $kwitansiService = app(\App\Services\KwitansiService::class);
        $kwitansi = $kwitansiService->ambilKwitansi($pembayaran);
        if ($kwitansi) {
            $kwitansi['nominal'] = $ringkasan['tagihan'];
        }

        return view('admin.verifikasi.cetak-kwitansi', compact('kwitansi', 'pembayaran'));
    }

    private function bolehAksesUploadFormulir(Peserta $peserta): bool
    {
        return $peserta->tahapanSelesai(2)
            || $peserta->tahapanSelesai(3)
            || $this->sudahLulusFinal($peserta);
    }

    private function sudahLulusFinal(Peserta $peserta): bool
    {
        return ($peserta->tahapanSpmb?->status_kelulusan === 'lulus')
            && (bool) ($peserta->tahapanSpmb?->tahap_7_selesai ?? false);
    }
}
