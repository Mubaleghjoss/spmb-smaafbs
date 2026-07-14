<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    use HasFactory;

    protected $table = 'tahun_ajaran';

    protected $fillable = [
        'nama',
        'aktif',
        'default',
        'kuota_peserta',
        'kuota_laki_laki',
        'kuota_perempuan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'default' => 'boolean',
            'kuota_peserta' => 'integer',
            'kuota_laki_laki' => 'integer',
            'kuota_perempuan' => 'integer',
        ];
    }

    public function gelombangPendaftaran(): HasMany
    {
        return $this->hasMany(GelombangPendaftaran::class);
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(Peserta::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function kuotaTerbatas(): bool
    {
        return (int) ($this->kuota_peserta ?? 0) > 0;
    }
}
