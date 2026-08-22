<?php

namespace App\Providers;

use App\Services\PengaturanService;
use App\Services\PeriodeContextService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share branding data ke semua view
        View::composer('*', function ($view) {
            try {
                $pengaturanService = app(PengaturanService::class);
                $branding = $pengaturanService->ambilBranding();
                $view->with('branding', $branding);
            } catch (\Exception $e) {
                // Fallback jika database belum ready
                $view->with('branding', [
                    'nama_institusi' => config('app.name', 'SPMB'),
                    'nama_singkat' => 'SPMB',
                    'alamat' => '',
                    'telepon' => '',
                    'email' => '',
                    'website' => '',
                    'logo' => '',
                    'favicon' => '',
                    'warna_primer' => '#0d6efd',
                    'warna_sekunder' => '#6c757d',
                    'tahun_ajaran' => date('Y') . '/' . (date('Y') + 1),
                ]);
            }
        });

        // Share konteks periode aktif ke layout admin (switcher header) & banner partial.
        View::composer(['layouts.admin', 'partials.periode-banner'], function ($view) {
            try {
                if (auth('pengguna')->check()) {
                    $periode = app(PeriodeContextService::class);
                    $view->with('periodeAktifLabel', $periode->label());
                    $view->with('periodeAktifSemua', $periode->semuaPeriode());
                    $view->with('periodeAktifId', $periode->tahunAjaranId());
                    $view->with('periodePilihan', $periode->pilihanTahunAjaran());
                }
            } catch (\Throwable $e) {
                // Abaikan bila DB belum siap.
            }
        });
    }
}
