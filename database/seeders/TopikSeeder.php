<?php

namespace Database\Seeders;

use App\Models\Topik;
use Illuminate\Database\Seeder;

class TopikSeeder extends Seeder
{
    public function run(): void
    {
        $topik = [
            ['nama' => 'Matematika', 'keterangan' => 'Soal-soal matematika dasar dan lanjutan'],
            ['nama' => 'Bahasa Indonesia', 'keterangan' => 'Soal-soal bahasa Indonesia'],
            ['nama' => 'Bahasa Inggris', 'keterangan' => 'Soal-soal bahasa Inggris'],
            ['nama' => 'IPA', 'keterangan' => 'Soal-soal Ilmu Pengetahuan Alam'],
            ['nama' => 'IPS', 'keterangan' => 'Soal-soal Ilmu Pengetahuan Sosial'],
            ['nama' => 'Pendidikan Agama Islam', 'keterangan' => 'Soal-soal PAI'],
            ['nama' => 'Pengetahuan Umum', 'keterangan' => 'Soal-soal pengetahuan umum'],
            ['nama' => 'Psikotes', 'keterangan' => 'Soal-soal psikotes'],
        ];

        foreach ($topik as $item) {
            Topik::updateOrCreate(
                ['nama' => $item['nama']],
                ['keterangan' => $item['keterangan']]
            );
        }
    }
}
