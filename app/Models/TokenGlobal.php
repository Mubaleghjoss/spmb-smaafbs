<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Token Global
 * Token yang bisa dipakai untuk semua tes dan banyak peserta
 */
class TokenGlobal extends Model
{
    use HasFactory;

    protected $table = 'token_global';

    protected $fillable = [
        'kode',
        'nama',
        'keterangan',
        'mulai',
        'selesai',
        'aktif',
        'jumlah_penggunaan',
    ];

    protected function casts(): array
    {
        return [
            'mulai' => 'datetime',
            'selesai' => 'datetime',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Relasi ke tes yang bisa diakses dengan token ini
     */
    public function tes(): BelongsToMany
    {
        return $this->belongsToMany(Tes::class, 'token_global_tes', 'token_global_id', 'tes_id')
            ->withTimestamps();
    }

    /**
     * Relasi ke log penggunaan
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TokenGlobalLog::class, 'token_global_id');
    }

    /**
     * Cek apakah token masih valid
     */
    public function masihValid(): bool
    {
        if (!$this->aktif) {
            return false;
        }
        
        // Cek waktu mulai
        if ($this->mulai && now()->lt($this->mulai)) {
            return false;
        }
        
        // Cek waktu selesai
        if ($this->selesai && now()->gt($this->selesai)) {
            return false;
        }
        
        return true;
    }

    /**
     * Cek apakah token sudah kedaluwarsa
     */
    public function getSudahKedaluwarsaAttribute(): bool
    {
        return $this->selesai && now()->gt($this->selesai);
    }

    /**
     * Cek apakah token belum mulai
     */
    public function getBelumMulaiAttribute(): bool
    {
        return $this->mulai && now()->lt($this->mulai);
    }

    /**
     * Catat penggunaan token
     */
    public function catatPenggunaan(Peserta $peserta, ?string $ip = null, ?string $userAgent = null): void
    {
        $this->logs()->create([
            'peserta_id' => $peserta->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
        
        $this->increment('jumlah_penggunaan');
    }

    /**
     * Scope untuk token yang aktif dan valid
     */
    public function scopeValid($query)
    {
        return $query->where('aktif', true)
            ->where(function ($q) {
                $q->whereNull('mulai')
                    ->orWhere('mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('selesai')
                    ->orWhere('selesai', '>', now());
            });
    }

    /**
     * Generate kode token unik
     */
    public static function generateKode(int $length = 8): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Tanpa I, O, 0, 1 untuk menghindari kebingungan
        $kode = '';
        
        do {
            $kode = '';
            for ($i = 0; $i < $length; $i++) {
                $kode .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (self::where('kode', $kode)->exists());
        
        return $kode;
    }
}
