<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel untuk konfigurasi tes gaya belajar (Visual/Auditori/Kinestetik)
     */
    public function up(): void
    {
        // Tabel konfigurasi gaya belajar per tes
        Schema::create('gaya_belajar_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->boolean('aktif')->default(false);
            $table->json('mapping_jawaban'); // {"A": "visual", "B": "auditori", "C": "kinestetik"}
            $table->json('deskripsi_tipe')->nullable(); // {"visual": "...", "auditori": "...", "kinestetik": "..."}
            $table->timestamps();
            
            $table->unique('tes_id');
        });

        // Tabel hasil gaya belajar
        Schema::create('hasil_gaya_belajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_tes_id')->constrained('sesi_tes')->onDelete('cascade');
            $table->string('hasil_gaya_belajar'); // visual, auditori, kinestetik, atau gabungan
            $table->json('detail_nilai'); // {"visual": 20, "auditori": 15, "kinestetik": 9}
            $table->timestamps();
            
            $table->unique('sesi_tes_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_gaya_belajar');
        Schema::dropIfExists('gaya_belajar_config');
    }
};
