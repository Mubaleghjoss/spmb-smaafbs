<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        $pengaturan = [
            // Branding
            ['kunci' => 'nama_institusi', 'nilai' => 'SMA Al-Furqon', 'grup' => 'branding'],
            ['kunci' => 'alamat', 'nilai' => 'Jl. Pendidikan No. 1, Kota', 'grup' => 'branding'],
            ['kunci' => 'telepon', 'nilai' => '021-12345678', 'grup' => 'branding'],
            ['kunci' => 'email', 'nilai' => 'info@smaalfurqon.sch.id', 'grup' => 'branding'],
            ['kunci' => 'website', 'nilai' => 'https://smaalfurqon.sch.id', 'grup' => 'branding'],
            ['kunci' => 'warna_primer', 'nilai' => '#667eea', 'grup' => 'branding'],
            ['kunci' => 'warna_sekunder', 'nilai' => '#764ba2', 'grup' => 'branding'],

            // SPMB
            ['kunci' => 'tahun_ajaran', 'nilai' => date('Y') . '/' . (date('Y') + 1), 'grup' => 'spmb'],
            ['kunci' => 'biaya_formulir', 'nilai' => '150000', 'grup' => 'spmb'],
            ['kunci' => 'biaya_daftar_ulang', 'nilai' => '5000000', 'grup' => 'spmb'],
            ['kunci' => 'rekening_bank', 'nilai' => 'BSI', 'grup' => 'spmb'],
            ['kunci' => 'nomor_rekening', 'nilai' => '7227212335', 'grup' => 'spmb'],
            ['kunci' => 'nama_rekening', 'nilai' => 'Yayasan Al-Furqon', 'grup' => 'spmb'],
            ['kunci' => 'pendaftaran_dibuka', 'nilai' => date('Y') . '-01-01', 'grup' => 'spmb'],
            ['kunci' => 'pendaftaran_ditutup', 'nilai' => date('Y') . '-06-30', 'grup' => 'spmb'],

            // Ujian
            ['kunci' => 'durasi_default', 'nilai' => '90', 'grup' => 'ujian'],
            ['kunci' => 'acak_soal', 'nilai' => '1', 'grup' => 'ujian'],
            ['kunci' => 'acak_jawaban', 'nilai' => '1', 'grup' => 'ujian'],
            ['kunci' => 'tampilkan_nilai', 'nilai' => '1', 'grup' => 'ujian'],
            ['kunci' => 'auto_submit', 'nilai' => '1', 'grup' => 'ujian'],
            ['kunci' => 'anti_cheat', 'nilai' => '1', 'grup' => 'ujian'],

            // Email
            ['kunci' => 'smtp_host', 'nilai' => '', 'grup' => 'email'],
            ['kunci' => 'smtp_port', 'nilai' => '587', 'grup' => 'email'],
            ['kunci' => 'smtp_username', 'nilai' => '', 'grup' => 'email'],
            ['kunci' => 'smtp_password', 'nilai' => '', 'grup' => 'email'],
            ['kunci' => 'smtp_encryption', 'nilai' => 'tls', 'grup' => 'email'],
            ['kunci' => 'email_pengirim', 'nilai' => 'noreply@smaalfurqon.sch.id', 'grup' => 'email'],
            ['kunci' => 'nama_pengirim', 'nilai' => 'SPMB Al-Furqon', 'grup' => 'email'],
        ];

        foreach ($pengaturan as $item) {
            Pengaturan::updateOrCreate(
                ['kunci' => $item['kunci']],
                ['nilai' => $item['nilai'], 'grup' => $item['grup']]
            );
        }
    }
}
