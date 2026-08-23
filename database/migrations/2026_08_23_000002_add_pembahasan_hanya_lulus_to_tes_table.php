<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom pembahasan_hanya_lulus pada tabel tes.
 *
 * true  = pembahasan hanya tampil untuk peserta yang LULUS (belum memenuhi
 *         syarat -> pembahasan disembunyikan). Ini permintaan sekolah.
 * false = pembahasan tampil untuk semua (mengikuti tampilkan_pembahasan saja).
 *
 * Default true agar aman (pembahasan tidak bocor ke yang belum lulus).
 * Aman: hanya ALTER ADD COLUMN, tidak menyentuh data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tes', 'pembahasan_hanya_lulus')) {
            Schema::table('tes', function (Blueprint $table) {
                $table->boolean('pembahasan_hanya_lulus')->default(true)->after('tampilkan_pembahasan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tes', 'pembahasan_hanya_lulus')) {
            Schema::table('tes', function (Blueprint $table) {
                $table->dropColumn('pembahasan_hanya_lulus');
            });
        }
    }
};
