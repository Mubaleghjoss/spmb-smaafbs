<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Jadwal Wawancara
 * Kebutuhan: 14.1
 */
class JadwalWawancara extends Model
{
    use HasFactory;

    protected $table = 'jadwal_wawancara';

    protected $fillable = [
        'judul',
        'tanggal_waktu',
        'lokasi',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_waktu' => 'datetime',
        ];
    }

    /**
     * Relasi ke peserta wawancara
     */
    public function pesertaWawancara(): HasMany
    {
        return $this->hasMany(PesertaWawancara::class, 'jadwal_id');
    }

    /**
     * Hitung jumlah peserta
     */
    public function getJumlahPesertaAttribute(): int
    {
        return $this->pesertaWawancara()->count();
    }
}
