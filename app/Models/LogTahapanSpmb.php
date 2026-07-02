<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Log Tahapan SPMB
 * Kebutuhan: 16.7
 */
class LogTahapanSpmb extends Model
{
    use HasFactory;

    protected $table = 'log_tahapan_spmb';

    protected $fillable = [
        'peserta_id',
        'tahap',
        'aksi',
        'status_lama',
        'status_baru',
        'pesan',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'status_lama' => 'boolean',
            'status_baru' => 'boolean',
        ];
    }

    /**
     * Relasi ke peserta
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    /**
     * Relasi ke admin
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'admin_id');
    }
}
