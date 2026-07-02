<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model Sesi Tes
 * Kebutuhan: 5.1, 5.4
 */
class SesiTes extends Model
{
    use HasFactory;

    protected $table = 'sesi_tes';

    protected $fillable = [
        'tes_id',
        'peserta_id',
        'token_id',
        'waktu_mulai',
        'waktu_selesai',
        'nilai',
        'status',
        'status_verifikasi_tes',
        'catatan_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'urutan_soal',
        'soal_saat_ini',
        'ip_address',
        'user_agent',
        'jumlah_peringatan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'diverifikasi_pada' => 'datetime',
            'urutan_soal' => 'array',
            'nilai' => 'decimal:2',
        ];
    }
    
    /**
     * Relasi ke admin yang memverifikasi
     */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Pengguna::class, 'diverifikasi_oleh');
    }
    
    /**
     * Cek apakah peserta lulus tes
     */
    public function lulus(): bool
    {
        return $this->nilai >= $this->tes->nilai_lulus;
    }
    
    /**
     * Cek apakah perlu verifikasi admin (tidak lulus tapi belum diverifikasi)
     */
    public function perluVerifikasi(): bool
    {
        return $this->sudahSelesai() && !$this->lulus() && $this->status_verifikasi_tes === 'menunggu';
    }
    
    /**
     * Cek apakah bisa lanjut ke tahap berikutnya
     */
    public function bisaLanjutTahap(): bool
    {
        if (!$this->sudahSelesai()) {
            return false;
        }
        
        // Lulus otomatis
        if ($this->lulus()) {
            return true;
        }
        
        // Diloloskan admin
        if ($this->status_verifikasi_tes === 'diloloskan') {
            return true;
        }
        
        return false;
    }

    /**
     * Relasi ke tes
     */
    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }

    /**
     * Relasi ke peserta
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    /**
     * Relasi ke token
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * Relasi ke jawaban peserta
     */
    public function jawabanPeserta(): HasMany
    {
        return $this->hasMany(JawabanPeserta::class);
    }

    public function hasilGayaBelajar(): HasOne
    {
        return $this->hasOne(HasilGayaBelajar::class);
    }

    public function hasilPsikotesKepribadian(): HasOne
    {
        return $this->hasOne(HasilPsikotesKepribadian::class);
    }

    public function hasilMbti(): HasOne
    {
        return $this->hasOne(HasilMbti::class);
    }

    public function hasilProfiling(): HasOne
    {
        return $this->hasOne(HasilProfiling::class);
    }

    /**
     * Hitung waktu tersisa dalam detik
     */
    public function waktuTersisa(): int
    {
        if ($this->sudahSelesai()) {
            return 0;
        }

        $durasiMenit = (int) ($this->tes?->durasi_menit ?? 0);
        if (!$this->waktu_mulai || $durasiMenit <= 0) {
            return 0;
        }

        $waktuBerakhir = $this->waktu_mulai->copy()->addMinutes($durasiMenit);
        $tersisa = now()->diffInSeconds($waktuBerakhir, false);
        
        return max(0, $tersisa);
    }

    public function durasiMenitBulat(): ?int
    {
        if (!$this->waktu_mulai || !$this->waktu_selesai) {
            return null;
        }

        $totalDetik = abs($this->waktu_mulai->diffInSeconds($this->waktu_selesai, false));

        return (int) round($totalDetik / 60);
    }

    public function getDurasiMenitBulatAttribute(): ?int
    {
        return $this->durasiMenitBulat();
    }

    /**
     * Cek apakah sesi sudah selesai
     */
    public function sudahSelesai(): bool
    {
        return in_array($this->status, ['selesai', 'timeout']);
    }

    /**
     * Cek apakah sesi sudah berakhir (termasuk dibatalkan)
     */
    public function sudahBerakhir(): bool
    {
        return in_array($this->status, ['selesai', 'timeout', 'dibatalkan']);
    }

    /**
     * Cek apakah waktu sudah habis
     */
    public function waktuHabis(): bool
    {
        return $this->waktuTersisa() <= 0;
    }

    /**
     * Hitung jumlah soal yang sudah dijawab
     */
    public function getJumlahDijawabAttribute(): int
    {
        return $this->jawabanPeserta()
            ->whereNotNull('jawaban_id')
            ->orWhereNotNull('jawaban_esai')
            ->orWhereNotNull('jawaban_ganda')
            ->count();
    }

    /**
     * Hitung persentase progres
     */
    public function getPersentaseProgresAttribute(): int
    {
        $totalSoal = $this->tes->jumlah_soal;
        if ($totalSoal === 0) {
            return 0;
        }
        
        return (int) round(($this->jumlah_dijawab / $totalSoal) * 100);
    }

    /**
     * Scope untuk sesi yang sedang berlangsung
     */
    public function scopeBerlangsung($query)
    {
        return $query->where('status', 'berlangsung');
    }

    /**
     * Scope untuk sesi yang sudah selesai
     */
    public function scopeSelesai($query)
    {
        return $query->whereIn('status', ['selesai', 'timeout']);
    }
}
