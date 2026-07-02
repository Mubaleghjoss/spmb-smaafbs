<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Riwayat Soal
 * Kebutuhan: 2.8
 */
class RiwayatSoal extends Model
{
    use HasFactory;

    protected $table = 'riwayat_soal';

    protected $fillable = [
        'soal_id',
        'pertanyaan_lama',
        'pertanyaan_baru',
        'diubah_oleh',
    ];

    /**
     * Relasi ke soal
     */
    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }

    /**
     * Relasi ke pengubah
     */
    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diubah_oleh');
    }
}
