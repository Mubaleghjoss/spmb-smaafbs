<?php

namespace App\Console\Commands;

use App\Services\MigrasiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Soal;
use App\Models\Peserta;
use App\Models\Tes;

class ValidasiMigrasiCommand extends Command
{
    protected $signature = 'spmb:validasi-migrasi 
                            {--detail : Tampilkan perbandingan detail per record}
                            {--tabel= : Validasi tabel tertentu (soal, peserta, tes)}
                            {--export : Export hasil validasi ke file}';

    protected $description = 'Validasi integritas data setelah migrasi dari sistem lama';

    protected ?MigrasiService $migrasiService = null;
    protected string $koneksiLama = 'mysql_lama';

    protected function getMigrasiService(): MigrasiService
    {
        if ($this->migrasiService === null) {
            $this->migrasiService = new MigrasiService();
        }
        return $this->migrasiService;
    }

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║           VALIDASI MIGRASI DATA SPMB                       ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $detail = $this->option('detail');
        $tabel = $this->option('tabel');
        $export = $this->option('export');

        try {
            // Validasi jumlah record
            $this->info('Memvalidasi jumlah record...');
            $hasilValidasi = $this->getMigrasiService()->validasiMigrasi();
            $this->tampilkanHasilValidasi($hasilValidasi);

            // Validasi detail jika diminta
            if ($detail) {
                $this->newLine();
                $this->info('Memvalidasi integritas data...');
                
                if ($tabel) {
                    $this->validasiTabel($tabel);
                } else {
                    $this->validasiTabel('soal');
                    $this->validasiTabel('peserta');
                    $this->validasiTabel('tes');
                }
            }

            // Export hasil jika diminta
            if ($export) {
                $this->newLine();
                $path = $this->getMigrasiService()->generateLaporanFile();
                $this->info("Laporan validasi disimpan ke: {$path}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Validasi gagal: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function tampilkanHasilValidasi(array $hasil): void
    {
        $headers = ['Tabel', 'Data Lama', 'Data Baru', 'Selisih', 'Status'];
        $rows = [];
        $semuaOk = true;

        foreach ($hasil as $tabel => $data) {
            $status = $data['status'] === 'OK' ? '✓ OK' : '✗ PERLU REVIEW';
            if ($data['status'] !== 'OK') $semuaOk = false;
            
            $rows[] = [
                ucfirst($tabel),
                $data['lama'],
                $data['baru'],
                $data['selisih'],
                $status,
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        if ($semuaOk) {
            $this->info('✓ Semua data berhasil dimigrasi dengan benar!');
        } else {
            $this->warn('⚠ Beberapa data perlu direview. Gunakan --detail untuk melihat perbedaan.');
        }
    }


    protected function validasiTabel(string $tabel): void
    {
        $this->newLine();
        $this->info("Validasi tabel: {$tabel}");

        match ($tabel) {
            'soal' => $this->validasiSoal(),
            'peserta' => $this->validasiPeserta(),
            'tes' => $this->validasiTes(),
            default => $this->warn("Tabel tidak dikenal: {$tabel}"),
        };
    }

    protected function validasiSoal(): void
    {
        $soalLama = DB::connection($this->koneksiLama)
            ->table('cbt_soal')
            ->limit(10)
            ->get();

        $this->line("Contoh perbandingan soal (10 record pertama):");
        
        $rows = [];
        foreach ($soalLama as $lama) {
            $baru = Soal::where('pertanyaan', $lama->soal_detail)->first();
            
            $rows[] = [
                $lama->soal_id,
                substr(strip_tags($lama->soal_detail), 0, 30) . '...',
                $baru ? $baru->id : '-',
                $baru ? '✓' : '✗',
            ];
        }

        $this->table(['ID Lama', 'Pertanyaan', 'ID Baru', 'Status'], $rows);
    }

    protected function validasiPeserta(): void
    {
        $pesertaLama = DB::connection($this->koneksiLama)
            ->table('cbt_user')
            ->limit(10)
            ->get();

        $this->line("Contoh perbandingan peserta (10 record pertama):");
        
        $rows = [];
        foreach ($pesertaLama as $lama) {
            $baru = Peserta::where('username', $lama->user_name)->first();
            
            $rows[] = [
                $lama->user_id,
                $lama->user_firstname ?? $lama->user_name,
                $baru ? $baru->id : '-',
                $baru ? '✓' : '✗',
            ];
        }

        $this->table(['ID Lama', 'Nama', 'ID Baru', 'Status'], $rows);
    }

    protected function validasiTes(): void
    {
        $tesLama = DB::connection($this->koneksiLama)
            ->table('cbt_tes')
            ->limit(10)
            ->get();

        $this->line("Contoh perbandingan tes (10 record pertama):");
        
        $rows = [];
        foreach ($tesLama as $lama) {
            $baru = Tes::where('nama', $lama->tes_nama)->first();
            
            $rows[] = [
                $lama->tes_id,
                substr($lama->tes_nama, 0, 30),
                $baru ? $baru->id : '-',
                $baru ? '✓' : '✗',
            ];
        }

        $this->table(['ID Lama', 'Nama Tes', 'ID Baru', 'Status'], $rows);
    }
}
