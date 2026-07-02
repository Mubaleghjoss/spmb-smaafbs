<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Sync Routes (dilindungi token)
|--------------------------------------------------------------------------
*/
Route::prefix('sync')->group(function () {
    Route::get('/export', [\App\Http\Controllers\Admin\SyncController::class, 'eksporData']);
    Route::post('/import', [\App\Http\Controllers\Admin\SyncController::class, 'imporData']);
});
