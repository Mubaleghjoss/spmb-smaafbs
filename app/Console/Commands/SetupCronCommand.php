<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupCronCommand extends Command
{
    protected $signature = 'spmb:setup-cron {--show : Tampilkan perintah cron saja}';
    protected $description = 'Tampilkan instruksi setup cron job untuk cPanel';

    public function handle(): int
    {
        $phpPath = PHP_BINARY;
        $artisanPath = base_path('artisan');
        
        $this->info('=== Setup Cron Job untuk SPMB Al-Furqon ===');
        $this->newLine();
        
        $this->info('Langkah-langkah setup cron di cPanel:');
        $this->line('1. Login ke cPanel');
        $this->line('2. Cari menu "Cron Jobs" di bagian Advanced');
        $this->line('3. Tambahkan cron job baru dengan pengaturan berikut:');
        $this->newLine();
        
        $this->info('Common Settings: Once Per Minute (* * * * *)');
        $this->newLine();
        
        $this->info('Command:');
        $cronCommand = "{$phpPath} {$artisanPath} schedule:run >> /dev/null 2>&1";
        $this->line($cronCommand);
        $this->newLine();
        
        $this->info('Atau jika menggunakan path PHP cPanel:');
        $cpanelCommand = "/usr/local/bin/php {$artisanPath} schedule:run >> /dev/null 2>&1";
        $this->line($cpanelCommand);
        $this->newLine();
        
        $this->info('Scheduled Tasks yang akan dijalankan:');
        $this->table(
            ['Task', 'Jadwal', 'Deskripsi'],
            [
                ['queue:work --stop-when-empty', 'Setiap menit', 'Proses antrian job'],
                ['cache:prune-stale-tags', 'Setiap jam', 'Bersihkan cache kadaluarsa'],
                ['spmb:cleanup-expired-tokens', 'Setiap hari 00:00', 'Hapus token kadaluarsa'],
                ['spmb:backup-database', 'Setiap hari 02:00', 'Backup database otomatis'],
            ]
        );
        
        return Command::SUCCESS;
    }
}
