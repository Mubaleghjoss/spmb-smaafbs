<?php

namespace App\Http\Controllers;

use App\Models\Tes;
use App\Models\Token;
use App\Models\Peserta;
use App\Models\SesiTes;
use App\Models\TokenGlobal;
use App\Services\UjianService;
use App\Services\TokenService;
use App\Services\TesService;
use App\Services\TokenGlobalService;
use App\Services\PengaturanService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Controller untuk pelaksanaan ujian
 * Kebutuhan: 5.1-5.5
 */
class UjianController extends Controller
{
    public function __construct(
        private UjianService $ujianService,
        private TokenService $tokenService,
        private TesService $tesService,
        private TokenGlobalService $tokenGlobalService,
        private PengaturanService $pengaturanService
    ) {}

    /**
     * Ambil peserta dari session
     */
    private function getPeserta(): ?Peserta
    {
        $pesertaId = session('peserta_id');
        if (!$pesertaId) {
            return null;
        }
        return Peserta::find($pesertaId);
    }

    /**
     * Cek kepemilikan sesi. Jika browser masih menyimpan session peserta lain,
     * arahkan login ulang agar tidak berakhir di halaman 403 mentah.
     */
    private function sesiMilikPeserta(SesiTes $sesi, ?Peserta $peserta): bool
    {
        return $peserta && (int) $sesi->peserta_id === (int) $peserta->id;
    }

    private function lupakanSessionPeserta(): void
    {
        session()->forget([
            'peserta_id',
            'peserta_nama',
            'peserta_nomor',
            'token_id',
            'tes_id',
            'token_global_id',
            'ujian_mode',
        ]);
    }

    private function redirectSessionPesertaTidakSesuai(): RedirectResponse
    {
        $this->lupakanSessionPeserta();

        return redirect()->route('peserta.login')
            ->with('error', 'Sesi ujian tidak sesuai dengan akun peserta yang sedang login. Silakan login ulang sebagai peserta yang benar.');
    }

    private function responseSessionPesertaTidakSesuai(): JsonResponse
    {
        $this->lupakanSessionPeserta();

        return response()->json([
            'error' => 'Sesi ujian tidak sesuai dengan akun peserta yang sedang login. Silakan login ulang.',
            'redirect' => route('peserta.login'),
        ], 403);
    }

