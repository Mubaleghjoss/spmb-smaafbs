<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\FormulirSpmb;
use App\Models\Peserta;
use App\Models\SesiTes;
use App\Services\MonitoringUjianService;
use App\Services\VerifikasiSpmbService;
use App\Services\PembayaranService;
use App\Enums\StatusPembayaran;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class VerifikasiSpmbController extends Controller
{
    public function __construct(
        private VerifikasiSpmbService $verifikasiService,
        private PembayaranService $pembayaranService
    ) {}

    /**
     * Dashboard verifikasi
     */
    public function index(Request $request): View
    {
        $statistik = $this->verifikasiService->ambilStatistik();

        // Ambil daftar peserta dengan filter
        $filter = $request->only(['tahap', 'status', 'cari']);
        $peserta = Peserta::with(['tahapanSpmb', 'formulirSpmb', 'pembayaran', 'sesiTes', 'wawancara'])
            ->when(!empty($filter['tahap']), function ($q) use ($filter) {
                $q->whereHas('tahapanSpmb', fn($t) => $t->where('tahap_saat_ini', $filter['tahap']));
            })
            ->when(!empty($filter['cari']), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('nama', 'like', "%{$filter['cari']}%")
                          ->orWhere('nomor_pendaftaran', 'like', "%{$filter['cari']}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.verifikasi.index', compact('statistik', 'peserta', 'filter'));
    }

    /**
     * Daftar peserta untuk verifikasi
     */
    public function daftarPeserta(Request $request): View
    {
        $filter = $request->only(['tahap', 'status', 'cari']);
        $peserta = $this->verifikasiService->ambilDaftarVerifikasi($filter);
        return view('admin.verifikasi.daftar-peserta', compact('peserta', 'filter'));
    }

    /**
     * Daftar pembayaran formulir menunggu verifikasi
     */
    public function pembayaranFormulir(): View
    {
        $pembayaran = $this->verifikasiService->ambilPembayaranMenunggu('formulir');

        // Ambil peserta yang belum upload bukti pembayaran formulir (tahap 3)
        $pesertaBelumUpload = Peserta::with('tahapanSpmb')
            ->whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 3))
            ->whereDoesntHave('pembayaran', fn($q) => $q->where('jenis', 'formulir'))
            ->get();

        return view('admin.verifikasi.pembayaran-formulir', compact('pembayaran', 'pesertaBelumUpload'));
    }

    /**
     * Upload bukti pembayaran formulir oleh admin (bantuan Tim SPMB)
     */
    public function uploadBuktiFormulir(Request $request, Peserta $peserta): RedirectResponse
    {
        $request->validate([
            'bukti' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'bukti.required' => 'Bukti pembayaran wajib diupload',
            'bukti.image'    => 'File harus berupa gambar',
            'bukti.mimes'    => 'Format file harus jpeg, png, atau jpg',
            'bukti.max'      => 'Ukuran file maksimal 2MB',
        ]);

        return $this->uploadBuktiBantuan($request, $peserta, [
            'jenis'            => 'formulir',
            'storage_folder'   => 'pembayaran/formulir',
            'tahap_selesai'    => [3],
            'with_kwitansi'    => true,
            'redirect_route'   => 'admin.verifikasi.pembayaran-formulir',
            'pesan_sukses'     => "Bukti pembayaran untuk {$peserta->nama} berhasil diupload dan diverifikasi.",
        ]);
    }

    /**
     * Detail pembayaran formulir
     */
    public function detailPembayaranFormulir(Pembayaran $pembayaran): View
    {
        $pembayaran->load('peserta');
        return view('admin.verifikasi.detail-pembayaran', compact('pembayaran'));
    }

    /**
     * Verifikasi pembayaran formulir
     */
    public function verifikasiPembayaranFormulir(Pembayaran $pembayaran): RedirectResponse
    {
        // Generate nomor kwitansi
        $kwitansiService = app(\App\Services\KwitansiService::class);
        $nomorKwitansi = $kwitansiService->generateNomorKwitansi();
        $pembayaran->update(['nomor_kwitansi' => $nomorKwitansi]);

        $this->verifikasiService->verifikasiPembayaranFormulir($pembayaran, auth('pengguna')->user());
        return redirect()->route('admin.verifikasi.pembayaran-formulir')
            ->with('success', "Pembayaran berhasil diverifikasi. No. Kwitansi: {$nomorKwitansi}");
    }

    /**
     * Tolak pembayaran formulir
     */
    public function tolakPembayaranFormulir(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $request->validate(['alasan' => 'required|string|max:500']);
        $this->verifikasiService->tolakPembayaranFormulir($pembayaran, $request->alasan, auth('pengguna')->user());
        return redirect()->route('admin.verifikasi.pembayaran-formulir')
            ->with('success', 'Pembayaran ditolak');
    }

    /**
     * Cetak kwitansi pembayaran
     */
    public function cetakKwitansi(Pembayaran $pembayaran): View
    {
        // Hanya bisa cetak jika sudah terverifikasi
        if ($pembayaran->status !== 'terverifikasi') {
            abort(404, 'Kwitansi tidak tersedia');
        }

        $kwitansiService = app(\App\Services\KwitansiService::class);
        $kwitansi = $kwitansiService->ambilKwitansi($pembayaran);

        return view('admin.verifikasi.cetak-kwitansi', compact('kwitansi', 'pembayaran'));
    }

    /**
     * Daftar formulir menunggu verifikasi
     */
    public function formulir(Request $request): View
    {
        $filter = $request->input('filter', 'menunggu');

        if ($filter === 'semua') {
            $formulir = FormulirSpmb::with('peserta')
                ->whereIn('status_verifikasi', ['menunggu', 'terverifikasi', 'ditolak'])
                ->latest()
                ->paginate(15);
        } elseif ($filter === 'belum_lengkap') {
            $formulir = FormulirSpmb::with('peserta')
                ->where(function($q) {
                    $q->whereNull('file_kk')
                      ->orWhereNull('file_akta')
                      ->orWhereNull('file_ijazah')
                      ->orWhereNull('file_bpjs')
                      ->orWhereNull('file_ktp_ibu')
                      ->orWhereNull('file_ktp_ayah');
                })
                ->latest()
                ->paginate(15);
        } else {
            $formulir = $this->verifikasiService->ambilFormulirMenunggu();
        }

        // Statistik
        $statistik = [
            'total' => FormulirSpmb::whereIn('status_verifikasi', ['menunggu', 'terverifikasi', 'ditolak'])->count(),
            'menunggu' => FormulirSpmb::where('status_verifikasi', 'menunggu')->count(),
            'terverifikasi' => FormulirSpmb::where('status_verifikasi', 'terverifikasi')->count(),
            'ditolak' => FormulirSpmb::where('status_verifikasi', 'ditolak')->count(),
            'belum_lengkap' => FormulirSpmb::where(function($q) {
                $q->whereNull('file_kk')
                  ->orWhereNull('file_akta')
                  ->orWhereNull('file_ijazah')
                  ->orWhereNull('file_bpjs')
                  ->orWhereNull('file_ktp_ibu')
                  ->orWhereNull('file_ktp_ayah');
            })->count(),
        ];

        return view('admin.verifikasi.formulir', compact('formulir', 'filter', 'statistik'));
    }

    /**
     * Export data formulir ke Excel/CSV
     */
    public function eksporFormulir(Request $request)
    {
        $formulir = FormulirSpmb::with('peserta')
            ->whereIn('status_verifikasi', ['menunggu', 'terverifikasi', 'ditolak'])
            ->get();

        $filename = 'data-formulir-spmb-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = [
            'No', 'No Pendaftaran', 'Nama Lengkap', 'Tempat Lahir', 'Tanggal Lahir',
            'Jenis Kelamin', 'Asal Sekolah', 'NISN', 'Nama Ayah', 'Nama Ibu',
            'Pekerjaan Ayah', 'Pekerjaan Ibu', 'Alamat Kelurahan', 'Alamat Kecamatan',
            'Alamat Kota', 'Alamat Provinsi', 'Telepon Siswa', 'Telepon Ayah', 'Telepon Ibu',
            'Status Verifikasi', 'File KK', 'File Akta', 'File Ijazah', 'File BPJS',
            'File KTP Ibu', 'File KTP Ayah'
        ];

        $callback = function() use ($formulir, $columns) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns, ';');

            $no = 1;
            foreach ($formulir as $f) {
                fputcsv($file, [
                    $no++,
                    $f->peserta->nomor_pendaftaran ?? '-',
                    $f->nama_lengkap ?? '-',
                    $f->tempat_lahir ?? '-',
                    $f->tanggal_lahir?->format('d/m/Y') ?? '-',
                    $f->jenis_kelamin === 'L' ? 'Laki-laki' : ($f->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                    $f->asal_sekolah ?? '-',
                    $f->nisn ?? '-',
                    $f->nama_ayah ?? '-',
                    $f->nama_ibu ?? '-',
                    $f->pekerjaan_ayah ?? '-',
                    $f->pekerjaan_ibu ?? '-',
                    $f->alamat_kelurahan ?? '-',
                    $f->alamat_kecamatan ?? '-',
                    $f->alamat_kota ?? '-',
                    $f->alamat_provinsi ?? '-',
                    $f->telepon ?? '-',
                    $f->telepon_ayah ?? '-',
                    $f->telepon_ibu ?? '-',
                    ucfirst($f->status_verifikasi),
                    $f->file_kk ? 'Sudah Upload' : 'Belum Upload',
                    $f->file_akta ? 'Sudah Upload' : 'Belum Upload',
                    $f->file_ijazah ? 'Sudah Upload' : 'Belum Upload',
                    $f->file_bpjs ? 'Sudah Upload' : 'Belum Upload',
                    $f->file_ktp_ibu ? 'Sudah Upload' : 'Belum Upload',
                    $f->file_ktp_ayah ? 'Sudah Upload' : 'Belum Upload',
                ], ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Detail formulir
     */
    public function detailFormulir(FormulirSpmb $formulir): View
    {
        $formulir->load('peserta');
        return view('admin.verifikasi.detail-formulir', compact('formulir'));
    }

    /**
     * Verifikasi formulir
     */
    public function verifikasiFormulir(FormulirSpmb $formulir): RedirectResponse
    {
        $this->verifikasiService->verifikasiFormulir($formulir, auth('pengguna')->user());
        return redirect()->route('admin.verifikasi.formulir')
            ->with('success', 'Formulir berhasil diverifikasi');
    }

    /**
     * Tolak formulir
     */
    public function tolakFormulir(Request $request, FormulirSpmb $formulir): RedirectResponse
    {
        $request->validate(['alasan' => 'required|string|max:500']);
        $this->verifikasiService->tolakFormulir($formulir, $request->alasan, auth('pengguna')->user());
        return redirect()->route('admin.verifikasi.formulir')
            ->with('success', 'Formulir ditolak');
    }

    /**
     * Daftar pelunasan menunggu verifikasi
     */
    public function pelunasan(): View
    {
        $pembayaran = $this->verifikasiService->ambilPembayaranMenunggu('pertama');

        // Ambil peserta yang belum upload bukti pelunasan (tahap 6)
        $pesertaBelumUpload = Peserta::with('tahapanSpmb')
            ->whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 6))
            ->whereDoesntHave('pembayaran', fn($q) => $q->where('jenis', 'pertama'))
            ->get();

        return view('admin.verifikasi.pelunasan', compact('pembayaran', 'pesertaBelumUpload'));
    }

    /**
     * Upload bukti pelunasan oleh admin (bantuan Tim SPMB)
     */
    public function uploadBuktiPelunasan(Request $request, Peserta $peserta): RedirectResponse
    {
        $request->validate([
            'bukti'   => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'nominal' => 'required|numeric|min:0',
        ], [
            'bukti.required'   => 'Bukti pembayaran wajib diupload',
            'bukti.image'      => 'File harus berupa gambar',
            'bukti.mimes'      => 'Format file harus jpeg, png, atau jpg',
            'bukti.max'        => 'Ukuran file maksimal 2MB',
            'nominal.required' => 'Nominal pembayaran wajib diisi',
            'nominal.numeric'  => 'Nominal harus berupa angka',
        ]);

        return $this->uploadBuktiBantuan($request, $peserta, [
            'jenis'          => 'pertama',
            'storage_folder' => 'pembayaran/pertama',
            'tahap_selesai'  => [6, 7],
            'with_kwitansi'  => false,
            'redirect_route' => 'admin.verifikasi.pelunasan',
            'pesan_sukses'   => "Bukti pelunasan untuk {$peserta->nama} berhasil diupload. Peserta resmi diterima.",
            'nominal'        => $request->nominal,
        ]);
    }

    /**
     * Helper untuk upload bukti pembayaran oleh Tim SPMB.
     * Digunakan oleh uploadBuktiFormulir dan uploadBuktiPelunasan.
     *
     * @param array{jenis: string, storage_folder: string, tahap_selesai: int[], with_kwitansi: bool, redirect_route: string, pesan_sukses: string, nominal?: numeric} $config
     */
    private function uploadBuktiBantuan(Request $request, Peserta $peserta, array $config): RedirectResponse
    {
        $admin = auth('pengguna')->user();
        $path  = $request->file('bukti')->store($config['storage_folder'], 'public');

        $nomorKwitansi = null;
        if ($config['with_kwitansi']) {
            $nomorKwitansi = app(\App\Services\KwitansiService::class)->generateNomorKwitansi();
        }

        Pembayaran::create(array_filter([
            'peserta_id'        => $peserta->id,
            'jenis'             => $config['jenis'],
            'bukti_file'        => $path,
            'nominal'           => $config['nominal'] ?? null,
            'status'            => StatusPembayaran::TERVERIFIKASI->value,
            'nomor_kwitansi'    => $nomorKwitansi,
            'diverifikasi_oleh' => $admin->id,
            'diverifikasi_pada' => now(),
            'catatan'           => 'Diupload oleh Tim SPMB: ' . $admin->nama,
        ], fn($v) => !is_null($v)));

        $spmbService = app(\App\Services\SpmbService::class);
        foreach ($config['tahap_selesai'] as $tahap) {
            $spmbService->selesaikanTahapan($peserta, $tahap, $admin->id);
        }

        $pesan = $config['pesan_sukses'];
        if ($nomorKwitansi) {
            $pesan .= " No. Kwitansi: {$nomorKwitansi}";
        }

        return redirect()->route($config['redirect_route'])->with('success', $pesan);
    }

    /**
     * Detail pelunasan
     */
    public function detailPelunasan(Pembayaran $pembayaran): View
    {
        $pembayaran->load('peserta');
        return view('admin.verifikasi.detail-pelunasan', compact('pembayaran'));
    }

    /**
     * Verifikasi pelunasan
     */
    public function verifikasiPelunasan(Pembayaran $pembayaran): RedirectResponse
    {
        $this->verifikasiService->verifikasiPelunasan($pembayaran, auth('pengguna')->user());
        return redirect()->route('admin.verifikasi.pelunasan')
            ->with('success', 'Pelunasan berhasil diverifikasi. Peserta resmi diterima.');
    }

    /**
     * Tolak pelunasan
     */
    public function tolakPelunasan(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $request->validate(['alasan' => 'required|string|max:500']);
        $this->verifikasiService->tolakPelunasan($pembayaran, $request->alasan, auth('pengguna')->user());
        return redirect()->route('admin.verifikasi.pelunasan')
            ->with('success', 'Pelunasan ditolak');
    }

    /**
     * Daftar hasil tes yang perlu verifikasi (peserta tidak lulus)
     */
    public function hasilTes(): View
    {
        $jumlahLulusOtomatis = $this->prosesSesiTesLulusOtomatis();

        $sesiMenunggu = SesiTes::with(['peserta', 'tes'])
            ->whereIn('status', ['selesai', 'timeout'])
            ->where(function ($q) {
                $q->where('status_verifikasi_tes', 'menunggu')
                  ->orWhereNull('status_verifikasi_tes');
            })
            ->where(function ($q) {
                $q->whereNull('permohonan_ulang_status')
                  ->orWhere('permohonan_ulang_status', '!=', SesiTes::PERMOHONAN_ULANG_PENDING);
            })
            ->whereHas('peserta')
            ->whereHas('tes', function ($q) {
                $q->whereColumn('sesi_tes.nilai', '<', 'tes.nilai_lulus');
            })
            ->latest()
            ->paginate(15);

        $permohonanTimeout = SesiTes::with(['peserta', 'tes'])
            ->where('status', 'timeout')
            ->where('permohonan_ulang_status', SesiTes::PERMOHONAN_ULANG_PENDING)
            ->whereHas('peserta')
            ->whereHas('tes')
            ->latest('permohonan_ulang_pada')
            ->paginate(10, ['*'], 'permohonan_page');

        return view('admin.verifikasi.hasil-tes', compact('sesiMenunggu', 'jumlahLulusOtomatis', 'permohonanTimeout'));
    }

    /**
     * Loloskan peserta yang tidak lulus tes
     * Peserta harus menyelesaikan semua tes sebelum lanjut ke tahap berikutnya
     */
    public function loloskanHasilTes(Request $request, \App\Models\SesiTes $sesi): RedirectResponse
    {
        $admin = auth('pengguna')->user();
        $sesi->loadMissing(['peserta', 'tes']);

        if (!$sesi->peserta || !$sesi->tes) {
            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('error', 'Data sesi tes tidak lengkap atau peserta sudah tidak ditemukan.');
        }

        $sesi->update([
            'status_verifikasi_tes' => 'diloloskan',
            'catatan_verifikasi' => $request->catatan ?? 'Diloloskan oleh admin',
            'diverifikasi_oleh' => $admin->id,
            'diverifikasi_pada' => now(),
        ]);

        $pesan = "Tes '{$sesi->tes->nama}' untuk {$sesi->peserta->nama} berhasil diloloskan.";

        if ($this->cekDanSelesaikanTahapTes($sesi->peserta, $admin)) {
            $pesan .= " Semua tes selesai, peserta lanjut ke tahap berikutnya.";
        } else {
            $pesan .= " Peserta masih perlu menyelesaikan tes lainnya.";
        }

        return redirect()->route('admin.verifikasi.hasil-tes')
            ->with('success', $pesan);
    }

    /**
     * Tolak peserta yang tidak lulus tes - peserta bisa mengulang tes
     */
    public function tolakHasilTes(Request $request, \App\Models\SesiTes $sesi): RedirectResponse
    {
        $request->validate(['alasan' => 'required|string|max:500']);

        $admin = auth('pengguna')->user();
        $pesertaNama = $sesi->peserta->nama;
        $tesNama = $sesi->tes->nama;

        // Hapus sesi tes sehingga peserta bisa mengulang
        $sesi->jawabanPeserta()->delete();
        $sesi->delete();

        return redirect()->route('admin.verifikasi.hasil-tes')
            ->with('success', "Sesi tes '{$tesNama}' untuk {$pesertaNama} dihapus. Peserta dapat mengulang tes.");
    }

    public function setujuiPerpanjanganTimeout(Request $request, SesiTes $sesi): RedirectResponse
    {
        $maxMenit = max((int) ($sesi->tes?->durasi_menit ?? 60), 1);

        $request->validate([
            'menit' => "required|integer|min:1|max:{$maxMenit}",
            'catatan' => 'nullable|string|max:500',
        ]);

        if (!$sesi->permohonanUlangPending()) {
            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('error', 'Permohonan ini sudah diproses atau belum diajukan.');
        }

        try {
            app(MonitoringUjianService::class)->setujuiPerpanjanganTimeout(
                $sesi,
                (int) $request->menit,
                auth('pengguna')->id(),
                $request->catatan
            );

            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('success', "Perpanjangan {$request->menit} menit untuk {$sesi->peserta?->nama} disetujui.");
        } catch (\Exception $e) {
            return redirect()->route('admin.verifikasi.hasil-tes')->with('error', $e->getMessage());
        }
    }

    public function setujuiUlangTimeout(Request $request, SesiTes $sesi): RedirectResponse
    {
        $request->validate([
            'catatan' => 'nullable|string|max:500',
        ]);

        if (!$sesi->permohonanUlangPending()) {
            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('error', 'Permohonan ini sudah diproses atau belum diajukan.');
        }

        try {
            app(MonitoringUjianService::class)->setujuiUlangDariAwalTimeout(
                $sesi,
                auth('pengguna')->id(),
                $request->catatan
            );

            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('success', "Permohonan ulang dari 0 untuk {$sesi->peserta?->nama} disetujui.");
        } catch (\Exception $e) {
            return redirect()->route('admin.verifikasi.hasil-tes')->with('error', $e->getMessage());
        }
    }

    public function tolakPermohonanTimeout(Request $request, SesiTes $sesi): RedirectResponse
    {
        $request->validate([
            'catatan' => 'required|string|max:500',
        ]);

        try {
            app(MonitoringUjianService::class)->tolakPermohonanTimeout(
                $sesi,
                auth('pengguna')->id(),
                $request->catatan
            );

            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('success', "Permohonan timeout {$sesi->peserta?->nama} ditolak.");
        } catch (\Exception $e) {
            return redirect()->route('admin.verifikasi.hasil-tes')->with('error', $e->getMessage());
        }
    }

    /**
     * Loloskan batch (terpilih) peserta yang tidak lulus tes
     */
    public function loloskanBatch(Request $request): RedirectResponse
    {
        $request->validate(['sesi_ids' => 'required|string']);

        $admin = auth('pengguna')->user();
        $sesiIds = array_filter(explode(',', $request->sesi_ids));

        if (empty($sesiIds)) {
            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('error', 'Tidak ada peserta yang dipilih.');
        }

        $count = 0;
        foreach ($sesiIds as $sesiId) {
            $sesi = \App\Models\SesiTes::with('peserta')->find($sesiId);
            if (!$sesi || !$sesi->peserta) {
                continue;
            }

            $sesi->update([
                'status_verifikasi_tes' => 'diloloskan',
                'catatan_verifikasi' => $request->catatan ?? 'Diloloskan batch oleh admin',
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
            ]);

            $this->cekDanSelesaikanTahapTes($sesi->peserta, $admin);
            $count++;
        }

        return redirect()->route('admin.verifikasi.hasil-tes')
            ->with('success', "{$count} peserta berhasil diloloskan.");
    }

    /**
     * Loloskan semua peserta yang menunggu verifikasi
     */
    public function loloskanSemua(Request $request): RedirectResponse
    {
        $admin = auth('pengguna')->user();

        $sesiMenunggu = SesiTes::with('peserta')
            ->whereIn('status', ['selesai', 'timeout'])
            ->where(function ($q) {
                $q->where('status_verifikasi_tes', 'menunggu')
                  ->orWhereNull('status_verifikasi_tes');
            })
            ->whereHas('peserta')
            ->whereHas('tes', function ($q) {
                $q->whereColumn('sesi_tes.nilai', '<', 'tes.nilai_lulus');
            })
            ->get();

        if ($sesiMenunggu->isEmpty()) {
            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('error', 'Tidak ada peserta yang menunggu verifikasi.');
        }

        $count = 0;
        foreach ($sesiMenunggu as $sesi) {
            if (!$sesi->peserta) {
                continue;
            }

            $sesi->update([
                'status_verifikasi_tes' => 'diloloskan',
                'catatan_verifikasi' => $request->catatan ?? 'Diloloskan semua oleh admin',
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
            ]);

            $this->cekDanSelesaikanTahapTes($sesi->peserta, $admin);
            $count++;
        }

        return redirect()->route('admin.verifikasi.hasil-tes')
            ->with('success', "{$count} peserta berhasil diloloskan.");
    }

    /**
     * Ulangi batch (terpilih) tes peserta
     */
    public function ulangiBatch(Request $request): RedirectResponse
    {
        $request->validate([
            'sesi_ids' => 'required|string',
            'alasan' => 'required|string|max:500',
        ]);

        $sesiIds = array_filter(explode(',', $request->sesi_ids));

        if (empty($sesiIds)) {
            return redirect()->route('admin.verifikasi.hasil-tes')
                ->with('error', 'Tidak ada peserta yang dipilih.');
        }

        $count = 0;

        foreach ($sesiIds as $sesiId) {
            $sesi = \App\Models\SesiTes::find($sesiId);
            if (!$sesi) continue;

            // Hapus sesi tes sehingga peserta bisa mengulang
            $sesi->jawabanPeserta()->delete();
            $sesi->delete();

            $count++;
        }

        return redirect()->route('admin.verifikasi.hasil-tes')
            ->with('success', "{$count} sesi tes berhasil dihapus. Peserta dapat mengulang tes.");
    }

    /**
     * Halaman verifikasi kelulusan (Tahap 7)
     */
    public function kelulusan(): View
    {
        // Ambil peserta yang sudah di tahap 7 (menunggu keputusan, lulus, atau tidak lulus)
        $peserta = Peserta::with(['tahapanSpmb', 'formulirSpmb'])
            ->whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 7))
            ->latest()
            ->paginate(20);

        // Statistik
        $statistik = [
            'menunggu' => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 7)
                ->where(function ($q2) {
                    $q2->whereNull('status_kelulusan')
                       ->orWhere('status_kelulusan', '')
                       ->orWhere('status_kelulusan', 'menunggu');
                })
            )->count(),
            'lulus' => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('status_kelulusan', 'lulus'))->count(),
            'tidak_lulus' => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('status_kelulusan', 'tidak_lulus'))->count(),
        ];

        // Ambil pengaturan kelulusan
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $pengaturanKelulusan = $pengaturanService->ambilPengaturanKelulusan();
        $skGelombang = $pengaturanService->ambilSuratKelulusanGelombang();
        $skGelombangById = collect($skGelombang)->keyBy('id')->all();

        return view('admin.verifikasi.kelulusan', compact('peserta', 'statistik', 'pengaturanKelulusan', 'skGelombang', 'skGelombangById'));
    }

    /**
     * Luluskan peserta
     */
    public function luluskanPeserta(Request $request, Peserta $peserta): RedirectResponse
    {
        $skGelombangId = $this->validasiSkGelombangKelulusan($request);
        $this->terapkanKelulusan($peserta, 'lulus', $skGelombangId);

        return redirect()->route('admin.verifikasi.kelulusan')
            ->with('success', "Peserta {$peserta->nama} berhasil diluluskan.");
    }

    /**
     * Tidak luluskan peserta
     */
    public function tidakLuluskanPeserta(Request $request, Peserta $peserta): RedirectResponse
    {
        $this->terapkanKelulusan($peserta, 'tidak_lulus');

        return redirect()->route('admin.verifikasi.kelulusan')
            ->with('success', "Peserta {$peserta->nama} ditandai tidak lulus.");
    }

    /**
     * Luluskan semua peserta yang menunggu
     */
    public function luluskanSemua(Request $request): RedirectResponse
    {
        $skGelombangId = $this->validasiSkGelombangKelulusan($request);
        $pesertaMenunggu = Peserta::whereHas('tahapanSpmb', function ($q) {
            $q->where('tahap_saat_ini', 7)
                ->where(function ($q2) {
                    $q2->whereNull('status_kelulusan')
                        ->orWhere('status_kelulusan', '')
                        ->orWhere('status_kelulusan', 'menunggu');
                });
        })->get();

        $count = $pesertaMenunggu->filter(fn($p) => $this->terapkanKelulusan($p, 'lulus', $skGelombangId))->count();

        return redirect()->route('admin.verifikasi.kelulusan')
            ->with('success', "{$count} peserta berhasil diluluskan.");
    }

    /**
     * Luluskan batch peserta
     */
    public function luluskanBatchKelulusan(Request $request): RedirectResponse
    {
        $request->validate(['peserta_ids' => 'required|string']);
        $skGelombangId = $this->validasiSkGelombangKelulusan($request);

        $pesertaIds = array_filter(explode(',', $request->peserta_ids));

        if (empty($pesertaIds)) {
            return redirect()->route('admin.verifikasi.kelulusan')
                ->with('error', 'Tidak ada peserta yang dipilih.');
        }

        $count = 0;
        foreach ($pesertaIds as $pesertaId) {
            $peserta = Peserta::find($pesertaId);
            if ($peserta && $this->terapkanKelulusan($peserta, 'lulus', $skGelombangId)) $count++;
        }

        return redirect()->route('admin.verifikasi.kelulusan')
            ->with('success', "{$count} peserta berhasil diluluskan.");
    }

    /**
     * Tidak luluskan batch peserta
     */
    public function tidakLulusBatch(Request $request): RedirectResponse
    {
        $request->validate(['peserta_ids' => 'required|string']);

        $pesertaIds = array_filter(explode(',', $request->peserta_ids));

        if (empty($pesertaIds)) {
            return redirect()->route('admin.verifikasi.kelulusan')
                ->with('error', 'Tidak ada peserta yang dipilih.');
        }

        $count = 0;
        foreach ($pesertaIds as $pesertaId) {
            $peserta = Peserta::find($pesertaId);
            if ($peserta && $this->terapkanKelulusan($peserta, 'tidak_lulus')) $count++;
        }

        return redirect()->route('admin.verifikasi.kelulusan')
            ->with('success', "{$count} peserta ditandai tidak lulus.");
    }

    /**
     * Halaman history/detail lengkap peserta
     */
    public function historyPeserta(Peserta $peserta): View
    {
        $peserta->load([
            'tahapanSpmb',
            'formulirSpmb',
            'pembayaran',
            'sesiTes.tes',
            'wawancara',
        ]);

        return view('admin.verifikasi.history-peserta', compact('peserta'));
    }

    /**
     * Export data peserta lengkap ke Excel/CSV
     */
    public function eksporPeserta(Request $request)
    {
        $filter = $request->only(['tahap', 'cari']);

        $peserta = Peserta::with(['tahapanSpmb', 'formulirSpmb', 'pembayaran', 'sesiTes.tes', 'wawancara'])
            ->when(!empty($filter['tahap']), function ($q) use ($filter) {
                $q->whereHas('tahapanSpmb', fn($t) => $t->where('tahap_saat_ini', $filter['tahap']));
            })
            ->when(!empty($filter['cari']), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('nama', 'like', "%{$filter['cari']}%")
                          ->orWhere('nomor_pendaftaran', 'like', "%{$filter['cari']}%");
                });
            })
            ->latest()
            ->get();

        $tahapLabel = !empty($filter['tahap']) ? '-tahap' . $filter['tahap'] : '';
        $filename = 'data-peserta-spmb' . $tahapLabel . '-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = [
            'No', 'No Pendaftaran', 'Nama', 'Email', 'Telepon', 'Tahap Saat Ini', 'Status Kelulusan',
            // Data Formulir
            'Nama Lengkap', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'NISN', 'Asal Sekolah',
            'Nama Ayah', 'Nama Ibu', 'Pekerjaan Ayah', 'Pekerjaan Ibu',
            'Telepon Ayah', 'Telepon Ibu', 'Alamat Kelurahan', 'Alamat Kecamatan', 'Alamat Kota', 'Alamat Provinsi',
            'Kelompok', 'Desa', 'Daerah',
            'Status Formulir', 'File KK', 'File Akta', 'File Ijazah', 'File BPJS', 'File KTP Ayah', 'File KTP Ibu',
            // Pembayaran
            'Status Bayar Formulir', 'Tgl Bayar Formulir', 'Status Pelunasan', 'Tgl Pelunasan', 'Nominal Pelunasan',
            // Tes
            'Nilai Tes', 'Status Tes', 'Tgl Tes',
            // Wawancara
            'Tgl Wawancara Ortu', 'Interviewer Ortu', 'Tgl Wawancara Siswa', 'Interviewer Siswa',
            'Hasil Wawancara', 'Catatan Wawancara',
            // Tanggal
            'Tanggal Daftar'
        ];

        $tahapanLabel = [
            1 => 'Daftar', 2 => 'Formulir', 3 => 'Bayar Formulir', 4 => 'Tes Online',
            5 => 'Wawancara', 6 => 'Pelunasan', 7 => 'Kelulusan'
        ];

        $callback = function() use ($peserta, $columns, $tahapanLabel) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns, ';');

            $no = 1;
            foreach ($peserta as $p) {
                $f = $p->formulirSpmb;
                $bayarFormulir = $p->pembayaran->where('jenis', 'formulir')->first();
                $pelunasan = $p->pembayaran->where('jenis', 'pertama')->first();
                $sesiTes = $p->sesiTes->whereIn('status', ['selesai', 'timeout'])->first();
                $w = $p->wawancara;
                $tahap = $p->tahapanSpmb?->tahap_saat_ini ?? 1;

                fputcsv($file, [
                    $no++,
                    $p->nomor_pendaftaran ?? '-',
                    $p->nama ?? '-',
                    $p->email ?? '-',
                    $p->telepon ?? '-',
                    $tahapanLabel[$tahap] ?? $tahap,
                    ucfirst($p->tahapanSpmb?->status_kelulusan ?? 'menunggu'),
                    // Data Formulir
                    $f?->nama_lengkap ?? '-',
                    $f?->tempat_lahir ?? '-',
                    $f?->tanggal_lahir?->format('d/m/Y') ?? '-',
                    $f?->jenis_kelamin === 'L' ? 'Laki-laki' : ($f?->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                    $f?->nisn ?? '-',
                    $f?->asal_sekolah ?? '-',
                    $f?->nama_ayah ?? '-',
                    $f?->nama_ibu ?? '-',
                    $f?->pekerjaan_ayah ?? '-',
                    $f?->pekerjaan_ibu ?? '-',
                    $f?->telepon_ayah ?? '-',
                    $f?->telepon_ibu ?? '-',
                    $f?->alamat_kelurahan ?? '-',
                    $f?->alamat_kecamatan ?? '-',
                    $f?->alamat_kota ?? '-',
                    $f?->alamat_provinsi ?? '-',
                    $f?->kelompok ?? '-',
                    $f?->desa ?? '-',
                    $f?->daerah ?? '-',
                    ucfirst($f?->status_verifikasi ?? '-'),
                    $f?->file_kk ? 'Ada' : 'Tidak Ada',
                    $f?->file_akta ? 'Ada' : 'Tidak Ada',
                    $f?->file_ijazah ? 'Ada' : 'Tidak Ada',
                    $f?->file_bpjs ? 'Ada' : 'Tidak Ada',
                    $f?->file_ktp_ayah ? 'Ada' : 'Tidak Ada',
                    $f?->file_ktp_ibu ? 'Ada' : 'Tidak Ada',
                    // Pembayaran
                    ucfirst($bayarFormulir?->status ?? '-'),
                    $bayarFormulir?->diverifikasi_pada?->format('d/m/Y') ?? '-',
                    ucfirst($pelunasan?->status ?? '-'),
                    $pelunasan?->diverifikasi_pada?->format('d/m/Y') ?? '-',
                    $pelunasan?->nominal ? 'Rp ' . number_format($pelunasan->nominal, 0, ',', '.') : '-',
                    // Tes
                    $sesiTes ? number_format($sesiTes->nilai, 1) : '-',
                    ucfirst($sesiTes?->status ?? '-'),
                    $sesiTes?->created_at?->format('d/m/Y H:i') ?? '-',
                    // Wawancara
                    $w?->tanggal_wawancara_ortu?->format('d/m/Y') ?? $w?->tanggal_wawancara?->format('d/m/Y') ?? '-',
                    $w?->interviewer_ortu ?? $w?->nama_interviewer ?? '-',
                    $w?->tanggal_wawancara_siswa?->format('d/m/Y') ?? '-',
                    $w?->interviewer_siswa ?? '-',
                    $w?->hasil_wawancara === 'lulus' ? 'LULUS' : ($w?->hasil_wawancara === 'tidak_lulus' ? 'TIDAK LULUS' : ($w?->hasil_wawancara === 'menunggu' ? 'MENUNGGU' : '-')),
                    $w?->catatan_interviewer ?? '-',
                    // Tanggal
                    $p->created_at?->format('d/m/Y H:i') ?? '-',
                ], ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Halaman verifikasi wawancara (Tahap 5)
     */
    public function wawancara(): View
    {
        // Ambil peserta yang sudah di tahap 5 (sudah lulus tes)
        $peserta = Peserta::with(['tahapanSpmb', 'formulirSpmb', 'wawancara'])
            ->whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 5))
            ->latest()
            ->paginate(20);

        // Statistik
        $statistik = [
            'menunggu' => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 5))
                ->whereDoesntHave('wawancara')
                ->count(),
            'sudah_wawancara' => \App\Models\Wawancara::where('hasil_wawancara', '!=', 'menunggu')->count(),
            'lulus' => \App\Models\Wawancara::where('hasil_wawancara', 'lulus')->count(),
            'tidak_lulus' => \App\Models\Wawancara::where('hasil_wawancara', 'tidak_lulus')->count(),
        ];

        return view('admin.verifikasi.wawancara', compact('peserta', 'statistik'));
    }

    /**
     * Form input wawancara peserta
     */
    public function formWawancara(Peserta $peserta): View
    {
        $peserta->load(['formulirSpmb', 'wawancara']);

        $pertanyaanOrtu = \App\Models\Wawancara::pertanyaanOrtu();
        $pertanyaanSiswa = \App\Models\Wawancara::pertanyaanSiswa();
        $spSiswaPoin = \App\Models\Wawancara::suratPernyataanSiswaPoin();
        $spOrtuPoin = \App\Models\Wawancara::suratPernyataanOrtuPoin();
        $daftarBerkas = \App\Models\Wawancara::daftarBerkas();

        return view('admin.verifikasi.form-wawancara', compact(
            'peserta',
            'pertanyaanOrtu',
            'pertanyaanSiswa',
            'spSiswaPoin',
            'spOrtuPoin',
            'daftarBerkas'
        ));
    }

    /**
     * Cetak surat pernyataan peserta (printable)
     */
    public function cetakSuratPernyataan(Peserta $peserta): View
    {
        $peserta->load(['formulirSpmb', 'wawancara']);
        $spSiswaPoin = \App\Models\Wawancara::suratPernyataanSiswaPoin();
        $spOrtuPoin = \App\Models\Wawancara::suratPernyataanOrtuPoin();
        return view('admin.verifikasi.cetak-surat-pernyataan', compact('peserta', 'spSiswaPoin', 'spOrtuPoin'));
    }

    /**
     * Download surat pernyataan peserta sebagai PDF
     */
    public function downloadSuratPernyataanPdf(Peserta $peserta)
    {
        $peserta->load(['formulirSpmb', 'wawancara']);
        $spSiswaPoin = \App\Models\Wawancara::suratPernyataanSiswaPoin();
        $spOrtuPoin = \App\Models\Wawancara::suratPernyataanOrtuPoin();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.verifikasi.cetak-surat-pernyataan', [
            'peserta' => $peserta,
            'isPdf' => true,
            'spSiswaPoin' => $spSiswaPoin,
            'spOrtuPoin' => $spOrtuPoin,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $namaFile = 'Surat-Pernyataan-' . str_replace(' ', '-', $peserta->nama) . '.pdf';

        return $pdf->download($namaFile);
    }

    /**
     * Simpan hasil wawancara
     */
    public function simpanWawancara(Request $request, Peserta $peserta): RedirectResponse
    {
        $request->validate([
            'kelompok' => 'nullable|string|max:100',
            // Wawancara Ortu
            'tanggal_wawancara_ortu' => 'nullable|date',
            'interviewer_ortu' => 'nullable|string|max:255',
            'jawaban_ortu' => 'nullable|array',
            'file_pertanyaan_ortu_manual' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'catatan_ortu' => 'nullable|string|max:2000',
            // Wawancara Siswa
            'tanggal_wawancara_siswa' => 'nullable|date',
            'interviewer_siswa' => 'nullable|string|max:255',
            'jawaban_siswa' => 'nullable|array',
            'file_pertanyaan_siswa_manual' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'catatan_siswa' => 'nullable|string|max:2000',
            // Surat Pernyataan manual
            'surat_pernyataan_siswa' => 'nullable|array',
            'surat_pernyataan_ortu' => 'nullable|array',
            'file_surat_pernyataan_manual' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'tanda_tangan_peserta_upload' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'tanda_tangan_ortu_upload' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            // Verifikasi
            'verifikasi_berkas' => 'nullable|array',
            'catatan_interviewer' => 'nullable|string|max:2000',
            'hasil_wawancara' => 'required|in:lulus,tidak_lulus,menunggu',
        ]);

        $admin = auth('pengguna')->user();
        $wawancaraSaatIni = \App\Models\Wawancara::firstOrNew(['peserta_id' => $peserta->id]);
        $suratPernyataanSiswa = $this->siapkanDataSuratPernyataan(
            $request->input('surat_pernyataan_siswa', []),
            $wawancaraSaatIni->surat_pernyataan_siswa ?? []
        );
        $suratPernyataanOrtu = $this->siapkanDataSuratPernyataan(
            $request->input('surat_pernyataan_ortu', []),
            $wawancaraSaatIni->surat_pernyataan_ortu ?? []
        );
        $jawabanOrtu = $this->siapkanJawabanWawancaraManual(
            $request->input('jawaban_ortu', []),
            $wawancaraSaatIni->jawaban_ortu ?? [],
            $request,
            'file_pertanyaan_ortu_manual',
            'wawancara/manual/pertanyaan-ortu'
        );
        $jawabanSiswa = $this->siapkanJawabanWawancaraManual(
            $request->input('jawaban_siswa', []),
            $wawancaraSaatIni->jawaban_siswa ?? [],
            $request,
            'file_pertanyaan_siswa_manual',
            'wawancara/manual/pertanyaan-siswa'
        );

        if ($request->hasFile('file_surat_pernyataan_manual')) {
            if (!$suratPernyataanSiswa) {
                $suratPernyataanSiswa = [
                    'setuju' => '1',
                    'tanggal_surat' => ($wawancaraSaatIni->surat_pernyataan_siswa['tanggal_surat'] ?? now()->toDateString()),
                ];
            }

            $suratPernyataanSiswa['_file_manual'] = $request->file('file_surat_pernyataan_manual')
                ->store('wawancara/manual/surat-pernyataan', 'public');
        } elseif (!empty($wawancaraSaatIni->surat_pernyataan_siswa['_file_manual'])) {
            $suratPernyataanSiswa['_file_manual'] = $wawancaraSaatIni->surat_pernyataan_siswa['_file_manual'];
        }

        $data = [
            'kelompok' => $request->kelompok,
            // Wawancara Ortu
            'tanggal_wawancara_ortu' => $request->tanggal_wawancara_ortu,
            'interviewer_ortu' => $request->interviewer_ortu,
            'jawaban_ortu' => $jawabanOrtu,
            'catatan_ortu' => $request->catatan_ortu,
            // Wawancara Siswa
            'tanggal_wawancara_siswa' => $request->tanggal_wawancara_siswa,
            'interviewer_siswa' => $request->interviewer_siswa,
            'jawaban_siswa' => $jawabanSiswa,
            'catatan_siswa' => $request->catatan_siswa,
            // Surat Pernyataan
            'surat_pernyataan_siswa' => $suratPernyataanSiswa,
            'surat_pernyataan_ortu' => $suratPernyataanOrtu,
            // Verifikasi
            'verifikasi_berkas' => $request->verifikasi_berkas ?? [],
            'catatan_interviewer' => $request->catatan_interviewer,
            'hasil_wawancara' => $request->hasil_wawancara,
            'diverifikasi_oleh' => $admin->id,
            'diverifikasi_pada' => now(),
            // Backward compatibility - set tanggal_wawancara dari salah satu
            'tanggal_wawancara' => $request->tanggal_wawancara_ortu ?? $request->tanggal_wawancara_siswa,
            'nama_interviewer' => $request->interviewer_ortu ?? $request->interviewer_siswa,
        ];

        if (!$wawancaraSaatIni->diisi_peserta_pada && ($suratPernyataanSiswa || $suratPernyataanOrtu || $jawabanOrtu || $jawabanSiswa)) {
            $data['diisi_peserta_pada'] = now();
        }

        if ($request->hasFile('tanda_tangan_peserta_upload')) {
            $data['tanda_tangan_peserta'] = $this->ubahFileTandaTanganMenjadiDataUrl($request->file('tanda_tangan_peserta_upload'));
        }

        if ($request->hasFile('tanda_tangan_ortu_upload')) {
            $data['tanda_tangan_ortu'] = $this->ubahFileTandaTanganMenjadiDataUrl($request->file('tanda_tangan_ortu_upload'));
        }

        $wawancara = \App\Models\Wawancara::updateOrCreate(
            ['peserta_id' => $peserta->id],
            $data
        );

        // Jika lulus wawancara, selesaikan tahap 5
        if ($request->hasil_wawancara === 'lulus') {
            app(\App\Services\SpmbService::class)->selesaikanTahapan($peserta, 5, $admin->id);
        }

        return redirect()->route('admin.verifikasi.wawancara')
            ->with('success', "Hasil wawancara untuk {$peserta->nama} berhasil disimpan.");
    }

    /**
     * Cetak form wawancara (kosong untuk diisi manual)
     */
    public function cetakFormWawancara(Peserta $peserta): View
    {
        $peserta->load(['formulirSpmb']);

        $pertanyaanOrtu = \App\Models\Wawancara::pertanyaanOrtu();
        $pertanyaanSiswa = \App\Models\Wawancara::pertanyaanSiswa();

        return view('admin.verifikasi.cetak-form-wawancara', compact(
            'peserta',
            'pertanyaanOrtu',
            'pertanyaanSiswa'
        ));
    }

    /**
     * Loloskan wawancara peserta
     */
    public function loloskanWawancara(Peserta $peserta): RedirectResponse
    {
        $admin = auth('pengguna')->user();

        $wawancara = \App\Models\Wawancara::firstOrCreate(
            ['peserta_id' => $peserta->id],
            ['tanggal_wawancara' => now()]
        );

        $wawancara->update([
            'hasil_wawancara' => 'lulus',
            'diverifikasi_oleh' => $admin->id,
            'diverifikasi_pada' => now(),
        ]);

        // Selesaikan tahap 5
        app(\App\Services\SpmbService::class)->selesaikanTahapan($peserta, 5, $admin->id);

        return redirect()->route('admin.verifikasi.wawancara')
            ->with('success', "Peserta {$peserta->nama} lulus wawancara dan lanjut ke tahap berikutnya.");
    }

    /**
     * Tidak loloskan wawancara peserta
     */
    public function tidakLolosWawancara(Request $request, Peserta $peserta): RedirectResponse
    {
        $admin = auth('pengguna')->user();

        $wawancara = $peserta->wawancara;
        if ($wawancara) {
            $wawancara->update([
                'hasil_wawancara' => 'tidak_lulus',
                'catatan_interviewer' => $request->catatan ?? $wawancara->catatan_interviewer,
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
            ]);
        }

        return redirect()->route('admin.verifikasi.wawancara')
            ->with('success', "Peserta {$peserta->nama} tidak lulus wawancara.");
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Cek apakah semua tes peserta sudah lulus/diloloskan,
     * jika ya selesaikan tahap 4 (lanjut ke wawancara).
     * Mengembalikan true jika semua tes selesai.
     */
    private function cekDanSelesaikanTahapTes(Peserta $peserta, $admin): bool
    {
        $tesService = app(\App\Services\TesService::class);
        $tesTersedia = $tesService->ambilTesTersediaUntukPeserta($peserta);

        if ($tesTersedia->isEmpty()) {
            return false;
        }

        foreach ($tesTersedia as $tes) {
            $sesiTes = \App\Models\SesiTes::where('peserta_id', $peserta->id)
                ->where('tes_id', $tes->id)
                ->whereIn('status', ['selesai', 'timeout'])
                ->first();

            if (!$sesiTes) {
                return false;
            }

            $lulus = $sesiTes->nilai >= $tes->nilai_lulus;
            $diloloskan = $sesiTes->status_verifikasi_tes === 'diloloskan';

            if (!$lulus && !$diloloskan) {
                return false;
            }
        }

        // Semua tes selesai, lanjut ke tahap 5
        app(\App\Services\SpmbService::class)->selesaikanTahapan($peserta, 4, $admin->id);

        return true;
    }

    private function prosesSesiTesLulusOtomatis(): int
    {
        $admin = auth('pengguna')->user();

        $sesiLulus = SesiTes::with(['peserta', 'tes'])
            ->whereIn('status', ['selesai', 'timeout'])
            ->where(function ($q) {
                $q->where('status_verifikasi_tes', 'menunggu')
                    ->orWhereNull('status_verifikasi_tes');
            })
            ->whereHas('peserta')
            ->whereHas('tes', function ($q) {
                $q->whereColumn('sesi_tes.nilai', '>=', 'tes.nilai_lulus');
            })
            ->get();

        foreach ($sesiLulus as $sesi) {
            $sesi->update([
                'status_verifikasi_tes' => 'lulus_otomatis',
                'catatan_verifikasi' => 'Lulus otomatis karena nilai memenuhi batas minimal.',
                'diverifikasi_oleh' => $admin?->id,
                'diverifikasi_pada' => now(),
            ]);

            if ($sesi->peserta) {
                $this->cekDanSelesaikanTahapTes($sesi->peserta, $admin);
            }
        }

        return $sesiLulus->count();
    }

    /**
     * Bersihkan data surat pernyataan dan beri tanggal pembuatan stabil.
     */
    private function siapkanDataSuratPernyataan(array $input, ?array $existing = []): array
    {
        $data = [];

        foreach ($input as $key => $value) {
            $data[$key] = is_string($value) ? trim($value) : $value;
        }

        $adaIsi = collect($data)
            ->except(['setuju', 'tanggal_surat'])
            ->contains(fn($value) => filled($value));

        if (!$adaIsi) {
            return [];
        }

        $data['setuju'] = '1';
        $data['tanggal_surat'] = $data['tanggal_surat']
            ?? ($existing['tanggal_surat'] ?? now()->toDateString());

        return $data;
    }

    /**
     * Gabungkan jawaban manual dengan file scan/foto pendukung bila admin mengunggahnya.
     */
    private function siapkanJawabanWawancaraManual(array $input, ?array $existing, Request $request, string $fileInput, string $folder): array
    {
        $data = [];

        foreach ($input as $key => $value) {
            $data[$key] = is_string($value) ? trim($value) : $value;
        }

        if ($request->hasFile($fileInput)) {
            $data['_file_manual'] = $request->file($fileInput)->store($folder, 'public');
        } elseif (!empty($existing['_file_manual'])) {
            $data['_file_manual'] = $existing['_file_manual'];
        }

        return $data;
    }

    /**
     * Tanda tangan disimpan sebagai data URL agar tetap kompatibel dengan cetakan lama.
     */
    private function ubahFileTandaTanganMenjadiDataUrl(\Illuminate\Http\UploadedFile $file): string
    {
        return 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
    }

    /**
     * Terapkan status kelulusan pada tahapan peserta.
     * Mengembalikan true jika berhasil, false jika tahapan tidak ditemukan.
     */
    private function terapkanKelulusan(Peserta $peserta, string $status, ?string $skGelombangId = null): bool
    {
        $tahapan = $peserta->tahapanSpmb;
        if (!$tahapan) {
            return false;
        }

        $data = [
            'status_kelulusan' => $status,
            'sk_gelombang_kelulusan' => $status === 'lulus' ? $skGelombangId : null,
        ];

        if ($status === 'lulus') {
            $data['tahap_saat_ini'] = 7;
            $data['tahap_7_selesai'] = true;
        }

        $tahapan->update($data);

        return true;
    }

    /**
     * Validasi pilihan SK gelombang untuk status lulus.
     */
    private function validasiSkGelombangKelulusan(Request $request): ?string
    {
        $skGelombang = app(\App\Services\PengaturanService::class)->ambilSuratKelulusanGelombang();

        if (empty($skGelombang)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sk_gelombang_kelulusan' => 'Tambahkan dan upload SK gelombang terlebih dahulu di Pengaturan SPMB > Kelulusan.',
            ]);
        }

        $request->validate([
            'sk_gelombang_kelulusan' => 'required|string',
        ], [
            'sk_gelombang_kelulusan.required' => 'Pilih SK gelombang terlebih dahulu.',
        ]);

        $selected = $request->input('sk_gelombang_kelulusan');
        if (!collect($skGelombang)->contains('id', $selected)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sk_gelombang_kelulusan' => 'SK gelombang yang dipilih tidak ditemukan.',
            ]);
        }

        return $selected;
    }
}
