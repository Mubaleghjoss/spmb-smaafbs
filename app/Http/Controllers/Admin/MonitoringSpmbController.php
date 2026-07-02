<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Services\MonitoringSpmbService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringSpmbController extends Controller
{
    public function __construct(private MonitoringSpmbService $monitoringService) {}

    /**
     * Dashboard monitoring
     */
    public function index(): View
    {
        $statistik = $this->monitoringService->ambilStatistikDashboard();
        return view('admin.monitoring.index', compact('statistik'));
    }

    /**
     * Daftar peserta dengan checklist tahapan
     */
    public function daftarPeserta(Request $request): View
    {
        $filter = $request->only(['tahap', 'cari']);
        $peserta = $this->monitoringService->ambilDaftarPeserta($filter);
        return view('admin.monitoring.daftar-peserta', compact('peserta', 'filter'));
    }

    /**
     * Update status tahapan peserta
     */
    public function updateTahapan(Request $request, Peserta $peserta): RedirectResponse
    {
        $request->validate([
            'tahap' => 'required|integer|min:1|max:7',
            'selesai' => 'required|boolean',
        ]);

        $this->monitoringService->updateStatusTahapan(
            $peserta,
            $request->tahap,
            $request->boolean('selesai'),
            auth('pengguna')->user()
        );

        return back()->with('success', 'Status tahapan berhasil diupdate');
    }

    /**
     * Bulk update tahapan
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'peserta_ids' => 'required|array',
            'peserta_ids.*' => 'exists:peserta,id',
            'tahap' => 'required|integer|min:1|max:7',
            'selesai' => 'required|boolean',
        ]);

        $count = $this->monitoringService->bulkUpdateTahapan(
            $request->peserta_ids,
            $request->tahap,
            $request->boolean('selesai'),
            auth('pengguna')->user()
        );

        return back()->with('success', "{$count} peserta berhasil diupdate");
    }

    /**
     * Log perubahan tahapan
     */
    public function logPerubahan(Request $request): View
    {
        $filter = $request->only(['peserta_id', 'tahap', 'tanggal_dari', 'tanggal_sampai']);
        $logs = $this->monitoringService->ambilLogPerubahan($filter);
        return view('admin.monitoring.log-perubahan', compact('logs', 'filter'));
    }

    /**
     * Ekspor data peserta ke Excel
     */
    public function ekspor(Request $request): StreamedResponse
    {
        $filter = $request->only(['tahap']);
        $data = $this->monitoringService->eksporDataPeserta($filter);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="peserta-spmb-' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            // Header
            fputcsv($handle, [
                'No. Pendaftaran', 'Nama', 'Email', 'Telepon', 'Asal Sekolah',
                'Tahap Saat Ini', 'Tahap 1', 'Tahap 2', 'Tahap 3', 'Tahap 4',
                'Tahap 5', 'Tahap 6', 'Tahap 7', 'Tanggal Daftar'
            ]);
            
            // Data
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            
            fclose($handle);
        }, 200, $headers);
    }
}
