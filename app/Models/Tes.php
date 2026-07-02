<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Tes
 * Kebutuhan: 4.1, 4.5
 */
class Tes extends Model
{
    use HasFactory;

    protected $table = 'tes';

    protected $fillable = [
        'pengguna_id',
        'nama',
        'keterangan',
        'durasi_menit',
        'nilai_lulus',
        'acak_soal',
        'acak_jawaban',
        'tampilkan_nilai',
        'tampilkan_pembahasan',
        'status',
        'mulai',
        'selesai',
    ];

    protected function casts(): array
    {
        return [
            'acak_soal' => 'boolean',
            'acak_jawaban' => 'boolean',
            'tampilkan_nilai' => 'boolean',
            'tampilkan_pembahasan' => 'boolean',
            'nilai_lulus' => 'decimal:2',
            'mulai' => 'datetime',
            'selesai' => 'datetime',
        ];
    }

    /**
     * Relasi ke pembuat
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class);
    }

    /**
     * Relasi ke soal
     */
    public function soal(): BelongsToMany
    {
        return $this->belongsToMany(Soal::class, 'tes_soal')
            ->withPivot('urutan', 'bobot_custom')
            ->withTimestamps()
            ->orderBy('tes_soal.urutan');
    }

    /**
     * Relasi ke token
     */
    public function token(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * Relasi ke sesi tes
     */
    public function sesiTes(): HasMany
    {
        return $this->hasMany(SesiTes::class);
    }

    /**
     * Relasi ke grup
     * Kebutuhan: 1.1, 5.1
     */
    public function grup(): BelongsToMany
    {
        return $this->belongsToMany(Grup::class, 'grup_tes')
            ->withTimestamps();
    }

    /**
     * Hitung jumlah grup yang di-assign ke tes
     * Kebutuhan: 5.1
     */
    public function getJumlahGrupAttribute(): int
    {
        return $this->grup()->count();
    }

    /**
     * Cek apakah tes sedang berlangsung
     * Hanya cek status, tidak cek waktu mulai/selesai
     */
    public function sedangBerlangsung(): bool
    {
        return in_array($this->status, ['aktif', 'berlangsung']);
    }



    /**
     * Hitung jumlah soal
     */
    public function getJumlahSoalAttribute(): int
    {
        return $this->soal()->count();
    }

    /**
     * Hitung jumlah peserta
     */
    public function getJumlahPesertaAttribute(): int
    {
        return $this->sesiTes()->distinct('peserta_id')->count();
    }

    /**
     * Scope untuk tes aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Scope untuk tes yang sedang berlangsung
     * Hanya cek status, tidak cek waktu mulai/selesai
     */
    public function scopeSedangBerlangsung($query)
    {
        return $query->whereIn('status', ['aktif', 'berlangsung']);
    }
}
