<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Soal
 * Kebutuhan: 2.1
 */
class Soal extends Model
{
    use HasFactory;

    protected $table = 'soal';

    protected $fillable = [
        'topik_id',
        'pertanyaan',
        'tipe',
        'bobot',
        'media',
        'tipe_media',
        'pembahasan',
        'aktif',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * Relasi ke topik
     */
    public function topik(): BelongsTo
    {
        return $this->belongsTo(Topik::class);
    }

    /**
     * Relasi ke pembuat
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh');
    }

    /**
     * Relasi ke jawaban
     */
    public function jawaban(): HasMany
    {
        return $this->hasMany(Jawaban::class)->orderBy('urutan');
    }

    /**
     * Relasi ke jawaban yang benar
     */
    public function jawabanBenar(): HasMany
    {
        return $this->hasMany(Jawaban::class)->where('benar', true);
    }

    /**
     * Relasi ke tes
     */
    public function tes(): BelongsToMany
    {
        return $this->belongsToMany(Tes::class, 'tes_soal')
            ->withPivot('urutan', 'bobot_custom')
            ->withTimestamps();
    }

    /**
     * Relasi ke riwayat perubahan
     */
    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatSoal::class);
    }

    /**
     * Scope untuk soal aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope untuk filter berdasarkan topik
     */
    public function scopeTopik($query, int $topikId)
    {
        return $query->where('topik_id', $topikId);
    }

    /**
     * Scope untuk filter berdasarkan tipe
     */
    public function scopeTipe($query, string $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    /**
     * Cek apakah soal memiliki media
     */
    public function getMemilikiMediaAttribute(): bool
    {
        return !empty($this->media);
    }
}
