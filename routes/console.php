<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Proses antrian job setiap menit
Schedule::command('queue:work --stop-when-empty')->everyMinute();

// Bersihkan cache kadaluarsa setiap jam
Schedule::command('cache:prune-stale-tags')->hourly();

// Hapus token kadaluarsa setiap hari tengah malam
Schedule::command('spmb:cleanup-expired-tokens')->dailyAt('00:00');

// Backup database otomatis setiap hari jam 2 pagi
Schedule::command('spmb:backup-database')->dailyAt('02:00');
