<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Tahapan SPMB
 * Kebutuhan: 0.3
 */
class TahapanSpmb extends Model
{
    use HasFactory;

    protected $table = 'tahapan_spmb';

    protected $fillable = [
        'peserta_id',
        'tahap_saat_ini',
        'tahap_1_selesai',
        'tahap_2_selesai',
        'tahap_3_selesai',
        'tahap_4_selesai',
        'tahap_5_selesai',
        'tahap_6_selesai',
        'tahap_7_selesai',
        'status_kelulusan',
        'sk_gelombang_kelulusan',
    ];

    protected function casts(): array
    {
        return [
            'tahap_1_selesai' => 'boolean',
            'tahap_2_selesai' => 'boolean',
            'tahap_3_selesai' => 'boolean',
            'tahap_4_selesai' => 'boolean',
            'tahap_5_selesai' => 'boolean',
            'tahap_6_selesai' => 'boolean',
            'tahap_7_selesai' => 'boolean',
        ];
    }

    /**
     * Relasi ke peserta
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    /**
     * Cek apakah tahapan tertentu selesai
     */
    public function tahapSelesai(int $tahap): bool
    {
        $kolom = "tahap_{$tahap}_selesai";
        return $this->$kolom ?? false;
    }

    /**
     * Set tahapan selesai
     */
    public function setTahapSelesai(int $tahap, bool $selesai = true): void
    {
        $kolom = "tahap_{$tahap}_selesai";
        $this->$kolom = $selesai;
        
        // Update tahap saat ini jika selesai
        if ($selesai && $tahap >= $this->tahap_saat_ini) {
            $this->tahap_saat_ini = min($tahap + 1, 7);
        }
        
        $this->save();
    }

    /**
     * Hitung persentase progres
     */
    public function getPersentaseProgresAttribute(): int
    {
        $selesai = 0;
        for ($i = 1; $i <= 7; $i++) {
            if ($this->tahapSelesai($i)) {
                $selesai++;
            }
        }
        return (int) round(($selesai / 7) * 100);
    }

    /**
     * Cek apakah semua tahapan selesai
     */
    public function getSemuaSelesaiAttribute(): bool
    {
        return $this->tahap_7_selesai;
    }
}
