<?php

namespace App\Models\Scopes;

use App\Services\JalurContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * JalurScope
 *
 * Global scope yang memfilter query Peserta ke JALUR PENDAFTARAN yang sedang
 * dipilih admin (siswa baru / pindahan). Sengaja dibuat sebagai global scope —
 * meniru PeriodeScope — supaya SELURUH halaman admin (dashboard, daftar peserta,
 * verifikasi, monitoring, hasil, alur, kartu statistik) otomatis konsisten tanpa
 * perlu menambal ratusan query satu per satu.
 *
 * PENTING — scope ini HANYA aktif bila:
 *   1. Ada admin (guard 'pengguna') yang login, DAN
 *   2. Admin sudah memilih jalur (bukan keadaan "Pilih Jalur Pendaftaran").
 *
 * Sehingga pendaftaran publik, proses sisi peserta, API integrasi, seeder, dan
 * artisan command TIDAK terpengaruh.
 *
 * Untuk melewati scope secara eksplisit:
 *   Peserta::withoutGlobalScope(JalurScope::class)->...
 * atau helper: Peserta::semuaJalur()->...
 */
class JalurScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Hanya berlaku untuk konteks admin yang sudah login.
        if (! auth('pengguna')->check()) {
            return;
        }

        $jenis = app(JalurContextService::class)->jenis();

        // null = belum memilih jalur → tidak difilter (halaman kerja sendiri
        // yang menampilkan kartu pengarah "Pilih Jalur Pendaftaran").
        if ($jenis === null) {
            return;
        }

        $builder->where($model->getTable() . '.jenis_pendaftaran', $jenis);
    }
}
