<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilProfiling extends Model
{
    protected $table = 'hasil_profiling';

    protected $fillable = [
        'sesi_tes_id',
        'pilar_dominan',
        'pilar_dominan_2',
        'skor_kreatif',
        'skor_emosional',
        'skor_aksi',
        'skor_logika',
        'skor_spiritual',
        'detail_jawaban',
    ];

    protected $casts = [
        'detail_jawaban' => 'array',
    ];

    public function sesiTes(): BelongsTo
    {
        return $this->belongsTo(SesiTes::class);
    }

    /**
     * Ambil semua skor sebagai array
     */
    public function getSkorArray(): array
    {
        return [
            'kreatif' => $this->skor_kreatif,
            'emosional' => $this->skor_emosional,
            'aksi' => $this->skor_aksi,
            'logika' => $this->skor_logika,
            'spiritual' => $this->skor_spiritual,
        ];
    }

    /**
     * Ambil deskripsi pilar dominan
     */
    public function getDeskripsiPilarAttribute(): ?array
    {
        return ProfilingConfig::pilarList()[$this->pilar_dominan] ?? null;
    }
}
