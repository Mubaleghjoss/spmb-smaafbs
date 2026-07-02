<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\Soal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk manajemen tes
 * Kebutuhan: 4.1, 4.2, 4.3
 */
class TesService
{
    /**
     * Ambil daftar tes dengan filter dan paginasi
     */
    public function ambilDaftar(array $filter = [], int $perHalaman = 15): LengthAwarePaginator
    {
        $query = Tes::with(['pengguna', 'soal'])
            ->withCount(['soal', 'sesiTes', 'token', 'grup']);

        // Filter berdasarkan status
        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        // Filter berdasarkan pencarian
        if (!empty($filter['cari'])) {
            $query->where(function ($q) use ($filter) {
                $q->where('nama', 'like', "%{$filter['cari']}%")
                    ->orWhere('keterangan', 'like', "%{$filter['cari']}%");
            });
        }

        // Filter berdasarkan pembuat
        if (!empty($filter['pengguna_id'])) {
            $query->where('pengguna_id', $filter['pengguna_id']);
        }

        // Filter berdasarkan tanggal
        if (!empty($filter['tanggal_mulai'])) {
            $query->whereDate('mulai', '>=', $filter['tanggal_mulai']);
        }
        if (!empty($filter['tanggal_selesai'])) {
            $query->whereDate('selesai', '<=', $filter['tanggal_selesai']);
        }

        // Sorting
        $sortBy = $filter['sort_by'] ?? 'created_at';
        $sortDir = $filter['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perHalaman);
    }

    /**
     * Ambil tes berdasarkan ID
     */
    public function ambilById(int $id): ?Tes
    {
        return Tes::with(['pengguna', 'soal.topik', 'soal.jawaban', 'token'])
            ->withCount(['soal', 'sesiTes', 'token'])
            ->find($id);
    }

    /**
     * Buat tes baru
     */
    public function buat(array $data): Tes
    {
        return DB::transaction(function () use ($data) {
            $tes = Tes::create([
                'pengguna_id' => $data['pengguna_id'] ?? auth('pengguna')->id(),
                'nama' => $data['nama'],
                'keterangan' => $data['keterangan'] ?? null,
                'durasi_menit' => $data['durasi_menit'] ?? 60,
                'nilai_lulus' => $data['nilai_lulus'] ?? 60.00,
                'mulai' => $data['mulai'] ?? null,
                'selesai' => $data['selesai'] ?? null,
                'acak_soal' => $data['acak_soal'] ?? false,
                'acak_jawaban' => $data['acak_jawaban'] ?? false,
                'tampilkan_nilai' => $data['tampilkan_nilai'] ?? true,
                'tampilkan_pembahasan' => $data['tampilkan_pembahasan'] ?? false,
                'status' => $data['status'] ?? 'draft',
            ]);

            // Tambahkan soal jika ada
            if (!empty($data['soal_ids'])) {
                $this->aturSoal($tes, $data['soal_ids']);
            }

            return $tes;
        });
    }

    /**
     * Update tes
     */
    public function update(Tes $tes, array $data): Tes
    {
        return DB::transaction(function () use ($tes, $data) {
            $tes->update([
                'nama' => $data['nama'] ?? $tes->nama,
                'keterangan' => $data['keterangan'] ?? $tes->keterangan,
                'durasi_menit' => $data['durasi_menit'] ?? $tes->durasi_menit,
                'nilai_lulus' => $data['nilai_lulus'] ?? $tes->nilai_lulus,
                'mulai' => $data['mulai'] ?? $tes->mulai,
                'selesai' => $data['selesai'] ?? $tes->selesai,
                'acak_soal' => $data['acak_soal'] ?? $tes->acak_soal,
                'acak_jawaban' => $data['acak_jawaban'] ?? $tes->acak_jawaban,
                'tampilkan_nilai' => $data['tampilkan_nilai'] ?? $tes->tampilkan_nilai,
                'tampilkan_pembahasan' => $data['tampilkan_pembahasan'] ?? $tes->tampilkan_pembahasan,
                'status' => $data['status'] ?? $tes->status,
            ]);

            // Update soal jika ada
            if (isset($data['soal_ids'])) {
                $this->aturSoal($tes, $data['soal_ids']);
            }

            return $tes->fresh();
        });
    }

    /**
     * Hapus tes
     */
    public function hapus(Tes $tes): bool
    {
        // Cek apakah ada sesi yang sedang berlangsung
        if ($tes->sesiTes()->where('status', 'berlangsung')->exists()) {
            throw new \Exception('Tidak dapat menghapus tes yang sedang berlangsung.');
        }

        return $tes->delete();
    }

    /**
     * Atur soal untuk tes
     */
    public function aturSoal(Tes $tes, array $soalIds, array $bobotCustom = []): void
    {
        $syncData = [];
        foreach ($soalIds as $index => $soalId) {
            $syncData[$soalId] = [
                'urutan' => $index + 1,
                'bobot_custom' => $bobotCustom[$soalId] ?? null,
            ];
        }

        $tes->soal()->sync($syncData);
    }

    /**
     * Tambah soal ke tes
     */
    public function tambahSoal(Tes $tes, int $soalId, ?int $bobotCustom = null): void
    {
        $maxUrutan = $tes->soal()->max('tes_soal.urutan') ?? 0;

        $tes->soal()->attach($soalId, [
            'urutan' => $maxUrutan + 1,
            'bobot_custom' => $bobotCustom,
        ]);
    }

    /**
     * Hapus soal dari tes
     */
    public function hapusSoal(Tes $tes, int $soalId): void
    {
        $tes->soal()->detach($soalId);
        $this->reorderSoal($tes);
    }

    /**
     * Reorder soal setelah penghapusan
     */
    private function reorderSoal(Tes $tes): void
    {
        $soalIds = $tes->soal()->orderBy('tes_soal.urutan')->pluck('soal.id')->toArray();
        $this->aturSoal($tes, $soalIds);
    }

    /**
     * Update urutan soal
     */
    public function updateUrutanSoal(Tes $tes, array $urutanBaru): void
    {
        foreach ($urutanBaru as $soalId => $urutan) {
            $tes->soal()->updateExistingPivot($soalId, ['urutan' => $urutan]);
        }
    }

    /**
     * Update bobot soal
     */
    public function updateBobotSoal(Tes $tes, int $soalId, ?int $bobotCustom): void
    {
        $tes->soal()->updateExistingPivot($soalId, ['bobot_custom' => $bobotCustom]);
    }


    /**
     * Acak soal untuk sesi tes
     * Kebutuhan: 4.3
     */
    public function acakSoal(Tes $tes, ?int $seed = null): array
    {
        $soalIds = $tes->soal()->pluck('soal.id')->toArray();

        if ($tes->acak_soal) {
            if ($seed !== null) {
                mt_srand($seed);
            }
            shuffle($soalIds);
            if ($seed !== null) {
                mt_srand(); // Reset seed
            }
        }

        return $soalIds;
    }

    /**
     * Acak jawaban untuk soal
     * Kebutuhan: 4.3
     */
    public function acakJawaban(Soal $soal, ?int $seed = null): array
    {
        $jawabanIds = $soal->jawaban()->pluck('id')->toArray();

        if ($seed !== null) {
            mt_srand($seed);
        }
        shuffle($jawabanIds);
        if ($seed !== null) {
            mt_srand(); // Reset seed
        }

        return $jawabanIds;
    }

    /**
     * Ubah status tes
     */
    public function ubahStatus(Tes $tes, string $status): Tes
    {
        if (!in_array($status, ['draft', 'aktif', 'selesai'])) {
            throw new \InvalidArgumentException('Status tidak valid.');
        }

        // Validasi sebelum mengaktifkan
        if ($status === 'aktif') {
            $this->validasiSebelumAktif($tes);
        }

        $tes->update(['status' => $status]);
        return $tes->fresh();
    }

    /**
     * Validasi tes sebelum diaktifkan
     */
    private function validasiSebelumAktif(Tes $tes): void
    {
        $errors = [];

        if ($tes->soal()->count() === 0) {
            $errors[] = 'Tes harus memiliki minimal 1 soal.';
        }

        if ($tes->durasi_menit < 1) {
            $errors[] = 'Durasi tes harus minimal 1 menit.';
        }

        if (!empty($errors)) {
            throw new \Exception(implode(' ', $errors));
        }
    }

    /**
     * Duplikat tes
     */
    public function duplikat(Tes $tes, ?string $namaBaru = null): Tes
    {
        return DB::transaction(function () use ($tes, $namaBaru) {
            $tesBaru = $tes->replicate();
            $tesBaru->nama = $namaBaru ?? $tes->nama . ' (Salinan)';
            $tesBaru->status = 'draft';
            $tesBaru->mulai = null;
            $tesBaru->selesai = null;
            $tesBaru->save();

            // Duplikat soal
            $soalData = $tes->soal()->get()->mapWithKeys(function ($soal) {
                return [$soal->id => [
                    'urutan' => $soal->pivot->urutan,
                    'bobot_custom' => $soal->pivot->bobot_custom,
                ]];
            })->toArray();

            $tesBaru->soal()->attach($soalData);

            return $tesBaru;
        });
    }

    /**
     * Ambil statistik tes
     */
    public function ambilStatistik(Tes $tes): array
    {
        $sesiSelesai = $tes->sesiTes()->where('status', 'selesai')->get();

        $nilaiList = $sesiSelesai->pluck('nilai')->filter()->values();

        return [
            'jumlah_soal' => $tes->soal()->count(),
            'jumlah_token' => $tes->token()->count(),
            'token_terpakai' => $tes->token()->where('terpakai', true)->count(),
            'token_tersedia' => $tes->token()->tersedia()->count(),
            'jumlah_peserta' => $tes->sesiTes()->distinct('peserta_id')->count(),
            'sesi_berlangsung' => $tes->sesiTes()->where('status', 'berlangsung')->count(),
            'sesi_selesai' => $sesiSelesai->count(),
            'nilai_rata_rata' => $nilaiList->avg() ?? 0,
            'nilai_tertinggi' => $nilaiList->max() ?? 0,
            'nilai_terendah' => $nilaiList->min() ?? 0,
            'jumlah_lulus' => $nilaiList->filter(fn($n) => $n >= $tes->nilai_lulus)->count(),
            'jumlah_tidak_lulus' => $nilaiList->filter(fn($n) => $n < $tes->nilai_lulus)->count(),
        ];
    }

    /**
     * Ambil soal yang tersedia untuk ditambahkan ke tes
     */
    public function ambilSoalTersedia(Tes $tes, array $filter = [], bool $tampilkanSemua = false): LengthAwarePaginator|Collection
    {
        $soalTerpilih = $tes->soal()->pluck('soal.id')->toArray();

        $query = Soal::with(['topik', 'jawaban'])
            ->where('aktif', true)
            ->whereNotIn('id', $soalTerpilih);

        // Filter berdasarkan topik
        if (!empty($filter['topik_id'])) {
            $query->where('topik_id', $filter['topik_id']);
        }

        // Filter berdasarkan tipe
        if (!empty($filter['tipe'])) {
            $query->where('tipe', $filter['tipe']);
        }

        // Filter berdasarkan pencarian
        if (!empty($filter['cari'])) {
            $query->where('pertanyaan', 'like', "%{$filter['cari']}%");
        }

        // Urutkan berdasarkan urutan, lalu created_at
        $query->orderBy('urutan', 'asc')->orderBy('created_at', 'desc');

        // Jika tampilkan semua, return collection tanpa pagination
        if ($tampilkanSemua) {
            return $query->get();
        }

        return $query->paginate(20);
    }

    /**
     * Ambil daftar tes aktif
     */
    public function ambilTesAktif(): Collection
    {
        return Tes::aktif()
            ->sedangBerlangsung()
            ->withCount('soal')
            ->orderBy('mulai')
            ->get();
    }

    /**
     * Hitung total bobot soal dalam tes
     */
    public function hitungTotalBobot(Tes $tes): int
    {
        $total = 0;
        foreach ($tes->soal as $soal) {
            $total += $soal->pivot->bobot_custom ?? $soal->bobot;
        }
        return $total;
    }

    /**
     * Assign grup ke tes
     * Kebutuhan: 1.3, 1.4
     */
    public function assignGrup(Tes $tes, array $grupIds): void
    {
        $tes->grup()->sync($grupIds);
    }

    /**
     * Ambil grup yang di-assign ke tes
     * Kebutuhan: 1.1
     */
    public function ambilGrupYangDiassign(Tes $tes): Collection
    {
        return $tes->grup()->withCount('peserta')->orderBy('nama')->get();
    }

    /**
     * Hitung potensial peserta berdasarkan grup yang di-assign
     * Kebutuhan: 5.3
     */
    public function hitungPotensialPeserta(Tes $tes): int
    {
        $grupIds = $tes->grup()->pluck('grup.id')->toArray();
        
        if (empty($grupIds)) {
            // Jika tidak ada grup, semua peserta bisa akses
            return \App\Models\Peserta::count();
        }
        
        // Hitung peserta unik dari semua grup yang di-assign
        return \App\Models\Peserta::whereHas('grup', function ($query) use ($grupIds) {
            $query->whereIn('grup.id', $grupIds);
        })->count();
    }

    /**
     * Ambil tes yang tersedia untuk peserta berdasarkan grup
     * Kebutuhan: 3.1, 3.3
     * Menampilkan semua tes aktif tanpa cek waktu, siswa bebas memilih
     */
    public function ambilTesTersediaUntukPeserta(\App\Models\Peserta $peserta): Collection
    {
        $grupIds = $peserta->grup()->pluck('grup.id')->toArray();
        
        // Ambil semua tes dengan status aktif
        return Tes::where('status', 'aktif')
            ->withCount('soal')
            ->where(function ($query) use ($grupIds) {
                // Tes tanpa grup (tersedia untuk semua peserta)
                $query->whereDoesntHave('grup');
                
                // Atau tes dengan grup yang peserta ikuti
                if (!empty($grupIds)) {
                    $query->orWhereHas('grup', function ($q) use ($grupIds) {
                        $q->whereIn('grup.id', $grupIds);
                    });
                }
            })
            ->orderBy('nama')
            ->get();
    }

    /**
     * Cek apakah peserta bisa akses tes
     * Kebutuhan: 3.2, 3.3
     * Tes tanpa grup = tersedia untuk semua peserta
     * Tes dengan grup = hanya peserta di grup tersebut
     */
    public function cekAksesPeserta(Tes $tes, \App\Models\Peserta $peserta): bool
    {
        // Jika tes tidak punya grup, semua peserta bisa akses
        $grupTes = $tes->grup()->pluck('grup.id')->toArray();
        if (empty($grupTes)) {
            return true;
        }
        
        // Cek apakah peserta ada di salah satu grup yang di-assign
        $pesertaGrupIds = $peserta->grup()->pluck('grup.id')->toArray();
        
        return !empty(array_intersect($grupTes, $pesertaGrupIds));
    }
}
