<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel pembayaran, wawancara, dan log tahapan
     * Kebutuhan: 13.1, 14.1, 16.7
     */
    public function up(): void
    {
        // Tabel pembayaran
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            $table->enum('jenis', ['formulir', 'pertama', 'pelunasan']);
            $table->string('bukti_file');
            $table->decimal('nominal', 12, 2)->nullable();
            $table->enum('status', ['menunggu', 'terverifikasi', 'ditolak'])->default('menunggu');
            $table->text('catatan')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();
        });

        // Tabel jadwal wawancara
        Schema::create('jadwal_wawancara', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->datetime('tanggal_waktu');
            $table->string('lokasi');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // Tabel peserta wawancara
        Schema::create('peserta_wawancara', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal_wawancara')->onDelete('cascade');
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            
            // Status wawancara
            $table->enum('status_wawancara', ['terjadwal', 'hadir', 'tidak_hadir', 'lulus', 'tidak_lulus'])->default('terjadwal');
            $table->integer('nilai_wawancara')->nullable();
            $table->text('catatan_wawancara')->nullable();
            
            // Status verifikasi berkas
            $table->enum('status_berkas', ['belum', 'lengkap', 'tidak_lengkap'])->default('belum');
            $table->json('checklist_berkas')->nullable();
            $table->text('catatan_berkas')->nullable();
            
            $table->timestamps();
            
            $table->unique(['jadwal_id', 'peserta_id']);
        });

        // Tabel log tahapan SPMB
        Schema::create('log_tahapan_spmb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            $table->tinyInteger('tahap');
            $table->enum('aksi', ['verifikasi', 'penolakan', 'manual_update', 'bulk_update']);
            $table->boolean('status_lama');
            $table->boolean('status_baru');
            $table->text('pesan')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['peserta_id', 'tahap']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_tahapan_spmb');
        Schema::dropIfExists('peserta_wawancara');
        Schema::dropIfExists('jadwal_wawancara');
        Schema::dropIfExists('pembayaran');
    }
};