    /**
     * Halaman daftar tes yang tersedia
     * Kebutuhan: 3.1, 3.3 - Filter tes berdasarkan grup peserta
     */
    public function index(): View
    {
        $peserta = $this->getPeserta();
        $aksesUjian = $this->pengaturanService->statusAksesUjian();
        
        // Cek apakah login dengan token global
        $tokenGlobalId = session('token_global_id');
        
        if ($tokenGlobalId) {
            // Jika login dengan token global, filter tes berdasarkan token
            $tokenGlobal = TokenGlobal::find($tokenGlobalId);
            if ($tokenGlobal && $tokenGlobal->masihValid()) {
                $tesAktif = $this->tokenGlobalService->ambilTesDenganToken($tokenGlobal);
            } else {
                // Token global tidak valid lagi, ambil tes berdasarkan grup
                $tesAktif = $this->tesService->ambilTesTersediaUntukPeserta($peserta);
            }
        } else {
            // Ambil tes yang tersedia untuk peserta berdasarkan grup
            $tesAktif = $this->tesService->ambilTesTersediaUntukPeserta($peserta);
        }

        // Cek status akses untuk setiap tes
        $tesDenganStatus = $tesAktif->map(function ($tes) use ($peserta, $aksesUjian) {
            $akses = $this->ujianService->bisaMengaksesTes($peserta, $tes);
            $sesiAktif = $this->ujianService->ambilSesiAktif($peserta, $tes);
            
            // Ambil sesi selesai untuk info status
            $sesiSelesai = SesiTes::where('peserta_id', $peserta->id)
                ->where('tes_id', $tes->id)
                ->whereIn('status', ['selesai', 'timeout'])
                ->first();
            
            // Cek apakah ini psikotes atau gaya belajar
            $isPsikotes = \App\Models\PsikotesKepribadianConfig::where('tes_id', $tes->id)->exists();
            $gayaBelajarConfig = \App\Models\GayaBelajarConfig::where('tes_id', $tes->id)->first();
            $isGayaBelajar = $gayaBelajarConfig && $gayaBelajarConfig->aktif;
            $isMbti = \App\Models\MbtiConfig::where('tes_id', $tes->id)->exists();
            $profilingConfig = \App\Models\ProfilingConfig::where('tes_id', $tes->id)->first();
            $isProfiling = $profilingConfig && $profilingConfig->aktif;
            
            $status = null;
            if ($sesiSelesai) {
                if ($isMbti) {
                    // MBTI selalu dianggap selesai (tidak ada lulus/tidak lulus)
                    $status = 'selesai_mbti';
                } elseif ($isProfiling) {
                    // Profiling selalu dianggap selesai (tidak ada lulus/tidak lulus)
                    $status = 'selesai_profiling';
                } elseif ($isGayaBelajar) {
                    // Gaya belajar selalu dianggap selesai (tidak ada lulus/tidak lulus)
                    $status = 'selesai_gaya_belajar';
                } elseif ($isPsikotes) {
                    // Psikotes selalu dianggap selesai (tidak ada lulus/tidak lulus)
                    $status = 'selesai_psikotes';
                } else {
                    $lulus = $sesiSelesai->nilai >= $tes->nilai_lulus;
                    if ($lulus) {
                        $status = 'lulus';
                    } elseif ($sesiSelesai->status_verifikasi_tes === 'diloloskan') {
                        $status = 'diloloskan';
                    } elseif ($sesiSelesai->status_verifikasi_tes === 'menunggu') {
                        $status = 'menunggu';
                    }
                }
            }

            if (!$aksesUjian['dibuka'] && !$sesiAktif && !$sesiSelesai) {
                $akses = [
                    'bisa' => false,
                    'pesan' => $aksesUjian['alasan'] ?? 'Tes online belum dibuka.',
                ];
            }
            
            return [
                'tes' => $tes,
                'bisa_akses' => $akses['bisa'],
                'pesan' => $akses['pesan'],
                'sesi_aktif' => $sesiAktif,
                'sesi_selesai' => $sesiSelesai,
                'status' => $status,
                'is_psikotes' => $isPsikotes,
                'is_gaya_belajar' => $isGayaBelajar,
                'is_mbti' => $isMbti,
                'is_profiling' => $isProfiling,
            ];
        });

        return view('ujian.index', [
            'daftarTes' => $tesDenganStatus,
            'aksesUjian' => $aksesUjian,
        ]);
    }

    /**
     * Halaman konfirmasi sebelum mulai ujian
     * Kebutuhan: 3.2 - Cek akses berdasarkan grup
     */
    public function konfirmasi(Tes $tes): View|RedirectResponse
    {
        $peserta = $this->getPeserta();
        $aksesUjian = $this->pengaturanService->statusAksesUjian();

        if (!$aksesUjian['dibuka']) {
            return redirect()->route('ujian.index')
                ->with('error', $aksesUjian['alasan'] ?? 'Tes online belum dibuka.');
        }
        
        // Cek akses berdasarkan grup
        if (!$this->tesService->cekAksesPeserta($tes, $peserta)) {
            return redirect()->route('ujian.index')
                ->with('error', 'Anda tidak memiliki akses ke tes ini.');
        }
        
        // Cek akses lainnya (status tes, sudah pernah dikerjakan)
        $akses = $this->ujianService->bisaMengaksesTes($peserta, $tes);
        if (!$akses['bisa']) {
            return redirect()->route('ujian.index')
                ->with('error', $akses['pesan']);
        }

        // Cek apakah ada sesi aktif
        $sesiAktif = $this->ujianService->ambilSesiAktif($peserta, $tes);
        if ($sesiAktif) {
            return redirect()->route('ujian.kerjakan', $sesiAktif);
        }

        return view('ujian.konfirmasi', [
            'tes' => $tes,
        ]);
    }

