<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\Grup;
use App\Models\TahapanSpmb;
use App\Helpers\NomorPendaftaranHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Service untuk manajemen peserta
 * Kebutuhan: 3.1, 3.3, 3.7
 */
class PesertaService
{
    /**
     * Ambil peserta dengan filter dan paginasi
     */
    public function ambilDenganFilter(array $filter = [], int $perHalaman = 15): LengthAwarePaginator
    {
        $query = Peserta::with(['tahapanSpmb', 'grup']);

        // Filter berdasarkan grup
        if (!empty($filter['grup_id'])) {
            $query->whereHas('grup', fn($q) => $q->where('grup.id', $filter['grup_id']));
        }

        // Filter berdasarkan tahapan
        if (!empty($filter['tahap'])) {
            $query->whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', $filter['tahap']));
        }

        // Filter berdasarkan status (aktif/dihapus)
        if (isset($filter['dengan_dihapus']) && $filter['dengan_dihapus']) {
            $query->withTrashed();
        }

        // Pencarian
        if (!empty($filter['cari'])) {
            $cari = $filter['cari'];
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$cari}%")
                  ->orWhere('email', 'like', "%{$cari}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perHalaman);
    }

    /**
     * Cari peserta berdasarkan ID
     */
    public function cariById(int $id): ?Peserta
    {
        return Peserta::with(['tahapanSpmb', 'grup', 'formulirSpmb', 'pembayaran'])->find($id);
    }

    /**
     * Buat peserta baru
     */
    public function buat(array $data): Peserta
    {
        return DB::transaction(function () use ($data) {
            // Generate nomor pendaftaran jika tidak ada
            if (empty($data['nomor_pendaftaran'])) {
                $data['nomor_pendaftaran'] = NomorPendaftaranHelper::generate();
            }

            // Simpan password plain untuk ditampilkan ke admin
            $plainPassword = $data['password'] ?? 'password123';

            // Password akan otomatis di-hash oleh model cast 'hashed'
            $peserta = Peserta::create([
                'nomor_pendaftaran' => $data['nomor_pendaftaran'],
                'nama' => $data['nama'],
                'email' => $data['email'],
                'password' => $plainPassword,
                'password_temp' => $plainPassword, // Simpan password sementara
                'telepon' => $data['telepon'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'asal_sekolah' => $data['asal_sekolah'] ?? null,
            ]);

            // Buat tahapan SPMB
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 2, // Langsung ke tahap 2 (Isi Formulir)
                'tahap_1_selesai' => true,
            ]);

            // Assign ke grup jika ada
            if (!empty($data['grup_id'])) {
                $peserta->grup()->attach($data['grup_id']);
            }

            return $peserta->load(['tahapanSpmb', 'grup']);
        });
    }


    /**
     * Perbarui peserta
     */
    public function perbarui(Peserta $peserta, array $data): Peserta
    {
        return DB::transaction(function () use ($peserta, $data) {
            $updateData = [
                'nama' => $data['nama'] ?? $peserta->nama,
                'email' => $data['email'] ?? $peserta->email,
                'telepon' => $data['telepon'] ?? $peserta->telepon,
                'alamat' => $data['alamat'] ?? $peserta->alamat,
                'asal_sekolah' => $data['asal_sekolah'] ?? $peserta->asal_sekolah,
            ];

            // Update password jika ada (akan otomatis di-hash oleh model cast 'hashed')
            if (!empty($data['password'])) {
                $updateData['password'] = $data['password'];
            }

            $peserta->update($updateData);

            // Update grup jika ada
            if (isset($data['grup_id'])) {
                $peserta->grup()->sync($data['grup_id'] ? [$data['grup_id']] : []);
            }

            return $peserta->fresh(['tahapanSpmb', 'grup']);
        });
    }

    /**
     * Hapus peserta (soft delete)
     */
    public function hapus(Peserta $peserta): bool
    {
        return $peserta->delete();
    }

    /**
     * Restore peserta yang dihapus
     */
    public function restore(int $id): ?Peserta
    {
        $peserta = Peserta::withTrashed()->find($id);
        if ($peserta && $peserta->trashed()) {
            $peserta->restore();
            return $peserta;
        }
        return null;
    }

    /**
     * Hapus permanen peserta
     */
    public function hapusPermanen(int $id): bool
    {
        $peserta = Peserta::withTrashed()->find($id);
        if ($peserta) {
            return $peserta->forceDelete();
        }
        return false;
    }

    /**
     * Ambil statistik peserta
     */
    public function ambilStatistik(): array
    {
        return [
            'total' => Peserta::count(),
            'aktif' => Peserta::count(),
            'dihapus' => Peserta::onlyTrashed()->count(),
            'per_tahap' => [
                1 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 1))->count(),
                2 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 2))->count(),
                3 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 3))->count(),
                4 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 4))->count(),
                5 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 5))->count(),
                6 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 6))->count(),
                7 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 7))->count(),
            ],
        ];
    }

    /**
     * Assign peserta ke grup
     */
    public function assignKeGrup(Peserta $peserta, int $grupId): void
    {
        $peserta->grup()->syncWithoutDetaching([$grupId]);
    }

    /**
     * Hapus peserta dari grup
     */
    public function hapusDariGrup(Peserta $peserta, int $grupId): void
    {
        $peserta->grup()->detach($grupId);
    }

    /**
     * Bulk assign peserta ke grup
     */
    public function bulkAssignKeGrup(array $pesertaIds, int $grupId): int
    {
        $count = 0;
        foreach ($pesertaIds as $pesertaId) {
            $peserta = Peserta::find($pesertaId);
            if ($peserta) {
                $peserta->grup()->syncWithoutDetaching([$grupId]);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Reset password peserta
     * Password akan otomatis di-hash oleh model cast 'hashed'
     */
    public function resetPassword(Peserta $peserta, string $passwordBaru = null): string
    {
        $password = $passwordBaru ?? substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
        $peserta->update([
            'password' => $password,
            'password_temp' => $password, // Simpan password sementara
        ]);
        return $password;
    }

    /**
     * Update password peserta secara manual oleh admin
     */
    public function updatePasswordManual(Peserta $peserta, string $password): void
    {
        $peserta->update([
            'password' => $password,
            'password_temp' => $password,
        ]);
    }
}
