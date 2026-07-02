<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel peserta, grup, dan SPMB
     * Kebutuhan: 3.1, 3.3, 3.7, 0.2, 0.3
     */
    public function up(): void
    {
        // Tabel grup peserta
        Schema::create('grup', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // Tabel peserta
        Schema::create('peserta', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pendaftaran')->unique();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('telepon')->nullable();
            $table->text('alamat')->nullable();
            $table->string('asal_sekolah')->nullable();
            $table->string('foto')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabel pivot grup_peserta
        Schema::create('grup_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grup_id')->constrained('grup')->onDelete('cascade');
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['grup_id', 'peserta_id']);
        });

        // Tabel tahapan SPMB
        Schema::create('tahapan_spmb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            $table->tinyInteger('tahap_saat_ini')->default(1);
            $table->boolean('tahap_1_selesai')->default(true); // Buat akun
            $table->boolean('tahap_2_selesai')->default(false); // Bayar formulir
            $table->boolean('tahap_3_selesai')->default(false); // Isi formulir
            $table->boolean('tahap_4_selesai')->default(false); // Tes online
            $table->boolean('tahap_5_selesai')->default(false); // Wawancara
            $table->boolean('tahap_6_selesai')->default(false); // Bayar pertama
            $table->boolean('tahap_7_selesai')->default(false); // Resmi diterima
            $table->timestamps();
        });

        // Tabel formulir SPMB
        Schema::create('formulir_spmb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            
            // Data diri
            $table->string('nama_lengkap');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('agama')->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            
            // Data orang tua - Ayah
            $table->string('nama_ayah')->nullable();
            $table->string('pekerjaan_ayah')->nullable();
            $table->string('telepon_ayah')->nullable();
            
            // Data orang tua - Ibu
            $table->string('nama_ibu')->nullable();
            $table->string('pekerjaan_ibu')->nullable();
            $table->string('telepon_ibu')->nullable();
            
            // Data sekolah asal
            $table->string('asal_sekolah')->nullable();
            $table->string('alamat_sekolah')->nullable();
            $table->string('nisn')->nullable();
            
            // Status verifikasi
            $table->enum('status_verifikasi', ['draft', 'menunggu', 'terverifikasi', 'ditolak'])->default('draft');
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formulir_spmb');
        Schema::dropIfExists('tahapan_spmb');
        Schema::dropIfExists('grup_peserta');
        Schema::dropIfExists('peserta');
        Schema::dropIfExists('grup');
    }
};
