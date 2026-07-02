<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GayaBelajarConfig extends Model
{
    use HasFactory;

    protected $table = 'gaya_belajar_config';

    protected $fillable = [
        'tes_id',
        'aktif',
        'mapping_jawaban',
        'deskripsi_tipe',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'mapping_jawaban' => 'array',
        'deskripsi_tipe' => 'array',
    ];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }

    /**
     * Default mapping jawaban ke tipe gaya belajar
     */
    public static function defaultMapping(): array
    {
        return [
            'A' => 'visual',
            'B' => 'auditori',
            'C' => 'kinestetik',
        ];
    }

    /**
     * Daftar tipe gaya belajar
     */
    public static function tipeGayaBelajarList(): array
    {
        return [
            'visual' => 'Visual',
            'auditori' => 'Auditori',
            'kinestetik' => 'Kinestetik',
        ];
    }

    /**
     * Default deskripsi tipe gaya belajar
     */
    public static function defaultDeskripsi(): array
    {
        return [
            'visual' => 'Gaya belajar visual adalah gaya belajar yang lebih banyak memanfaatkan penglihatan. Orang dengan gaya belajar visual cenderung lebih mudah memahami informasi melalui gambar, diagram, grafik, dan tulisan.',
            'auditori' => 'Gaya belajar auditori adalah gaya belajar yang lebih banyak memanfaatkan pendengaran. Orang dengan gaya belajar auditori cenderung lebih mudah memahami informasi melalui mendengarkan penjelasan, diskusi, dan musik.',
            'kinestetik' => 'Gaya belajar kinestetik adalah gaya belajar yang lebih banyak memanfaatkan gerakan dan sentuhan. Orang dengan gaya belajar kinestetik cenderung lebih mudah memahami informasi melalui praktik langsung, eksperimen, dan aktivitas fisik.',
        ];
    }
}
