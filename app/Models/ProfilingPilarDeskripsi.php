<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilingPilarDeskripsi extends Model
{
    protected $table = 'profiling_pilar_deskripsi';

    protected $fillable = [
        'tes_id',
        'pilar',
        'kode_qx',
        'nama_qx',
        'deskripsi',
        'kekuatan',
        'saran_pengembangan',
    ];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }
}
