<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda periode yang status kuotanya DIKUNCI.
 *
 * Dipakai agar aturan kuota baru (harus lengkap formulir + pembayaran Tahap 3)
 * tidak berlaku surut pada periode yang sudah berjalan. Periode yang dikunci
 * dilewati oleh rekalkulasi, sehingga status kuota pesertanya tidak berubah.
 *
 * Aman: ADD COLUMN dengan default, tidak menyentuh data existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tahun_ajaran', 'kunci_kuota')) {
            Schema::table('tahun_ajaran', function (Blueprint $table) {
                $table->boolean('kunci_kuota')->default(false)->after('kuota_perempuan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tahun_ajaran', 'kunci_kuota')) {
            Schema::table('tahun_ajaran', function (Blueprint $table) {
                $table->dropColumn('kunci_kuota');
            });
        }
    }
};