    /**
     * Mulai ujian
     * Kebutuhan: 3.2 - Cek akses berdasarkan grup
     */
    public function mulai(Request $request, Tes $tes): RedirectResponse
    {
        $peserta = $this->getPeserta();
        $aksesUjian = $this->pengaturanService->statusAksesUjian();

        if (!$aksesUjian['dibuka']) {
            return redirect()->route('ujian.index')
                ->with('error', $aksesUjian['alasan'] ?? 'Tes online belum dibuka.');
        }

        // Cek akses berdasarkan grup
        if (!$this->tesService->cekAksesPeserta($tes, $peserta)) {
            return redirect()->route('ujian.index')
                ->with('error', 'Anda tidak memiliki akses ke tes ini.');
        }

        // Cek akses lainnya
        $akses = $this->ujianService->bisaMengaksesTes($peserta, $tes);
        if (!$akses['bisa']) {
            return redirect()->route('ujian.index')
                ->with('error', $akses['pesan']);
        }

        try {
            $sesi = $this->ujianService->mulaiSesi($tes, $peserta, null, $request);
            return redirect()->route('ujian.kerjakan', $sesi);
        } catch (\Exception $e) {
            return redirect()->route('ujian.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Halaman mengerjakan ujian
     */
    public function kerjakan(Request $request, SesiTes $sesi): View|RedirectResponse
    {
        $peserta = $this->getPeserta();

        // Validasi kepemilikan sesi
        if (!$this->sesiMilikPeserta($sesi, $peserta)) {
            return $this->redirectSessionPesertaTidakSesuai();
        }

        // Cek apakah sesi sudah selesai
        if ($sesi->sudahSelesai()) {
            return redirect()->route('ujian.hasil', $sesi);
        }

        // Cek waktu
        if ($sesi->waktuHabis()) {
            $this->ujianService->selesaikanSesi($sesi, 'timeout');
            return redirect()->route('ujian.hasil', $sesi)
                ->with('info', 'Waktu ujian telah habis.');
        }

        // Ambil nomor soal dari request atau posisi terakhir
        $nomorSoal = $request->get('nomor', $sesi->soal_saat_ini);
        
        // Ambil data soal
        $dataSoal = $this->ujianService->ambilSoal($sesi, $nomorSoal);
        if (!$dataSoal) {
            $dataSoal = $this->ujianService->ambilSoal($sesi, 1);
            $nomorSoal = 1;
        }

        // Update posisi soal
        $this->ujianService->updatePosisiSoal($sesi, $nomorSoal);

        // Ambil ringkasan untuk navigasi
        $ringkasan = $this->ujianService->ambilRingkasanJawaban($sesi);
        $statistik = $this->ujianService->ambilStatistikSesi($sesi);

        return view('ujian.kerjakan', [
            'sesi' => $sesi,
            'tes' => $sesi->tes,
            'dataSoal' => $dataSoal,
            'ringkasan' => $ringkasan,
            'statistik' => $statistik,
        ]);
    }

    /**
     * Simpan jawaban (AJAX)
     */
    public function simpanJawaban(Request $request, SesiTes $sesi): JsonResponse
    {
        $peserta = $this->getPeserta();

        // Validasi kepemilikan sesi
        if (!$this->sesiMilikPeserta($sesi, $peserta)) {
            return $this->responseSessionPesertaTidakSesuai();
        }

        // Validasi sesi masih berlangsung
        if ($sesi->sudahSelesai()) {
            return response()->json(['error' => 'Sesi sudah berakhir.'], 400);
        }

        $validated = $request->validate([
            'soal_id' => 'required|exists:soal,id',
            'jawaban_id' => 'nullable|exists:jawaban,id',
            'jawaban_ganda' => 'nullable|array',
            'jawaban_ganda.*' => 'exists:jawaban,id',
            'jawaban_esai' => 'nullable|string',
            'ragu' => 'boolean',
        ]);

        try {
            $jawaban = $this->ujianService->simpanJawaban($sesi, $validated['soal_id'], $validated);
            
            return response()->json([
                'sukses' => true,
                'jawaban' => $jawaban,
                'statistik' => $this->ujianService->ambilStatistikSesi($sesi),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Selesaikan ujian
     */
    public function selesai(Request $request, SesiTes $sesi): RedirectResponse
    {
        $peserta = $this->getPeserta();

        // Validasi kepemilikan sesi
        if (!$this->sesiMilikPeserta($sesi, $peserta)) {
            return $this->redirectSessionPesertaTidakSesuai();
        }

        // Konfirmasi dari user
        if (!$request->has('konfirmasi')) {
            return back()->with('error', 'Konfirmasi diperlukan untuk menyelesaikan ujian.');
        }

        try {
            $this->ujianService->selesaikanSesi($sesi);
            return redirect()->route('ujian.hasil', $sesi)
                ->with('sukses', 'Ujian berhasil diselesaikan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Halaman hasil ujian
     */
    public function hasil(SesiTes $sesi): View|RedirectResponse
    {
        $peserta = $this->getPeserta();

        // Validasi kepemilikan sesi
        if (!$this->sesiMilikPeserta($sesi, $peserta)) {
            return $this->redirectSessionPesertaTidakSesuai();
        }

        // Cek apakah sesi sudah selesai
        if (!$sesi->sudahSelesai()) {
            return redirect()->route('ujian.kerjakan', $sesi);
        }

        $hasil = $this->ujianService->ambilHasil($sesi);

        return view('ujian.hasil', [
            'hasil' => $hasil,
            'tes' => $sesi->tes,
        ]);
    }

    /**
     * Ambil waktu tersisa (AJAX untuk timer)
     */
    public function waktuTersisa(SesiTes $sesi): JsonResponse
    {
        $peserta = $this->getPeserta();

        if (!$this->sesiMilikPeserta($sesi, $peserta)) {
            return $this->responseSessionPesertaTidakSesuai();
        }

        $waktuTersisa = $sesi->waktuTersisa();

        // Auto-submit jika waktu habis
        if ($waktuTersisa <= 0 && !$sesi->sudahSelesai()) {
            $this->ujianService->selesaikanSesi($sesi, 'timeout');
            return response()->json([
                'waktu_tersisa' => 0,
                'selesai' => true,
                'redirect' => route('ujian.hasil', $sesi),
            ]);
        }

        return response()->json([
            'waktu_tersisa' => $waktuTersisa,
            'selesai' => false,
        ]);
    }

    /**
     * Pulihkan sesi yang terputus
     */
    public function pulihkan(): RedirectResponse
    {
        $peserta = $this->getPeserta();
        
        $sesi = $this->ujianService->pulihkanSesi($peserta);
        
        if ($sesi) {
            return redirect()->route('ujian.kerjakan', $sesi)
                ->with('info', 'Sesi ujian Anda telah dipulihkan.');
        }

        return redirect()->route('ujian.index')
            ->with('info', 'Tidak ada sesi ujian yang perlu dipulihkan.');
    }

    /**
     * Catat peringatan anti-cheat (AJAX)
     */
    public function catatPeringatan(SesiTes $sesi): JsonResponse
    {
        $peserta = $this->getPeserta();

        // Validasi kepemilikan sesi
        if (!$this->sesiMilikPeserta($sesi, $peserta)) {
            return $this->responseSessionPesertaTidakSesuai();
        }

        // Validasi sesi masih berlangsung
        if ($sesi->sudahSelesai()) {
            return response()->json(['error' => 'Sesi sudah berakhir.'], 400);
        }

        // Increment jumlah peringatan
        $sesi->increment('jumlah_peringatan');

        return response()->json([
            'sukses' => true,
            'jumlah_peringatan' => $sesi->fresh()->jumlah_peringatan,
        ]);
    }
}
