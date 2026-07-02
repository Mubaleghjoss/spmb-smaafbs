<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Services\PesertaService;
use App\Services\GrupService;
use App\Services\ImporEksporPesertaService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller untuk manajemen peserta
 * Kebutuhan: 3.1, 3.5
 */
class PesertaController extends Controller
{
    public function __construct(
        private PesertaService $pesertaService,
        private GrupService $grupService,
        private ImporEksporPesertaService $imporEksporService
    ) {}

    /**
     * Tampilkan daftar peserta
     */
    public function index(Request $request): View
    {
        $filter = [
            'grup_id' => $request->get('grup_id'),
            'tahap' => $request->get('tahap'),
            'cari' => $request->get('cari'),
            'dengan_dihapus' => $request->boolean('dengan_dihapus'),
        ];

        $peserta = $this->pesertaService->ambilDenganFilter($filter, 15);
        $grup = $this->grupService->ambilSemua();
        $statistik = $this->pesertaService->ambilStatistik();

        return view('admin.peserta.index', compact('peserta', 'grup', 'statistik', 'filter'));
    }

    /**
     * Tampilkan form tambah peserta
     */
    public function create(): View
    {
        $grup = $this->grupService->ambilSemua();
        return view('admin.peserta.create', compact('grup'));
    }

    /**
     * Simpan peserta baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:peserta,email',
            'password' => 'nullable|string|min:6',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'asal_sekolah' => 'nullable|string|max:255',
            'grup_id' => 'nullable|exists:grup,id',
        ]);

        $this->pesertaService->buat($validated);

        return redirect()
            ->route('admin.peserta.index')
            ->with('success', 'Peserta berhasil ditambahkan.');
    }

    /**
     * Tampilkan detail peserta
     */
    public function show(Peserta $peserta): View
    {
        $peserta->load(['tahapanSpmb', 'grup', 'formulirSpmb', 'pembayaran', 'logTahapan']);
        return view('admin.peserta.show', compact('peserta'));
    }

    /**
     * Tampilkan form edit peserta
     */
    public function edit(Peserta $peserta): View
    {
        $peserta->load('grup');
        $grup = $this->grupService->ambilSemua();
        return view('admin.peserta.edit', compact('peserta', 'grup'));
    }

