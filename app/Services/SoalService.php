<?php

namespace App\Services;

use App\Models\Soal;
use App\Models\Jawaban;
use App\Models\RiwayatSoal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Service untuk manajemen bank soal
 * Kebutuhan: 2.1, 2.7
 */
class SoalService
{
    /**
     * Ambil soal dengan filter dan paginasi
     */
    public function ambilDenganFilter(array $filter = [], int $perHalaman = 15): LengthAwarePaginator
    {
        $query = Soal::with(['topik', 'jawaban', 'pembuat']);

        // Filter berdasarkan topik
        if (!empty($filter['topik_id'])) {
            $query->where('topik_id', $filter['topik_id']);
        }

        // Filter berdasarkan tipe
        if (!empty($filter['tipe'])) {
            $query->where('tipe', $filter['tipe']);
        }

        // Filter berdasarkan status aktif
        if (isset($filter['aktif'])) {
            $query->where('aktif', $filter['aktif']);
        }

        // Pencarian
        if (!empty($filter['cari'])) {
            $query->where('pertanyaan', 'like', '%' . $filter['cari'] . '%');
        }

        // Urutkan berdasarkan kolom urutan, lalu created_at
        return $query->orderBy('urutan', 'asc')->orderBy('created_at', 'desc')->paginate($perHalaman);
    }

