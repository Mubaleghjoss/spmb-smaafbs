<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MbtiTipeDeskripsi extends Model
{
    protected $table = 'mbti_tipe_deskripsi';

    protected $fillable = [
        'tes_id',
        'tipe',
        'nama',
        'deskripsi',
        'kekuatan',
        'kelemahan',
        'karir_cocok',
    ];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }
}
