<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Jawaban Peserta
 * Kebutuhan: 5.2
 */
class JawabanPeserta extends Model
{
    use HasFactory;

    protected $table = 'jawaban_peserta';

    protected $fillable = [
        'sesi_tes_id',
        'soal_id',
        'jawaban_id',
        'jawaban_ganda',
        'jawaban_esai',
        'benar',
        'ragu',
    ];

    protected function casts(): array
    {
        return [
            'sesi_tes_id' => 'integer',
            'soal_id' => 'integer',
            'jawaban_id' => 'integer',
            'jawaban_ganda' => 'array',
            'benar' => 'boolean',
            'ragu' => 'boolean',
        ];
    }

    /**
     * Relasi ke sesi tes
     */
    public function sesiTes(): BelongsTo
    {
        return $this->belongsTo(SesiTes::class);
    }

    /**
     * Relasi ke soal
     */
    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }

    /**
     * Relasi ke jawaban yang dipilih
     */
    public function jawaban(): BelongsTo
    {
        return $this->belongsTo(Jawaban::class);
    }

    /**
     * Cek apakah sudah dijawab
     */
    public function getSudahDijawabAttribute(): bool
    {
        return $this->jawaban_id !== null 
            || $this->jawaban_esai !== null 
            || !empty($this->jawaban_ganda);
    }
}
