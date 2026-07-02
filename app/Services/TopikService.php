<?php

namespace App\Services;

use App\Models\Topik;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service untuk manajemen topik/kategori soal
 * Kebutuhan: 2.5
 */
class TopikService
{
    /**
     * Ambil semua topik dengan hierarki
     */
    public function ambilSemua(): Collection
    {
        return Topik::with('children', 'soal')
            ->whereNull('parent_id')
            ->orderBy('nama')
            ->get();
    }

    /**
     * Ambil semua topik flat (untuk dropdown)
     */
    public function ambilSemuaFlat(): Collection
    {
        return Topik::orderBy('nama')->get();
    }

    /**
     * Ambil topik dengan paginasi
     */
    public function ambilDenganPaginasi(int $perHalaman = 15): LengthAwarePaginator
    {
        return Topik::with('parent')
            ->withCount('soal')
            ->orderBy('nama')
            ->paginate($perHalaman);
    }

    /**
     * Cari topik berdasarkan ID
     */
    public function cariById(int $id): ?Topik
    {
        return Topik::with('children', 'soal')->find($id);
    }

    /**
     * Buat topik baru
     */
    public function buat(array $data): Topik
    {
        return Topik::create([
            'nama' => $data['nama'],
            'keterangan' => $data['keterangan'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
        ]);
    }

    /**
     * Perbarui topik
     */
    public function perbarui(Topik $topik, array $data): Topik
    {
        $topik->update([
            'nama' => $data['nama'],
            'keterangan' => $data['keterangan'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return $topik->fresh();
    }

    /**
     * Hapus topik
     */
    public function hapus(Topik $topik): bool
    {
        // Pindahkan soal ke topik parent atau null
        if ($topik->soal()->count() > 0) {
            $topik->soal()->update(['topik_id' => $topik->parent_id]);
        }

        // Pindahkan children ke parent
        if ($topik->children()->count() > 0) {
            $topik->children()->update(['parent_id' => $topik->parent_id]);
        }

        return $topik->delete();
    }

    /**
     * Ambil statistik topik
     */
    public function ambilStatistik(): array
    {
        $totalTopik = Topik::count();
        $topikDenganSoal = Topik::has('soal')->count();
        $topikKosong = $totalTopik - $topikDenganSoal;

        return [
            'total' => $totalTopik,
            'dengan_soal' => $topikDenganSoal,
            'kosong' => $topikKosong,
        ];
    }
}
