<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tes;
use App\Models\Token;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller untuk manajemen token
 * Kebutuhan: 4.4
 */
class TokenController extends Controller
{
    public function __construct(
        private TokenService $tokenService
    ) {}

    /**
     * Tampilkan daftar token untuk tes
     */
    public function index(Request $request, Tes $tes): View
    {
        $filter = $request->only(['terpakai', 'kedaluwarsa', 'cari']);
        
        if (isset($filter['terpakai'])) {
            $filter['terpakai'] = $filter['terpakai'] === '1';
        }

        $daftarToken = $this->tokenService->ambilDaftar($tes->id, $filter);
        $statistik = $this->tokenService->ambilStatistik($tes);

        return view('admin.tes.token.index', [
            'tes' => $tes,
            'daftarToken' => $daftarToken,
            'statistik' => $statistik,
            'filter' => $filter,
        ]);
    }

    /**
     * Tampilkan form generate token
     */
    public function create(Tes $tes): View
    {
        return view('admin.tes.token.create', [
            'tes' => $tes,
        ]);
    }

    /**
     * Generate batch token
     */
    public function store(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1|max:500',
            'kedaluwarsa' => 'nullable|date|after:now',
        ]);

        $kedaluwarsa = $validated['kedaluwarsa'] 
            ? new \DateTime($validated['kedaluwarsa']) 
            : null;

        $tokens = $this->tokenService->generateBatch(
            $tes, 
            $validated['jumlah'], 
            $kedaluwarsa
        );

        return redirect()
            ->route('admin.tes.token.index', $tes)
            ->with('success', count($tokens) . ' token berhasil dibuat.');
    }

    /**
     * Hapus token
     */
    public function destroy(Tes $tes, Token $token): RedirectResponse
    {
        try {
            $this->tokenService->hapus($token);
            return back()->with('success', 'Token berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Hapus batch token
     */
    public function hapusBatch(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'token_ids' => 'required|array',
            'token_ids.*' => 'exists:token,id',
        ]);

        $jumlah = $this->tokenService->hapusBatch($tes, $validated['token_ids']);

        return back()->with('success', $jumlah . ' token berhasil dihapus.');
    }

    /**
     * Hapus semua token yang belum terpakai
     */
    public function hapusSemuaBelumTerpakai(Tes $tes): RedirectResponse
    {
        $jumlah = $this->tokenService->hapusSemuaBelumTerpakai($tes);

        return back()->with('success', $jumlah . ' token berhasil dihapus.');
    }

    /**
     * Update kedaluwarsa token
     */
    public function updateKedaluwarsa(Request $request, Tes $tes, Token $token): RedirectResponse
    {
        $validated = $request->validate([
            'kedaluwarsa' => 'nullable|date',
        ]);

        $kedaluwarsa = $validated['kedaluwarsa'] 
            ? new \DateTime($validated['kedaluwarsa']) 
            : null;

        $this->tokenService->updateKedaluwarsa($token, $kedaluwarsa);

        return back()->with('success', 'Kedaluwarsa token berhasil diperbarui.');
    }

    /**
     * Update kedaluwarsa batch
     */
    public function updateKedaluwarsaBatch(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'token_ids' => 'required|array',
            'token_ids.*' => 'exists:token,id',
            'kedaluwarsa' => 'nullable|date',
        ]);

        $kedaluwarsa = $validated['kedaluwarsa'] 
            ? new \DateTime($validated['kedaluwarsa']) 
            : null;

        $jumlah = $this->tokenService->updateKedaluwarsaBatch(
            $tes, 
            $validated['token_ids'], 
            $kedaluwarsa
        );

        return back()->with('success', $jumlah . ' token berhasil diperbarui.');
    }

    /**
     * Reset token
     */
    public function reset(Tes $tes, Token $token): RedirectResponse
    {
        $this->tokenService->reset($token);

        return back()->with('success', 'Token berhasil direset.');
    }

    /**
     * Ekspor token ke Excel
     */
    public function ekspor(Request $request, Tes $tes): StreamedResponse
    {
        $hanyaTersedia = $request->boolean('hanya_tersedia');
        $data = $this->tokenService->eksporKeArray($tes, $hanyaTersedia);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['Kode Token', 'Kedaluwarsa', 'Terpakai', 'Dipakai Oleh', 'Dipakai Pada'];
        $sheet->fromArray($headers, null, 'A1');

        // Style header
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');

        // Data
        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['kode']);
            $sheet->setCellValue('B' . $row, $item['kedaluwarsa']);
            $sheet->setCellValue('C' . $row, $item['terpakai']);
            $sheet->setCellValue('D' . $row, $item['dipakai_oleh']);
            $sheet->setCellValue('E' . $row, $item['dipakai_pada']);
            $row++;
        }

        // Auto width
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'token_' . str_replace(' ', '_', $tes->nama) . '_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
