<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model Grup Peserta
 * Kebutuhan: 3.3
 */
class Grup extends Model
{
    use HasFactory;

    protected $table = 'grup';

    protected $fillable = [
        'nama',
        'keterangan',
    ];

    /**
     * Relasi ke peserta
     */
    public function peserta(): BelongsToMany
    {
        return $this->belongsToMany(Peserta::class, 'grup_peserta')
            ->withTimestamps();
    }

    /**
     * Relasi ke tes
     * Kebutuhan: 2.1, 5.2
     */
    public function tes(): BelongsToMany
    {
        return $this->belongsToMany(Tes::class, 'grup_tes')
            ->withTimestamps();
    }

    /**
     * Hitung jumlah peserta dalam grup
     */
    public function getJumlahPesertaAttribute(): int
    {
        return $this->peserta()->count();
    }

    /**
     * Hitung jumlah tes yang di-assign ke grup
     * Kebutuhan: 5.2
     */
    public function getJumlahTesAttribute(): int
    {
        return $this->tes()->count();
    }
}
