<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wawancara', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            
            // Info Wawancara
            $table->date('tanggal_wawancara')->nullable();
            $table->string('nama_interviewer')->nullable();
            $table->string('kelompok')->nullable();
            
            // Jawaban Interview Orang Tua (JSON)
            $table->json('jawaban_ortu')->nullable();
            
            // Jawaban Interview Siswa (JSON)
            $table->json('jawaban_siswa')->nullable();
            
            // Verifikasi Berkas
            $table->json('verifikasi_berkas')->nullable();
            
            // Catatan dan Kesimpulan
            $table->text('catatan_interviewer')->nullable();
            $table->enum('hasil_wawancara', ['lulus', 'tidak_lulus', 'menunggu'])->default('menunggu');
            
            // Verifikasi
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wawancara');
    }
};
