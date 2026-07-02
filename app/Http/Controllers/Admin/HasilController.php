<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\SesiTes;
use App\Models\JawabanPeserta;
use App\Services\PenilaianService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Controller untuk manajemen hasil ujian
 * Kebutuhan: 6.3, 6.4, 6.5
 */
class HasilController extends Controller
{
    public function __construct(
        private PenilaianService $penilaianService
    ) {}

    /**
     * Daftar tes dengan hasil - format rekap
     */
    public function index(Request $request)
    {
        // Ambil semua tes yang punya hasil
        $tesList = Tes::withCount(['sesiTes as peserta_selesai' => function ($q) {
            $q->whereIn('status', ['selesai', 'timeout']);
        }])
        ->having('peserta_selesai', '>', 0)
        ->orderBy('created_at', 'asc')
        ->get();

        // Ambil semua sesi tes yang selesai
        $sesiQuery = SesiTes::whereIn('status', ['selesai', 'timeout'])
            ->with(['peserta:id,nama,nomor_pendaftaran', 'tes:id,nama,nilai_lulus']);

        if ($request->filled('cari')) {
            $sesiQuery->whereHas('peserta', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->cari . '%')
                  ->orWhere('nomor_pendaftaran', 'like', '%' . $request->cari . '%');
            });
        }

        $sesiList = $sesiQuery->get();

        // Group by peserta
        $rekapPeserta = [];
        foreach ($sesiList as $sesi) {
            // Skip jika peserta sudah dihapus (orphaned record)
            if (!$sesi->peserta) {
                continue;
            }

            $pesertaId = $sesi->peserta_id;
            if (!isset($rekapPeserta[$pesertaId])) {
                $rekapPeserta[$pesertaId] = [
                    'peserta' => $sesi->peserta,
                    'hasil' => [],
                ];
            }

            // Cek apakah ini psikotes kepribadian
            $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $sesi->tes_id)->exists();
            $hasilKepribadian = null;
            $detailNilaiPsikotes = null;

            if ($isPsikotes) {
                $hasilPsikotes = \App\Models\HasilPsikotesKepribadian::where('sesi_tes_id', $sesi->id)->first();
                $hasilKepribadian = $hasilPsikotes?->hasil_kepribadian;
                $detailNilaiPsikotes = $hasilPsikotes?->detail_nilai;
            }

            // Cek apakah ini tes gaya belajar
            $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $sesi->tes_id)->first();
            $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
            $hasilGayaBelajar = null;
            $detailNilaiGB = null;

            if ($isGayaBelajar) {
                $hasilGB = \App\Models\HasilGayaBelajar::where('sesi_tes_id', $sesi->id)->first();
                $hasilGayaBelajar = $hasilGB?->hasil_gaya_belajar;
                $detailNilaiGB = $hasilGB?->detail_nilai;
            }

            // Cek apakah ini tes MBTI
            $isMbti = \App\Models\MbtiConfig::where('tes_id', $sesi->tes_id)->exists();
            $hasilMbti = null;
            $hasilMbti2 = null;

            if ($isMbti) {
                $hasilMbtiData = \App\Models\HasilMbti::where('sesi_tes_id', $sesi->id)->first();
                $hasilMbti = $hasilMbtiData?->tipe_mbti;
                $hasilMbti2 = $hasilMbtiData?->tipe_mbti_2;
            }

            // Cek apakah ini tes Profiling
            $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $sesi->tes_id)->first();
            $isProfiling = $profilingConfig && $profilingConfig->aktif;
            $pilarDominan = null;
            $pilarDominan2 = null;
            $skorProfiling = null;

            if ($isProfiling) {
                $hasilProfilingData = \App\Models\HasilProfiling::where('sesi_tes_id', $sesi->id)->first();
                $pilarDominan = $hasilProfilingData?->pilar_dominan;
                $pilarDominan2 = $hasilProfilingData?->pilar_dominan_2;
                $skorProfiling = $hasilProfilingData?->getSkorArray();
            }

            $rekapPeserta[$pesertaId]['hasil'][$sesi->tes_id] = [
                'nilai' => $sesi->nilai,
                'lulus' => $sesi->nilai >= $sesi->tes->nilai_lulus,
                'waktu_selesai' => $sesi->waktu_selesai,
                'is_psikotes' => $isPsikotes,
                'hasil_kepribadian' => $hasilKepribadian,
                'detail_nilai_psikotes' => $detailNilaiPsikotes,
                'is_gaya_belajar' => $isGayaBelajar,
                'hasil_gaya_belajar' => $hasilGayaBelajar,
                'detail_nilai_gb' => $detailNilaiGB,
                'is_mbti' => $isMbti,
                'hasil_mbti' => $hasilMbti,
                'hasil_mbti_2' => $hasilMbti2,
                'is_profiling' => $isProfiling,
                'pilar_dominan' => $pilarDominan,
                'pilar_dominan_2' => $pilarDominan2,
                'skor_profiling' => $skorProfiling,
            ];
        }

        // Sort by nama peserta
        uasort($rekapPeserta, function ($a, $b) {
            return strcmp($a['peserta']->nama ?? '', $b['peserta']->nama ?? '');
        });

        // Pagination manual
        $page = $request->get('page', 1);
        $perPage = 20;
        $total = count($rekapPeserta);
        $rekapPeserta = array_slice($rekapPeserta, ($page - 1) * $perPage, $perPage, true);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $rekapPeserta,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.hasil.index', [
            'tesList' => $tesList,
            'rekapPeserta' => $paginator,
        ]);
    }

    /**
     * Detail hasil semua tes per peserta
     */
    public function detailPesertaRekap(Request $request, $pesertaId)
    {
        $peserta = \App\Models\Peserta::findOrFail($pesertaId);

        $sesiList = SesiTes::where('peserta_id', $pesertaId)
            ->whereIn('status', ['selesai', 'timeout'])
            ->with(['tes', 'jawabanPeserta'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.hasil.detail-peserta-rekap', compact('peserta', 'sesiList'));
    }

    /**
     * Detail hasil per tes
     */
    public function show(Tes $tes, Request $request)
    {
        $statistik = $this->penilaianService->ambilStatistikTes($tes);

        $query = $tes->sesiTes()
            ->with('peserta')
            ->whereIn('status', ['selesai', 'timeout'])
            ->orderBy('nilai', 'desc');

        if ($request->filled('cari')) {
            $query->whereHas('peserta', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->cari . '%')
                  ->orWhere('nomor_pendaftaran', 'like', '%' . $request->cari . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'lulus') {
                $query->where('nilai', '>=', $tes->nilai_lulus);
            } else {
                $query->where('nilai', '<', $tes->nilai_lulus);
            }
        }

        $sesiList = $query->paginate(20)->withQueryString();
        $peringkat = $this->penilaianService->ambilPeringkat($tes);

        return view('admin.hasil.show', compact('tes', 'statistik', 'sesiList', 'peringkat'));
    }

    /**
     * Detail hasil per peserta
     */
    public function detailPeserta(Tes $tes, SesiTes $sesi)
    {
        $sesi->load(['peserta', 'jawabanPeserta.soal.jawaban', 'jawabanPeserta.jawaban']);

        $detailJawaban = $sesi->jawabanPeserta->map(function ($jawaban) {
            $soal = $jawaban->soal;
            $jawabanBenar = $soal->jawaban()->where('benar', true)->first();

            return [
                'soal' => $soal,
                'jawaban_peserta' => $jawaban,
                'jawaban_benar' => $jawabanBenar,
                'benar' => $jawaban->benar,
            ];
        });

        return view('admin.hasil.detail-peserta', compact('tes', 'sesi', 'detailJawaban'));
    }

    /**
     * Halaman analisis butir soal
     */
    public function analisisButirSoal(Tes $tes)
    {
        $analisis = $this->penilaianService->analisisButirSoal($tes);
        $statistik = $this->penilaianService->ambilStatistikTes($tes);

        return view('admin.hasil.analisis-butir-soal', compact('tes', 'analisis', 'statistik'));
    }

    /**
     * Halaman penilaian esai
     */
    public function penilaianEsai(Tes $tes)
    {
        $esaiBelumDinilai = $this->penilaianService->ambilEsaiBelumDinilai($tes);

        return view('admin.hasil.penilaian-esai', compact('tes', 'esaiBelumDinilai'));
    }

    /**
     * Simpan penilaian esai
     */
    public function simpanPenilaianEsai(Request $request, Tes $tes, JawabanPeserta $jawaban)
    {
        $request->validate([
            'benar' => 'required|boolean',
        ]);

        $this->penilaianService->nilaiEsai($jawaban, $request->boolean('benar'));

        return back()->with('success', 'Penilaian esai berhasil disimpan.');
    }

    /**
     * Hitung ulang nilai tes
     */
    public function hitungUlang(Tes $tes)
    {
        $count = $this->penilaianService->hitungUlangNilaiTes($tes);

        return back()->with('success', "Berhasil menghitung ulang nilai untuk {$count} peserta.");
    }

    /**
     * Hitung ulang hasil MBTI untuk semua peserta yang sudah mengerjakan
     */
    public function hitungUlangMbti(Tes $tes)
    {
        return $this->hitungUlangPsikometri($tes, 'mbti');
    }

    /**
     * Hitung ulang hasil Profiling untuk semua peserta yang sudah mengerjakan
     */
    public function hitungUlangProfiling(Tes $tes)
    {
        return $this->hitungUlangPsikometri($tes, 'profiling');
    }

    /**
     * Hitung ulang hasil Psikotes Kepribadian untuk semua peserta yang sudah mengerjakan
     */
    public function hitungUlangPsikotes(Tes $tes)
    {
        return $this->hitungUlangPsikometri($tes, 'psikotes');
    }

    /**
     * Helper terpadu untuk menghitung ulang hasil tes psikometri.
     * Menghindari duplikasi antara hitungUlangMbti, hitungUlangProfiling, hitungUlangPsikotes.
     */
    private function hitungUlangPsikometri(Tes $tes, string $tipe)
    {
        [$service, $label, $errorMsg] = match ($tipe) {
            'mbti'     => [app(\App\Services\MbtiService::class),                'MBTI',                'Tes ini bukan tes MBTI.'],
            'profiling' => [app(\App\Services\ProfilingService::class),          'Profiling',           'Tes ini bukan tes Profiling.'],
            'psikotes' => [app(\App\Services\PsikotesKepribadianService::class), 'Psikotes Kepribadian', 'Tes ini bukan tes Psikotes Kepribadian.'],
        };

        $cekMethod = match ($tipe) {
            'mbti'     => 'isMbti',
            'profiling' => 'isProfiling',
            'psikotes' => 'isPsikotesKepribadian',
        };

        if (!$service->$cekMethod($tes)) {
            return back()->with('error', $errorMsg);
        }

        $sesiList = SesiTes::where('tes_id', $tes->id)
            ->whereIn('status', ['selesai', 'timeout'])
            ->get();

        $count = $sesiList->filter(fn($sesi) => $service->hitungHasil($sesi))->count();

        return back()->with('success', "Berhasil menghitung ulang hasil {$label} untuk {$count} peserta.");
    }

    /**
     * Hitung ulang hasil Psikotes Kepribadian untuk SEMUA tes psikotes
     */
    public function hitungUlangSemuaPsikotes()
    {
        $psikotesService = app(\App\Services\PsikotesKepribadianService::class);
        $gayaBelajarService = app(\App\Services\GayaBelajarService::class);
        $mbtiService = app(\App\Services\MbtiService::class);
        $profilingService = app(\App\Services\ProfilingService::class);

        $count = 0;

        // Hitung ulang Psikotes
        $tesPsikotesIds = \App\Models\PsikotesKepribadianConfig::distinct()->pluck('tes_id');
        $sesiPsikotes = SesiTes::whereIn('tes_id', $tesPsikotesIds)
            ->whereIn('status', ['selesai', 'timeout', 'selesai_psikotes'])
            ->get();
        foreach ($sesiPsikotes as $sesi) {
            if ($psikotesService->hitungHasil($sesi)) $count++;
        }

        // Hitung ulang Gaya Belajar
        $tesGBIds = \App\Models\GayaBelajarConfig::where('aktif', true)->pluck('tes_id');
        $sesiGB = SesiTes::whereIn('tes_id', $tesGBIds)
            ->whereIn('status', ['selesai', 'timeout', 'selesai_gaya_belajar'])
            ->get();
        foreach ($sesiGB as $sesi) {
            if ($gayaBelajarService->hitungHasil($sesi)) $count++;
        }

        // Hitung ulang MBTI
        $tesMbtiIds = \App\Models\MbtiConfig::distinct()->pluck('tes_id');
        $sesiMbti = SesiTes::whereIn('tes_id', $tesMbtiIds)
            ->whereIn('status', ['selesai', 'timeout', 'selesai_mbti'])
            ->get();
        foreach ($sesiMbti as $sesi) {
            if ($mbtiService->hitungHasil($sesi)) $count++;
        }

        // Hitung ulang Profiling
        $tesProfilingIds = \App\Models\ProfilingConfig::where('aktif', true)->pluck('tes_id');
        $sesiProfiling = SesiTes::whereIn('tes_id', $tesProfilingIds)
            ->whereIn('status', ['selesai', 'timeout', 'selesai_profiling'])
            ->get();
        foreach ($sesiProfiling as $sesi) {
            if ($profilingService->hitungHasil($sesi)) $count++;
        }

        return back()->with('success', "Berhasil menghitung ulang {$count} hasil tes kepribadian (Psikotes, Gaya Belajar, MBTI, Profiling).");
    }

    /**
     * Izinkan peserta mengulang tes (hapus sesi tes)
     */
    public function izinkanUlang(Request $request, Tes $tes, SesiTes $sesi)
    {
        $pesertaNama = $sesi->peserta->nama;

        // Hapus jawaban peserta
        $sesi->jawabanPeserta()->delete();
        // Hapus sesi tes
        $sesi->delete();

        return redirect()->route('admin.hasil.show', $tes)
            ->with('success', "Sesi tes untuk {$pesertaNama} berhasil dihapus. Peserta dapat mengulang tes.");
    }

    /**
     * Ekspor hasil ke Excel
     */
    public function ekspor(Tes $tes)
    {
        $data = $this->penilaianService->eksporHasil($tes);
        $statistik = $this->penilaianService->ambilStatistikTes($tes);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hasil Ujian');

        // Header info tes
        $sheet->setCellValue('A1', 'Hasil Ujian: ' . $tes->nama);
        $sheet->setCellValue('A2', 'Tanggal Ekspor: ' . now()->format('d/m/Y H:i'));
        $sheet->setCellValue('A3', 'Nilai Lulus: ' . $tes->nilai_lulus);
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        // Statistik
        $sheet->setCellValue('A5', 'STATISTIK');
        $sheet->setCellValue('A6', 'Total Peserta');
        $sheet->setCellValue('B6', $statistik['total_peserta']);
        $sheet->setCellValue('A7', 'Rata-rata');
        $sheet->setCellValue('B7', $statistik['rata_rata']);
        $sheet->setCellValue('A8', 'Nilai Tertinggi');
        $sheet->setCellValue('B8', $statistik['nilai_tertinggi']);
        $sheet->setCellValue('A9', 'Nilai Terendah');
        $sheet->setCellValue('B9', $statistik['nilai_terendah']);
        $sheet->setCellValue('A10', 'Jumlah Lulus');
        $sheet->setCellValue('B10', $statistik['jumlah_lulus']);
        $sheet->setCellValue('A11', 'Persentase Lulus');
        $sheet->setCellValue('B11', $statistik['persentase_lulus'] . '%');

        // Header data
        $row = 14;
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $col++;
            }

            // Data
            $row++;
            foreach ($data as $item) {
                $col = 'A';
                foreach ($item as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style header
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $sheet->getStyle('A14:I14')->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);
        $filename = 'hasil-ujian-' . str_replace(' ', '-', strtolower($tes->nama)) . '-' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Ekspor rekap semua peserta ke Excel dengan sheet per peserta
     */
    public function eksporRekap()
    {
        // Ambil semua tes yang punya hasil
        $tesList = Tes::withCount(['sesiTes as peserta_selesai' => function ($q) {
            $q->whereIn('status', ['selesai', 'timeout']);
        }])
        ->having('peserta_selesai', '>', 0)
        ->orderBy('created_at', 'asc')
        ->get();

        // Ambil semua sesi tes yang selesai
        $sesiList = SesiTes::whereIn('status', ['selesai', 'timeout'])
            ->with(['peserta:id,nama,nomor_pendaftaran,email,telepon', 'tes:id,nama,nilai_lulus', 'jawabanPeserta'])
            ->get();

        // Group by peserta
        $rekapPeserta = [];
        foreach ($sesiList as $sesi) {
            $pesertaId = $sesi->peserta_id;
            if (!isset($rekapPeserta[$pesertaId])) {
                $rekapPeserta[$pesertaId] = [
                    'peserta' => $sesi->peserta,
                    'sesi_list' => collect(),
                    'hasil' => [],
                ];
            }
            $rekapPeserta[$pesertaId]['sesi_list']->push($sesi);

            // Cek jenis tes
            $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $sesi->tes_id)->exists();
            $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $sesi->tes_id)->first();
            $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
            $isMbti = \App\Models\MbtiConfig::where('tes_id', $sesi->tes_id)->exists();
            $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $sesi->tes_id)->first();
            $isProfiling = $profilingConfig && $profilingConfig->aktif;

            $hasilKepribadian = null;
            $hasilGayaBelajar = null;
            $hasilMbti = null;
            $pilarDominan = null;
            $detailProfiling = null;
            $detailMbti = null;

            if ($isPsikotes) {
                $hasilPsikotes = \App\Models\HasilPsikotesKepribadian::where('sesi_tes_id', $sesi->id)->first();
                $hasilKepribadian = $hasilPsikotes?->hasil_kepribadian;
            }
            if ($isGayaBelajar) {
                $hasilGB = \App\Models\HasilGayaBelajar::where('sesi_tes_id', $sesi->id)->first();
                $hasilGayaBelajar = $hasilGB?->hasil_gaya_belajar;
            }
            if ($isMbti) {
                $hasilMbtiData = \App\Models\HasilMbti::where('sesi_tes_id', $sesi->id)->first();
                $hasilMbti = $hasilMbtiData?->tipe_mbti;
                if ($hasilMbtiData) {
                    $detailMbti = [
                        'E' => $hasilMbtiData->skor_e,
                        'I' => $hasilMbtiData->skor_i,
                        'S' => $hasilMbtiData->skor_s,
                        'N' => $hasilMbtiData->skor_n,
                        'T' => $hasilMbtiData->skor_t,
                        'F' => $hasilMbtiData->skor_f,
                        'J' => $hasilMbtiData->skor_j,
                        'P' => $hasilMbtiData->skor_p,
                    ];
                }
            }
            if ($isProfiling) {
                $hasilProfilingData = \App\Models\HasilProfiling::where('sesi_tes_id', $sesi->id)->first();
                $pilarDominan = $hasilProfilingData?->pilar_dominan;
                if ($hasilProfilingData) {
                    $detailProfiling = [
                        'CQ' => $hasilProfilingData->skor_kreatif,
                        'EQ' => $hasilProfilingData->skor_emosional,
                        'AQ' => $hasilProfilingData->skor_aksi,
                        'IQ' => $hasilProfilingData->skor_logika,
                        'SQ' => $hasilProfilingData->skor_spiritual,
                    ];
                }
            }

            $rekapPeserta[$pesertaId]['hasil'][$sesi->tes_id] = [
                'sesi' => $sesi,
                'nilai' => $sesi->nilai,
                'lulus' => $sesi->nilai >= $sesi->tes->nilai_lulus,
                'is_psikotes' => $isPsikotes,
                'hasil_kepribadian' => $hasilKepribadian,
                'is_gaya_belajar' => $isGayaBelajar,
                'hasil_gaya_belajar' => $hasilGayaBelajar,
                'is_mbti' => $isMbti,
                'hasil_mbti' => $hasilMbti,
                'detail_mbti' => $detailMbti,
                'is_profiling' => $isProfiling,
                'pilar_dominan' => $pilarDominan,
                'detail_profiling' => $detailProfiling,
            ];
        }

        // Sort by nama peserta
        uasort($rekapPeserta, function ($a, $b) {
            return strcmp($a['peserta']->nama ?? '', $b['peserta']->nama ?? '');
        });

        $spreadsheet = new Spreadsheet();
        $pilarList = \App\Models\ProfilingConfig::pilarList();

        // ========== SHEET 1: REKAP SEMUA PESERTA ==========
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Semua Peserta');

        // Header
        $sheet->setCellValue('A1', 'REKAP HASIL UJIAN SEMUA PESERTA');
        $sheet->setCellValue('A2', 'Tanggal Ekspor: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A1:' . $this->getColumnLetter(3 + $tesList->count()) . '1');
        $sheet->mergeCells('A2:' . $this->getColumnLetter(3 + $tesList->count()) . '2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header tabel
        $row = 4;
        $sheet->setCellValue('A' . $row, 'No');
        $sheet->setCellValue('B' . $row, 'Nama Peserta');
        $sheet->setCellValue('C' . $row, 'No. Pendaftaran');

        $col = 'D';
        foreach ($tesList as $tes) {
            $sheet->setCellValue($col . $row, $tes->nama);
            $col++;
        }

        // Style header
        $headerRange = 'A' . $row . ':' . $this->getColumnLetter(3 + $tesList->count()) . $row;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4CAF50');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');

        // Data peserta
        $row++;
        $no = 1;
        foreach ($rekapPeserta as $data) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $data['peserta']->nama ?? '-');
            $sheet->setCellValue('C' . $row, $data['peserta']->nomor_pendaftaran ?? '-');

            $col = 'D';
            foreach ($tesList as $tes) {
                if (isset($data['hasil'][$tes->id])) {
                    $hasil = $data['hasil'][$tes->id];
                    if ($hasil['is_mbti'] && $hasil['hasil_mbti']) {
                        $sheet->setCellValue($col . $row, $hasil['hasil_mbti']);
                    } elseif ($hasil['is_profiling'] && $hasil['pilar_dominan']) {
                        $sheet->setCellValue($col . $row, $pilarList[$hasil['pilar_dominan']]['kode_qx'] ?? ucfirst($hasil['pilar_dominan']));
                    } elseif ($hasil['is_gaya_belajar'] && $hasil['hasil_gaya_belajar']) {
                        $sheet->setCellValue($col . $row, ucfirst($hasil['hasil_gaya_belajar']));
                    } elseif ($hasil['is_psikotes'] && $hasil['hasil_kepribadian']) {
                        $sheet->setCellValue($col . $row, ucfirst($hasil['hasil_kepribadian']));
                    } else {
                        $sheet->setCellValue($col . $row, number_format($hasil['nilai'], 1));
                        // Warna berdasarkan lulus/tidak
                        if ($hasil['lulus']) {
                            $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('28A745');
                        } else {
                            $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('DC3545');
                        }
                    }
                } else {
                    $sheet->setCellValue($col . $row, '-');
                }
                $col++;
            }
            $row++;
            $no++;
        }

        // Auto-size columns
        foreach (range('A', $this->getColumnLetter(3 + $tesList->count())) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ========== SHEET PER PESERTA ==========
        $sheetIndex = 1;
        foreach ($rekapPeserta as $pesertaId => $data) {
            $peserta = $data['peserta'];
            $sesiListPeserta = $data['sesi_list'];

            // Buat sheet baru
            $sheetPeserta = $spreadsheet->createSheet($sheetIndex);
            // Nama sheet max 31 karakter
            $sheetName = substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $peserta->nama ?? 'Peserta'), 0, 25) . ' (' . $pesertaId . ')';
            $sheetPeserta->setTitle($sheetName);

            // Info Peserta
            $sheetPeserta->setCellValue('A1', 'DETAIL HASIL UJIAN PESERTA');
            $sheetPeserta->mergeCells('A1:G1');
            $sheetPeserta->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $sheetPeserta->setCellValue('A3', 'Nama Lengkap');
            $sheetPeserta->setCellValue('B3', $peserta->nama ?? '-');
            $sheetPeserta->setCellValue('A4', 'No. Pendaftaran');
            $sheetPeserta->setCellValue('B4', $peserta->nomor_pendaftaran ?? '-');
            $sheetPeserta->setCellValue('A5', 'Email');
            $sheetPeserta->setCellValue('B5', $peserta->email ?? '-');
            $sheetPeserta->setCellValue('A6', 'No. HP');
            $sheetPeserta->setCellValue('B6', $peserta->telepon ?? '-');
            $sheetPeserta->setCellValue('A7', 'Total Tes');
            $sheetPeserta->setCellValue('B7', $sesiListPeserta->count() . ' tes');

            $sheetPeserta->getStyle('A3:A7')->getFont()->setBold(true);

            // Header tabel hasil
            $row = 9;
            $sheetPeserta->setCellValue('A' . $row, 'No');
            $sheetPeserta->setCellValue('B' . $row, 'Nama Tes');
            $sheetPeserta->setCellValue('C' . $row, 'Jenis');
            $sheetPeserta->setCellValue('D' . $row, 'Hasil/Nilai');
            $sheetPeserta->setCellValue('E' . $row, 'Status');
            $sheetPeserta->setCellValue('F' . $row, 'Benar/Total');
            $sheetPeserta->setCellValue('G' . $row, 'Peringatan');
            $sheetPeserta->setCellValue('H' . $row, 'Waktu Mulai');
            $sheetPeserta->setCellValue('I' . $row, 'Waktu Selesai');
            $sheetPeserta->setCellValue('J' . $row, 'Durasi (menit)');
            $sheetPeserta->setCellValue('K' . $row, 'Detail Skor');

            $headerRange = 'A' . $row . ':K' . $row;
            $sheetPeserta->getStyle($headerRange)->getFont()->setBold(true);
            $sheetPeserta->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('2196F3');
            $sheetPeserta->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');

            // Data hasil
            $row++;
            $no = 1;
            foreach ($data['hasil'] as $tesId => $hasil) {
                $sesi = $hasil['sesi'];
                $tes = $sesi->tes;

                $sheetPeserta->setCellValue('A' . $row, $no);
                $sheetPeserta->setCellValue('B' . $row, $tes->nama);

                // Jenis tes
                $jenisTes = 'Akademik';
                if ($hasil['is_mbti']) $jenisTes = 'MBTI';
                elseif ($hasil['is_profiling']) $jenisTes = 'Profiling';
                elseif ($hasil['is_gaya_belajar']) $jenisTes = 'Gaya Belajar';
                elseif ($hasil['is_psikotes']) $jenisTes = 'Psikotes';
                $sheetPeserta->setCellValue('C' . $row, $jenisTes);

                // Hasil/Nilai
                if ($hasil['is_mbti'] && $hasil['hasil_mbti']) {
                    $sheetPeserta->setCellValue('D' . $row, $hasil['hasil_mbti']);
                } elseif ($hasil['is_profiling'] && $hasil['pilar_dominan']) {
                    $pilarNama = $pilarList[$hasil['pilar_dominan']]['nama'] ?? ucfirst($hasil['pilar_dominan']);
                    $pilarKode = $pilarList[$hasil['pilar_dominan']]['kode_qx'] ?? '';
                    $sheetPeserta->setCellValue('D' . $row, $pilarNama . ' (' . $pilarKode . ')');
                } elseif ($hasil['is_gaya_belajar'] && $hasil['hasil_gaya_belajar']) {
                    $sheetPeserta->setCellValue('D' . $row, ucfirst($hasil['hasil_gaya_belajar']));
                } elseif ($hasil['is_psikotes'] && $hasil['hasil_kepribadian']) {
                    $sheetPeserta->setCellValue('D' . $row, ucfirst($hasil['hasil_kepribadian']));
                } else {
                    $sheetPeserta->setCellValue('D' . $row, number_format($hasil['nilai'], 1));
                }

                // Status
                if ($hasil['is_mbti'] || $hasil['is_profiling'] || $hasil['is_gaya_belajar'] || $hasil['is_psikotes']) {
                    $sheetPeserta->setCellValue('E' . $row, 'Selesai');
                } else {
                    $status = $hasil['lulus'] ? 'LULUS' : 'TIDAK LULUS';
                    $sheetPeserta->setCellValue('E' . $row, $status);
                    if ($hasil['lulus']) {
                        $sheetPeserta->getStyle('E' . $row)->getFont()->getColor()->setRGB('28A745');
                    } else {
                        $sheetPeserta->getStyle('E' . $row)->getFont()->getColor()->setRGB('DC3545');
                    }
                }

                // Benar/Total
                $totalSoal = $sesi->jawabanPeserta->count();
                $benar = $sesi->jawabanPeserta->where('benar', true)->count();
                $sheetPeserta->setCellValue('F' . $row, $benar . '/' . $totalSoal);

                // Peringatan
                $peringatan = $sesi->jumlah_peringatan ?? 0;
                $sheetPeserta->setCellValue('G' . $row, $peringatan > 0 ? $peringatan . 'x' : '0');
                if ($peringatan > 0) {
                    $sheetPeserta->getStyle('G' . $row)->getFont()->getColor()->setRGB('DC3545');
                    $sheetPeserta->getStyle('G' . $row)->getFont()->setBold(true);
                }

                // Waktu
                $sheetPeserta->setCellValue('H' . $row, $sesi->waktu_mulai?->format('d/m/Y H:i') ?? '-');
                $sheetPeserta->setCellValue('I' . $row, $sesi->waktu_selesai?->format('d/m/Y H:i') ?? '-');

                // Durasi
                if ($sesi->waktu_mulai && $sesi->waktu_selesai) {
                    $sheetPeserta->setCellValue('J' . $row, $sesi->durasi_menit_bulat);
                } else {
                    $sheetPeserta->setCellValue('J' . $row, '-');
                }

                // Detail Skor
                $detailSkor = '';
                if ($hasil['is_mbti'] && $hasil['detail_mbti']) {
                    $detailSkor = implode(', ', array_map(fn($k, $v) => "$k:$v", array_keys($hasil['detail_mbti']), $hasil['detail_mbti']));
                } elseif ($hasil['is_profiling'] && $hasil['detail_profiling']) {
                    $detailSkor = implode(', ', array_map(fn($k, $v) => "$k:$v", array_keys($hasil['detail_profiling']), $hasil['detail_profiling']));
                }
                $sheetPeserta->setCellValue('K' . $row, $detailSkor);

                $row++;
                $no++;
            }

            // Auto-size columns
            foreach (range('A', 'K') as $col) {
                $sheetPeserta->getColumnDimension($col)->setAutoSize(true);
            }

            $sheetIndex++;
        }

        // Set active sheet ke sheet pertama
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = 'rekap-hasil-ujian-semua-peserta-' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Helper untuk mendapatkan huruf kolom Excel
     */
    private function getColumnLetter($columnNumber)
    {
        $letter = '';
        while ($columnNumber > 0) {
            $temp = ($columnNumber - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $columnNumber = (int)(($columnNumber - $temp - 1) / 26);
        }
        return $letter;
    }
}
