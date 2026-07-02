<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambah kolom tipe_mbti_2 ke tabel hasil_mbti
        Schema::table('hasil_mbti', function (Blueprint $table) {
            $table->string('tipe_mbti_2', 4)->nullable()->after('tipe_mbti');
        });

        // Tambah kolom pilar_dominan_2 ke tabel hasil_profiling
        Schema::table('hasil_profiling', function (Blueprint $table) {
            $table->string('pilar_dominan_2')->nullable()->after('pilar_dominan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_mbti', function (Blueprint $table) {
            $table->dropColumn('tipe_mbti_2');
        });

        Schema::table('hasil_profiling', function (Blueprint $table) {
            $table->dropColumn('pilar_dominan_2');
        });
    }
};
