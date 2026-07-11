<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Services\PesertaService;
use App\Services\GrupService;
use App\Services\ImporEksporPesertaService;
use App\Services\PeriodePendaftaranService;
use App\Models\TahunAjaran;
use App\Models\GelombangPendaftaran;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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
        private ImporEksporPesertaService $imporEksporService,
        private PeriodePendaftaranService $periodePendaftaranService
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
            'tahun_ajaran_id' => $request->get('tahun_ajaran_id'),
            'gelombang_pendaftaran_id' => $request->get('gelombang_pendaftaran_id'),
            'jenis_pendaftaran' => $request->get('jenis_pendaftaran'),
            'kelas_tujuan' => $request->get('kelas_tujuan'),
            'status_kuota' => $request->get('status_kuota'),
            'asal_sekolah_smp' => $request->get('asal_sekolah_smp'),
            'kelompok' => $request->get('kelompok'),
            'desa' => $request->get('desa'),
            'daerah' => $request->get('daerah'),
        ];

        $peserta = $this->pesertaService->ambilDenganFilter($filter, 15);
        $rekapFormulir = $this->pesertaService->rekapFormulir($filter);
        $grup = $this->grupService->ambilSemua();
        $statistik = $this->pesertaService->ambilStatistik();
        $skGelombang = app(\App\Services\PengaturanService::class)->ambilSuratKelulusanGelombang();
        [$tahunAjaran, $gelombangPendaftaran] = $this->periodeOptions();

        return view('admin.peserta.index', compact(
            'peserta',
            'grup',
            'statistik',
            'filter',
            'skGelombang',
            'tahunAjaran',
            'gelombangPendaftaran',
            'rekapFormulir'
        ));
    }

    /**
     * Tampilkan form tambah peserta
     */
    public function create(): View
    {
        $grup = $this->grupService->ambilSemua();
        [$tahunAjaran, $gelombangPendaftaran] = $this->periodeOptions();
        $kategoriDefault = $this->periodePendaftaranService->kategoriDefault();

        return view('admin.peserta.create', compact(
            'grup',
            'tahunAjaran',
            'gelombangPendaftaran',
            'kategoriDefault'
        ));
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
            'tahun_ajaran_id' => 'required|integer|exists:tahun_ajaran,id',
            'gelombang_pendaftaran_id' => 'required|integer|exists:gelombang_pendaftaran,id',
            'jenis_pendaftaran' => 'required|in:siswa_baru,pindahan',
            'kelas_tujuan' => 'required|integer|in:10,11',
        ]);

        $validated = [
            ...$validated,
            ...$this->periodePendaftaranService->validasiKategori($validated),
        ];
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
        $peserta->load([
            'tahapanSpmb',
            'grup',
            'formulirSpmb',
            'pembayaran',
            'logTahapan',
            'tahunAjaran',
            'gelombangPendaftaran',
        ]);
        return view('admin.peserta.show', compact('peserta'));
    }

    /**
     * Tampilkan form edit peserta
     */
    public function edit(Peserta $peserta): View
    {
        $peserta->load(['grup', 'tahunAjaran', 'gelombangPendaftaran']);
        $grup = $this->grupService->ambilSemua();
        [$tahunAjaran, $gelombangPendaftaran] = $this->periodeOptions();
        $kategoriDefault = $this->periodePendaftaranService->kategoriDefault();

        return view('admin.peserta.edit', compact(
            'peserta',
            'grup',
            'tahunAjaran',
            'gelombangPendaftaran',
            'kategoriDefault'
        ));
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
            'tahun_ajaran_id' => 'required|integer|exists:tahun_ajaran,id',
            'gelombang_pendaftaran_id' => 'required|integer|exists:gelombang_pendaftaran,id',
            'jenis_pendaftaran' => 'required|in:siswa_baru,pindahan',
            'kelas_tujuan' => 'required|integer|in:10,11',
        ]);

        $validated = [
            ...$validated,
            ...$this->periodePendaftaranService->validasiKategori($validated),
        ];
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

    public function bulkUpdateKategori(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'peserta_ids' => 'required|array|min:1',
            'peserta_ids.*' => 'exists:peserta,id',
            'tahun_ajaran_id' => 'required|integer|exists:tahun_ajaran,id',
            'gelombang_pendaftaran_id' => 'required|integer|exists:gelombang_pendaftaran,id',
            'jenis_pendaftaran' => 'required|in:siswa_baru,pindahan',
            'kelas_tujuan' => 'required|integer|in:10,11',
        ]);
        $kategori = $this->periodePendaftaranService->validasiKategori($validated);
        $count = $this->pesertaService->bulkPerbaruiKategori(
            $validated['peserta_ids'],
            $kategori
        );

        return back()->with('success', "{$count} peserta berhasil diperbarui kategorinya.");
    }

    private function periodeOptions(): array
    {
        return [
            TahunAjaran::query()
                ->with('gelombangPendaftaran')
                ->orderByDesc('default')
                ->orderByDesc('nama')
                ->get(),
            GelombangPendaftaran::query()
                ->with('tahunAjaran')
                ->orderBy('tahun_ajaran_id')
                ->orderBy('tanggal_buka')
                ->orderBy('nama')
                ->get(),
        ];
    }

    /**
     * Bulk pindahkan peserta ke tahap tertentu.
     */
    public function bulkUpdateTahap(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'peserta_ids' => 'required|array|min:1',
            'peserta_ids.*' => 'exists:peserta,id',
            'tahap_baru' => 'required|integer|min:1|max:7',
            'luluskan_final' => 'nullable|boolean',
            'sk_gelombang_kelulusan' => 'nullable|string',
        ]);

        $tahapBaru = (int) $validated['tahap_baru'];
        $luluskanFinal = $tahapBaru === 7 && $request->boolean('luluskan_final');
        $skGelombangId = null;

        if ($luluskanFinal) {
            $skGelombang = app(\App\Services\PengaturanService::class)->ambilSuratKelulusanGelombang();
            $selectedSk = $request->input('sk_gelombang_kelulusan');

            if (empty($skGelombang)) {
                return redirect()
                    ->back()
                    ->with('error', 'Tambahkan dan upload SK gelombang terlebih dahulu di Pengaturan SPMB > Kelulusan.');
            }

            if (blank($selectedSk)) {
                return redirect()
                    ->back()
                    ->with('error', 'Pilih SK gelombang untuk menandai peserta lulus final.');
            }

            if (!collect($skGelombang)->contains('id', $selectedSk)) {
                return redirect()
                    ->back()
                    ->with('error', 'SK gelombang yang dipilih tidak ditemukan.');
            }

            $skGelombangId = $selectedSk;
        }

        $count = $this->pesertaService->bulkPindahkanTahap(
            $validated['peserta_ids'],
            $tahapBaru,
            auth('pengguna')->id(),
            $luluskanFinal,
            $skGelombangId
        );

        $tahapLabels = [
            1 => 'Pendaftaran', 2 => 'Isi Formulir', 3 => 'Bayar Formulir',
            4 => 'Tes Online', 5 => 'Wawancara', 6 => 'Pelunasan', 7 => 'Kelulusan'
        ];
        $pesan = "{$count} peserta berhasil dipindahkan ke Tahap {$tahapBaru}: {$tahapLabels[$tahapBaru]}.";
        if ($luluskanFinal) {
            $pesan .= ' Peserta juga ditandai LULUS final.';
        }

        return redirect()
            ->back()
            ->with('success', $pesan);
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

    public function prosesImporRekapSeleksi(Request $request): RedirectResponse
    {
        if ($request->input('aksi_rekap') === 'batal') {
            $token = (string) $request->input('rekap_preview_token');
            if ($token !== '') {
                session()->forget($this->sessionKeyPreviewRekap($token));
            }

            return redirect()
                ->route('admin.peserta.impor')
                ->with('success', 'Preview import rekap dibatalkan. Tidak ada data yang diubah.');
        }

        if ($request->filled('rekap_preview_token')) {
            $token = (string) $request->input('rekap_preview_token');
            $preview = session($this->sessionKeyPreviewRekap($token));

            if (! is_array($preview)) {
                return redirect()
                    ->route('admin.peserta.impor')
                    ->with('errors_impor', ['Preview import sudah kedaluwarsa. Upload ulang file rekap.']);
            }

            $hasil = $this->imporEksporService->terapkanPreviewRekapSeleksi(
                $preview,
                $request->input('keputusan', [])
            );

            session()->forget($this->sessionKeyPreviewRekap($token));

            return $this->redirectHasilImporRekap($hasil);
        }

        $request->validate([
            'file_rekap' => 'required|file|mimes:xlsx,xls|max:5120',
        ], [
            'file_rekap.required' => 'File rekap seleksi wajib diupload.',
            'file_rekap.mimes' => 'File rekap seleksi harus berformat xlsx atau xls.',
        ]);

        $preview = $this->imporEksporService->previewImporRekapSeleksi(
            $request->file('file_rekap')->getRealPath()
        );

        if (! empty($preview['errors'])) {
            return redirect()
                ->back()
                ->with('errors_impor', $preview['errors'])
                ->with('warnings_impor', $preview['warnings'] ?? []);
        }

        if (! empty($preview['conflicts'])) {
            $token = (string) Str::uuid();
            session()->put($this->sessionKeyPreviewRekap($token), $preview);

            return redirect()
                ->back()
                ->with('rekap_preview', [
                    'token' => $token,
                    'summary' => $preview['summary'] ?? [],
                    'conflicts' => $preview['conflicts'],
                    'warnings' => $preview['warnings'] ?? [],
                ])
                ->with('warnings_impor', $preview['warnings'] ?? []);
        }

        return $this->redirectHasilImporRekap(
            $this->imporEksporService->terapkanPreviewRekapSeleksi($preview, [])
        );
    }

    private function redirectHasilImporRekap(array $hasil): RedirectResponse
    {
        $pesan = "Impor rekap selesai: {$hasil['sukses']} berhasil ({$hasil['baru']} baru, {$hasil['update']} update, {$hasil['tidak_berubah']} tidak berubah), {$hasil['gagal']} gagal.";

        $redirect = redirect()->back()->with('success', $pesan);

        if (! empty($hasil['errors'])) {
            $redirect->with('errors_impor', $hasil['errors']);
        }

        if (! empty($hasil['warnings'])) {
            $redirect->with('warnings_impor', $hasil['warnings']);
        }

        return $redirect;
    }

    private function sessionKeyPreviewRekap(string $token): string
    {
        return 'rekap_import_preview_' . $token;
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

    public function downloadTemplateRekapSeleksi(): BinaryFileResponse
    {
        $path = $this->imporEksporService->generateTemplateRekapSeleksi();

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

        $this->pesertaService->pindahkanTahap($peserta, $tahapBaru, auth('pengguna')->id());

        $tahapLabels = [
            1 => 'Pendaftaran', 2 => 'Isi Formulir', 3 => 'Bayar Formulir',
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
        $peserta = Peserta::with([
            'tahapanSpmb',
            'tahunAjaran',
            'gelombangPendaftaran',
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'akun_peserta_spmb_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($peserta) {
            $handle = fopen('php://output', 'w');
            
            // BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($handle, [
                'No Pendaftaran',
                'Nama',
                'No HP/WA',
                'Email',
                'Password',
                'Tahun Ajaran',
                'Gelombang',
                'Jenis Pendaftaran',
                'Kelas Tujuan',
                'Kelas Penempatan',
                'Status Kuota',
                'Tahap',
                'Tanggal Daftar',
            ], ';');
            
            // Data
            foreach ($peserta as $p) {
                fputcsv($handle, [
                    $p->nomor_pendaftaran,
                    $p->nama,
                    $p->telepon,
                    $p->email ?? '-',
                    $p->password_temp ?? '(sudah diubah)',
                    $p->tahunAjaran?->nama ?? '-',
                    $p->gelombangPendaftaran?->nama ?? '-',
                    $p->jenis_pendaftaran_label,
                    $p->kelas_tujuan ? 'Kelas ' . $p->kelas_tujuan : '-',
                    $p->kelas_penempatan ?? '-',
                    $p->status_kuota_label,
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
        $peserta = Peserta::with([
            'formulirSpmb',
            'tahapanSpmb',
            'tahunAjaran',
            'gelombangPendaftaran',
        ])
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
                'Tahun Ajaran', 'Gelombang', 'Jenis Pendaftaran', 'Kelas Tujuan', 'Kelas Penempatan', 'Status Kuota',
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
                    $p->tahunAjaran?->nama ?? '-',
                    $p->gelombangPendaftaran?->nama ?? '-',
                    $p->jenis_pendaftaran_label,
                    $p->kelas_tujuan ? 'Kelas ' . $p->kelas_tujuan : '-',
                    $p->kelas_penempatan ?? '-',
                    $p->status_kuota_label,
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
