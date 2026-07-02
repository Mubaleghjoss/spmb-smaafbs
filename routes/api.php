<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1/integrations/akses')
    ->middleware(['akses.sync', 'throttle:30,1'])
    ->group(function () {
        Route::get('/graduated-students', \App\Http\Controllers\Api\GraduatedStudentController::class);
    });

/*
|--------------------------------------------------------------------------
| API Sync Routes (dilindungi token)
|--------------------------------------------------------------------------
*/
Route::prefix('sync')->group(function () {
    Route::get('/export', [\App\Http\Controllers\Admin\SyncController::class, 'eksporData']);
    Route::post('/import', [\App\Http\Controllers\Admin\SyncController::class, 'imporData']);
});
