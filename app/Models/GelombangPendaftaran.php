<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GelombangPendaftaran extends Model
{
    use HasFactory;

    protected $table = 'gelombang_pendaftaran';

    protected $fillable = [
        'tahun_ajaran_id',
        'nama',
        'tanggal_buka',
        'tanggal_tutup',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_buka' => 'date',
            'tanggal_tutup' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(Peserta::class);
    }

    public function scopeTersedia(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where('aktif', true)
            ->where(function (Builder $query) use ($today) {
                $query->whereNull('tanggal_buka')->orWhereDate('tanggal_buka', '<=', $today);
            })
            ->where(function (Builder $query) use ($today) {
                $query->whereNull('tanggal_tutup')->orWhereDate('tanggal_tutup', '>=', $today);
            });
    }

    public function sedangDibuka(): bool
    {
        if (!$this->aktif || !$this->tahunAjaran?->aktif) {
            return false;
        }

        $today = now()->startOfDay();

        return (!$this->tanggal_buka || $this->tanggal_buka->startOfDay()->lte($today))
            && (!$this->tanggal_tutup || $this->tanggal_tutup->endOfDay()->gte($today));
    }
}
