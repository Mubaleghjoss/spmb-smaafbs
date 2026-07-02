<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilingMapping extends Model
{
    protected $table = 'profiling_mapping';

    protected $fillable = [
        'tes_id',
        'nomor_soal',
        'jawaban_a',
        'jawaban_b',
        'jawaban_c',
        'jawaban_d',
        'jawaban_e',
    ];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }

    /**
     * Ambil pilar untuk jawaban tertentu
     */
    public function getPilarForJawaban(string $jawaban): ?string
    {
        $jawaban = strtolower($jawaban);
        return match($jawaban) {
            'a' => $this->jawaban_a,
            'b' => $this->jawaban_b,
            'c' => $this->jawaban_c,
            'd' => $this->jawaban_d,
            'e' => $this->jawaban_e,
            default => null,
        };
    }
}
