<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\SesiTes;
use App\Services\MonitoringUjianService;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Controller untuk monitoring ujian
 * Kebutuhan: 7.1, 7.2, 7.3, 7.4
 */
class MonitoringUjianController extends Controller
{
    public function __construct(
        private MonitoringUjianService $monitoringService
    ) {}

    /**
     * Dashboard monitoring
     */
    public function index()
    {
        $statistik = $this->monitoringService->ambilStatistikDashboard();
        $tesAktif = $this->monitoringService->ambilTesAktif();
        $grafikAktivitas = $this->monitoringService->ambilGrafikAktivitas(Carbon::today());

        return view('admin.monitoring-ujian.index', compact('statistik', 'tesAktif', 'grafikAktivitas'));
    }

    /**
     * Monitoring semua peserta dengan sesi tes mereka
     */
    public function semuaPeserta(Request $request)
    {
        $query = \App\Models\Peserta::with(['tahapanSpmb'])
            ->withCount('sesiTes as total_sesi')
            ->withCount(['sesiTes as sesi_selesai' => function ($q) {
                $q->whereIn('status', ['selesai', 'timeout']);
            }])
            ->withCount(['sesiTes as sesi_berlangsung' => function ($q) {
                $q->where('status', 'berlangsung');
            }]);

        // Search
        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$cari}%");
            });
        }

        // Filter status tes
        if ($request->filled('status_tes')) {
            if ($request->status_tes === 'belum_tes') {
                $query->doesntHave('sesiTes');
            } elseif ($request->status_tes === 'berlangsung') {
                $query->whereHas('sesiTes', fn($q) => $q->where('status', 'berlangsung'));
            } elseif ($request->status_tes === 'selesai') {
                $query->whereHas('sesiTes', fn($q) => $q->whereIn('status', ['selesai', 'timeout']));
            }
        }

        $pesertaList = $query->orderBy('nama')->paginate(20)->withQueryString();

        // Get sesi tes for displayed peserta
        $pesertaIds = $pesertaList->pluck('id');
        $sesiTesAll = SesiTes::with([
                'tes',
                'hasilGayaBelajar',
                'hasilPsikotesKepribadian',
                'hasilMbti',
                'hasilProfiling',
            ])
            ->whereIn('peserta_id', $pesertaIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('peserta_id');

        // Get all available tes
        $semuaTes = \App\Models\Tes::orderBy('nama')->get();
        $tesIds = $semuaTes->pluck('id');
        $jenisTes = [
            'psikotes' => \App\Models\PsikotesKepribadianConfig::whereIn('tes_id', $tesIds)->distinct()->pluck('tes_id')->flip(),
            'gaya_belajar' => \App\Models\GayaBelajarConfig::whereIn('tes_id', $tesIds)->where('aktif', true)->pluck('tes_id')->flip(),
            'mbti' => \App\Models\MbtiConfig::whereIn('tes_id', $tesIds)->distinct()->pluck('tes_id')->flip(),
            'profiling' => \App\Models\ProfilingConfig::whereIn('tes_id', $tesIds)->where('aktif', true)->pluck('tes_id')->flip(),
        ];
        $pilarList = \App\Models\ProfilingConfig::pilarList();

        // Stats
        $totalPeserta = \App\Models\Peserta::count();
        $sudahTes = SesiTes::distinct('peserta_id')->count('peserta_id');
        $sedangTes = SesiTes::where('status', 'berlangsung')->count();
        $selesaiTes = SesiTes::whereIn('status', ['selesai', 'timeout'])->distinct('peserta_id')->count('peserta_id');

        $stats = compact('totalPeserta', 'sudahTes', 'sedangTes', 'selesaiTes');

        return view('admin.monitoring-ujian.semua-peserta', compact('pesertaList', 'sesiTesAll', 'semuaTes', 'jenisTes', 'pilarList', 'stats'));
    }

    /**
     * Monitoring tes tertentu
     */
    public function show(Tes $tes)
    {
        $pesertaOnline = $this->monitoringService->ambilPesertaOnline($tes);
        $statistik = $this->monitoringService->ambilStatistikTes($tes);

        return view('admin.monitoring-ujian.show', compact('tes', 'pesertaOnline', 'statistik'));
    }

    /**
     * API: Ambil peserta online (untuk refresh AJAX)
     */
    public function pesertaOnline(Tes $tes)
    {
        $pesertaOnline = $this->monitoringService->ambilPesertaOnline($tes);

        return response()->json([
            'peserta' => $pesertaOnline,
            'total' => $pesertaOnline->count(),
        ]);
    }

    /**
     * Riwayat sesi tes
     */
    public function riwayat(Tes $tes, Request $request)
    {
        $filter = [
            'status' => $request->status,
            'tanggal' => $request->tanggal,
        ];

        $riwayat = $this->monitoringService->ambilRiwayatSesi($tes, $filter);

        return view('admin.monitoring-ujian.riwayat', compact('tes', 'riwayat'));
    }

    /**
     * Perpanjang waktu sesi
     */
    public function perpanjangWaktu(Request $request, SesiTes $sesi)
    {
        $request->validate([
            'menit' => 'required|integer|min:1|max:120',
        ]);

        try {
            $this->monitoringService->perpanjangWaktu($sesi, $request->menit);
            return back()->with('success', "Waktu berhasil diperpanjang {$request->menit} menit.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reset sesi peserta
     */
    public function resetSesi(SesiTes $sesi)
    {
        try {
            $this->monitoringService->resetSesi($sesi);
            return back()->with('success', 'Sesi berhasil direset.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Paksa selesaikan sesi
     */
    public function paksaSelesai(SesiTes $sesi)
    {
        try {
            $this->monitoringService->paksaSelesai($sesi);
            return back()->with('success', 'Sesi berhasil diselesaikan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Batalkan sesi
     */
    public function batalkanSesi(Request $request, SesiTes $sesi)
    {
        try {
            $this->monitoringService->batalkanSesi($sesi, $request->alasan ?? '');
            return back()->with('success', 'Sesi berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Statistik per grup
     */
    public function statistikGrup(Tes $tes)
    {
        $statistikGrup = $this->monitoringService->ambilStatistikPerGrup($tes);

        return view('admin.monitoring-ujian.statistik-grup', compact('tes', 'statistikGrup'));
    }

    /**
     * Paksa selesaikan tes untuk peserta yang belum mengerjakan
     * Membuat sesi tes langsung dengan status selesai
     */
    public function paksaSelesaiTanpaSesi(Request $request)
    {
        $request->validate([
            'peserta_id' => 'required|exists:peserta,id',
            'tes_id' => 'required|exists:tes,id',
        ]);

        $peserta = \App\Models\Peserta::findOrFail($request->peserta_id);
        $tes = Tes::findOrFail($request->tes_id);

        // Check if sesi already exists
        $existingSesi = SesiTes::where('peserta_id', $peserta->id)
            ->where('tes_id', $tes->id)
            ->first();

        if ($existingSesi) {
            return back()->with('error', "Peserta {$peserta->nama} sudah memiliki sesi untuk tes '{$tes->nama}'.");
        }

        // Create completed sesi
        SesiTes::create([
            'tes_id' => $tes->id,
            'peserta_id' => $peserta->id,
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
            'nilai' => $tes->nilai_lulus, // Set to passing score
            'status' => 'selesai',
            'status_verifikasi_tes' => 'diloloskan',
            'catatan_verifikasi' => 'Diselesaikan oleh admin tanpa tes',
            'diverifikasi_oleh' => auth()->id(),
            'diverifikasi_pada' => now(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Tes '{$tes->nama}' berhasil diselesaikan untuk {$peserta->nama}.");
    }
}
