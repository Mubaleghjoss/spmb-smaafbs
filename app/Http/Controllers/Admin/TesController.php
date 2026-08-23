<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grup;
use App\Models\Tes;
use App\Models\Topik;
use App\Services\GrupService;
use App\Services\TesService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controller untuk manajemen tes
 * Kebutuhan: 4.1, 4.7
 */
class TesController extends Controller
{
    public function __construct(
        private TesService $tesService,
        private TokenService $tokenService,
        private GrupService $grupService
    ) {}

    /**
     * Tampilkan daftar tes
     */
    public function index(Request $request): View
    {
        $filter = $request->only(['status', 'cari', 'tanggal_mulai', 'tanggal_selesai', 'sort_by', 'sort_dir']);
        $daftarTes = $this->tesService->ambilDaftar($filter);

        return view('admin.tes.index', [
            'daftarTes' => $daftarTes,
            'filter' => $filter,
        ]);
    }

    /**
     * Tampilkan form tambah tes
     */
    public function create(): View
    {
        return view('admin.tes.create');
    }

    /**
     * Simpan tes baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'durasi_menit' => 'required|integer|min:1|max:600',
            'nilai_lulus' => 'required|numeric|min:0|max:100',
            'mulai' => 'nullable|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'acak_soal' => 'boolean',
            'acak_jawaban' => 'boolean',
            'tampilkan_nilai' => 'boolean',
            'tampilkan_pembahasan' => 'boolean',
        ]);

        $validated['acak_soal'] = $request->boolean('acak_soal');
        $validated['acak_jawaban'] = $request->boolean('acak_jawaban');
        $validated['tampilkan_nilai'] = $request->boolean('tampilkan_nilai');
        $validated['tampilkan_pembahasan'] = $request->boolean('tampilkan_pembahasan');
        $validated['pembahasan_hanya_lulus'] = $request->boolean('pembahasan_hanya_lulus');

        $tes = $this->tesService->buat($validated);

        return redirect()
            ->route('admin.tes.show', $tes)
            ->with('success', 'Tes berhasil dibuat.');
    }

    /**
     * Tampilkan detail tes
     */
    public function show(Tes $tes): View
    {
        $tes = $this->tesService->ambilById($tes->id);
        $statistik = $this->tesService->ambilStatistik($tes);
        $statistikToken = $this->tokenService->ambilStatistik($tes);

        return view('admin.tes.show', [
            'tes' => $tes,
            'statistik' => $statistik,
            'statistikToken' => $statistikToken,
        ]);
    }

    /**
     * Tampilkan form edit tes
     */
    public function edit(Tes $tes): View
    {
        return view('admin.tes.edit', [
            'tes' => $tes,
        ]);
    }

