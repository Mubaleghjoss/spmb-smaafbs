<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class GelombangPendaftaran extends Model
{
    use HasFactory;

    protected $table = 'gelombang_pendaftaran';

    protected $fillable = [
        'tahun_ajaran_id',
        'nama',
        'tanggal_buka',
        'waktu_buka',
        'tanggal_tutup',
        'waktu_tutup',
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
        $time = now()->format('H:i:s');

        return $query
            ->where('aktif', true)
            ->where(function (Builder $query) use ($today, $time) {
                $query
                    ->whereNull('tanggal_buka')
                    ->orWhereDate('tanggal_buka', '<', $today)
                    ->orWhere(function (Builder $query) use ($today, $time) {
                        $query->whereDate('tanggal_buka', $today)
                            ->where(function (Builder $query) use ($time) {
                                $query->whereNull('waktu_buka')
                                    ->orWhereTime('waktu_buka', '<=', $time);
                            });
                    });
            })
            ->where(function (Builder $query) use ($today, $time) {
                $query
                    ->whereNull('tanggal_tutup')
                    ->orWhereDate('tanggal_tutup', '>', $today)
                    ->orWhere(function (Builder $query) use ($today, $time) {
                        $query->whereDate('tanggal_tutup', $today)
                            ->where(function (Builder $query) use ($time) {
                                $query->whereNull('waktu_tutup')
                                    ->orWhereTime('waktu_tutup', '>=', $time);
                            });
                    });
            });
    }

    public function sedangDibuka(): bool
    {
        if (!$this->aktif || !$this->tahunAjaran?->aktif) {
            return false;
        }

        $mulai = $this->mulaiPendaftaran();
        $selesai = $this->selesaiPendaftaran();
        $now = now();

        return (!$mulai || $mulai->lte($now))
            && (!$selesai || $selesai->gte($now));
    }

    public function mulaiPendaftaran(): ?Carbon
    {
        return $this->gabungkanTanggalWaktu($this->tanggal_buka, $this->waktu_buka, false);
    }

    public function selesaiPendaftaran(): ?Carbon
    {
        return $this->gabungkanTanggalWaktu($this->tanggal_tutup, $this->waktu_tutup, true);
    }

    public function labelPeriodePendaftaran(): string
    {
        $mulai = $this->mulaiPendaftaran();
        $selesai = $this->selesaiPendaftaran();

        if ($mulai && $selesai) {
            return $this->formatTanggalWaktu($mulai, !empty($this->waktu_buka))
                . ' sampai '
                . $this->formatTanggalWaktu($selesai, !empty($this->waktu_tutup));
        }

        if ($mulai) {
            return 'Dibuka mulai ' . $this->formatTanggalWaktu($mulai, !empty($this->waktu_buka));
        }

        if ($selesai) {
            return 'Ditutup pada ' . $this->formatTanggalWaktu($selesai, !empty($this->waktu_tutup));
        }

        return 'Tanpa batas jadwal';
    }

    public function statusPendaftaran(): array
    {
        if (!$this->tahunAjaran?->aktif) {
            return ['label' => 'Tahun nonaktif', 'class' => 'secondary'];
        }

        if (!$this->aktif) {
            return ['label' => 'Nonaktif', 'class' => 'secondary'];
        }

        $mulai = $this->mulaiPendaftaran();
        $selesai = $this->selesaiPendaftaran();
        $now = now();

        if ($mulai && $now->lt($mulai)) {
            return ['label' => 'Belum dibuka', 'class' => 'warning text-dark'];
        }

        if ($selesai && $now->gt($selesai)) {
            return ['label' => 'Ditutup', 'class' => 'danger'];
        }

        return ['label' => 'Dibuka', 'class' => 'success'];
    }

    private function gabungkanTanggalWaktu(mixed $tanggal, ?string $waktu, bool $akhirHari): ?Carbon
    {
        if (empty($tanggal)) {
            return null;
        }

        $tanggal = $tanggal instanceof Carbon
            ? $tanggal->copy()->format('Y-m-d')
            : Carbon::parse($tanggal)->format('Y-m-d');
        $jam = $waktu ?: ($akhirHari ? '23:59:59' : '00:00:00');

        return Carbon::parse(trim($tanggal . ' ' . $jam));
    }

    private function formatTanggalWaktu(Carbon $tanggal, bool $pakaiJam): string
    {
        return $tanggal->locale('id')->translatedFormat($pakaiJam ? 'd M Y H:i' : 'd M Y')
            . ($pakaiJam ? ' WIB' : '');
    }
}
