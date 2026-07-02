<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model Pengguna untuk admin dan operator
 * Kebutuhan: 1.3, 1.4
 */
class Pengguna extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'pengguna';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'peran',
        'menu_akses',
        'aktif',
        'percobaan_login',
        'dikunci_sampai',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'dikunci_sampai' => 'datetime',
            'password' => 'hashed',
            'menu_akses' => 'array',
        ];
    }

    /**
     * Daftar menu yang tersedia
     */
    public static function daftarMenu(): array
    {
        return [
            'peserta' => [
                'label' => 'Peserta',
                'icon' => 'bi-people',
                'route' => 'admin.peserta.index',
                'route_prefix' => 'admin.peserta.',
            ],
            'verifikasi' => [
                'label' => 'Verifikasi SPMB',
                'icon' => 'bi-clipboard-check',
                'route' => 'admin.verifikasi.index',
                'route_prefix' => 'admin.verifikasi.',
            ],
            'monitoring' => [
                'label' => 'Monitoring SPMB',
                'icon' => 'bi-graph-up',
                'route' => 'admin.monitoring.index',
                'route_prefix' => 'admin.monitoring.',
            ],
            'tes' => [
                'label' => 'Tes',
                'icon' => 'bi-file-earmark-text',
                'route' => 'admin.tes.index',
                'route_prefix' => 'admin.tes.',
            ],
            'soal' => [
                'label' => 'Bank Soal',
                'icon' => 'bi-question-circle',
                'route' => 'admin.soal.index',
                'route_prefix' => 'admin.soal.',
            ],
            'monitoring_ujian' => [
                'label' => 'Monitoring Ujian',
                'icon' => 'bi-display',
                'route' => 'admin.monitoring-ujian.index',
                'route_prefix' => 'admin.monitoring-ujian.',
            ],
            'hasil' => [
                'label' => 'Hasil Ujian',
                'icon' => 'bi-bar-chart',
                'route' => 'admin.hasil.index',
                'route_prefix' => 'admin.hasil.',
            ],
            'pengaturan' => [
                'label' => 'Pengaturan',
                'icon' => 'bi-gear',
                'route' => 'admin.pengaturan.index',
                'route_prefix' => 'admin.pengaturan.',
            ],
            'pengguna' => [
                'label' => 'Pengguna',
                'icon' => 'bi-person-gear',
                'route' => 'admin.pengguna.index',
                'route_prefix' => 'admin.pengguna.',
            ],
        ];
    }

    /**
     * Menu default untuk operator dan tim_spmb
     */
    public static function menuDefault(): array
    {
        return ['peserta', 'verifikasi', 'monitoring', 'hasil'];
    }

    /**
     * Ambil menu yang bisa diakses pengguna
     */
    public function getMenuAksesArray(): array
    {
        // Admin punya akses semua
        if ($this->peran === 'admin') {
            return array_keys(self::daftarMenu());
        }

        // Jika belum diset, gunakan default
        if (empty($this->menu_akses)) {
            return self::menuDefault();
        }

        return $this->menu_akses;
    }

    /**
     * Cek apakah pengguna punya akses ke menu tertentu
     */
    public function bisaAkses(string $menu): bool
    {
        // Admin selalu bisa akses semua
        if ($this->peran === 'admin') {
            return true;
        }

        return in_array($menu, $this->getMenuAksesArray());
    }

    /**
     * Cek apakah pengguna bisa akses route tertentu
     */
    public function bisaAksesRoute(string $routeName): bool
    {
        // Admin selalu bisa
        if ($this->peran === 'admin') {
            return true;
        }

        // Dashboard selalu bisa diakses
        if ($routeName === 'admin.dashboard') {
            return true;
        }

        // Cek berdasarkan prefix route
        foreach (self::daftarMenu() as $key => $menu) {
            if (str_starts_with($routeName, $menu['route_prefix'])) {
                return $this->bisaAkses($key);
            }
        }

        return false;
    }

    /**
     * Relasi ke tes yang dibuat
     */
    public function tes(): HasMany
    {
        return $this->hasMany(Tes::class, 'pengguna_id');
    }

    /**
     * Relasi ke soal yang dibuat
     */
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'dibuat_oleh');
    }

    /**
     * Cek apakah pengguna adalah admin
     */
    public function adalahAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    /**
     * Cek apakah pengguna adalah operator
     */
    public function adalahOperator(): bool
    {
        return $this->peran === 'operator';
    }

    /**
     * Cek apakah pengguna adalah tim SPMB
     */
    public function adalahTimSpmb(): bool
    {
        return $this->peran === 'tim_spmb';
    }

    /**
     * Cek apakah pengguna memiliki akses admin (admin atau tim_spmb)
     */
    public function punyaAksesAdmin(): bool
    {
        return in_array($this->peran, ['admin', 'tim_spmb', 'operator']);
    }

    /**
     * Cek apakah akun sedang dikunci
     */
    public function sedangDikunci(): bool
    {
        return $this->dikunci_sampai !== null && $this->dikunci_sampai->isFuture();
    }

    /**
     * Kunci akun selama durasi tertentu (dalam menit)
     */
    public function kunciAkun(int $menitDurasi = 15): void
    {
        $this->update([
            'dikunci_sampai' => now()->addMinutes($menitDurasi),
            'percobaan_login' => 0,
        ]);
    }

    /**
     * Reset percobaan login
     */
    public function resetPercobaanLogin(): void
    {
        $this->update([
            'percobaan_login' => 0,
            'dikunci_sampai' => null,
        ]);
    }

    /**
     * Tambah percobaan login gagal
     */
    public function tambahPercobaanGagal(): void
    {
        $this->increment('percobaan_login');
        
        if ($this->percobaan_login >= 3) {
            $this->kunciAkun();
        }
    }
}
