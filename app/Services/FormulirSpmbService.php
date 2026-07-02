<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\FormulirSpmb;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class FormulirSpmbService
{
    private SpmbService $spmbService;

    public function __construct(SpmbService $spmbService)
    {
        $this->spmbService = $spmbService;
    }

    /**
     * Simpan atau update formulir SPMB
     */
    public function simpan(Peserta $peserta, array $data): FormulirSpmb
    {
        $formulir = FormulirSpmb::where('peserta_id', $peserta->id)->first();
        
        if ($formulir) {
            $formulir->update($data);
        } else {
            $formulir = FormulirSpmb::create([
                'peserta_id' => $peserta->id,
                ...$data,
                'status_verifikasi' => 'draft',
            ]);
        }
        
        return $formulir->fresh();
    }

    /**
     * Submit formulir untuk diverifikasi
     */
    public function submit(Peserta $peserta): FormulirSpmb
    {
        $peserta->refresh();
        $formulir = $peserta->formulirSpmb;
        
        if (!$formulir) {
            throw new \Exception('Formulir belum diisi');
        }
        
        $formulir->update([
            'status_verifikasi' => 'menunggu',
        ]);
        
        return $formulir;
    }

    /**
     * Verifikasi formulir oleh admin
     * Tahap 2 = Isi Formulir
     */
    public function verifikasi(FormulirSpmb $formulir, Pengguna $admin): void
    {
        DB::transaction(function () use ($formulir, $admin) {
            $formulir->update([
                'status_verifikasi' => 'terverifikasi',
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
            ]);
            
            // Selesaikan tahap 2 (Isi Formulir)
            $this->spmbService->selesaikanTahapan($formulir->peserta, 2, $admin->id);
        });
    }

    /**
     * Tolak formulir dengan catatan
     */
    public function tolak(FormulirSpmb $formulir, string $catatan, Pengguna $admin): void
    {
        $formulir->update([
            'status_verifikasi' => 'ditolak',
            'catatan_verifikasi' => $catatan,
            'diverifikasi_oleh' => $admin->id,
            'diverifikasi_pada' => now(),
        ]);
    }

    /**
     * Ambil formulir peserta
     */
    public function ambilFormulir(Peserta $peserta): ?FormulirSpmb
    {
        return $peserta->formulirSpmb;
    }

    /**
     * Cek apakah formulir sudah lengkap
     * Hanya cek field yang benar-benar wajib (minimal data)
     */
    public function cekKelengkapan(FormulirSpmb $formulir): array
    {
        $wajib = [
            'nama_lengkap' => 'Nama Lengkap',
            'tempat_lahir' => 'Kota Kelahiran',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'asal_sekolah' => 'Asal Sekolah SMP',
            'nama_ayah' => 'Nama Ayah',
            'nama_ibu' => 'Nama Ibu',
        ];
        
        $kosong = [];
        foreach ($wajib as $field => $label) {
            if (empty($formulir->$field)) {
                $kosong[] = $label;
            }
        }
        
        return [
            'lengkap' => empty($kosong),
            'kosong' => $kosong,
        ];
    }

    /**
     * Validasi data formulir (untuk simpan draft - semua nullable)
     */
    public function validasi(array $data): array
    {
        return [
            // Data diri
            'nama_lengkap' => 'nullable|string|max:255',
            'tempat_lahir' => 'nullable|string|max:100',
            'provinsi_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date|before:today',
            'jenis_kelamin' => 'nullable|in:L,P',
            'agama' => 'nullable|string|max:50',
            // Data fisik
            'tinggi_badan' => 'nullable|numeric|min:50|max:250',
            'berat_badan' => 'nullable|numeric|min:10|max:200',
            'lingkar_kepala' => 'nullable|numeric|min:30|max:80',
            'lingkar_dada' => 'nullable|numeric|min:30|max:200',
            'lingkar_pinggang' => 'nullable|numeric|min:30|max:200',
            'panjang_celana' => 'nullable|numeric|min:30|max:200',
            // Data tambahan
            'hobi' => 'nullable|string|max:255',
            'cita_cita' => 'nullable|string|max:255',
            'prestasi' => 'nullable|string|max:255',
            'jumlah_saudara' => 'nullable|integer|min:0|max:20',
            // Alamat
            'alamat_kelurahan' => 'nullable|string|max:100',
            'alamat_kecamatan' => 'nullable|string|max:100',
            'alamat_kota' => 'nullable|string|max:100',
            'alamat_provinsi' => 'nullable|string|max:100',
            'desa' => 'nullable|string|max:100',
            'daerah' => 'nullable|string|max:100',
            'kelompok' => 'nullable|string|max:100',
            // Kontak
            'telp_rumah' => 'nullable|string|max:20',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            // Data orang tua
            'nama_ayah' => 'nullable|string|max:255',
            'pekerjaan_ayah' => 'nullable|string|max:100',
            'pendidikan_ayah' => 'nullable|string|max:50',
            'telepon_ayah' => 'nullable|string|max:20',
            'nama_ibu' => 'nullable|string|max:255',
            'pekerjaan_ibu' => 'nullable|string|max:100',
            'pendidikan_ibu' => 'nullable|string|max:50',
            'telepon_ibu' => 'nullable|string|max:20',
            // Data sekolah
            'asal_sekolah' => 'nullable|string|max:255',
            'nisn' => 'nullable|string|max:20',
            'tanggal_daftar' => 'nullable|date',
            // File dokumen
            'file_kk' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'file_akta' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'file_ijazah' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'file_bpjs' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'file_ktp_ibu' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'file_ktp_ayah' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    /**
     * Cek apakah formulir bisa diedit
     */
    public function bisaDiedit(FormulirSpmb $formulir): bool
    {
        return in_array($formulir->status_verifikasi, ['draft', 'ditolak']);
    }

    /**
     * Cek apakah formulir sudah disubmit
     */
    public function sudahDiSubmit(Peserta $peserta): bool
    {
        $formulir = $peserta->formulirSpmb;
        return $formulir && in_array($formulir->status_verifikasi, ['menunggu', 'terverifikasi']);
    }
}
