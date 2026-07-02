<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Model Pengaturan
 * Kebutuhan: 8.1
 */
class Pengaturan extends Model
{
    use HasFactory;

    protected $table = 'pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
        'grup',
        'tipe',
        'keterangan',
    ];

    /**
     * Ambil nilai pengaturan berdasarkan kunci
     */
    public static function ambil(string $kunci, mixed $default = null): mixed
    {
        $pengaturan = Cache::remember("pengaturan.{$kunci}", 3600, function () use ($kunci) {
            return static::where('kunci', $kunci)->first();
        });

        if (!$pengaturan) {
            return $default;
        }

        return match ($pengaturan->tipe) {
            'boolean' => filter_var($pengaturan->nilai, FILTER_VALIDATE_BOOLEAN),
            'number' => (int) $pengaturan->nilai,
            'json' => json_decode($pengaturan->nilai, true),
            default => $pengaturan->nilai,
        };
    }

    /**
     * Set nilai pengaturan
     */
    public static function atur(string $kunci, mixed $nilai, string $tipe = 'text'): void
    {
        $nilaiDisimpan = match ($tipe) {
            'json' => json_encode($nilai),
            'boolean' => $nilai ? '1' : '0',
            default => (string) $nilai,
        };

        static::updateOrCreate(
            ['kunci' => $kunci],
            ['nilai' => $nilaiDisimpan, 'tipe' => $tipe]
        );

        Cache::forget("pengaturan.{$kunci}");
    }

    /**
     * Ambil semua pengaturan berdasarkan grup
     */
    public static function ambilGrup(string $grup): array
    {
        return static::where('grup', $grup)
            ->get()
            ->mapWithKeys(fn ($item) => [$item->kunci => static::ambil($item->kunci)])
            ->toArray();
    }
}
