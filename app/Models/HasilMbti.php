<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilMbti extends Model
{
    protected $table = 'hasil_mbti';

    protected $fillable = [
        'sesi_tes_id',
        'tipe_mbti',
        'tipe_mbti_2',
        'skor_e',
        'skor_i',
        'skor_s',
        'skor_n',
        'skor_t',
        'skor_f',
        'skor_j',
        'skor_p',
        'detail_perhitungan',
    ];

    protected $casts = [
        'detail_perhitungan' => 'array',
    ];

    public function sesiTes(): BelongsTo
    {
        return $this->belongsTo(SesiTes::class);
    }

    /**
     * Ambil deskripsi tipe MBTI
     */
    public function getDeskripsiTipeAttribute(): ?array
    {
        return MbtiConfig::tipeMbtiList()[$this->tipe_mbti] ?? null;
    }
}
