<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Topik;
use App\Models\Soal;
use App\Models\Jawaban;

/**
 * Seeder untuk migrasi data soal dari database lama (CodeIgniter)
 * ke database baru (Laravel)
 * 
 * Mapping:
 * - cbt_topik → topik
 * - cbt_soal → soal
 * - cbt_jawaban → jawaban
 * 
 * Database lama: sman5479_ujian (CodeIgniter)
 * Database baru: spmb_alfurqon (Laravel)
 */
class MigrasiSoalSeeder extends Seeder
{
    /**
     * Nama koneksi database lama
     */
    private string $dbLama = 'mysql_lama';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai migrasi data soal dari database lama...');

        // Setup koneksi ke database lama
        $this->setupKoneksiDbLama();

        // Cek apakah tabel lama ada
        if (!$this->tabelLamaAda()) {
            $this->command->error('Tabel cbt_topik, cbt_soal, atau cbt_jawaban tidak ditemukan di database lama!');
            $this->command->info('Pastikan database sman5479_ujian tersedia.');
            return;
        }

        DB::transaction(function () {
            // 1. Migrasi Topik
            $this->migrasiTopik();

            // 2. Migrasi Soal
            $this->migrasiSoal();

            // 3. Migrasi Jawaban
            $this->migrasiJawaban();
        });

        $this->command->info('Migrasi data soal selesai!');
    }

    /**
     * Setup koneksi ke database lama
     */
    private function setupKoneksiDbLama(): void
    {
        Config::set('database.connections.' . $this->dbLama, [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => 'sman5479_ujian', // Database lama CodeIgniter
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);
    }

    /**
     * Cek apakah tabel lama ada
     */
    private function tabelLamaAda(): bool
    {
        try {
            DB::connection($this->dbLama)->table('cbt_topik')->limit(1)->get();
            DB::connection($this->dbLama)->table('cbt_soal')->limit(1)->get();
            DB::connection($this->dbLama)->table('cbt_jawaban')->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            $this->command->error('Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Migrasi data topik
     */
    private function migrasiTopik(): void
    {
        $this->command->info('Migrasi topik...');

        $topikLama = DB::connection($this->dbLama)->table('cbt_topik')->get();
        $count = 0;

        foreach ($topikLama as $topik) {
            // Cek apakah sudah ada
            $existing = DB::table('topik')->where('id', $topik->topik_id)->first();
            
            if (!$existing) {
                DB::table('topik')->insert([
                    'id' => $topik->topik_id,
                    'nama' => $topik->topik_nama,
                    'keterangan' => $topik->topik_detail ?? null,
                    'parent_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        $this->command->info("  - {$count} topik berhasil dimigrasi");
    }

    /**
     * Migrasi data soal
     */
    private function migrasiSoal(): void
    {
        $this->command->info('Migrasi soal...');

        $soalLama = DB::connection($this->dbLama)->table('cbt_soal')->get();
        $count = 0;

        foreach ($soalLama as $soal) {
            // Cek apakah sudah ada
            $existing = Soal::where('id', $soal->soal_id)->first();
            
            if (!$existing) {
                // Mapping tipe soal
                $tipe = $this->mappingTipeSoal($soal->soal_tipe);

                Soal::create([
                    'id' => $soal->soal_id,
                    'topik_id' => $soal->soal_topik_id,
                    'pertanyaan' => $soal->soal_detail,
                    'tipe' => $tipe,
                    'bobot' => 1,
                    'media' => $soal->soal_audio ?? null,
                    'tipe_media' => $soal->soal_audio ? 'audio' : null,
                    'pembahasan' => null,
                    'aktif' => (bool) $soal->soal_aktif,
                    'dibuat_oleh' => null,
                ]);
                $count++;
            }
        }

        $this->command->info("  - {$count} soal berhasil dimigrasi");
    }

    /**
     * Migrasi data jawaban
     */
    private function migrasiJawaban(): void
    {
        $this->command->info('Migrasi jawaban...');

        $jawabanLama = DB::connection($this->dbLama)->table('cbt_jawaban')->get();
        $count = 0;
        $urutan = [];

        foreach ($jawabanLama as $jawaban) {
            // Cek apakah sudah ada
            $existing = Jawaban::where('id', $jawaban->jawaban_id)->first();
            
            if (!$existing) {
                // Cek apakah soal ada
                $soalAda = Soal::where('id', $jawaban->jawaban_soal_id)->exists();
                
                if ($soalAda) {
                    // Track urutan per soal
                    if (!isset($urutan[$jawaban->jawaban_soal_id])) {
                        $urutan[$jawaban->jawaban_soal_id] = 0;
                    }
                    $urutan[$jawaban->jawaban_soal_id]++;

                    Jawaban::create([
                        'id' => $jawaban->jawaban_id,
                        'soal_id' => $jawaban->jawaban_soal_id,
                        'isi_jawaban' => $jawaban->jawaban_detail,
                        'benar' => (bool) $jawaban->jawaban_benar,
                        'urutan' => $urutan[$jawaban->jawaban_soal_id],
                    ]);
                    $count++;
                }
            }
        }

        $this->command->info("  - {$count} jawaban berhasil dimigrasi");
    }

    /**
     * Mapping tipe soal dari database lama ke baru
     * Lama: 1=Pilihan ganda, 2=essay, 3=jawaban singkat
     * Baru: pilihan_ganda, jawaban_ganda, esai, benar_salah
     */
    private function mappingTipeSoal(int $tipeLama): string
    {
        return match ($tipeLama) {
            1 => 'pilihan_ganda',
            2 => 'esai',
            3 => 'pilihan_ganda', // jawaban singkat → pilihan_ganda
            default => 'pilihan_ganda',
        };
    }
}
