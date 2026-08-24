<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel log aktivitas Tim SPMB.
 *
 * Mencatat setiap aksi yang MENGUBAH data (verifikasi, loloskan, hapus,
 * tambah waktu ujian, dsb) beserta pelakunya. Nama pengguna & label subjek
 * disimpan sebagai teks agar log tetap terbaca meski akun atau peserta
 * yang bersangkutan sudah dihapus.
 *
 * Aman: CREATE TABLE saja, tidak menyentuh data existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('log_aktivitas')) {
            return;
        }

        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();

            // Pelaku — nama & peran disalin agar riwayat tetap utuh bila akun dihapus
            $table->foreignId('pengguna_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->string('nama_pengguna');
            $table->string('peran', 50)->nullable();

            $table->string('aksi', 60);      // mis. kelulusan.loloskan
            $table->string('kategori', 40);  // peserta|formulir|pembayaran|ujian|wawancara|kelulusan|pengaturan|akun

            // Subjek yang dikenai aksi (polimorfik, label disalin sebagai teks)
            $table->string('subjek_tipe')->nullable();
            $table->unsignedBigInteger('subjek_id')->nullable();
            $table->string('subjek_label')->nullable();

            $table->text('keterangan');
            $table->json('data')->nullable();
            $table->string('ip', 45)->nullable();

            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajaran')->nullOnDelete();

            $table->timestamps();

            $table->index(['kategori', 'created_at']);
            $table->index(['pengguna_id', 'created_at']);
            $table->index(['subjek_tipe', 'subjek_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
