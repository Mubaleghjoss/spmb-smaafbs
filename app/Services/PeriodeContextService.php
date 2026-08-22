<?php

namespace App\Services;

use App\Models\TahunAjaran;
use Illuminate\Support\Collection;

/**
 * PeriodeContextService
 *
 * Memegang "Periode Aktif" (tahun ajaran) yang dipilih admin untuk seluruh
 * halaman monitoring/dashboard/verifikasi/hasil/peserta. Konteks disimpan di
 * session sehingga pilihan diingat antar-halaman & antar-login (per browser).
 *
 * Level pemisahan: TAHUN AJARAN (bukan gelombang).
 * Mendukung nilai khusus "Semua Periode" (SEMUA) untuk melihat agregat lintas tahun.
 */
class PeriodeContextService
{
    /** Kunci session penyimpan pilihan periode aktif admin. */
    public const SESSION_KEY = 'periode_aktif_tahun_ajaran_id';

    /** Nilai penanda "Semua Periode" (agregat lintas tahun). */
    public const SEMUA = 'semua';

    /**
     * ID tahun ajaran yang sedang aktif untuk konteks admin.
     *
     * @return int|null  null berarti "Semua Periode" (tanpa filter),
     *                   integer berarti terfilter ke satu tahun ajaran.
     */
    public function tahunAjaranId(): ?int
    {
        if (! session()->has(self::SESSION_KEY)) {
            // Belum pernah memilih → tetapkan default (tanpa memaksa popup).
            $this->set($this->resolveDefault());
        }

        $nilai = session(self::SESSION_KEY);

        if ($nilai === self::SEMUA) {
            return null; // Semua Periode
        }

        return $nilai !== null ? (int) $nilai : null;
    }

    /**
     * Apakah konteks saat ini "Semua Periode".
     */
    public function semuaPeriode(): bool
    {
        // Pastikan default sudah ter-resolusi.
        $this->tahunAjaranId();

        return session(self::SESSION_KEY) === self::SEMUA;
    }

    /**
     * Set periode aktif. Terima int|string id, atau self::SEMUA, atau null.
     */
    public function set(int|string|null $tahunAjaranId): void
    {
        if ($tahunAjaranId === self::SEMUA) {
            session([self::SESSION_KEY => self::SEMUA]);
            return;
        }

        if ($tahunAjaranId === null || $tahunAjaranId === '') {
            session([self::SESSION_KEY => $this->resolveDefault()]);
            return;
        }

        session([self::SESSION_KEY => (int) $tahunAjaranId]);
    }

    /**
     * Objek TahunAjaran aktif (null bila Semua Periode / tidak ada data).
     */
    public function tahunAjaranAktif(): ?TahunAjaran
    {
        $id = $this->tahunAjaranId();

        if ($id === null) {
            return null;
        }

        return TahunAjaran::find($id);
    }

    /**
     * Label ringkas untuk ditampilkan di header/switcher.
     */
    public function label(): string
    {
        if ($this->semuaPeriode()) {
            return 'Semua Periode';
        }

        return $this->tahunAjaranAktif()?->nama ?? 'Semua Periode';
    }

    /**
     * Daftar tahun ajaran untuk dropdown switcher (terbaru dulu).
     *
     * @return Collection<int, TahunAjaran>
     */
    public function pilihanTahunAjaran(): Collection
    {
        return TahunAjaran::query()
            ->orderByDesc('default')
            ->orderByDesc('nama')
            ->get();
    }

    /**
     * Tentukan tahun ajaran default: tahun default → tahun aktif terbaru → terbaru apa pun.
     */
    protected function resolveDefault(): int|string|null
    {
        $tahun = TahunAjaran::query()->where('default', true)->first()
            ?? TahunAjaran::query()->where('aktif', true)->orderByDesc('nama')->first()
            ?? TahunAjaran::query()->orderByDesc('nama')->first();

        return $tahun?->id;
    }
}
