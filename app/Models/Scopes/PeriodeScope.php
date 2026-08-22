<?php

namespace App\Models\Scopes;

use App\Services\PeriodeContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * PeriodeScope
 *
 * Global scope yang memfilter query Peserta ke tahun ajaran yang sedang aktif
 * di konteks admin (PeriodeContextService). Dengan begitu SEMUA halaman admin
 * (dashboard, monitoring, verifikasi, hasil, daftar peserta) otomatis konsisten
 * memakai periode yang dipilih tanpa perlu mengubah ratusan query satu per satu.
 *
 * PENTING — scope ini HANYA aktif bila:
 *   1. Ada admin (guard 'pengguna') yang login, DAN
 *   2. Konteks periode bukan "Semua Periode".
 *
 * Sehingga: pendaftaran publik, proses sisi peserta, seeder, artisan command,
 * dan mode "Semua Periode" TIDAK terpengaruh (tidak ikut difilter).
 *
 * Untuk melewati scope secara eksplisit di query tertentu:
 *   Peserta::withoutGlobalScope(PeriodeScope::class)->...
 * atau helper: Peserta::semuaPeriode()->...
 */
class PeriodeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Hanya berlaku untuk konteks admin yang sudah login.
        if (! auth('pengguna')->check()) {
            return;
        }

        $context = app(PeriodeContextService::class);
        $tahunAjaranId = $context->tahunAjaranId();

        // null = "Semua Periode" → tidak difilter.
        if ($tahunAjaranId === null) {
            return;
        }

        $builder->where($model->getTable() . '.tahun_ajaran_id', $tahunAjaranId);
    }
}
