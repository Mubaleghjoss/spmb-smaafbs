<?php

namespace App\Console\Commands;

use App\Models\Token;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredTokensCommand extends Command
{
    protected $signature = 'spmb:cleanup-expired-tokens';
    protected $description = 'Hapus token yang sudah kadaluarsa';

    public function handle(): int
    {
        $count = Token::where('kedaluwarsa_pada', '<', now())
            ->whereNull('digunakan_pada')
            ->delete();

        $message = "Berhasil menghapus {$count} token kadaluarsa";
        $this->info($message);
        Log::info($message);

        return Command::SUCCESS;
    }
}
