<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Peserta;
use App\Models\SesiTes;
use App\Services\SpmbService;
use App\Enums\TahapanSpmb as TahapanSpmbEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardSpmbController extends Controller
{
    public function __construct(
        private SpmbService $spmbService
    ) {}

    /**
     * Tampilkan dashboard SPMB peserta
     */
    public function index(): View
    {
        $peserta = Peserta::with(['tahapanSpmb', 'formulirSpmb', 'pembayaran', 'wawancara', 'sesiTes' => function($q) {
            $q->whereIn('status', ['selesai', 'timeout'])->with('tes')->latest();
        }])->find(session('peserta_id'));
        $statusData = $this->spmbService->ambilStatusTahapan($peserta);
        
        $statusTahapan = $this->buildStatusTahapan($peserta);
        
        // Ambil semua sesi tes yang menunggu verifikasi (untuk menampilkan nama tes)
        $sesiTesList = $peserta->sesiTes->filter(function($sesi) {
            return $sesi->status === 'timeout' || in_array($sesi->status_verifikasi_tes, ['menunggu', 'ditolak']);
        });
        
        // Ambil sesi tes terakhir untuk backward compatibility
        $sesiTes = $peserta->sesiTes->first();
        
        // Hitung berkas yang belum diunggah
        $berkasBelumLengkap = $this->hitungBerkasBelumLengkap($peserta->formulirSpmb);
        $kelengkapanPascakelulusan = $this->buildKelengkapanPascakelulusan($peserta);
        
        return view('peserta.dashboard', [
            'peserta' => $peserta,
            'tahapan' => $peserta->tahapanSpmb,
            'statusTahapan' => $statusTahapan,
            'sesiTes' => $sesiTes,
            'sesiTesList' => $sesiTesList,
            'berkasBelumLengkap' => $berkasBelumLengkap,
            'kelengkapanPascakelulusan' => $kelengkapanPascakelulusan,
        ]);
    }

    /**
     * Ambil status tahapan untuk update real-time
     */
    public function ambilStatusTahapan(): JsonResponse
    {
        $peserta = Peserta::with('tahapanSpmb')->find(session('peserta_id'));
        $statusData = $this->spmbService->ambilStatusTahapan($peserta);
        
        return response()->json($statusData);
    }

    /**
     * Halaman konfirmasi diterima (Tahap 7)
     */
    public function konfirmasiDiterima(): View|RedirectResponse
    {
        $peserta = Peserta::with(['tahapanSpmb', 'formulirSpmb'])->find(session('peserta_id'));
        $pengaturanService = app(\App\Services\PengaturanService::class);
        
        // Ambil status kelulusan
        $statusKelulusan = $peserta->tahapanSpmb?->status_kelulusan ?? 'menunggu';
        $tahap7Selesai = $peserta->tahapanSpmb?->tahap_7_selesai ?? false;
        
        // Cek apakah peserta sudah di tahap 6 atau 7 (sudah bisa melihat pengumuman)
        $tahapSaatIni = $peserta->tahapanSpmb?->tahap_saat_ini ?? 1;
        if ($tahapSaatIni < 6) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Anda belum menyelesaikan tahapan sebelumnya');
        }
        
        // Ambil pengaturan kelulusan
        $pengaturanKelulusan = $pengaturanService->ambilPengaturanKelulusan();
        $pengaturanTahapan = $pengaturanService->ambilPengaturanTahapan();
        $branding = $pengaturanService->ambilBranding();
        $skKelulusan = $pengaturanService->ambilSuratKelulusanUntukGelombang(
            $peserta->tahapanSpmb?->sk_gelombang_kelulusan
        );
        
        return view('peserta.konfirmasi-diterima', compact(
            'peserta', 
            'statusKelulusan', 
            'tahap7Selesai',
            'pengaturanKelulusan',
            'pengaturanTahapan',
            'branding',
            'skKelulusan'
        ));
    }

    /**
     * Download SK kelulusan dengan nama file per peserta.
     */
    public function downloadSuratKelulusan(): RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $peserta = Peserta::with('tahapanSpmb')->find(session('peserta_id'));

        if (!$peserta || $peserta->tahapanSpmb?->status_kelulusan !== 'lulus' || !($peserta->tahapanSpmb?->tahap_7_selesai ?? false)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'SK kelulusan belum tersedia.');
        }

        $pengaturanService = app(\App\Services\PengaturanService::class);
        $skKelulusan = $pengaturanService->ambilSuratKelulusanUntukGelombang(
            $peserta->tahapanSpmb?->sk_gelombang_kelulusan
        );

        if (!$skKelulusan || empty($skKelulusan['file']) || !Storage::disk('public')->exists($skKelulusan['file'])) {
            return redirect()->route('peserta.konfirmasi-diterima')
                ->with('error', 'File SK kelulusan belum tersedia.');
        }

        $extension = pathinfo($skKelulusan['file'], PATHINFO_EXTENSION) ?: 'pdf';
        $namaPeserta = Str::upper(Str::slug($peserta->nama, '-')) ?: 'PESERTA';
        $filename = "SK-SPMB-SMAAFBS-{$namaPeserta}.{$extension}";

        return Storage::disk('public')->download($skKelulusan['file'], $filename);
    }

    /**
     * Halaman info wawancara (Tahap 5)
     */
    public function infoWawancara(): View|RedirectResponse
    {
        $peserta = Peserta::with(['tahapanSpmb', 'wawancara'])->find(session('peserta_id'));
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $pengaturanTahapan = $pengaturanService->ambilPengaturanTahapan();
        
        // Peserta lulus final tetap boleh melengkapi data wawancara yang terlewat.
        if (!$peserta->tahapanSpmb?->tahap_4_selesai && !$this->sudahLulusFinal($peserta)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Anda harus menyelesaikan tes online terlebih dahulu');
        }
        
        $infoWawancara = $pengaturanTahapan['tahap_5'] ?? [];
        $pertanyaanOrtu = \App\Models\Wawancara::pertanyaanOrtu();
        $pertanyaanSiswa = \App\Models\Wawancara::pertanyaanSiswa();
        $spSiswaPoin = \App\Models\Wawancara::suratPernyataanSiswaPoin();
        $spOrtuPoin = \App\Models\Wawancara::suratPernyataanOrtuPoin();
        $wawancara = $peserta->wawancara;
        $kelengkapanWawancara = $this->hitungKelengkapanWawancara($wawancara);
        
        return view('peserta.wawancara-info', compact(
            'peserta', 'infoWawancara', 'pertanyaanOrtu', 'pertanyaanSiswa',
            'spSiswaPoin', 'spOrtuPoin', 'wawancara', 'kelengkapanWawancara'
        ));
    }

    /**
     * Simpan jawaban wawancara per step
     */
    public function simpanWawancara(Request $request): RedirectResponse
    {
        $peserta = Peserta::with(['tahapanSpmb', 'wawancara'])->find(session('peserta_id'));

        if (!$peserta->tahapanSpmb?->tahap_4_selesai && !$this->sudahLulusFinal($peserta)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Anda harus menyelesaikan tes online terlebih dahulu');
        }

        $wawancara = $peserta->wawancara ?? new \App\Models\Wawancara(['peserta_id' => $peserta->id]);
        $step = (int) $request->input('step', 1);

        switch ($step) {
            case 1:
                $wawancara->jawaban_ortu = $request->input('jawaban_ortu', []);
                $msg = 'Jawaban orang tua berhasil disimpan.';
                break;
            case 2:
                $wawancara->jawaban_siswa = $request->input('jawaban_siswa', []);
                $msg = 'Jawaban siswa berhasil disimpan.';
                break;
            case 3:
                $suratSiswa = $request->input('sp_siswa', []);
                $suratSiswa['tanggal_surat'] = $suratSiswa['tanggal_surat']
                    ?? ($wawancara->surat_pernyataan_siswa['tanggal_surat'] ?? now()->toDateString());
                $wawancara->surat_pernyataan_siswa = $suratSiswa;
                if ($request->filled('tanda_tangan_peserta')) {
                    $wawancara->tanda_tangan_peserta = $request->input('tanda_tangan_peserta');
                }
                $msg = 'Surat pernyataan siswa berhasil disimpan.';
                break;
            case 4:
                $suratOrtu = $request->input('sp_ortu', []);
                $suratOrtu['tanggal_surat'] = $suratOrtu['tanggal_surat']
                    ?? ($wawancara->surat_pernyataan_ortu['tanggal_surat'] ?? now()->toDateString());
                $wawancara->surat_pernyataan_ortu = $suratOrtu;
                if ($request->filled('tanda_tangan_ortu')) {
                    $wawancara->tanda_tangan_ortu = $request->input('tanda_tangan_ortu');
                }
                $msg = 'Surat pernyataan orangtua berhasil disimpan.';
                break;
            case 5:
                if ($request->hasFile('file_tes_pegon')) {
                    $path = $request->file('file_tes_pegon')->store('wawancara/pegon', 'public');
                    $wawancara->file_tes_pegon = $path;
                }
                $msg = 'Jawaban tes pegon berhasil diupload.';
                break;
            case 6:
                if ($request->hasFile('file_voice_quran')) {
                    $path = $request->file('file_voice_quran')->store('wawancara/voice', 'public');
                    $wawancara->file_voice_quran = $path;
                }
                $wawancara->surat_quran_random = $request->input('surat_quran_random');
                $msg = 'Rekaman bacaan Quran berhasil dikirim.';
                break;
            default:
                $msg = 'Data berhasil disimpan.';
        }

        $wawancara->diisi_peserta_pada = now();
        $wawancara->save();

        return redirect()->route('peserta.wawancara.info')
            ->with('success', $msg);
    }

    /**
     * Download soal tes pegon (halaman A4 printable)
     */
    public function downloadTesPegon(): View
    {
        $peserta = Peserta::find(session('peserta_id'));
        $teksPegon = \App\Models\Wawancara::teksPegon();
        return view('peserta.tes-pegon', compact('peserta', 'teksPegon'));
    }

    /**
     * Download surat pernyataan sebagai PDF (peserta-facing)
     */
    public function downloadSuratPernyataanPdf()
    {
        $peserta = Peserta::with(['formulirSpmb', 'wawancara'])->find(session('peserta_id'));

        if (!$peserta->wawancara?->surat_pernyataan_siswa && !$peserta->wawancara?->surat_pernyataan_ortu) {
            return redirect()->route('peserta.wawancara.info')
                ->with('error', 'Surat pernyataan belum diisi.');
        }

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
     * Halaman cetak surat pernyataan (peserta-facing, printable)
     */
    public function cetakSuratPernyataan(): View|RedirectResponse
    {
        $peserta = Peserta::with(['formulirSpmb', 'wawancara'])->find(session('peserta_id'));

        if (!$peserta->wawancara?->surat_pernyataan_siswa && !$peserta->wawancara?->surat_pernyataan_ortu) {
            return redirect()->route('peserta.wawancara.info')
                ->with('error', 'Surat pernyataan belum diisi.');
        }

        $spSiswaPoin = \App\Models\Wawancara::suratPernyataanSiswaPoin();
        $spOrtuPoin = \App\Models\Wawancara::suratPernyataanOrtuPoin();

        return view('peserta.cetak-surat-pernyataan', compact('peserta', 'spSiswaPoin', 'spOrtuPoin'));
    }

    /**
     * Build array status tahapan untuk view
     * Urutan: 1. Buat Akun, 2. Isi Formulir, 3. Bayar Formulir, 4. Tes Online, 5. Wawancara, 6. Bayar Pertama, 7. Diterima
     */
    private function buildStatusTahapan(Peserta $peserta): array
    {
        $tahapan = $peserta->tahapanSpmb;
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $pengaturanTahapan = $pengaturanService->ambilPengaturanTahapan();
        
        $tahapanConfig = [
            1 => ['enum' => TahapanSpmbEnum::BUAT_AKUN, 'icon' => 'person-plus', 'route' => null, 'route_selesai' => null, 'aksi' => null],
            2 => ['enum' => TahapanSpmbEnum::ISI_FORMULIR, 'icon' => 'file-earmark-text', 'route' => 'peserta.formulir.isi', 'route_selesai' => 'peserta.formulir.review', 'aksi' => 'Isi Formulir'],
            3 => ['enum' => TahapanSpmbEnum::BAYAR_FORMULIR, 'icon' => 'credit-card', 'route' => 'peserta.pembayaran.formulir', 'route_selesai' => 'peserta.pembayaran.status-formulir', 'aksi' => 'Upload Bukti'],
            4 => ['enum' => TahapanSpmbEnum::TES_ONLINE, 'icon' => 'laptop', 'route' => 'ujian.index', 'route_selesai' => 'ujian.index', 'aksi' => 'Mulai Tes'],
            5 => ['enum' => TahapanSpmbEnum::WAWANCARA, 'icon' => 'people', 'route' => 'peserta.wawancara.info', 'route_selesai' => 'peserta.wawancara.info', 'aksi' => 'Lihat Info'],
            6 => ['enum' => TahapanSpmbEnum::BAYAR_PERTAMA, 'icon' => 'wallet2', 'route' => 'peserta.pembayaran.pelunasan', 'route_selesai' => 'peserta.pembayaran.status-pelunasan', 'aksi' => 'Upload Bukti'],
            7 => ['enum' => TahapanSpmbEnum::RESMI_DITERIMA, 'icon' => 'mortarboard', 'route' => 'peserta.konfirmasi-diterima', 'route_selesai' => 'peserta.konfirmasi-diterima', 'aksi' => 'Lihat Info'],
        ];
        
        $result = [];
        foreach ($tahapanConfig as $num => $config) {
            $kolomSelesai = "tahap_{$num}_selesai";
            $statusInfo = $this->cekTahapanDibukaDetail($pengaturanTahapan, $num, $tahapan);
            
            $selesai = $num === 1 ? ($tahapan?->$kolomSelesai ?? true) : ($tahapan?->$kolomSelesai ?? false);
            
            $result[$num] = [
                'selesai' => $selesai,
                'label' => $config['enum']->label(),
                'deskripsi' => $config['enum']->deskripsi(),
                'icon' => $config['icon'],
                'route' => $selesai ? ($config['route_selesai'] ?? $config['route']) : $config['route'],
                'aksi' => $config['aksi'],
                'dibuka' => $statusInfo['dibuka'],
                'alasan' => $statusInfo['alasan'],
                'tanggal_buka' => $statusInfo['tanggal_buka'],
                'jadwal_label' => $statusInfo['jadwal_label'] ?? null,
            ];
        }
        
        return $result;
    }

    /**
     * Cek apakah tahapan dibuka berdasarkan pengaturan waktu dengan detail alasan
     */
    private function cekTahapanDibukaDetail(array $pengaturan, int $tahap, $tahapanPeserta): array
    {
        $result = ['dibuka' => true, 'alasan' => null, 'tanggal_buka' => null, 'jadwal_label' => null];
        
        // Tahap 1 selalu dibuka
        if ($tahap === 1) {
            return $result;
        }
        
        // Tahap sebelumnya harus selesai
        $tahapSebelumnya = $tahap - 1;
        $kolomSebelumnya = "tahap_{$tahapSebelumnya}_selesai";
        if (!($tahapanPeserta?->$kolomSebelumnya ?? false)) {
            $result['dibuka'] = false;
            $result['alasan'] = 'Selesaikan tahap sebelumnya terlebih dahulu';
            return $result;
        }

        $key = "tahap_{$tahap}";
        return app(\App\Services\PengaturanService::class)->statusAksesTahap(
            $tahap,
            $pengaturan[$key] ?? []
        );
    }

    /**
     * Hitung berkas yang belum diunggah
     */
    private function hitungBerkasBelumLengkap($formulir): array
    {
        if (!$formulir) {
            return ['count' => 0, 'fields' => []];
        }

        $berkasFields = [
            'file_kk' => 'Kartu Keluarga (KK)',
            'file_akta' => 'Akta Lahir',
            'file_ijazah' => 'Ijazah SMP',
            'file_bpjs' => 'Kartu BPJS',
            'file_ktp_ibu' => 'KTP Ibu',
            'file_ktp_ayah' => 'KTP Ayah',
        ];

        $belumLengkap = [];
        foreach ($berkasFields as $field => $label) {
            if (empty($formulir->$field)) {
                $belumLengkap[] = $label;
            }
        }

        return [
            'count' => count($belumLengkap),
            'fields' => $belumLengkap,
        ];
    }

    private function buildKelengkapanPascakelulusan(Peserta $peserta): array
    {
        if (!$this->sudahLulusFinal($peserta)) {
            return ['aktif' => false, 'items' => [], 'total' => 0];
        }

        $items = [];
        $formulir = $peserta->formulirSpmb;
        $berkas = $this->hitungBerkasBelumLengkap($formulir);
        $fieldWajib = $formulir ? $this->hitungFieldFormulirBelumLengkap($formulir) : ['Formulir SPMB'];

        if (!$formulir || !empty($fieldWajib) || $berkas['count'] > 0) {
            $detail = [];
            if (!$formulir) {
                $detail[] = 'Formulir belum pernah diisi.';
            } elseif (!empty($fieldWajib)) {
                $detail[] = 'Data wajib belum lengkap: ' . implode(', ', $fieldWajib) . '.';
            }
            if ($berkas['count'] > 0) {
                $detail[] = 'Berkas belum diunggah: ' . implode(', ', $berkas['fields']) . '.';
            }

            $items[] = [
                'icon' => 'file-earmark-text',
                'judul' => 'Formulir dan berkas belum lengkap',
                'detail' => implode(' ', $detail),
                'route' => route('peserta.formulir.review'),
                'aksi' => 'Perbaiki Formulir',
                'level' => 'warning',
            ];
        }

        if (!$this->adaPembayaran($peserta, 'formulir')) {
            $items[] = [
                'icon' => 'credit-card',
                'judul' => 'Bukti pembayaran formulir belum ada',
                'detail' => 'Silakan upload bukti pembayaran formulir agar data keuangan peserta lengkap.',
                'route' => route('peserta.pembayaran.formulir'),
                'aksi' => 'Upload Bukti Formulir',
                'level' => 'warning',
            ];
        }

        foreach ($this->ambilTesBelumLengkap($peserta) as $tesBelumLengkap) {
            $items[] = $tesBelumLengkap;
        }

        $wawancara = $this->hitungKelengkapanWawancara($peserta->wawancara);
        if ($wawancara['count'] > 0) {
            $items[] = [
                'icon' => 'people',
                'judul' => 'Data wawancara belum lengkap',
                'detail' => 'Bagian belum lengkap: ' . implode(', ', $wawancara['fields']) . '.',
                'route' => route('peserta.wawancara.info'),
                'aksi' => 'Lengkapi Wawancara',
                'level' => 'warning',
            ];
        }

        if (!$this->adaPembayaran($peserta, 'pertama')) {
            $items[] = [
                'icon' => 'wallet2',
                'judul' => 'Bukti pembayaran tahap pertama belum ada',
                'detail' => 'Silakan upload bukti pembayaran tahap pertama atau pelunasan awal.',
                'route' => route('peserta.pembayaran.pelunasan'),
                'aksi' => 'Upload Bukti Bayar',
                'level' => 'warning',
            ];
        }

        return [
            'aktif' => !empty($items),
            'items' => $items,
            'total' => count($items),
        ];
    }

    private function hitungFieldFormulirBelumLengkap($formulir): array
    {
        $fields = [
            'nama_lengkap' => 'Nama lengkap',
            'tanggal_lahir' => 'Tanggal lahir',
            'jenis_kelamin' => 'Jenis kelamin',
            'asal_sekolah' => 'Asal sekolah SMP',
            'nama_ayah' => 'Nama ayah',
            'nama_ibu' => 'Nama ibu',
        ];

        $kosong = [];
        foreach ($fields as $field => $label) {
            if (empty($formulir->$field)) {
                $kosong[] = $label;
            }
        }

        return $kosong;
    }

    private function adaPembayaran(Peserta $peserta, string $jenis): bool
    {
        return Pembayaran::where('peserta_id', $peserta->id)
            ->where('jenis', $jenis)
            ->exists();
    }

    private function ambilTesBelumLengkap(Peserta $peserta): array
    {
        $tesService = app(\App\Services\TesService::class);
        $daftarTes = $tesService->ambilTesTersediaUntukPeserta($peserta);
        $items = [];

        foreach ($daftarTes as $tes) {
            $sesiAktif = SesiTes::where('peserta_id', $peserta->id)
                ->where('tes_id', $tes->id)
                ->where('status', 'berlangsung')
                ->latest()
                ->first();

            $sesiSelesai = SesiTes::where('peserta_id', $peserta->id)
                ->where('tes_id', $tes->id)
                ->whereIn('status', ['selesai', 'timeout'])
                ->latest()
                ->first();

            if ($sesiSelesai && !($sesiSelesai->status === 'timeout' && $sesiSelesai->status_verifikasi_tes !== 'diloloskan')) {
                continue;
            }

            if ($sesiSelesai?->status === 'timeout') {
                $items[] = [
                    'icon' => 'hourglass-bottom',
                    'judul' => $tes->nama,
                    'detail' => 'Tes berakhir karena waktu habis. Ajukan perpanjangan waktu atau ulang dari 0.',
                    'route' => route('ujian.hasil', $sesiSelesai),
                    'aksi' => 'Ajukan Permohonan',
                    'level' => 'warning',
                ];
                continue;
            }

            $items[] = [
                'icon' => 'laptop',
                'judul' => $tes->nama,
                'detail' => $sesiAktif ? 'Tes sedang berjalan dan belum diselesaikan.' : 'Tes belum pernah dikerjakan.',
                'route' => $sesiAktif ? route('ujian.kerjakan', $sesiAktif) : route('ujian.konfirmasi', $tes),
                'aksi' => $sesiAktif ? 'Lanjutkan Tes' : 'Mulai Tes',
                'level' => 'danger',
            ];
        }

        return $items;
    }

    private function hitungKelengkapanWawancara($wawancara): array
    {
        $fields = [
            'jawaban_ortu' => 'Form pertanyaan orang tua',
            'jawaban_siswa' => 'Form pertanyaan siswa',
            'surat_pernyataan_siswa' => 'Surat pernyataan siswa',
            'surat_pernyataan_ortu' => 'Surat pernyataan orang tua',
            'file_tes_pegon' => 'Tes pegon',
            'file_voice_quran' => 'Rekaman bacaan Quran',
        ];

        if (!$wawancara) {
            return ['count' => count($fields), 'fields' => array_values($fields)];
        }

        $kosong = [];
        foreach ($fields as $field => $label) {
            if (empty($wawancara->$field)) {
                $kosong[] = $label;
            }
        }

        return ['count' => count($kosong), 'fields' => $kosong];
    }

    private function sudahLulusFinal(Peserta $peserta): bool
    {
        return ($peserta->tahapanSpmb?->status_kelulusan === 'lulus')
            && (bool) ($peserta->tahapanSpmb?->tahap_7_selesai ?? false);
    }
}
