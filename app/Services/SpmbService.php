<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Models\LogTahapanSpmb;
use App\Enums\TahapanSpmb as TahapanSpmbEnum;
use Illuminate\Support\Facades\DB;

class SpmbService
{
    /**
     * Ambil status semua tahapan untuk peserta
     * Urutan: 1. Buat Akun, 2. Isi Formulir, 3. Bayar Formulir, 4. Tes Online, 5. Wawancara, 6. Bayar Pertama, 7. Diterima
     */
    public function ambilStatusTahapan(Peserta $peserta): array
    {
        $tahapan = $peserta->tahapanSpmb;
        
        if (!$tahapan) {
            $tahapan = $this->buatTahapanAwal($peserta);
        }

        return [
            'tahap_saat_ini' => $tahapan->tahap_saat_ini,
            'tahapan' => [
                1 => [
                    'selesai' => $tahapan->tahap_1_selesai,
                    'label' => TahapanSpmbEnum::BUAT_AKUN->label(),
                    'icon' => 'person-plus',
                    'deskripsi' => 'Buat akun dan login ke sistem',
                ],
                2 => [
                    'selesai' => $tahapan->tahap_2_selesai,
                    'label' => TahapanSpmbEnum::ISI_FORMULIR->label(),
                    'icon' => 'file-earmark-text',
                    'deskripsi' => 'Lengkapi data formulir pendaftaran',
                ],
                3 => [
                    'selesai' => $tahapan->tahap_3_selesai,
                    'label' => TahapanSpmbEnum::BAYAR_FORMULIR->label(),
                    'icon' => 'credit-card',
                    'deskripsi' => 'Transfer biaya formulir dan upload bukti',
                ],
                4 => [
                    'selesai' => $tahapan->tahap_4_selesai,
                    'label' => TahapanSpmbEnum::TES_ONLINE->label(),
                    'icon' => 'laptop',
                    'deskripsi' => 'Ikuti tes seleksi online',
                ],
                5 => [
                    'selesai' => $tahapan->tahap_5_selesai,
                    'label' => TahapanSpmbEnum::WAWANCARA->label(),
                    'icon' => 'people',
                    'deskripsi' => 'Wawancara dan verifikasi berkas',
                ],
                6 => [
                    'selesai' => $tahapan->tahap_6_selesai,
                    'label' => TahapanSpmbEnum::BAYAR_PERTAMA->label(),
                    'icon' => 'wallet2',
                    'deskripsi' => 'Upload bukti pembayaran pertama',
                ],
                7 => [
                    'selesai' => $tahapan->tahap_7_selesai,
                    'label' => TahapanSpmbEnum::RESMI_DITERIMA->label(),
                    'icon' => 'mortarboard',
                    'deskripsi' => 'Resmi menjadi peserta didik',
                ],
            ],
        ];
    }

    /**
     * Cek apakah tahapan tertentu sudah selesai
     */
    public function cekTahapanSelesai(Peserta $peserta, int $tahap): bool
    {
        $tahapan = $peserta->tahapanSpmb;
        
        if (!$tahapan) {
            return $tahap === 1; // Tahap 1 otomatis selesai saat daftar
        }

        $kolom = "tahap_{$tahap}_selesai";
        return $tahapan->$kolom ?? false;
    }

    /**
     * Buka akses ke tahapan berikutnya
     */
    public function bukaAksesTahapan(Peserta $peserta, int $tahap): void
    {
        $tahapan = $peserta->tahapanSpmb;
        
        if (!$tahapan) {
            $tahapan = $this->buatTahapanAwal($peserta);
        }

        // Update tahapan saat ini jika lebih tinggi
        if ($tahap > $tahapan->tahap_saat_ini) {
            $tahapan->tahap_saat_ini = $tahap;
        }

        $tahapan->save();
    }

    /**
     * Tandai tahapan sebagai selesai
     */
    public function selesaikanTahapan(Peserta $peserta, int $tahap, ?int $adminId = null): void
    {
        $tahapan = $peserta->tahapanSpmb;
        
        if (!$tahapan) {
            $tahapan = $this->buatTahapanAwal($peserta);
        }

        $kolom = "tahap_{$tahap}_selesai";
        $statusLama = $tahapan->$kolom;
        
        DB::transaction(function () use ($tahapan, $kolom, $tahap, $peserta, $adminId, $statusLama) {
            $tahapan->$kolom = true;
            
            // Update tahap saat ini ke tahap berikutnya
            if ($tahap >= $tahapan->tahap_saat_ini && $tahap < 7) {
                $tahapan->tahap_saat_ini = $tahap + 1;
            }
            
            $tahapan->save();

            // Log perubahan
            LogTahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap' => $tahap,
                'aksi' => $adminId ? 'manual_update' : 'verifikasi',
                'status_lama' => $statusLama ?? false,
                'status_baru' => true,
                'admin_id' => $adminId,
            ]);
        });
    }

    /**
     * Ambil informasi detail tahapan
     * Urutan: 1. Buat Akun, 2. Isi Formulir, 3. Bayar Formulir, 4. Tes Online, 5. Wawancara, 6. Bayar Pertama, 7. Diterima
     */
    public function ambilInformasiTahapan(int $tahap): array
    {
        return match($tahap) {
            1 => [
                'judul' => TahapanSpmbEnum::BUAT_AKUN->label(),
                'instruksi' => 'Selamat! Anda sudah berhasil membuat akun.',
                'aksi' => null,
            ],
            2 => [
                'judul' => TahapanSpmbEnum::ISI_FORMULIR->label(),
                'instruksi' => 'Lengkapi formulir pendaftaran dengan data yang benar',
                'aksi' => 'Isi Formulir',
            ],
            3 => [
                'judul' => TahapanSpmbEnum::BAYAR_FORMULIR->label(),
                'instruksi' => 'Transfer biaya formulir dan upload bukti pembayaran',
                'aksi' => 'Upload Bukti Transfer',
            ],
            4 => [
                'judul' => TahapanSpmbEnum::TES_ONLINE->label(),
                'instruksi' => 'Ikuti tes seleksi online sesuai jadwal yang ditentukan',
                'aksi' => 'Mulai Tes',
            ],
            5 => [
                'judul' => TahapanSpmbEnum::WAWANCARA->label(),
                'instruksi' => 'Hadiri sesi wawancara sesuai jadwal dan bawa berkas asli',
                'aksi' => 'Lihat Info',
            ],
            6 => [
                'judul' => TahapanSpmbEnum::BAYAR_PERTAMA->label(),
                'instruksi' => 'Lakukan pembayaran tahap pertama sesuai ketentuan',
                'aksi' => 'Upload Bukti Transfer',
            ],
            7 => [
                'judul' => TahapanSpmbEnum::RESMI_DITERIMA->label(),
                'instruksi' => 'Selamat! Anda resmi diterima sebagai peserta didik baru',
                'aksi' => 'Lihat Info',
            ],
            default => [
                'judul' => 'Tahapan Tidak Dikenal',
                'instruksi' => '',
                'aksi' => null,
            ],
        };
    }

    /**
     * Buat record tahapan awal untuk peserta baru
     */
    private function buatTahapanAwal(Peserta $peserta): TahapanSpmb
    {
        return TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 1,
            'tahap_1_selesai' => true,
        ]);
    }
}
