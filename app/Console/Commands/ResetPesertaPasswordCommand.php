<?php

namespace App\Console\Commands;

use App\Models\Peserta;
use Illuminate\Console\Command;

class ResetPesertaPasswordCommand extends Command
{
    protected $signature = 'peserta:reset-password {email?} {--all : Reset semua peserta}';
    protected $description = 'Reset password peserta ke password default';

    public function handle(): int
    {
        if ($this->option('all')) {
            $count = Peserta::count();
            if ($this->confirm("Apakah Anda yakin ingin reset password {$count} peserta?")) {
                // Password akan otomatis di-hash oleh model cast 'hashed'
                Peserta::query()->update(['password' => 'password123']);
                $this->info("Password {$count} peserta berhasil direset ke 'password123'");
            }
            return Command::SUCCESS;
        }

        $email = $this->argument('email');
        if (!$email) {
            $email = $this->ask('Masukkan email peserta');
        }

        $peserta = Peserta::where('email', $email)->first();
        if (!$peserta) {
            $this->error("Peserta dengan email {$email} tidak ditemukan");
            return Command::FAILURE;
        }

        // Password akan otomatis di-hash oleh model cast 'hashed'
        $peserta->update(['password' => 'password123']);
        $this->info("Password peserta {$peserta->nama} ({$email}) berhasil direset ke 'password123'");

        return Command::SUCCESS;
    }
}
