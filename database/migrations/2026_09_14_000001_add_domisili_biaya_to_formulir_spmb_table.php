<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->string('domisili_biaya', 40)->nullable()->after('kelompok');
            $table->string('nama_daerah_luar', 100)->nullable()->after('domisili_biaya');
        });
    }

    public function down(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->dropColumn(['domisili_biaya', 'nama_daerah_luar']);
        });
    }
};
