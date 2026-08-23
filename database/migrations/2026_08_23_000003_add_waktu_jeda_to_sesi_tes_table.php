<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom waktu_jeda pada sesi_tes untuk PAUSE ujian yang sungguhan.
 *
 * Saat peserta keluar dari halaman ujian, waktu_jeda = now() (waktu dibekukan).
 * Saat peserta kembali, sisa waktu dipulihkan persis: waktu_mulai digeser maju
 * sebesar durasi jeda, lalu waktu_jeda dikosongkan.
 *
 * Aman: hanya ALTER ADD COLUMN nullable, tidak menyentuh data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sesi_tes', 'waktu_jeda')) {
            Schema::table('sesi_tes', function (Blueprint $table) {
                $table->timestamp('waktu_jeda')->nullable()->after('waktu_mulai');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sesi_tes', 'waktu_jeda')) {
            Schema::table('sesi_tes', function (Blueprint $table) {
                $table->dropColumn('waktu_jeda');
            });
        }
    }
};
