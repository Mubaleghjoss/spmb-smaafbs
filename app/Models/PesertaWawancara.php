<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Peserta Wawancara
 * Kebutuhan: 14.3, 14.4
 */
class PesertaWawancara extends Model
{
    use HasFactory;

    protected $table = 'peserta_wawancara';

    protected $fillable = [
        'jadwal_id',
        'peserta_id',
        'status_wawancara',
        'nilai_wawancara',
        'catatan_wawancara',
        'status_berkas',
        'checklist_berkas',
        'catatan_berkas',
    ];

    protected function casts(): array
    {
        return [
            'checklist_berkas' => 'array',
        ];
    }

    /**
     * Relasi ke jadwal
     */
    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalWawancara::class, 'jadwal_id');
    }

    /**
     * Relasi ke peserta
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    /**
     * Cek apakah lulus wawancara
     */
    public function getLulusWawancaraAttribute(): bool
    {
        return $this->status_wawancara === 'lulus';
    }

    /**
     * Cek apakah berkas lengkap
     */
    public function getBerkasLengkapAttribute(): bool
    {
        return $this->status_berkas === 'lengkap';
    }
}
