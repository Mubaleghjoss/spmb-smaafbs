<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Formulir SPMB
 * Kebutuhan: 0.3
 */
class FormulirSpmb extends Model
{
    use HasFactory;

    protected $table = 'formulir_spmb';

    protected $fillable = [
        'peserta_id',
        // Data diri
        'nama_lengkap',
        'tempat_lahir',
        'provinsi_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        // Data fisik
        'tinggi_badan',
        'berat_badan',
        'lingkar_kepala',
        'lingkar_dada',
        'lingkar_pinggang',
        'panjang_celana',
        // Data tambahan siswa
        'hobi',
        'cita_cita',
        'prestasi',
        'jumlah_saudara',
        // Alamat
        'alamat',
        'alamat_kelurahan',
        'alamat_kecamatan',
        'alamat_kota',
        'alamat_provinsi',
        'desa',
        'daerah',
        'kelompok',
        // Kontak
        'telepon',
        'telp_rumah',
        'email',
        // Data orang tua
        'nama_ayah',
        'pekerjaan_ayah',
        'pendidikan_ayah',
        'telepon_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'pendidikan_ibu',
        'telepon_ibu',
        // Data sekolah
        'asal_sekolah',
        'alamat_sekolah',
        'nisn',
        'tanggal_daftar',
        // File dokumen
        'file_kk',
        'file_akta',
        'file_ijazah',
        'file_bpjs',
        'file_ktp_ibu',
        'file_ktp_ayah',
        'foto',
        // Status
        'status_verifikasi',
        'catatan_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_daftar' => 'date',
            'diverifikasi_pada' => 'datetime',
            'tinggi_badan' => 'decimal:1',
            'berat_badan' => 'decimal:1',
            'lingkar_kepala' => 'decimal:1',
            'lingkar_dada' => 'decimal:1',
            'lingkar_pinggang' => 'decimal:1',
            'panjang_celana' => 'decimal:1',
            'jumlah_saudara' => 'integer',
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
        return $this->status_verifikasi === 'terverifikasi';
    }

    /**
     * Cek apakah ditolak
     */
    public function getDitolakAttribute(): bool
    {
        return $this->status_verifikasi === 'ditolak';
    }

    /**
     * Cek apakah menunggu verifikasi
     */
    public function getMenungguVerifikasiAttribute(): bool
    {
        return $this->status_verifikasi === 'menunggu';
    }
}
