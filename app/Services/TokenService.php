<?php

namespace App\Services;

use App\Models\Tes;
use App\Models\Token;
use App\Models\Peserta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Service untuk manajemen token
 * Kebutuhan: 4.4
 */
class TokenService
{
    /**
     * Ambil daftar token dengan filter dan paginasi
     */
    public function ambilDaftar(int $tesId, array $filter = [], int $perHalaman = 20): LengthAwarePaginator
    {
        $query = Token::with(['tes', 'peserta'])
            ->where('tes_id', $tesId);

        // Filter berdasarkan status
        if (isset($filter['terpakai'])) {
            $query->where('terpakai', $filter['terpakai']);
        }

        // Filter berdasarkan kedaluwarsa
        if (!empty($filter['kedaluwarsa'])) {
            if ($filter['kedaluwarsa'] === 'valid') {
                $query->where(function ($q) {
                    $q->whereNull('kedaluwarsa')
                        ->orWhere('kedaluwarsa', '>', now());
                });
            } elseif ($filter['kedaluwarsa'] === 'expired') {
                $query->where('kedaluwarsa', '<=', now());
            }
        }

        // Filter berdasarkan pencarian kode
        if (!empty($filter['cari'])) {
            $query->where('kode', 'like', "%{$filter['cari']}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perHalaman);
    }

    /**
     * Generate token unik
     * Kebutuhan: 4.4
     */
    public function generateKodeUnik(int $panjang = 8): string
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $kode = strtoupper(Str::random($panjang));
            $exists = Token::where('kode', $kode)->exists();
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new \Exception('Gagal generate token unik setelah ' . $maxAttempts . ' percobaan.');
            }
        } while ($exists);

        return $kode;
    }

    /**
     * Buat token baru
     */
    public function buat(Tes $tes, ?\DateTime $kedaluwarsa = null): Token
    {
        return Token::create([
            'tes_id' => $tes->id,
            'kode' => $this->generateKodeUnik(),
            'kedaluwarsa' => $kedaluwarsa,
            'terpakai' => false,
        ]);
    }

    /**
     * Generate batch token
     */
    public function generateBatch(Tes $tes, int $jumlah, ?\DateTime $kedaluwarsa = null): array
    {
        $tokens = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $tokens[] = $this->buat($tes, $kedaluwarsa);
        }

        return $tokens;
    }

    /**
     * Validasi token
     */
    public function validasi(string $kode): ?Token
    {
        $token = Token::with('tes')->where('kode', $kode)->first();

        if (!$token) {
            return null;
        }

        if (!$token->masihValid()) {
            return null;
        }

        if (!$token->tes->sedangBerlangsung()) {
            return null;
        }

        return $token;
    }

    /**
     * Gunakan token
     */
    public function gunakan(Token $token, Peserta $peserta): bool
    {
        if (!$token->masihValid()) {
            return false;
        }

        $token->gunakan($peserta);
        return true;
    }

    /**
     * Hapus token
     */
    public function hapus(Token $token): bool
    {
        if ($token->terpakai) {
            throw new \Exception('Tidak dapat menghapus token yang sudah terpakai.');
        }

        return $token->delete();
    }

    /**
     * Hapus batch token yang belum terpakai
     */
    public function hapusBatch(Tes $tes, array $tokenIds): int
    {
        return Token::where('tes_id', $tes->id)
            ->whereIn('id', $tokenIds)
            ->where('terpakai', false)
            ->delete();
    }

    /**
     * Hapus semua token yang belum terpakai
     */
    public function hapusSemuaBelumTerpakai(Tes $tes): int
    {
        return Token::where('tes_id', $tes->id)
            ->where('terpakai', false)
            ->delete();
    }

    /**
     * Update kedaluwarsa token
     */
    public function updateKedaluwarsa(Token $token, ?\DateTime $kedaluwarsa): Token
    {
        $token->update(['kedaluwarsa' => $kedaluwarsa]);
        return $token->fresh();
    }

    /**
     * Update kedaluwarsa batch
     */
    public function updateKedaluwarsaBatch(Tes $tes, array $tokenIds, ?\DateTime $kedaluwarsa): int
    {
        return Token::where('tes_id', $tes->id)
            ->whereIn('id', $tokenIds)
            ->where('terpakai', false)
            ->update(['kedaluwarsa' => $kedaluwarsa]);
    }

    /**
     * Ambil statistik token
     */
    public function ambilStatistik(Tes $tes): array
    {
        $total = Token::where('tes_id', $tes->id)->count();
        $terpakai = Token::where('tes_id', $tes->id)->where('terpakai', true)->count();
        $tersedia = Token::where('tes_id', $tes->id)->tersedia()->count();
        $kedaluwarsa = Token::where('tes_id', $tes->id)
            ->where('terpakai', false)
            ->where('kedaluwarsa', '<=', now())
            ->count();

        return [
            'total' => $total,
            'terpakai' => $terpakai,
            'tersedia' => $tersedia,
            'kedaluwarsa' => $kedaluwarsa,
            'belum_terpakai' => $total - $terpakai,
        ];
    }

    /**
     * Ambil token tersedia untuk tes
     */
    public function ambilTersedia(Tes $tes, int $limit = 10): Collection
    {
        return Token::where('tes_id', $tes->id)
            ->tersedia()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Cek apakah kode token sudah ada
     */
    public function kodeExists(string $kode): bool
    {
        return Token::where('kode', $kode)->exists();
    }

    /**
     * Reset token (untuk digunakan ulang)
     */
    public function reset(Token $token): Token
    {
        $token->update([
            'terpakai' => false,
            'dipakai_oleh' => null,
            'dipakai_pada' => null,
        ]);

        return $token->fresh();
    }

    /**
     * Ekspor token ke array
     */
    public function eksporKeArray(Tes $tes, bool $hanyaTersedia = false): array
    {
        $query = Token::where('tes_id', $tes->id);

        if ($hanyaTersedia) {
            $query->tersedia();
        }

        return $query->orderBy('created_at')->get()->map(function ($token) {
            return [
                'kode' => $token->kode,
                'kedaluwarsa' => $token->kedaluwarsa?->format('Y-m-d H:i:s'),
                'terpakai' => $token->terpakai ? 'Ya' : 'Tidak',
                'dipakai_oleh' => $token->peserta?->nama ?? '-',
                'dipakai_pada' => $token->dipakai_pada?->format('Y-m-d H:i:s') ?? '-',
            ];
        })->toArray();
    }
}
