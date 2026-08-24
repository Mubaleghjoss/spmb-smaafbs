<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Catatan aktivitas Tim SPMB (hanya aksi yang mengubah data).
 */
class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';

    public const KAT_PESERTA = 'peserta';
    public const KAT_FORMULIR = 'formulir';
    public const KAT_PEMBAYARAN = 'pembayaran';
    public const KAT_UJIAN = 'ujian';
    public const KAT_WAWANCARA = 'wawancara';
    public const KAT_KELULUSAN = 'kelulusan';
    public const KAT_PENGATURAN = 'pengaturan';
    public const KAT_AKUN = 'akun';

    protected $fillable = [
        'pengguna_id',
        'nama_pengguna',
        'peran',
        'aksi',
        'kategori',
        'subjek_tipe',
        'subjek_id',
        'subjek_label',
        'keterangan',
        'data',
        'ip',
        'tahun_ajaran_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'subjek_id' => 'integer',
            'pengguna_id' => 'integer',
            'tahun_ajaran_id' => 'integer',
        ];
    }

    /**
     * Daftar kategori beserta labelnya untuk filter & badge.
     *
     * @return array<string, string>
     */
    public static function daftarKategori(): array
    {
        return [
            self::KAT_PESERTA => 'Peserta',
            self::KAT_FORMULIR => 'Formulir',
            self::KAT_PEMBAYARAN => 'Pembayaran',
            self::KAT_UJIAN => 'Tes Online',
            self::KAT_WAWANCARA => 'Wawancara',
            self::KAT_KELULUSAN => 'Kelulusan',
            self::KAT_PENGATURAN => 'Pengaturan',
            self::KAT_AKUN => 'Akun Tim',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function subjek(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'subjek_tipe', 'subjek_id');
    }

    public function getKategoriLabelAttribute(): string
    {
        return self::daftarKategori()[$this->kategori] ?? ucfirst((string) $this->kategori);
    }

    public function getKategoriWarnaAttribute(): string
    {
        return match ($this->kategori) {
            self::KAT_PESERTA => 'primary',
            self::KAT_FORMULIR => 'info',
            self::KAT_PEMBAYARAN => 'warning',
            self::KAT_UJIAN => 'secondary',
            self::KAT_WAWANCARA => 'dark',
            self::KAT_KELULUSAN => 'success',
            self::KAT_PENGATURAN => 'light',
            self::KAT_AKUN => 'danger',
            default => 'secondary',
        };
    }

    public function getKategoriIkonAttribute(): string
    {
        return match ($this->kategori) {
            self::KAT_PESERTA => 'people',
            self::KAT_FORMULIR => 'file-earmark-text',
            self::KAT_PEMBAYARAN => 'credit-card',
            self::KAT_UJIAN => 'laptop',
            self::KAT_WAWANCARA => 'chat-dots',
            self::KAT_KELULUSAN => 'mortarboard',
            self::KAT_PENGATURAN => 'gear',
            self::KAT_AKUN => 'person-badge',
            default => 'activity',
        };
    }

    /**
     * Apakah subjek masih ada di database (peserta bisa sudah dihapus permanen).
     */
    public function getSubjekMasihAdaAttribute(): bool
    {
        if (empty($this->subjek_tipe) || empty($this->subjek_id)) {
            return false;
        }

        if (!class_exists($this->subjek_tipe)) {
            return false;
        }

        return $this->subjek_tipe::query()->whereKey($this->subjek_id)->exists();
    }
}
