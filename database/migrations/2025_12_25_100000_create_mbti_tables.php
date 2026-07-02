<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Konfigurasi MBTI per tes
        Schema::create('mbti_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->string('dimensi'); // EI, SN, TF, JP
            $table->json('soal_bagian_1')->nullable(); // Array nomor soal bagian 1 (15 soal)
            $table->json('soal_bagian_2')->nullable(); // Array nomor soal bagian 2 (9 soal)
            $table->json('soal_bagian_3')->nullable(); // Array nomor soal bagian 3 (1 soal)
            $table->string('label_a')->nullable(); // Label untuk jawaban A (E, S, T, J)
            $table->string('label_b')->nullable(); // Label untuk jawaban B (I, N, F, P)
            $table->text('deskripsi_a')->nullable(); // Deskripsi untuk hasil A
            $table->text('deskripsi_b')->nullable(); // Deskripsi untuk hasil B
            $table->timestamps();
            
            $table->unique(['tes_id', 'dimensi']);
        });

        // Deskripsi 16 tipe kepribadian MBTI
        Schema::create('mbti_tipe_deskripsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->string('tipe', 4); // ISTJ, ISFJ, INFJ, INTJ, dll
            $table->string('nama')->nullable(); // Nama tipe (The Inspector, dll)
            $table->text('deskripsi')->nullable();
            $table->text('kekuatan')->nullable();
            $table->text('kelemahan')->nullable();
            $table->text('karir_cocok')->nullable();
            $table->timestamps();
            
            $table->unique(['tes_id', 'tipe']);
        });

        // Hasil MBTI peserta
        Schema::create('hasil_mbti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_tes_id')->constrained('sesi_tes')->onDelete('cascade');
            $table->string('tipe_mbti', 4); // ISTJ, ISFJ, dll
            $table->integer('skor_e')->default(0);
            $table->integer('skor_i')->default(0);
            $table->integer('skor_s')->default(0);
            $table->integer('skor_n')->default(0);
            $table->integer('skor_t')->default(0);
            $table->integer('skor_f')->default(0);
            $table->integer('skor_j')->default(0);
            $table->integer('skor_p')->default(0);
            $table->json('detail_perhitungan')->nullable();
            $table->timestamps();
            
            $table->unique('sesi_tes_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_mbti');
        Schema::dropIfExists('mbti_tipe_deskripsi');
        Schema::dropIfExists('mbti_config');
    }
};
