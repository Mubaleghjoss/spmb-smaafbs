<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel tes, token, sesi_tes, dan jawaban_peserta
     * Kebutuhan: 4.1, 4.4, 5.1
     */
    public function up(): void
    {
        // Tabel tes
        Schema::create('tes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->integer('durasi_menit')->default(60);
            $table->decimal('nilai_lulus', 5, 2)->default(60.00);
            $table->datetime('mulai')->nullable();
            $table->datetime('selesai')->nullable();
            $table->boolean('acak_soal')->default(false);
            $table->boolean('acak_jawaban')->default(false);
            $table->boolean('tampilkan_nilai')->default(true);
            $table->boolean('tampilkan_pembahasan')->default(false);
            $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft');
            $table->timestamps();
            
            $table->index(['status', 'mulai', 'selesai']);
        });

        // Tabel pivot tes_soal
        Schema::create('tes_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->foreignId('soal_id')->constrained('soal')->onDelete('cascade');
            $table->integer('urutan')->default(0);
            $table->integer('bobot_custom')->nullable(); // Override bobot soal
            $table->timestamps();
            
            $table->unique(['tes_id', 'soal_id']);
            $table->index(['tes_id', 'urutan']);
        });

        // Tabel token akses tes
        Schema::create('token', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->string('kode', 20)->unique();
            $table->datetime('kedaluwarsa')->nullable();
            $table->boolean('terpakai')->default(false);
            $table->foreignId('dipakai_oleh')->nullable()->constrained('peserta')->nullOnDelete();
            $table->timestamp('dipakai_pada')->nullable();
            $table->timestamps();
            
            $table->index(['tes_id', 'terpakai']);
        });

        // Tabel sesi tes
        Schema::create('sesi_tes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            $table->foreignId('token_id')->nullable()->constrained('token')->nullOnDelete();
            $table->datetime('waktu_mulai');
            $table->datetime('waktu_selesai')->nullable();
            $table->decimal('nilai', 5, 2)->nullable();
            $table->enum('status', ['berlangsung', 'selesai', 'timeout', 'dibatalkan'])->default('berlangsung');
            $table->json('urutan_soal')->nullable(); // Urutan soal yang diacak
            $table->integer('soal_saat_ini')->default(1);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['tes_id', 'peserta_id', 'status']);
            $table->index(['peserta_id', 'status']);
        });

        // Tabel jawaban peserta
        Schema::create('jawaban_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_tes_id')->constrained('sesi_tes')->onDelete('cascade');
            $table->foreignId('soal_id')->constrained('soal')->onDelete('cascade');
            $table->foreignId('jawaban_id')->nullable()->constrained('jawaban')->nullOnDelete();
            $table->json('jawaban_ganda')->nullable(); // Untuk tipe jawaban ganda
            $table->text('jawaban_esai')->nullable();
            $table->boolean('benar')->nullable();
            $table->boolean('ragu')->default(false); // Tandai ragu-ragu
            $table->timestamps();
            
            $table->unique(['sesi_tes_id', 'soal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jawaban_peserta');
        Schema::dropIfExists('sesi_tes');
        Schema::dropIfExists('token');
        Schema::dropIfExists('tes_soal');
        Schema::dropIfExists('tes');
    }
};
