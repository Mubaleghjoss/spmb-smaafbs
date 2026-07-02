<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\Peserta;
use App\Models\TokenGlobal;
use Illuminate\Support\Collection;

class TokenGlobalService
{
    /**
     * Buat token global baru
     */
    public function buat(array $data): TokenGlobal
    {
        $token = TokenGlobal::create([
            'kode' => $data['kode'] ?? TokenGlobal::generateKode(),
            'nama' => $data['nama'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'mulai' => $data['mulai'] ?? null,
            'selesai' => $data['selesai'] ?? null,
            'aktif' => $data['aktif'] ?? true,
        ]);

        // Assign tes jika ada
        if (!empty($data['tes_ids'])) {
            $token->tes()->sync($data['tes_ids']);
        }

        return $token;
    }

    /**
     * Update token global
     */
    public function update(TokenGlobal $token, array $data): TokenGlobal
    {
        $token->update([
            'nama' => $data['nama'] ?? $token->nama,
            'keterangan' => $data['keterangan'] ?? $token->keterangan,
            'mulai' => $data['mulai'] ?? $token->mulai,
            'selesai' => $data['selesai'] ?? $token->selesai,
            'aktif' => $data['aktif'] ?? $token->aktif,
        ]);

        // Update tes jika ada
        if (isset($data['tes_ids'])) {
            $token->tes()->sync($data['tes_ids']);
        }

        return $token->fresh();
    }

    /**
     * Hapus token global
     */
    public function hapus(TokenGlobal $token): bool
    {
        return $token->delete();
    }

    /**
     * Validasi token untuk login
     */
    public function validasi(string $kode): ?TokenGlobal
    {
        $token = TokenGlobal::where('kode', $kode)->first();

        if (!$token || !$token->masihValid()) {
            return null;
        }

        return $token;
    }

    /**
     * Ambil tes yang bisa diakses dengan token
     */
    public function ambilTesDenganToken(TokenGlobal $token): Collection
    {
        // Jika tidak ada tes yang di-assign, berarti semua tes aktif bisa diakses
        if ($token->tes->isEmpty()) {
            return Tes::where('status', 'aktif')
                ->withCount('soal')
                ->get();
        }

        return $token->tes()
            ->where('status', 'aktif')
            ->withCount('soal')
            ->get();
    }

    /**
     * Catat penggunaan token
     */
    public function catatPenggunaan(TokenGlobal $token, Peserta $peserta, ?string $ip = null, ?string $userAgent = null): void
    {
        $token->catatPenggunaan($peserta, $ip, $userAgent);
    }

    /**
     * Ambil semua token global
     */
    public function ambilSemua(array $filter = []): Collection
    {
        $query = TokenGlobal::with(['tes'])
            ->withCount('logs');

        if (!empty($filter['cari'])) {
            $query->where(function ($q) use ($filter) {
                $q->where('kode', 'like', '%' . $filter['cari'] . '%')
                    ->orWhere('nama', 'like', '%' . $filter['cari'] . '%');
            });
        }

        if (isset($filter['aktif'])) {
            $query->where('aktif', $filter['aktif']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Ambil statistik token global
     */
    public function ambilStatistik(): array
    {
        return [
            'total' => TokenGlobal::count(),
            'aktif' => TokenGlobal::where('aktif', true)->count(),
            'tidak_aktif' => TokenGlobal::where('aktif', false)->count(),
            'total_penggunaan' => TokenGlobal::sum('jumlah_penggunaan'),
        ];
    }

    /**
     * Generate token baru dengan assign ke semua tes aktif
     */
    public function buatUntukSemuaTes(array $data): TokenGlobal
    {
        $tesAktif = Tes::where('status', 'aktif')->pluck('id')->toArray();
        $data['tes_ids'] = $tesAktif;
        
        return $this->buat($data);
    }

    /**
     * Toggle status aktif token
     */
    public function toggleAktif(TokenGlobal $token): TokenGlobal
    {
        $token->update(['aktif' => !$token->aktif]);
        return $token->fresh();
    }
}
