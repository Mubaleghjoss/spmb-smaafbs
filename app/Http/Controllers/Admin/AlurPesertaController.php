<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlurPesertaController extends Controller
{
    /**
     * Nama-nama tahap SPMB
     */
    private const TAHAP_LABELS = [
        1 => 'Pendaftaran',
        2 => 'Pembayaran Formulir',
        3 => 'Pengisian Formulir',
        4 => 'Tes Masuk',
        5 => 'Wawancara',
        6 => 'Pelunasan',
        7 => 'Kelulusan',
    ];

    private const TAHAP_ICONS = [
        1 => 'bi-person-plus-fill',
        2 => 'bi-credit-card-fill',
        3 => 'bi-file-earmark-text-fill',
        4 => 'bi-pencil-square',
        5 => 'bi-chat-dots-fill',
        6 => 'bi-cash-coin',
        7 => 'bi-mortarboard-fill',
    ];

    private const TAHAP_COLORS = [
        1 => '#6366f1',
        2 => '#f59e0b',
        3 => '#3b82f6',
        4 => '#8b5cf6',
        5 => '#ec4899',
        6 => '#f97316',
        7 => '#10b981',
    ];

    /**
     * Pipeline overview — semua peserta grouped by tahap
     */
    public function index(Request $request): View
    {
        $filterTahap = $request->input('tahap');
        $filterCari = $request->input('cari');

        // Count per tahap
        $perTahap = [];
        for ($i = 1; $i <= 7; $i++) {
            $perTahap[$i] = Peserta::whereHas('tahapanSpmb', function ($q) use ($i) {
                $q->where('tahap_saat_ini', $i);
            })->count();
        }

        // Peserta yang belum punya tahapan (tahap 1 default)
        $belumAdaTahapan = Peserta::doesntHave('tahapanSpmb')->count();
        $perTahap[1] += $belumAdaTahapan;

        // Total
        $total = array_sum($perTahap);

        // Peserta lulus & tidak lulus
        $lulus = Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('status_kelulusan', 'lulus'))->count();
        $tidakLulus = Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('status_kelulusan', 'tidak_lulus'))->count();

        // Query peserta untuk tabel (optional filter by tahap)
        $query = Peserta::with(['tahapanSpmb', 'formulirSpmb'])
            ->orderBy('created_at', 'desc');

        if ($filterTahap) {
            if ($filterTahap == 1) {
                $query->where(function ($q) {
                    $q->whereHas('tahapanSpmb', fn($sub) => $sub->where('tahap_saat_ini', 1))
                      ->orDoesntHave('tahapanSpmb');
                });
            } else {
                $query->whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', $filterTahap));
            }
        }

        if ($filterCari) {
            $query->where(function ($q) use ($filterCari) {
                $q->where('nama', 'like', "%{$filterCari}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$filterCari}%");
            });
        }

        $peserta = $query->paginate(20)->withQueryString();

        return view('admin.alur-peserta.index', [
            'perTahap' => $perTahap,
            'total' => $total,
            'lulus' => $lulus,
            'tidakLulus' => $tidakLulus,
            'peserta' => $peserta,
            'tahapLabels' => self::TAHAP_LABELS,
            'tahapIcons' => self::TAHAP_ICONS,
            'tahapColors' => self::TAHAP_COLORS,
            'filterTahap' => $filterTahap,
            'filterCari' => $filterCari,
        ]);
    }

    /**
     * Ekspor data peserta ke CSV
     */
    public function eksporCsv(Request $request): StreamedResponse
    {
        $filterTahap = $request->input('tahap');

        $query = Peserta::with(['tahapanSpmb', 'formulirSpmb']);

        if ($filterTahap) {
            $query->whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', $filterTahap));
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="peserta-spmb-' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($handle, [
                'No. Pendaftaran', 'Nama', 'Email', 'Telepon', 'Asal Sekolah',
                'Tahap Saat Ini', 'Tahap 1', 'Tahap 2', 'Tahap 3', 'Tahap 4',
                'Tahap 5', 'Tahap 6', 'Tahap 7', 'Status Kelulusan', 'Tanggal Daftar'
            ], ';');

            // Data
            foreach ($data as $peserta) {
                $tahapan = $peserta->tahapanSpmb;
                fputcsv($handle, [
                    $peserta->nomor_pendaftaran,
                    $peserta->nama,
                    $peserta->email,
                    $peserta->telepon,
                    $peserta->formulirSpmb?->asal_sekolah ?? '-',
                    $tahapan?->tahap_saat_ini ?? 1,
                    $tahapan?->tahap_1_selesai ? 'Ya' : 'Tidak',
                    $tahapan?->tahap_2_selesai ? 'Ya' : 'Tidak',
                    $tahapan?->tahap_3_selesai ? 'Ya' : 'Tidak',
                    $tahapan?->tahap_4_selesai ? 'Ya' : 'Tidak',
                    $tahapan?->tahap_5_selesai ? 'Ya' : 'Tidak',
                    $tahapan?->tahap_6_selesai ? 'Ya' : 'Tidak',
                    $tahapan?->tahap_7_selesai ? 'Ya' : 'Tidak',
                    $tahapan?->status_kelulusan ?? 'proses',
                    $peserta->created_at->format('d/m/Y'),
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
    }
}
