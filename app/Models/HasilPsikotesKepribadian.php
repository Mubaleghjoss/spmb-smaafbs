<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilPsikotesKepribadian extends Model
{
    use HasFactory;

    protected $table = 'hasil_psikotes_kepribadian';

    protected $fillable = [
        'sesi_tes_id',
        'hasil_kepribadian',
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
     * Get label kepribadian dengan warna
     */
    public function getLabelAttribute(): string
    {
        return match($this->hasil_kepribadian) {
            'koleris' => 'Koleris',
            'sanguin' => 'Sanguin',
            'plegmatis' => 'Plegmatis',
            'melankolis' => 'Melankolis',
            default => ucfirst($this->hasil_kepribadian),
        };
    }

    /**
     * Get badge color
     */
    public function getBadgeColorAttribute(): string
    {
        return match($this->hasil_kepribadian) {
            'koleris' => 'danger',
            'sanguin' => 'warning',
            'plegmatis' => 'success',
            'melankolis' => 'primary',
            default => 'secondary',
        };
    }
}