    /**
     * Perbarui peserta
     */
    public function update(Request $request, Peserta $peserta): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|unique:peserta,email,' . $peserta->id,
            'password' => 'nullable|string|min:6',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'asal_sekolah' => 'nullable|string|max:255',
            'grup_id' => 'nullable|exists:grup,id',
        ]);

        $this->pesertaService->perbarui($peserta, $validated);

        return redirect()
            ->route('admin.peserta.index')
            ->with('success', 'Peserta berhasil diperbarui.');
    }

    /**
     * Hapus peserta (soft delete)
     */
    public function destroy(Peserta $peserta): RedirectResponse
    {
        $this->pesertaService->hapus($peserta);

        return redirect()
            ->route('admin.peserta.index')
            ->with('success', 'Peserta berhasil dihapus.');
    }

    /**
     * Restore peserta yang dihapus
     */
    public function restore(int $id): RedirectResponse
    {
        $peserta = $this->pesertaService->restore($id);

        if ($peserta) {
            return redirect()
                ->route('admin.peserta.index')
                ->with('success', 'Peserta berhasil dipulihkan.');
        }

        return redirect()
            ->route('admin.peserta.index')
            ->with('error', 'Peserta tidak ditemukan.');
    }

    /**
     * Reset password peserta
     */
    public function resetPassword(Peserta $peserta): RedirectResponse
    {
        $passwordBaru = $this->pesertaService->resetPassword($peserta);

        return redirect()
            ->back()
            ->with('success', "Password berhasil direset. Password baru: {$passwordBaru}");
    }

    /**
     * Bulk assign peserta ke grup
     */
    public function bulkAssignGrup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'peserta_ids' => 'required|array',
            'peserta_ids.*' => 'exists:peserta,id',
            'grup_id' => 'required|exists:grup,id',
        ]);

        $count = $this->pesertaService->bulkAssignKeGrup(
            $validated['peserta_ids'],
            $validated['grup_id']
        );

        return redirect()
            ->back()
            ->with('success', "{$count} peserta berhasil ditambahkan ke grup.");
    }

    /**
     * Tampilkan halaman impor peserta
     */
    public function impor(): View
    {
        return view('admin.peserta.impor');
    }

    /**
     * Proses impor peserta dari Excel
     */
    public function prosesImpor(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $hasil = $this->imporEksporService->imporDariExcel(
            $request->file('file')->getRealPath()
        );

        $pesan = "Impor selesai: {$hasil['sukses']} berhasil, {$hasil['gagal']} gagal.";

        if (!empty($hasil['errors'])) {
            return redirect()
                ->back()
                ->with('success', $pesan)
                ->with('errors_impor', $hasil['errors']);
        }

        return redirect()
            ->route('admin.peserta.index')
            ->with('success', $pesan);
    }

    /**
     * Ekspor peserta ke Excel
     */
    public function ekspor(Request $request): BinaryFileResponse
    {
        $grupId = $request->get('grup_id');
        $path = $this->imporEksporService->eksporKeExcel($grupId);

        return response()->download($path)->deleteFileAfterSend();
    }

    /**
     * Download template impor Excel
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $path = $this->imporEksporService->generateTemplate();
        return response()->download($path)->deleteFileAfterSend();
    }

    /**
     * Cetak kartu peserta
     */
    public function cetakKartu(Peserta $peserta): View
    {
        return view('admin.peserta.kartu', compact('peserta'));
    }

    /**
     * Tampilkan history lengkap peserta dari semua tahap
     */
    public function history(Peserta $peserta): View
    {
        $peserta->load([
            'tahapanSpmb',
            'grup',
            'formulirSpmb',
            'pembayaran',
            'sesiTes.tes',
            'sesiTes.jawabanPeserta',
            'wawancara',
            'logTahapan.pengguna',
        ]);

        return view('admin.peserta.history', compact('peserta'));
    }

    /**
     * Update password peserta secara manual
     */
    public function updatePassword(Request $request, Peserta $peserta): RedirectResponse
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $this->pesertaService->updatePasswordManual($peserta, $validated['password']);

        return redirect()
            ->back()
            ->with('success', 'Password berhasil diperbarui.');
    }

    /**
     * Update tahap peserta (maju/mundur/loncat)
     */
    public function updateTahap(Request $request, Peserta $peserta): RedirectResponse
    {
        $validated = $request->validate([
            'tahap_baru' => 'required|integer|min:1|max:7',
        ]);

        $tahapBaru = (int) $validated['tahap_baru'];

        // Get or create tahapan record
        $tahapan = $peserta->tahapanSpmb;
        if (!$tahapan) {
            $tahapan = $peserta->tahapanSpmb()->create([
                'tahap_saat_ini' => 1,
            ]);
        }

        // Update tahap_saat_ini
        $tahapan->tahap_saat_ini = $tahapBaru;

        // Mark all stages below the new stage as completed
        for ($i = 1; $i <= 7; $i++) {
            $kolom = "tahap_{$i}_selesai";
            $tahapan->$kolom = ($i < $tahapBaru);
        }

        $tahapan->save();

        $tahapLabels = [
            1 => 'Pendaftaran', 2 => 'Bayar Formulir', 3 => 'Isi Formulir',
            4 => 'Tes Online', 5 => 'Wawancara', 6 => 'Pelunasan', 7 => 'Kelulusan'
        ];

        return redirect()
            ->back()
            ->with('sukses', "Tahap peserta berhasil diubah ke Tahap {$tahapBaru}: {$tahapLabels[$tahapBaru]}.");
    }

    /**
     * Download daftar akun peserta dengan password (CSV)
     */
    public function downloadAkun(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $peserta = Peserta::with('tahapanSpmb')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'akun_peserta_spmb_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($peserta) {
            $handle = fopen('php://output', 'w');
            
            // BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($handle, ['No Pendaftaran', 'Nama', 'No HP/WA', 'Email', 'Password', 'Tahap', 'Tanggal Daftar'], ';');
            
            // Data
            foreach ($peserta as $p) {
                fputcsv($handle, [
                    $p->nomor_pendaftaran,
                    $p->nama,
                    $p->telepon,
                    $p->email ?? '-',
                    $p->password_temp ?? '(sudah diubah)',
                    'Tahap ' . $p->tahap_saat_ini,
                    $p->created_at->format('d/m/Y H:i'),
                ], ';');
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Download semua biodata peserta lengkap (CSV)
     */
    public function downloadBiodata(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $peserta = Peserta::with(['formulirSpmb', 'tahapanSpmb'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'biodata_peserta_spmb_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($peserta) {
            $handle = fopen('php://output', 'w');
            
            // BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($handle, [
                'No Pendaftaran', 'Nama Lengkap', 'Jenis Kelamin',
                'Tempat Lahir', 'Provinsi Lahir', 'Tanggal Lahir',
                'Asal Sekolah', 'NISN', 'Prestasi',
                'Tinggi Badan', 'Berat Badan', 'Lingkar Kepala',
                'Lingkar Dada', 'Lingkar Pinggang', 'Panjang Celana',
                'Hobi', 'Cita-cita',
                'Nama Ayah', 'Pekerjaan Ayah', 'Pendidikan Ayah',
                'Nama Ibu', 'Pekerjaan Ibu', 'Pendidikan Ibu',
                'Kelurahan', 'Kecamatan', 'Kota/Kab', 'Provinsi',
                'Telp Rumah', 'HP/WA Siswa', 'HP/WA Ayah', 'HP/WA Ibu',
                'Jumlah Saudara', 'Kelompok', 'Desa', 'Daerah',
                'Tanggal Daftar', 'Email', 'Tahap', 'Status Verifikasi',
            ], ';');
            
            // Data
            foreach ($peserta as $p) {
                $f = $p->formulirSpmb;
                fputcsv($handle, [
                    $p->nomor_pendaftaran,
                    $f?->nama_lengkap ?? $p->nama,
                    $f?->jenis_kelamin ?? '-',
                    $f?->tempat_lahir ?? '-',
                    $f?->provinsi_lahir ?? '-',
                    $f?->tanggal_lahir?->format('d/m/Y') ?? '-',
                    $f?->asal_sekolah ?? '-',
                    $f?->nisn ?? '-',
                    $f?->prestasi ?? '-',
                    $f?->tinggi_badan ?? '-',
                    $f?->berat_badan ?? '-',
                    $f?->lingkar_kepala ?? '-',
                    $f?->lingkar_dada ?? '-',
                    $f?->lingkar_pinggang ?? '-',
                    $f?->panjang_celana ?? '-',
                    $f?->hobi ?? '-',
                    $f?->cita_cita ?? '-',
                    $f?->nama_ayah ?? '-',
                    $f?->pekerjaan_ayah ?? '-',
                    $f?->pendidikan_ayah ?? '-',
                    $f?->nama_ibu ?? '-',
                    $f?->pekerjaan_ibu ?? '-',
                    $f?->pendidikan_ibu ?? '-',
                    $f?->alamat_kelurahan ?? '-',
                    $f?->alamat_kecamatan ?? '-',
                    $f?->alamat_kota ?? '-',
                    $f?->alamat_provinsi ?? '-',
                    $f?->telp_rumah ?? '-',
                    $f?->telepon ?? $p->telepon ?? '-',
                    $f?->telepon_ayah ?? '-',
                    $f?->telepon_ibu ?? '-',
                    $f?->jumlah_saudara ?? '-',
                    $f?->kelompok ?? '-',
                    $f?->desa ?? '-',
                    $f?->daerah ?? '-',
                    $f?->tanggal_daftar?->format('d/m/Y') ?? $p->created_at->format('d/m/Y'),
                    $p->email ?? '-',
                    'Tahap ' . $p->tahap_saat_ini,
                    ucfirst($f?->status_verifikasi ?? 'belum isi'),
                ], ';');
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
