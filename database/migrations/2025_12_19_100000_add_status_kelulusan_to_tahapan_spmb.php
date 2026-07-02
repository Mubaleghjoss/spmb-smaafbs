<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom status_kelulusan untuk fitur verifikasi kelulusan
     */
    public function up(): void
    {
        Schema::table('tahapan_spmb', function (Blueprint $table) {
            $table->enum('status_kelulusan', ['menunggu', 'lulus', 'tidak_lulus'])
                  ->default('menunggu')
                  ->after('tahap_7_selesai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tahapan_spmb', function (Blueprint $table) {
            $table->dropColumn('status_kelulusan');
        });
    }
};
