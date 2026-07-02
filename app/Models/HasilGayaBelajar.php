<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilGayaBelajar extends Model
{
    use HasFactory;

    protected $table = 'hasil_gaya_belajar';

    protected $fillable = [
        'sesi_tes_id',
        'hasil_gaya_belajar',
        'detail_nilai',
    ];

    protected $casts = [
        'detail_nilai' => 'array',
    ];

    public function sesiTes(): BelongsTo
    {
        return $this->belongsTo(SesiTes::class);
    }

    /**
     * Format hasil gaya belajar untuk ditampilkan
     */
    public function getHasilFormatAttribute(): string
    {
        $tipeList = GayaBelajarConfig::tipeGayaBelajarList();
        $hasil = explode(' & ', $this->hasil_gaya_belajar);
        
        return collect($hasil)
            ->map(fn($h) => $tipeList[$h] ?? ucfirst($h))
            ->implode(' & ');
    }

    /**
     * Warna badge berdasarkan hasil
     */
    public function getBadgeColorAttribute(): string
    {
        $colors = [
            'visual' => 'primary',
            'auditori' => 'success',
            'kinestetik' => 'warning',
        ];

        $hasil = explode(' & ', $this->hasil_gaya_belajar);
        
        if (count($hasil) > 1) {
            return 'info'; // Gabungan
        }

        return $colors[$hasil[0]] ?? 'secondary';
    }
}
