<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Konfigurasi Profiling (PiES) per tes
        Schema::create('profiling_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->boolean('aktif')->default(true);
            $table->integer('jumlah_soal')->default(30);
            $table->timestamps();
            
            $table->unique('tes_id');
        });

        // Mapping jawaban ke pilar per soal
        // Contoh: Soal 1, Jawaban A = Kreatif, Jawaban B = Emosional, dst
        Schema::create('profiling_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->integer('nomor_soal');
            $table->string('jawaban_a')->nullable(); // kreatif, emosional, aksi, logika, spiritual
            $table->string('jawaban_b')->nullable();
            $table->string('jawaban_c')->nullable();
            $table->string('jawaban_d')->nullable();
            $table->string('jawaban_e')->nullable();
            $table->timestamps();
            
            $table->unique(['tes_id', 'nomor_soal']);
        });

        // Deskripsi pilar
        Schema::create('profiling_pilar_deskripsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->string('pilar'); // kreatif, emosional, aksi, logika, spiritual
            $table->string('kode_qx'); // CQ, EQ, AQ, IQ, SQ
            $table->string('nama_qx'); // Creativity Quotient, dll
            $table->text('deskripsi')->nullable();
            $table->text('kekuatan')->nullable();
            $table->text('saran_pengembangan')->nullable();
            $table->timestamps();
            
            $table->unique(['tes_id', 'pilar']);
        });

        // Hasil Profiling peserta
        Schema::create('hasil_profiling', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_tes_id')->constrained('sesi_tes')->onDelete('cascade');
            $table->string('pilar_dominan'); // kreatif, emosional, aksi, logika, spiritual
            $table->integer('skor_kreatif')->default(0);
            $table->integer('skor_emosional')->default(0);
            $table->integer('skor_aksi')->default(0);
            $table->integer('skor_logika')->default(0);
            $table->integer('skor_spiritual')->default(0);
            $table->json('detail_jawaban')->nullable(); // Detail jawaban per soal
            $table->timestamps();
            
            $table->unique('sesi_tes_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_profiling');
        Schema::dropIfExists('profiling_pilar_deskripsi');
        Schema::dropIfExists('profiling_mapping');
        Schema::dropIfExists('profiling_config');
    }
};
