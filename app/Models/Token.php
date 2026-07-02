<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Token
 * Kebutuhan: 4.4
 */
class Token extends Model
{
    use HasFactory;

    protected $table = 'token';

    protected $fillable = [
        'tes_id',
        'kode',
        'kedaluwarsa',
        'terpakai',
        'dipakai_oleh',
        'dipakai_pada',
    ];

    protected function casts(): array
    {
        return [
            'kedaluwarsa' => 'datetime',
            'terpakai' => 'boolean',
            'dipakai_pada' => 'datetime',
        ];
    }

    /**
     * Relasi ke tes
     */
    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }

    /**
     * Relasi ke peserta yang memakai
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'dipakai_oleh');
    }

    /**
     * Cek apakah token masih valid
     */
    public function masihValid(): bool
    {
        if ($this->terpakai) {
            return false;
        }
        
        if ($this->kedaluwarsa && now()->gt($this->kedaluwarsa)) {
            return false;
        }
        
        return true;
    }

    /**
     * Cek apakah token sudah kedaluwarsa
     */
    public function getSudahKedaluwarsaAttribute(): bool
    {
        return $this->kedaluwarsa && now()->gt($this->kedaluwarsa);
    }

    /**
     * Gunakan token
     */
    public function gunakan(Peserta $peserta): void
    {
        $this->update([
            'terpakai' => true,
            'dipakai_oleh' => $peserta->id,
            'dipakai_pada' => now(),
        ]);
    }

    /**
     * Scope untuk token yang tersedia
     */
    public function scopeTersedia($query)
    {
        return $query->where('terpakai', false)
            ->where(function ($q) {
                $q->whereNull('kedaluwarsa')
                    ->orWhere('kedaluwarsa', '>', now());
            });
    }
}
