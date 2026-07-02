<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel topik, soal, dan jawaban
     * Kebutuhan: 2.1, 2.5
     */
    public function up(): void
    {
        // Tabel topik (kategori soal)
        Schema::create('topik', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('topik')->nullOnDelete();
            $table->timestamps();
        });

        // Tabel soal
        Schema::create('soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topik_id')->nullable()->constrained('topik')->nullOnDelete();
            $table->text('pertanyaan');
            $table->enum('tipe', ['pilihan_ganda', 'jawaban_ganda', 'esai', 'benar_salah'])->default('pilihan_ganda');
            $table->integer('bobot')->default(1);
            $table->string('media')->nullable(); // Path ke file gambar/audio
            $table->enum('tipe_media', ['gambar', 'audio', 'video'])->nullable();
            $table->text('pembahasan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['topik_id', 'aktif']);
        });

        // Tabel jawaban
        Schema::create('jawaban', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('soal')->onDelete('cascade');
            $table->text('isi_jawaban');
            $table->boolean('benar')->default(false);
            $table->integer('urutan')->default(0);
            $table->timestamps();
            
            $table->index(['soal_id', 'benar']);
        });

        // Tabel riwayat versi soal
        Schema::create('riwayat_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('soal')->onDelete('cascade');
            $table->text('pertanyaan_lama');
            $table->text('pertanyaan_baru');
            $table->foreignId('diubah_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_soal');
        Schema::dropIfExists('jawaban');
        Schema::dropIfExists('soal');
        Schema::dropIfExists('topik');
    }
};