    /**
     * Update tes
     */
    public function update(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'durasi_menit' => 'required|integer|min:1|max:600',
            'nilai_lulus' => 'required|numeric|min:0|max:100',
            'mulai' => 'nullable|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'acak_soal' => 'boolean',
            'acak_jawaban' => 'boolean',
            'tampilkan_nilai' => 'boolean',
            'tampilkan_pembahasan' => 'boolean',
        ]);

        $validated['acak_soal'] = $request->boolean('acak_soal');
        $validated['acak_jawaban'] = $request->boolean('acak_jawaban');
        $validated['tampilkan_nilai'] = $request->boolean('tampilkan_nilai');
        $validated['tampilkan_pembahasan'] = $request->boolean('tampilkan_pembahasan');
        $validated['pembahasan_hanya_lulus'] = $request->boolean('pembahasan_hanya_lulus');

        $this->tesService->update($tes, $validated);

        return redirect()
            ->route('admin.tes.show', $tes)
            ->with('success', 'Tes berhasil diperbarui.');
    }

    /**
     * Hapus tes
     */
    public function destroy(Tes $tes): RedirectResponse
    {
        try {
            $this->tesService->hapus($tes);
            return redirect()
                ->route('admin.tes.index')
                ->with('success', 'Tes berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ubah status tes
     */
    public function ubahStatus(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,aktif,selesai',
        ]);

        try {
            $this->tesService->ubahStatus($tes, $validated['status']);
            return back()->with('success', 'Status tes berhasil diubah.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Duplikat tes
     */
    public function duplikat(Tes $tes): RedirectResponse
    {
        $tesBaru = $this->tesService->duplikat($tes);

        return redirect()
            ->route('admin.tes.edit', $tesBaru)
            ->with('success', 'Tes berhasil diduplikat.');
    }

    /**
     * Bulk update status tes (aktifkan/stop)
     */
    public function bulkStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:aktifkan,stop',
            'tes_ids' => 'required|string',
        ]);

        $tesIds = json_decode($validated['tes_ids'], true);
        $action = $validated['action'];
        $sukses = 0;
        $gagal = 0;

        foreach ($tesIds as $tesId) {
            try {
                $tes = Tes::find($tesId);
                if ($tes) {
                    if ($action === 'aktifkan') {
                        $this->tesService->ubahStatus($tes, 'aktif');
                    } else {
                        $this->tesService->ubahStatus($tes, 'selesai');
                    }
                    $sukses++;
                }
            } catch (\Exception $e) {
                $gagal++;
            }
        }

        $pesan = $action === 'aktifkan' ? 'diaktifkan' : 'dihentikan';
        return back()->with('success', "{$sukses} tes berhasil {$pesan}" . ($gagal > 0 ? ", {$gagal} gagal" : ''));
    }

    /**
     * Bulk update durasi tes
     */
    public function bulkDurasi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tes_ids' => 'required|string',
            'durasi_menit' => 'required|integer|min:1|max:300',
        ]);

        $tesIds = json_decode($validated['tes_ids'], true);
        $durasi = $validated['durasi_menit'];
        $sukses = 0;

        foreach ($tesIds as $tesId) {
            $tes = Tes::find($tesId);
            if ($tes) {
                $tes->update(['durasi_menit' => $durasi]);
                $sukses++;
            }
        }

        return back()->with('success', "{$sukses} tes berhasil diubah durasinya menjadi {$durasi} menit.");
    }

    /**
     * Bulk update durasi dan jadwal tes
     */
    public function bulkDurasiJadwal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tes_ids' => 'required|string',
            'ubah_durasi' => 'nullable',
            'durasi_menit' => 'nullable|integer|min:1|max:300',
        ]);

        $tesIds = json_decode($validated['tes_ids'], true);
        $sukses = 0;

        // Hanya durasi pengerjaan. Jadwal buka/tutup tes online OTOMATIS mengikuti
        // Tahap 4 (Alur & Jadwal) per periode — tidak lagi diset manual di sini.
        if ($request->has('ubah_durasi') && !empty($validated['durasi_menit'])) {
            foreach ($tesIds as $tesId) {
                $tes = Tes::find($tesId);
                if ($tes) {
                    $tes->update(['durasi_menit' => $validated['durasi_menit']]);
                    $sukses++;
                }
            }
            return back()->with('success', "{$sukses} tes berhasil diubah durasinya menjadi {$validated['durasi_menit']} menit.");
        }

        return back()->with('success', 'Tidak ada perubahan durasi.');
    }

    /**
     * Tampilkan daftar sesi peserta
     */
    public function daftarSesi(Request $request, Tes $tes): View
    {
        $status = $request->get('status', 'semua');
        
        $query = $tes->sesiTes()->with(['peserta']);
        
        if ($status === 'berlangsung') {
            $query->where('status', 'berlangsung');
        } elseif ($status === 'selesai') {
            $query->where('status', 'selesai');
        }
        
        $sesiList = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.tes.sesi', [
            'tes' => $tes,
            'sesiList' => $sesiList,
            'status' => $status,
        ]);
    }

    /**
     * Tampilkan halaman pengaturan soal
     */
    public function soal(Request $request, Tes $tes): View
    {
        $filter = $request->only(['topik_id', 'tipe', 'cari']);
        $tampilkanSemua = $request->boolean('tampilkan_semua');
        $soalTerpilih = $tes->soal()->with(['topik', 'jawaban'])->get();
        $soalTersedia = $this->tesService->ambilSoalTersedia($tes, $filter, $tampilkanSemua);
        $topikList = Topik::orderBy('nama')->get();

        return view('admin.tes.soal', [
            'tes' => $tes,
            'soalTerpilih' => $soalTerpilih,
            'soalTersedia' => $soalTersedia,
            'topikList' => $topikList,
            'filter' => $filter,
            'tampilkanSemua' => $tampilkanSemua,
            'totalBobot' => $this->tesService->hitungTotalBobot($tes),
        ]);
    }

    /**
     * Tambah soal ke tes
     */
    public function tambahSoal(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'soal_id' => 'required|exists:soal,id',
            'bobot_custom' => 'nullable|integer|min:1',
        ]);

        $this->tesService->tambahSoal($tes, $validated['soal_id'], $validated['bobot_custom'] ?? null);

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    /**
     * Tambah batch soal ke tes (pilih semua)
     */
    public function tambahSoalBatch(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'soal_ids' => 'required|string',
        ]);

        $soalIds = array_filter(explode(',', $validated['soal_ids']));
        $count = 0;

        foreach ($soalIds as $soalId) {
            if (is_numeric($soalId) && !$tes->soal()->where('soal.id', $soalId)->exists()) {
                $this->tesService->tambahSoal($tes, (int) $soalId);
                $count++;
            }
        }

        return back()->with('success', "{$count} soal berhasil ditambahkan.");
    }

    /**
     * Hapus soal dari tes
     */
    public function hapusSoal(Tes $tes, int $soalId): RedirectResponse
    {
        $this->tesService->hapusSoal($tes, $soalId);

        return back()->with('success', 'Soal berhasil dihapus dari tes.');
    }

    /**
     * Hapus batch soal dari tes
     */
    public function hapusSoalBatch(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'soal_ids' => 'required|string',
        ]);

        $soalIds = array_filter(explode(',', $validated['soal_ids']));
        $count = 0;

        foreach ($soalIds as $soalId) {
            if (is_numeric($soalId)) {
                $this->tesService->hapusSoal($tes, (int) $soalId);
                $count++;
            }
        }

        return back()->with('success', "{$count} soal berhasil dihapus.");
    }

    /**
     * Update urutan soal
     */
    public function updateUrutanSoal(Request $request, Tes $tes): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'urutan' => 'required|array',
            'urutan.*' => 'integer|min:1',
        ]);

        $this->tesService->updateUrutanSoal($tes, $validated['urutan']);

        // Jika AJAX request, return JSON tanpa redirect
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Update bobot soal
     */
    public function updateBobotSoal(Request $request, Tes $tes, int $soalId): RedirectResponse
    {
        $validated = $request->validate([
            'bobot_custom' => 'nullable|integer|min:1',
        ]);

        $this->tesService->updateBobotSoal($tes, $soalId, $validated['bobot_custom']);

        return back()->with('success', 'Bobot soal berhasil diperbarui.');
    }

    /**
     * Tampilkan halaman pengaturan grup untuk tes
     * Kebutuhan: 1.1, 1.2
     */
    public function grupTes(Tes $tes): View
    {
        $grupTerpilih = $this->tesService->ambilGrupYangDiassign($tes);
        $semuaGrup = Grup::withCount('peserta')->orderBy('nama')->get();
        $potensialPeserta = $this->tesService->hitungPotensialPeserta($tes);

        return view('admin.tes.grup', [
            'tes' => $tes,
            'grupTerpilih' => $grupTerpilih,
            'semuaGrup' => $semuaGrup,
            'potensialPeserta' => $potensialPeserta,
        ]);
    }

    /**
     * Simpan pengaturan grup untuk tes
     * Kebutuhan: 1.3, 1.4
     */
    public function simpanGrupTes(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'grup_ids' => 'nullable|array',
            'grup_ids.*' => 'exists:grup,id',
        ]);

        $grupIds = $validated['grup_ids'] ?? [];
        $this->tesService->assignGrup($tes, $grupIds);

        return redirect()
            ->route('admin.tes.show', $tes)
            ->with('success', 'Pengaturan grup berhasil disimpan.');
    }

    /**
     * Bulk assign grup ke beberapa tes
     * Kebutuhan: 4.1, 4.2
     */
    public function bulkAssignGrup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tes_ids' => 'required|string',
            'grup_ids' => 'nullable|array',
            'grup_ids.*' => 'exists:grup,id',
        ]);

        $tesIds = json_decode($validated['tes_ids'], true);
        $grupIds = $validated['grup_ids'] ?? [];
        $sukses = 0;

        foreach ($tesIds as $tesId) {
            $tes = Tes::find($tesId);
            if ($tes) {
                $this->tesService->assignGrup($tes, $grupIds);
                $sukses++;
            }
        }

        return back()->with('success', "{$sukses} tes berhasil diatur grupnya.");
    }

    /**
     * Halaman atur PERIODE (tahun ajaran) untuk sebuah tes.
     * Admin menentukan tes ini dipakai di tahun ajaran mana saja.
     */
    public function periodeTes(Tes $tes): View
    {
        $tahunTerpilih = $tes->tahunAjaran()->pluck('tahun_ajaran.id')->all();
        $semuaTahun = \App\Models\TahunAjaran::orderByDesc('default')
            ->orderByDesc('nama')
            ->get();

        return view('admin.tes.periode', [
            'tes' => $tes,
            'tahunTerpilih' => $tahunTerpilih,
            'semuaTahun' => $semuaTahun,
        ]);
    }

    /**
     * Simpan penugasan periode (tahun ajaran) untuk tes.
     * Kosong = tes berlaku untuk semua periode (fallback).
     */
    public function simpanPeriodeTes(Request $request, Tes $tes): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_ajaran_ids' => 'nullable|array',
            'tahun_ajaran_ids.*' => 'exists:tahun_ajaran,id',
        ]);

        $tes->tahunAjaran()->sync($validated['tahun_ajaran_ids'] ?? []);

        return redirect()
            ->route('admin.tes.show', $tes)
            ->with('success', 'Penugasan periode tes berhasil disimpan.');
    }

    /**
     * Bulk: atur penugasan periode (tahun ajaran) untuk beberapa tes sekaligus.
     * Mode 'ganti' = timpa penugasan; 'tambah' = tambahkan tanpa menghapus yang ada.
     * tahun_ajaran_ids kosong + mode 'ganti' = jadikan berlaku semua periode (fallback).
     */
    public function bulkAssignPeriode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tes_ids' => 'required|string',
            'tahun_ajaran_ids' => 'nullable|array',
            'tahun_ajaran_ids.*' => 'exists:tahun_ajaran,id',
            'mode' => 'nullable|in:ganti,tambah',
        ]);

        $tesIds = json_decode($validated['tes_ids'], true) ?: [];
        $tahunIds = $validated['tahun_ajaran_ids'] ?? [];
        $mode = $validated['mode'] ?? 'ganti';
        $sukses = 0;

        foreach ($tesIds as $tesId) {
            $tes = Tes::find($tesId);
            if (! $tes) {
                continue;
            }
            if ($mode === 'tambah') {
                $tes->tahunAjaran()->syncWithoutDetaching($tahunIds);
            } else {
                $tes->tahunAjaran()->sync($tahunIds);
            }
            $sukses++;
        }

        $pesan = $tahunIds === [] && $mode === 'ganti'
            ? "{$sukses} tes disetel berlaku untuk semua periode."
            : "{$sukses} tes berhasil diatur periodenya.";

        return back()->with('success', $pesan);
    }
}
