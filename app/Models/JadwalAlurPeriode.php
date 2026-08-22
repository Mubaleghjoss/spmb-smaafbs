<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jadwal satu tahap SPMB untuk satu periode (tahun ajaran).
 */
class JadwalAlurPeriode extends Model
{
    protected $table = 'jadwal_alur_periode';

    protected $fillable = [
        'tahun_ajaran_id',
        'tahap',
        'dibuka',
        'tanggal_buka',
        'waktu_mulai',
        'tanggal_tutup',
        'waktu_selesai',
        'lokasi',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'dibuka' => 'boolean',
            'tanggal_buka' => 'date',
            'tanggal_tutup' => 'date',
            'tahap' => 'integer',
        ];
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
