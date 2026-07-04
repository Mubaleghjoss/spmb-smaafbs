<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model Peserta
 * Kebutuhan: 3.1, 3.7
 */
class Peserta extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const JENIS_SISWA_BARU = 'siswa_baru';
    public const JENIS_PINDAHAN = 'pindahan';

    protected $table = 'peserta';

    protected $fillable = [
        'nomor_pendaftaran',
        'tahun_ajaran_id',
        'gelombang_pendaftaran_id',
        'jenis_pendaftaran',
        'kelas_tujuan',
        'nama',
        'email',
        'password',
        'password_temp',
        'telepon',
        'alamat',
        'asal_sekolah',
        'foto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'tahun_ajaran_id' => 'integer',
            'gelombang_pendaftaran_id' => 'integer',
            'kelas_tujuan' => 'integer',
        ];
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function gelombangPendaftaran(): BelongsTo
    {
        return $this->belongsTo(GelombangPendaftaran::class);
    }

    public function getJenisPendaftaranLabelAttribute(): string
    {
        return match ($this->jenis_pendaftaran) {
            self::JENIS_SISWA_BARU => 'Siswa Baru',
            self::JENIS_PINDAHAN => 'Pindahan',
            default => 'Belum ditentukan',
        };
    }

    /**
     * Relasi ke grup
     */
    public function grup(): BelongsToMany
    {
        return $this->belongsToMany(Grup::class, 'grup_peserta')
            ->withTimestamps();
    }

    /**
     * Relasi ke sesi tes
     */
    public function sesiTes(): HasMany
    {
        return $this->hasMany(SesiTes::class);
    }

    /**
     * Relasi ke tahapan SPMB
     */
    public function tahapanSpmb(): HasOne
    {
        return $this->hasOne(TahapanSpmb::class);
    }

    /**
     * Relasi ke formulir SPMB
     */
    public function formulirSpmb(): HasOne
    {
        return $this->hasOne(FormulirSpmb::class);
    }

    /**
     * Relasi ke pembayaran
     */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /**
     * Relasi ke wawancara
     */
    public function wawancara(): HasOne
    {
        return $this->hasOne(Wawancara::class);
    }

    /**
     * Relasi ke log tahapan
     */
    public function logTahapan(): HasMany
    {
        return $this->hasMany(LogTahapanSpmb::class);
    }

    /**
     * Ambil tahapan saat ini
     */
    public function getTahapSaatIniAttribute(): int
    {
        return $this->tahapanSpmb?->tahap_saat_ini ?? 1;
    }

    /**
     * Cek apakah tahapan tertentu sudah selesai
     */
    public function tahapanSelesai(int $tahap): bool
    {
        $kolom = "tahap_{$tahap}_selesai";
        return $this->tahapanSpmb?->$kolom ?? false;
    }

    /**
     * Ambil pembayaran formulir
     */
    public function pembayaranFormulir(): HasOne
    {
        return $this->hasOne(Pembayaran::class)
            ->where('jenis', 'formulir')
            ->latest();
    }

    /**
     * Ambil pembayaran pertama
     */
    public function pembayaranPertama(): HasOne
    {
        return $this->hasOne(Pembayaran::class)
            ->where('jenis', 'pertama')
            ->latest();
    }
}