    /**
     * Ambil soal dengan filter tanpa paginasi (untuk preview)
     */
    public function ambilDenganFilterTanpaPagination(array $filter = []): Collection
    {
        $query = Soal::with(['topik', 'jawaban', 'pembuat']);

        if (!empty($filter['topik_id'])) {
            $query->where('topik_id', $filter['topik_id']);
        }

        if (!empty($filter['tipe'])) {
            $query->where('tipe', $filter['tipe']);
        }

        if (isset($filter['aktif'])) {
            $query->where('aktif', $filter['aktif']);
        }

        if (!empty($filter['cari'])) {
            $query->where('pertanyaan', 'like', '%' . $filter['cari'] . '%');
        }

        return $query->orderBy('urutan', 'asc')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Cari soal berdasarkan ID
     */
    public function cariById(int $id): ?Soal
    {
        return Soal::with(['topik', 'jawaban', 'pembuat'])->find($id);
    }

    /**
     * Buat soal baru dengan jawaban
     */
    public function buat(array $data, int $penggunaId): Soal
    {
        return DB::transaction(function () use ($data, $penggunaId) {
            $soal = Soal::create([
                'topik_id' => $data['topik_id'] ?? null,
                'pertanyaan' => $data['pertanyaan'],
                'tipe' => $data['tipe'] ?? 'pilihan_ganda',
                'bobot' => $data['bobot'] ?? 1,
                'pembahasan' => $data['pembahasan'] ?? null,
                'aktif' => $data['aktif'] ?? true,
                'dibuat_oleh' => $penggunaId,
            ]);

            // Simpan jawaban jika ada
            if (!empty($data['jawaban'])) {
                $this->simpanJawaban($soal, $data['jawaban']);
            }

            return $soal->load('jawaban');
        });
    }


    /**
     * Perbarui soal
     */
    public function perbarui(Soal $soal, array $data, int $penggunaId): Soal
    {
        return DB::transaction(function () use ($soal, $data, $penggunaId) {
            $pertanyaanLama = $soal->pertanyaan;

            $soal->update([
                'topik_id' => $data['topik_id'] ?? $soal->topik_id,
                'pertanyaan' => $data['pertanyaan'] ?? $soal->pertanyaan,
                'tipe' => $data['tipe'] ?? $soal->tipe,
                'bobot' => $data['bobot'] ?? $soal->bobot,
                'pembahasan' => $data['pembahasan'] ?? $soal->pembahasan,
                'aktif' => $data['aktif'] ?? $soal->aktif,
            ]);

            // Simpan riwayat jika pertanyaan berubah
            if ($pertanyaanLama !== $soal->pertanyaan) {
                RiwayatSoal::create([
                    'soal_id' => $soal->id,
                    'pertanyaan_lama' => $pertanyaanLama,
                    'pertanyaan_baru' => $soal->pertanyaan,
                    'diubah_oleh' => $penggunaId,
                ]);
            }

            // Update jawaban jika ada
            if (!empty($data['jawaban'])) {
                $soal->jawaban()->delete();
                $this->simpanJawaban($soal, $data['jawaban']);
            }

            return $soal->fresh(['topik', 'jawaban']);
        });
    }

    /**
     * Hapus soal
     */
    public function hapus(Soal $soal): bool
    {
        return DB::transaction(function () use ($soal) {
            // Hapus media jika ada
            if ($soal->media) {
                Storage::disk('public')->delete($soal->media);
            }

            return $soal->delete();
        });
    }

    /**
     * Toggle status aktif soal
     */
    public function toggleAktif(Soal $soal): Soal
    {
        $soal->update(['aktif' => !$soal->aktif]);
        return $soal;
    }

    /**
     * Simpan jawaban untuk soal
     */
    private function simpanJawaban(Soal $soal, array $jawabanList): void
    {
        foreach ($jawabanList as $index => $jawaban) {
            Jawaban::create([
                'soal_id' => $soal->id,
                'isi_jawaban' => $jawaban['isi'],
                'benar' => $jawaban['benar'] ?? false,
                'urutan' => $index,
            ]);
        }
    }

    /**
     * Upload media untuk soal
     */
    public function uploadMedia(Soal $soal, $file, string $tipeMedia): Soal
    {
        // Hapus media lama jika ada
        if ($soal->media) {
            Storage::disk('public')->delete($soal->media);
        }

        $path = $file->store('soal/media', 'public');

        $soal->update([
            'media' => $path,
            'tipe_media' => $tipeMedia,
        ]);

        return $soal;
    }

    /**
     * Hapus media dari soal
     */
    public function hapusMedia(Soal $soal): Soal
    {
        if ($soal->media) {
            Storage::disk('public')->delete($soal->media);
            $soal->update([
                'media' => null,
                'tipe_media' => null,
            ]);
        }

        return $soal;
    }

    /**
     * Duplikat soal
     */
    public function duplikat(Soal $soal, int $penggunaId): Soal
    {
        return DB::transaction(function () use ($soal, $penggunaId) {
            $soalBaru = $soal->replicate();
            $soalBaru->pertanyaan = '[DUPLIKAT] ' . $soal->pertanyaan;
            $soalBaru->dibuat_oleh = $penggunaId;
            $soalBaru->save();

            // Duplikat jawaban
            foreach ($soal->jawaban as $jawaban) {
                $jawabanBaru = $jawaban->replicate();
                $jawabanBaru->soal_id = $soalBaru->id;
                $jawabanBaru->save();
            }

            return $soalBaru->load('jawaban');
        });
    }

    /**
     * Ambil statistik soal
     */
    public function ambilStatistik(): array
    {
        return [
            'total' => Soal::count(),
            'aktif' => Soal::where('aktif', true)->count(),
            'nonaktif' => Soal::where('aktif', false)->count(),
            'per_tipe' => [
                'pilihan_ganda' => Soal::where('tipe', 'pilihan_ganda')->count(),
                'jawaban_ganda' => Soal::where('tipe', 'jawaban_ganda')->count(),
                'esai' => Soal::where('tipe', 'esai')->count(),
                'benar_salah' => Soal::where('tipe', 'benar_salah')->count(),
            ],
        ];
    }

    /**
     * Ambil soal acak berdasarkan topik
     */
    public function ambilAcak(int $jumlah, ?int $topikId = null): Collection
    {
        $query = Soal::aktif()->with('jawaban');

        if ($topikId) {
            $query->where('topik_id', $topikId);
        }

        return $query->inRandomOrder()->limit($jumlah)->get();
    }
}
