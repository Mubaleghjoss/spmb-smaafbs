<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Jawaban
 * Kebutuhan: 2.1
 */
class Jawaban extends Model
{
    use HasFactory;

    protected $table = 'jawaban';

    protected $fillable = [
        'soal_id',
        'isi_jawaban',
        'benar',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'benar' => 'boolean',
        ];
    }

    /**
     * Relasi ke soal
     */
    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }

    /**
     * Scope untuk jawaban benar
     */
    public function scopeBenar($query)
    {
        return $query->where('benar', true);
    }
}
