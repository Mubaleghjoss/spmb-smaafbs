<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\JadwalAlurService;
use App\Services\PeriodeContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Satu halaman terpusat untuk mengatur ALUR & JADWAL SPMB per tahap (1..7),
 * per PERIODE (tahun ajaran aktif). Menggabungkan pengaturan waktu buka/tutup
 * tiap tahap + keterangan/note yang dilihat pendaftar — sebelumnya tersebar di
 * beberapa halaman (Pengaturan SPMB, Ujian, Jadwal, Periode).
 */
class AlurJadwalController extends Controller
{
    public function __construct(
        private JadwalAlurService $jadwalAlur,
        private PeriodeContextService $periode,
    ) {}

    public function index(): View
    {
        $tahunAjaranId = $this->periode->tahunAjaranId();

        // Bila konteks "Semua Periode", jatuhkan ke tahun default agar ada 1 periode konkret untuk diedit.
        if ($tahunAjaranId === null) {
            $tahunAjaranId = optional($this->periode->pilihanTahunAjaran()->firstWhere('default', true))->id
                ?? optional($this->periode->pilihanTahunAjaran()->first())->id;
        }

        $tahunAjaran = $this->periode->pilihanTahunAjaran()->firstWhere('id', $tahunAjaranId);
        $jadwal = $tahunAjaranId ? $this->jadwalAlur->jadwalPeriode((int) $tahunAjaranId) : [];

        return view('admin.pengaturan.alur-jadwal', [
            'tahunAjaranId' => $tahunAjaranId,
            'tahunAjaran' => $tahunAjaran,
            'daftarTahun' => $this->periode->pilihanTahunAjaran(),
            'jadwal' => $jadwal,
            'periodeSemua' => $this->periode->semuaPeriode(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => 'required|integer|exists:tahun_ajaran,id',
            'tahap' => 'required|array',
            'tahap.*.dibuka' => 'nullable',
            'tahap.*.tanggal_buka' => 'nullable|date',
            'tahap.*.waktu_mulai' => 'nullable',
            'tahap.*.tanggal_tutup' => 'nullable|date',
            'tahap.*.waktu_selesai' => 'nullable',
            'tahap.*.lokasi' => 'nullable|string|max:255',
            'tahap.*.keterangan' => 'nullable|string|max:2000',
        ]);

        $this->jadwalAlur->simpanJadwalPeriode(
            (int) $validated['tahun_ajaran_id'],
            $validated['tahap']
        );

        return back()->with('success', 'Jadwal & alur SPMB untuk periode ini berhasil disimpan.');
    }
}
