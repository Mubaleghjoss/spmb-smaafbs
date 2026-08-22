<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GelombangPendaftaran;
use App\Services\JadwalAlurService;
use App\Services\PeriodeContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Satu halaman terpusat: ALUR & JADWAL SPMB per tahap (1..7), per PERIODE (tahun ajaran)
 * dan per GELOMBANG. Sumber tunggal — sinkron ke halaman publik /jadwal, timeline
 * frontend, dan status tahap di dashboard peserta.
 */
class AlurJadwalController extends Controller
{
    public function __construct(
        private JadwalAlurService $jadwalAlur,
        private PeriodeContextService $periode,
    ) {}

    public function index(Request $request): View
    {
        $tahunAjaranId = $this->periode->tahunAjaranId();
        if ($tahunAjaranId === null) {
            $tahunAjaranId = optional($this->periode->pilihanTahunAjaran()->firstWhere('default', true))->id
                ?? optional($this->periode->pilihanTahunAjaran()->first())->id;
        }

        $tahunAjaran = $this->periode->pilihanTahunAjaran()->firstWhere('id', $tahunAjaranId);

        // Daftar gelombang pada tahun ini
        $daftarGelombang = $tahunAjaranId
            ? $this->jadwalAlur->gelombangTahun((int) $tahunAjaranId)
            : collect();

        // Gelombang terpilih: dari query, else gelombang publik terpilih, else pertama.
        $gelombangId = $request->integer('gelombang') ?: null;
        if ($gelombangId && ! $daftarGelombang->firstWhere('id', $gelombangId)) {
            $gelombangId = null; // id tidak valid utk tahun ini
        }
        if (! $gelombangId && $daftarGelombang->isNotEmpty()) {
            $gelombangId = optional($this->jadwalAlur->gelombangPublikTerpilih((int) $tahunAjaranId))->id
                ?? $daftarGelombang->first()->id;
        }

        $gelombang = $gelombangId ? $daftarGelombang->firstWhere('id', $gelombangId) : null;

        $jadwal = $tahunAjaranId
            ? $this->jadwalAlur->jadwalGelombang((int) $tahunAjaranId, $gelombangId)
            : [];

        return view('admin.pengaturan.alur-jadwal', [
            'tahunAjaranId' => $tahunAjaranId,
            'tahunAjaran' => $tahunAjaran,
            'daftarTahun' => $this->periode->pilihanTahunAjaran(),
            'daftarGelombang' => $daftarGelombang,
            'gelombangId' => $gelombangId,
            'gelombang' => $gelombang,
            'jadwal' => $jadwal,
            'periodeSemua' => $this->periode->semuaPeriode(),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => 'required|integer|exists:tahun_ajaran,id',
            'gelombang_pendaftaran_id' => 'nullable|integer|exists:gelombang_pendaftaran,id',
            'tahap' => 'required|array',
            'tahap.*.dibuka' => 'nullable',
            'tahap.*.tanggal_buka' => 'nullable|date',
            'tahap.*.waktu_mulai' => 'nullable',
            'tahap.*.tanggal_tutup' => 'nullable|date',
            'tahap.*.waktu_selesai' => 'nullable',
            'tahap.*.lokasi' => 'nullable|string|max:255',
            'tahap.*.keterangan' => 'nullable|string|max:2000',
        ]);

        $gelombangId = $validated['gelombang_pendaftaran_id'] ?? null;

        $this->jadwalAlur->simpanJadwalGelombang(
            (int) $validated['tahun_ajaran_id'],
            $gelombangId ? (int) $gelombangId : null,
            $validated['tahap']
        );

        $namaGel = $gelombangId
            ? optional(GelombangPendaftaran::find($gelombangId))->nama
            : 'semua gelombang (tingkat tahun)';

        return redirect()
            ->route('admin.alur-jadwal.index', ['gelombang' => $gelombangId])
            ->with('success', "Jadwal & alur untuk {$namaGel} berhasil disimpan.");
    }
}
