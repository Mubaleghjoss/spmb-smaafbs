<?php

namespace App\Services;

use App\Models\LogAktivitas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Pencatat aktivitas Tim SPMB.
 *
 * Hanya aksi yang MENGUBAH data yang dicatat (bukan aksi baca/ekspor).
 * Kegagalan pencatatan TIDAK PERNAH menggagalkan aksi utama — log adalah
 * catatan pendukung, bukan bagian dari transaksi bisnis.
 */
class LogAktivitasService
{
    /**
     * Catat satu aktivitas.
     *
     * @param  string  $aksi        Kode aksi, mis. 'kelulusan.loloskan'
     * @param  string  $kategori    Salah satu konstanta LogAktivitas::KAT_*
     * @param  string  $keterangan  Kalimat siap baca untuk admin
     * @param  Model|null  $subjek  Objek yang dikenai aksi (peserta, tes, dsb)
     * @param  array<string, mixed>  $data  Detail tambahan (nilai lama/baru, menit, dsb)
     */
    public function catat(
        string $aksi,
        string $kategori,
        string $keterangan,
        ?Model $subjek = null,
        array $data = [],
        ?int $tahunAjaranId = null,
    ): ?LogAktivitas {
        try {
            $admin = auth('pengguna')->user();

            return LogAktivitas::create([
                'pengguna_id' => $admin?->id,
                'nama_pengguna' => $admin?->nama ?? 'Sistem',
                'peran' => $admin?->peran,
                'aksi' => $aksi,
                'kategori' => $kategori,
                'subjek_tipe' => $subjek ? $subjek::class : null,
                'subjek_id' => $subjek?->getKey(),
                'subjek_label' => $subjek ? $this->labelSubjek($subjek) : null,
                'keterangan' => $keterangan,
                'data' => empty($data) ? null : $data,
                'ip' => request()?->ip(),
                'tahun_ajaran_id' => $tahunAjaranId ?? $this->tebakTahunAjaran($subjek),
            ]);
        } catch (\Throwable $e) {
            // Jangan pernah menggagalkan aksi utama karena pencatatan gagal.
            Log::warning('Gagal mencatat log aktivitas: ' . $e->getMessage(), [
                'aksi' => $aksi,
                'kategori' => $kategori,
            ]);

            return null;
        }
    }

    /**
     * Label subjek yang mudah dibaca: "Nama (nomor)" bila tersedia.
     */
    private function labelSubjek(Model $subjek): string
    {
        $nama = $subjek->nama ?? $subjek->nama_lengkap ?? $subjek->judul ?? null;
        $nomor = $subjek->nomor_pendaftaran ?? null;

        if ($nama && $nomor) {
            return "{$nama} ({$nomor})";
        }

        if ($nama) {
            return (string) $nama;
        }

        return class_basename($subjek) . ' #' . $subjek->getKey();
    }

    private function tebakTahunAjaran(?Model $subjek): ?int
    {
        if ($subjek && isset($subjek->tahun_ajaran_id)) {
            return (int) $subjek->tahun_ajaran_id;
        }

        try {
            return app(PeriodeContextService::class)->tahunAjaranId();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Query log dengan filter untuk halaman admin.
     *
     * @param  array{kategori?: string|null, pengguna_id?: int|string|null, tanggal_dari?: string|null, tanggal_sampai?: string|null, cari?: string|null, subjek_tipe?: string|null, subjek_id?: int|null}  $filter
     */
    public function daftar(array $filter = [], int $perHalaman = 30): LengthAwarePaginator
    {
        return $this->query($filter)->paginate($perHalaman)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    public function query(array $filter = []): Builder
    {
        $q = LogAktivitas::query()->with('pengguna')->latest('created_at')->latest('id');

        if (!empty($filter['kategori'])) {
            $q->where('kategori', $filter['kategori']);
        }

        if (!empty($filter['pengguna_id'])) {
            $q->where('pengguna_id', (int) $filter['pengguna_id']);
        }

        if (!empty($filter['tanggal_dari'])) {
            $q->whereDate('created_at', '>=', $filter['tanggal_dari']);
        }

        if (!empty($filter['tanggal_sampai'])) {
            $q->whereDate('created_at', '<=', $filter['tanggal_sampai']);
        }

        if (!empty($filter['subjek_tipe']) && !empty($filter['subjek_id'])) {
            $q->where('subjek_tipe', $filter['subjek_tipe'])
                ->where('subjek_id', (int) $filter['subjek_id']);
        }

        if (!empty($filter['cari'])) {
            $cari = trim((string) $filter['cari']);
            $q->where(function ($sub) use ($cari) {
                $sub->where('keterangan', 'like', "%{$cari}%")
                    ->orWhere('subjek_label', 'like', "%{$cari}%")
                    ->orWhere('nama_pengguna', 'like', "%{$cari}%")
                    ->orWhere('aksi', 'like', "%{$cari}%");
            });
        }

        return $q;
    }

    /**
     * Riwayat aktivitas untuk satu subjek (mis. satu peserta).
     */
    public function riwayatSubjek(Model $subjek, int $batas = 50)
    {
        return LogAktivitas::query()
            ->where('subjek_tipe', $subjek::class)
            ->where('subjek_id', $subjek->getKey())
            ->latest('created_at')
            ->limit($batas)
            ->get();
    }

    /**
     * Ringkasan untuk kartu statistik.
     *
     * @return array{hari_ini: int, tujuh_hari: int, total: int, teraktif: string|null}
     */
    public function statistik(): array
    {
        $teraktif = LogAktivitas::query()
            ->selectRaw('nama_pengguna, COUNT(*) as jumlah')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('nama_pengguna')
            ->orderByDesc('jumlah')
            ->first();

        return [
            'hari_ini' => LogAktivitas::whereDate('created_at', today())->count(),
            'tujuh_hari' => LogAktivitas::where('created_at', '>=', now()->subDays(7))->count(),
            'total' => LogAktivitas::count(),
            'teraktif' => $teraktif?->nama_pengguna,
        ];
    }
}
