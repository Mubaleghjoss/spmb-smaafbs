<?php

namespace App\Console\Commands;

use App\Models\Pengguna;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetPenggunaLoginCommand extends Command
{
    protected $signature = 'pengguna:reset-login
                            {email : Email admin/operator}
                            {--password= : Password baru, minimal 6 karakter}
                            {--nama= : Nama pengguna jika akun perlu dibuat}
                            {--peran=operator : Peran pengguna, admin atau operator}';

    protected $description = 'Reset atau buat login admin/operator, aktifkan akun, dan bersihkan lockout.';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) ($this->option('password') ?: $this->secret('Password baru'));
        $peran = (string) $this->option('peran');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }

        if (strlen($password) < 6) {
            $this->error('Password minimal 6 karakter.');

            return self::FAILURE;
        }

        if (! in_array($peran, ['admin', 'operator'], true)) {
            $this->error('Peran harus admin atau operator.');

            return self::FAILURE;
        }

        $pengguna = Pengguna::query()->where('email', $email)->first();
        $nama = (string) ($this->option('nama') ?: $pengguna?->nama ?: 'Pengguna SPMB');

        $pengguna = Pengguna::query()->updateOrCreate(
            ['email' => $email],
            [
                'nama' => $nama,
                'password' => $password,
                'peran' => $peran,
                'aktif' => true,
                'percobaan_login' => 0,
                'dikunci_sampai' => null,
            ]
        );

        Cache::forget('login_attempts_' . md5($email));

        $this->info("Login {$pengguna->email} berhasil direset.");
        $this->line("Nama: {$pengguna->nama}");
        $this->line("Peran: {$pengguna->peran}");
        $this->line('Status: aktif, lockout bersih.');

        return self::SUCCESS;
    }
}
