<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Topik (Kategori Soal)
 * Kebutuhan: 2.5
 */
class Topik extends Model
{
    use HasFactory;

    protected $table = 'topik';

    protected $fillable = [
        'nama',
        'keterangan',
        'parent_id',
    ];

    /**
     * Relasi ke parent topik
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Topik::class, 'parent_id');
    }

    /**
     * Relasi ke child topik
     */
    public function children(): HasMany
    {
        return $this->hasMany(Topik::class, 'parent_id');
    }

    /**
     * Relasi ke soal
     */
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    /**
     * Hitung jumlah soal dalam topik
     */
    public function getJumlahSoalAttribute(): int
    {
        return $this->soal()->count();
    }
}
