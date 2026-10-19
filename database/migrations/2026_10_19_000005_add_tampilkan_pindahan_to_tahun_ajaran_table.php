<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tahun_ajaran', 'tampilkan_pindahan')) {
            Schema::table('tahun_ajaran', function (Blueprint $table) {
                $table->boolean('tampilkan_pindahan')->default(true)->after('tampilkan_kuota');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tahun_ajaran', 'tampilkan_pindahan')) {
            Schema::table('tahun_ajaran', function (Blueprint $table) {
                $table->dropColumn('tampilkan_pindahan');
            });
        }
    }
};
