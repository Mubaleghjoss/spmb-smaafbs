<?php

namespace App\Services;

use App\Models\Grup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service untuk manajemen grup peserta
 * Kebutuhan: 3.3
 */
class GrupService
{
    /**
     * Ambil semua grup
     */
    public function ambilSemua(): Collection
    {
        return Grup::withCount('peserta')->orderBy('nama')->get();
    }

    /**
     * Ambil grup dengan paginasi
     */
    public function ambilDenganPaginasi(int $perHalaman = 15): LengthAwarePaginator
    {
        return Grup::withCount(['peserta', 'tes'])
            ->orderBy('nama')
            ->paginate($perHalaman);
    }

    /**
     * Cari grup berdasarkan ID
     */
    public function cariById(int $id): ?Grup
    {
        return Grup::with('peserta')->find($id);
    }

    /**
     * Buat grup baru
     */
    public function buat(array $data): Grup
    {
        return Grup::create([
            'nama' => $data['nama'],
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }

    /**
     * Perbarui grup
     */
    public function perbarui(Grup $grup, array $data): Grup
    {
        $grup->update([
            'nama' => $data['nama'] ?? $grup->nama,
            'keterangan' => $data['keterangan'] ?? $grup->keterangan,
        ]);

        return $grup->fresh();
    }

    /**
     * Hapus grup
     */
    public function hapus(Grup $grup): bool
    {
        // Detach semua peserta dari grup
        $grup->peserta()->detach();
        return $grup->delete();
    }

    /**
     * Ambil statistik grup
     */
    public function ambilStatistik(): array
    {
        $totalGrup = Grup::count();
        $grupDenganPeserta = Grup::has('peserta')->count();
        $grupKosong = $totalGrup - $grupDenganPeserta;

        return [
            'total' => $totalGrup,
            'dengan_peserta' => $grupDenganPeserta,
            'kosong' => $grupKosong,
        ];
    }

    /**
     * Assign tes ke grup
     * Kebutuhan: 2.3
     */
    public function assignTes(Grup $grup, array $tesIds): void
    {
        $grup->tes()->sync($tesIds);
    }

    /**
     * Ambil tes yang di-assign ke grup
     * Kebutuhan: 2.1
     */
    public function ambilTesYangDiassign(Grup $grup): \Illuminate\Database\Eloquent\Collection
    {
        return $grup->tes()->withCount('soal')->orderBy('nama')->get();
    }
}
