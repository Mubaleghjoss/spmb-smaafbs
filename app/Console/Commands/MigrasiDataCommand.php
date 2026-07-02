<?php

namespace App\Console\Commands;

use App\Services\MigrasiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrasiDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spmb:migrasi 
                            {--tipe= : Tipe migrasi (semua, soal, peserta, tes, grup, topik)}
                            {--validasi : Jalankan validasi setelah migrasi}
                            {--dry-run : Simulasi migrasi tanpa menyimpan data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrasi data dari sistem CBT lama (CodeIgniter) ke sistem baru (Laravel)';

    protected ?MigrasiService $migrasiService = null;

    protected function getMigrasiService(): MigrasiService
    {
        if ($this->migrasiService === null) {
            $this->migrasiService = new MigrasiService();
        }
        return $this->migrasiService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║     MIGRASI DATA SPMB - CodeIgniter ke Laravel             ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $tipe = $this->option('tipe') ?? 'semua';
        $validasi = $this->option('validasi');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode DRY-RUN: Data tidak akan disimpan ke database');
            $this->newLine();
        }

        // Konfirmasi sebelum migrasi
        if (!$this->confirm('Apakah Anda yakin ingin melanjutkan migrasi data?')) {
            $this->info('Migrasi dibatalkan.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info("Memulai migrasi tipe: {$tipe}");
        $this->newLine();

        try {
            $startTime = microtime(true);

            // Progress bar
            $this->output->progressStart(8);

            $laporan = match ($tipe) {
                'semua' => $this->migrasiSemua(),
                'soal' => $this->migrasiSoal(),
                'peserta' => $this->migrasiPeserta(),
                'tes' => $this->migrasiTes(),
                'grup' => $this->migrasiGrup(),
                'topik' => $this->migrasiTopik(),
                default => $this->migrasiSemua(),
            };

            $this->output->progressFinish();
            $this->newLine();

            // Tampilkan laporan
            $this->tampilkanLaporan($laporan);

            // Validasi jika diminta
            if ($validasi) {
                $this->newLine();
                $this->info('Menjalankan validasi migrasi...');
                $hasilValidasi = $this->getMigrasiService()->validasiMigrasi();
                $this->tampilkanValidasi($hasilValidasi);
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->newLine();
            $this->info("Migrasi selesai dalam {$duration} detik");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Migrasi gagal: ' . $e->getMessage());
            Log::error('Migrasi gagal', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }

    protected function migrasiSemua(): array
    {
        $service = $this->getMigrasiService();
        
        $this->output->progressAdvance();
        $this->line(' Migrasi pengguna...');
        $service->migrasiPengguna();

        $this->output->progressAdvance();
        $this->line(' Migrasi grup...');
        $service->migrasiGrup();

        $this->output->progressAdvance();
        $this->line(' Migrasi topik...');
        $service->migrasiTopik();

        $this->output->progressAdvance();
        $this->line(' Migrasi soal...');
        $service->migrasiSoal();

        $this->output->progressAdvance();
        $this->line(' Migrasi jawaban...');
        $service->migrasiJawaban();

        $this->output->progressAdvance();
        $this->line(' Migrasi peserta...');
        $service->migrasiPeserta();

        $this->output->progressAdvance();
        $this->line(' Migrasi tes...');
        $service->migrasiTes();

        $this->output->progressAdvance();
        $this->line(' Migrasi sesi tes...');
        $service->migrasiSesiTes();

        return $service->ambilLaporan();
    }


    protected function migrasiSoal(): array
    {
        $service = $this->getMigrasiService();
        $service->migrasiTopik();
        $service->migrasiSoal();
        $service->migrasiJawaban();
        return $service->ambilLaporan();
    }

    protected function migrasiPeserta(): array
    {
        $service = $this->getMigrasiService();
        $service->migrasiGrup();
        $service->migrasiPeserta();
        return $service->ambilLaporan();
    }

    protected function migrasiTes(): array
    {
        $service = $this->getMigrasiService();
        $service->migrasiTes();
        $service->migrasiSesiTes();
        return $service->ambilLaporan();
    }

    protected function migrasiGrup(): array
    {
        $service = $this->getMigrasiService();
        $service->migrasiGrup();
        return $service->ambilLaporan();
    }

    protected function migrasiTopik(): array
    {
        $service = $this->getMigrasiService();
        $service->migrasiTopik();
        return $service->ambilLaporan();
    }

    protected function tampilkanLaporan(array $laporan): void
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                    LAPORAN MIGRASI                         ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $headers = ['Tabel', 'Sukses', 'Gagal', 'Total'];
        $rows = [];

        foreach ($laporan as $tabel => $data) {
            if ($tabel === 'error_umum') continue;
            
            $rows[] = [
                ucfirst($tabel),
                $data['sukses'] ?? 0,
                $data['gagal'] ?? 0,
                ($data['sukses'] ?? 0) + ($data['gagal'] ?? 0),
            ];
        }

        $this->table($headers, $rows);

        // Tampilkan error jika ada
        foreach ($laporan as $tabel => $data) {
            if ($tabel === 'error_umum') continue;
            
            if (!empty($data['error'])) {
                $this->newLine();
                $this->warn("Error pada tabel {$tabel}:");
                foreach (array_slice($data['error'], 0, 5) as $error) {
                    $this->line("  - ID: {$error['id']}, Pesan: {$error['pesan']}");
                }
                if (count($data['error']) > 5) {
                    $this->line("  ... dan " . (count($data['error']) - 5) . " error lainnya");
                }
            }
        }

        if (isset($laporan['error_umum'])) {
            $this->newLine();
            $this->error("Error umum: {$laporan['error_umum']}");
        }
    }

    protected function tampilkanValidasi(array $hasil): void
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                   VALIDASI MIGRASI                         ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $headers = ['Tabel', 'Data Lama', 'Data Baru', 'Selisih', 'Status'];
        $rows = [];

        foreach ($hasil as $tabel => $data) {
            $status = $data['status'] === 'OK' ? '<fg=green>OK</>' : '<fg=yellow>PERLU REVIEW</>';
            $rows[] = [
                ucfirst($tabel),
                $data['lama'],
                $data['baru'],
                $data['selisih'],
                $status,
            ];
        }

        $this->table($headers, $rows);
    }
}
