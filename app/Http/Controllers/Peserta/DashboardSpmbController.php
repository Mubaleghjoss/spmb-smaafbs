<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
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
        $peserta = Peserta::with(['tahapanSpmb', 'formulirSpmb', 'sesiTes' => function($q) {
            $q->whereIn('status', ['selesai', 'timeout'])->with('tes')->latest();
        }])->find(session('peserta_id'));
        $statusData = $this->spmbService->ambilStatusTahapan($peserta);
        
        $statusTahapan = $this->buildStatusTahapan($peserta);
        
        // Ambil semua sesi tes yang menunggu verifikasi (untuk menampilkan nama tes)
        $sesiTesList = $peserta->sesiTes->filter(function($sesi) {
            return in_array($sesi->status_verifikasi_tes, ['menunggu', 'ditolak']);
        });
        
        // Ambil sesi tes terakhir untuk backward compatibility
        $sesiTes = $peserta->sesiTes->first();
        
        // Hitung berkas yang belum diunggah
        $berkasBelumLengkap = $this->hitungBerkasBelumLengkap($peserta->formulirSpmb);
        
        return view('peserta.dashboard', [
            'peserta' => $peserta,
            'tahapan' => $peserta->tahapanSpmb,
            'statusTahapan' => $statusTahapan,
            'sesiTes' => $sesiTes,
            'sesiTesList' => $sesiTesList,
            'berkasBelumLengkap' => $berkasBelumLengkap,
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
        
        // Cek apakah tahap 4 sudah selesai
        if (!$peserta->tahapanSpmb?->tahap_4_selesai) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Anda harus menyelesaikan tes online terlebih dahulu');
        }
        
        $infoWawancara = $pengaturanTahapan['tahap_5'] ?? [];
        $pertanyaanOrtu = \App\Models\Wawancara::pertanyaanOrtu();
        $pertanyaanSiswa = \App\Models\Wawancara::pertanyaanSiswa();
        $spSiswaPoin = \App\Models\Wawancara::suratPernyataanSiswaPoin();
        $spOrtuPoin = \App\Models\Wawancara::suratPernyataanOrtuPoin();
        $wawancara = $peserta->wawancara;
        
        return view('peserta.wawancara-info', compact(
            'peserta', 'infoWawancara', 'pertanyaanOrtu', 'pertanyaanSiswa',
            'spSiswaPoin', 'spOrtuPoin', 'wawancara'
        ));
    }

    /**
     * Simpan jawaban wawancara per step
     */
    public function simpanWawancara(Request $request): RedirectResponse
    {
        $peserta = Peserta::with(['tahapanSpmb', 'wawancara'])->find(session('peserta_id'));

        if (!$peserta->tahapanSpmb?->tahap_4_selesai) {
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
        $aksesUjian = $pengaturanService->statusAksesUjian();
        
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
            $statusInfo = $this->cekTahapanDibukaDetail($pengaturanTahapan, $num, $tahapan, $aksesUjian);
            
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
    private function cekTahapanDibukaDetail(array $pengaturan, int $tahap, $tahapanPeserta, ?array $aksesUjian = null): array
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

        // Tahap 4 (Tes Online) dikendalikan dari Pengaturan Ujian.
        if ($tahap === 4) {
            $aksesUjian ??= app(\App\Services\PengaturanService::class)->statusAksesUjian();
            $result['tanggal_buka'] = $aksesUjian['mulai_label'] ?? null;
            $mulaiLabel = $aksesUjian['mulai_label'] ?? null;
            $selesaiLabel = $aksesUjian['selesai_label'] ?? null;

            if ($mulaiLabel && $selesaiLabel) {
                $result['jadwal_label'] = 'Jadwal tes: ' . $mulaiLabel . ' sampai ' . $selesaiLabel;
            } elseif ($mulaiLabel) {
                $result['jadwal_label'] = 'Tes online mulai dibuka pada ' . $mulaiLabel;
            } elseif ($selesaiLabel) {
                $result['jadwal_label'] = 'Tes online ditutup pada ' . $selesaiLabel;
            }

            if (!($aksesUjian['dibuka'] ?? true)) {
                $result['dibuka'] = false;
                $result['alasan'] = $aksesUjian['alasan'] ?? 'Tes online belum dibuka.';
            }

            return $result;
        }

        // Cek pengaturan waktu
        $key = "tahap_{$tahap}";
        if (isset($pengaturan[$key])) {
            $config = $pengaturan[$key];
            $now = now();
            $dibukaManual = filter_var($config['dibuka'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $dibukaManual = $dibukaManual ?? true;
            $tanggalBuka = $this->gabungkanTanggalWaktuTahapan($config['tanggal_buka'] ?? '', $config['waktu_mulai'] ?? '', false);
            $tanggalTutup = $this->gabungkanTanggalWaktuTahapan($config['tanggal_tutup'] ?? '', $config['waktu_selesai'] ?? '', true);
            $mulaiLabel = $tanggalBuka ? $this->formatTanggalWaktuTahapan($tanggalBuka, !empty($config['waktu_mulai'])) : null;
            $selesaiLabel = $tanggalTutup ? $this->formatTanggalWaktuTahapan($tanggalTutup, !empty($config['waktu_selesai'])) : null;
            $labelJadwal = match ($tahap) {
                2 => 'isi formulir',
                3 => 'pembayaran formulir',
                5 => 'wawancara',
                6 => 'pembayaran pertama',
                7 => 'kelulusan',
                default => 'tahap',
            };

            if ($mulaiLabel && $selesaiLabel) {
                $result['jadwal_label'] = 'Jadwal ' . $labelJadwal . ': ' . $mulaiLabel . ' sampai ' . $selesaiLabel;
            } elseif ($mulaiLabel) {
                $result['jadwal_label'] = 'Dibuka pada ' . $mulaiLabel;
            } elseif ($selesaiLabel) {
                $result['jadwal_label'] = 'Ditutup pada ' . $selesaiLabel;
            }

            if (!$dibukaManual) {
                $result['dibuka'] = false;
                $result['alasan'] = 'Tahap ini sedang ditutup oleh admin.';
                return $result;
            }
            
            if ($tanggalBuka) {
                $result['tanggal_buka'] = $mulaiLabel;
                
                if ($now < $tanggalBuka) {
                    $result['dibuka'] = false;
                    $result['alasan'] = 'Dibuka pada ' . $mulaiLabel;
                    return $result;
                }
            }
            
            if ($tanggalTutup) {
                if ($now > $tanggalTutup) {
                    $result['dibuka'] = false;
                    $result['alasan'] = 'Sudah ditutup pada ' . $selesaiLabel;
                    return $result;
                }
            }
        }
        // Jika tidak ada pengaturan waktu, tahap tetap dibuka (default behavior)

        return $result;
    }

    private function gabungkanTanggalWaktuTahapan(?string $tanggal, ?string $waktu, bool $akhirHari): ?\Carbon\Carbon
    {
        if (empty($tanggal)) {
            return null;
        }

        $jam = $waktu ?: ($akhirHari ? '23:59:59' : '00:00:00');

        return \Carbon\Carbon::parse(trim($tanggal . ' ' . $jam));
    }

    private function formatTanggalWaktuTahapan(\Carbon\Carbon $tanggal, bool $pakaiJam): string
    {
        return $tanggal->locale('id')->translatedFormat($pakaiJam ? 'd F Y H:i' : 'd F Y') . ($pakaiJam ? ' WIB' : '');
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
}
