<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Controls whether quota information is shown for an academic year in the
 * admin dashboard. Existing years remain visible by default.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tahun_ajaran', 'tampilkan_kuota')) {
            Schema::table('tahun_ajaran', function (Blueprint $table) {
                $table->boolean('tampilkan_kuota')->default(true)->after('kunci_kuota');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tahun_ajaran', 'tampilkan_kuota')) {
            Schema::table('tahun_ajaran', function (Blueprint $table) {
                $table->dropColumn('tampilkan_kuota');
            });
        }
    }
};
