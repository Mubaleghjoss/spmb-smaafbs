<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'spmb:backup-database {--path= : Path untuk menyimpan backup}';
    protected $description = 'Backup database MySQL ke file SQL';

    public function handle(): int
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $path = $this->option('path') ?: storage_path('app/backups');

        // Buat direktori jika belum ada
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filepath = $path . '/' . $filename;

        // Command mysqldump
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($filepath)) {
            $size = $this->formatBytes(filesize($filepath));
            $message = "Backup database berhasil: {$filename} ({$size})";
            $this->info($message);
            Log::info($message);

            // Hapus backup lama (simpan 7 hari terakhir)
            $this->cleanupOldBackups($path);

            return Command::SUCCESS;
        }

        $error = implode("\n", $output);
        $this->error("Backup database gagal: {$error}");
        Log::error("Backup database gagal: {$error}");

        return Command::FAILURE;
    }

    private function cleanupOldBackups(string $path): void
    {
        $files = glob($path . '/backup_*.sql');
        $maxAge = 7 * 24 * 60 * 60; // 7 hari

        foreach ($files as $file) {
            if (time() - filemtime($file) > $maxAge) {
                unlink($file);
                $this->line("Menghapus backup lama: " . basename($file));
            }
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
