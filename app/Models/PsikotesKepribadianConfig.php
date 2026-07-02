<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsikotesKepribadianConfig extends Model
{
    use HasFactory;

    protected $table = 'psikotes_kepribadian_config';

    protected $fillable = [
        'tes_id',
        'tipe_kepribadian',
        'nomor_soal',
        'deskripsi',
    ];

    protected $casts = [
        'nomor_soal' => 'array',
    ];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }

    /**
     * Daftar tipe kepribadian yang tersedia
     */
    public static function tipeKepribadianList(): array
    {
        return [
            'koleris' => 'Koleris',
            'sanguin' => 'Sanguin',
            'plegmatis' => 'Plegmatis',
            'melankolis' => 'Melankolis',
        ];
    }

    /**
     * Default mapping nomor soal
     */
    public static function defaultMapping(): array
    {
        return [
            'koleris' => [1, 5, 9, 13, 17, 21, 25, 29],
            'sanguin' => [2, 6, 10, 14, 18, 22, 26, 30],
            'plegmatis' => [3, 7, 11, 15, 19, 23, 27, 31],
            'melankolis' => [4, 8, 12, 16, 20, 24, 28, 32],
        ];
    }
}
