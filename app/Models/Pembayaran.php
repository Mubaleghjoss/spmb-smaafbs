<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Pembayaran
 * Kebutuhan: 13.1
 */
class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'peserta_id',
        'jenis',
        'bukti_file',
        'nominal',
        'status',
        'nomor_kwitansi',
        'catatan',
        'diverifikasi_oleh',
        'diverifikasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'diverifikasi_pada' => 'datetime',
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
     * Relasi ke verifikator
     */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diverifikasi_oleh');
    }

    /**
     * Cek apakah sudah terverifikasi
     */
    public function getSudahTerverifikasiAttribute(): bool
    {
        return $this->status === 'terverifikasi';
    }

    /**
     * Cek apakah ditolak
     */
    public function getDitolakAttribute(): bool
    {
        return $this->status === 'ditolak';
    }

    /**
     * Cek apakah menunggu verifikasi
     */
    public function getMenungguVerifikasiAttribute(): bool
    {
        return $this->status === 'menunggu';
    }

    /**
     * Scope untuk filter berdasarkan jenis
     */
    public function scopeJenis($query, string $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
