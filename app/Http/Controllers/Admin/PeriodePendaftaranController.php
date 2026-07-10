<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GelombangPendaftaran;
use App\Models\TahunAjaran;
use App\Services\KuotaPendaftaranService;
use App\Services\PeriodePendaftaranService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PeriodePendaftaranController extends Controller
{
    public function __construct(
        private PeriodePendaftaranService $periodeService,
        private KuotaPendaftaranService $kuotaPendaftaranService
    ) {}

    public function index(): View
    {
        $tahunAjaran = TahunAjaran::query()
            ->withCount('peserta')
            ->with([
                'gelombangPendaftaran' => fn($query) => $query
                    ->withCount('peserta')
                    ->orderBy('tanggal_buka')
                    ->orderBy('nama'),
            ])
            ->orderByDesc('default')
            ->orderByDesc('nama')
            ->get();

        $ringkasanKuota = $this->kuotaPendaftaranService->ringkasanBanyak($tahunAjaran);

        return view('admin.pengaturan.periode-pendaftaran', compact('tahunAjaran', 'ringkasanKuota'));
    }

    public function storeTahun(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'kuota_peserta' => ['nullable', 'integer', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
            'default' => ['nullable', 'boolean'],
        ]);
        $nama = $this->periodeService->normalisasiNamaTahun($validated['nama']);

        if (TahunAjaran::query()->where('nama', $nama)->exists()) {
            return back()->withInput()->withErrors(['nama' => 'Tahun ajaran tersebut sudah tersedia.']);
        }

        $tahun = DB::transaction(function () use ($request, $nama) {
            $tahun = TahunAjaran::query()->create([
                'nama' => $nama,
                'aktif' => $request->boolean('aktif'),
                'default' => false,
                'kuota_peserta' => $this->normalisasiKuota($request->input('kuota_peserta')),
            ]);

            if ($request->boolean('default') || TahunAjaran::query()->count() === 1) {
                $this->periodeService->jadikanDefault($tahun);
            }

            return $tahun;
        });

        return back()->with('success', "Tahun ajaran {$tahun->nama} berhasil ditambahkan.");
    }

    public function updateTahun(Request $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'kuota_peserta' => ['nullable', 'integer', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
            'default' => ['nullable', 'boolean'],
        ]);
        $nama = $this->periodeService->normalisasiNamaTahun($validated['nama']);

        if (TahunAjaran::query()
            ->where('nama', $nama)
            ->where('id', '!=', $tahunAjaran->id)
            ->exists()) {
            return back()->withErrors(['nama' => 'Tahun ajaran tersebut sudah tersedia.']);
        }

        if ($tahunAjaran->default && !$request->boolean('aktif')) {
            return back()->with('error', 'Tahun ajaran default tidak dapat dinonaktifkan. Pilih default lain terlebih dahulu.');
        }

        DB::transaction(function () use ($request, $tahunAjaran, $nama) {
            $tahunAjaran->update([
                'nama' => $nama,
                'aktif' => $request->boolean('aktif'),
                'kuota_peserta' => $this->normalisasiKuota($request->input('kuota_peserta')),
            ]);

            if ($request->boolean('default')) {
                $this->periodeService->jadikanDefault($tahunAjaran);
            } elseif ($tahunAjaran->default) {
                app(\App\Services\PengaturanService::class)->simpan('tahun_ajaran', $nama);
            }

            $this->kuotaPendaftaranService->rekalkulasiTahun($tahunAjaran->id);
        });

        return back()->with('success', "Tahun ajaran {$nama} berhasil diperbarui.");
    }

    public function destroyTahun(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if ($tahunAjaran->default) {
            return back()->with('error', 'Tahun ajaran default tidak dapat dihapus.');
        }

        if ($tahunAjaran->peserta()->withTrashed()->exists()
            || $tahunAjaran->gelombangPendaftaran()->exists()) {
            return back()->with('error', 'Tahun ajaran yang sudah memiliki gelombang atau peserta hanya dapat dinonaktifkan.');
        }

        $nama = $tahunAjaran->nama;
        $tahunAjaran->delete();

        return back()->with('success', "Tahun ajaran {$nama} berhasil dihapus.");
    }

    public function storeGelombang(Request $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $validated = $this->validateGelombang($request, $tahunAjaran);

        $tahunAjaran->gelombangPendaftaran()->create([
            ...$validated,
            'aktif' => $request->boolean('aktif'),
        ]);

        return back()->with('success', 'Gelombang pendaftaran berhasil ditambahkan.');
    }

    public function updateGelombang(
        Request $request,
        TahunAjaran $tahunAjaran,
        GelombangPendaftaran $gelombang
    ): RedirectResponse {
        $this->pastikanMilikTahun($tahunAjaran, $gelombang);
        $validated = $this->validateGelombang($request, $tahunAjaran, $gelombang);

        $gelombang->update([
            ...$validated,
            'aktif' => $request->boolean('aktif'),
        ]);

        return back()->with('success', 'Gelombang pendaftaran berhasil diperbarui.');
    }

    public function destroyGelombang(
        TahunAjaran $tahunAjaran,
        GelombangPendaftaran $gelombang
    ): RedirectResponse {
        $this->pastikanMilikTahun($tahunAjaran, $gelombang);

        if ($gelombang->peserta()->withTrashed()->exists()) {
            return back()->with('error', 'Gelombang yang sudah digunakan peserta hanya dapat dinonaktifkan.');
        }

        $gelombang->delete();

        return back()->with('success', 'Gelombang pendaftaran berhasil dihapus.');
    }

    private function validateGelombang(
        Request $request,
        TahunAjaran $tahunAjaran,
        ?GelombangPendaftaran $gelombang = null
    ): array {
        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('gelombang_pendaftaran', 'nama')
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->ignore($gelombang?->id),
            ],
            'tanggal_buka' => ['nullable', 'date'],
            'waktu_buka' => ['nullable', 'date_format:H:i'],
            'tanggal_tutup' => ['nullable', 'date', 'after_or_equal:tanggal_buka'],
            'waktu_tutup' => ['nullable', 'date_format:H:i'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $errors = [];

        if ($request->filled('waktu_buka') && !$request->filled('tanggal_buka')) {
            $errors['tanggal_buka'] = 'Tanggal buka wajib diisi jika jam buka diisi.';
        }

        if ($request->filled('waktu_tutup') && !$request->filled('tanggal_tutup')) {
            $errors['tanggal_tutup'] = 'Tanggal tutup wajib diisi jika jam tutup diisi.';
        }

        if ($request->filled('tanggal_buka') && $request->filled('tanggal_tutup')) {
            $mulai = Carbon::parse($request->input('tanggal_buka') . ' ' . ($request->input('waktu_buka') ?: '00:00'));
            $selesai = Carbon::parse($request->input('tanggal_tutup') . ' ' . ($request->input('waktu_tutup') ?: '23:59'));

            if ($selesai->lt($mulai)) {
                $errors['tanggal_tutup'] = 'Jadwal tutup gelombang tidak boleh sebelum jadwal buka.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    private function pastikanMilikTahun(
        TahunAjaran $tahunAjaran,
        GelombangPendaftaran $gelombang
    ): void {
        abort_unless((int) $gelombang->tahun_ajaran_id === (int) $tahunAjaran->id, 404);
    }

    private function normalisasiKuota(mixed $kuota): ?int
    {
        $kuota = (int) ($kuota ?? 0);

        return $kuota > 0 ? $kuota : null;
    }
}
