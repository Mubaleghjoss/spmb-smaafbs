<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel untuk konfigurasi psikotes kepribadian
     */
    public function up(): void
    {
        // Update enum tipe soal untuk menambahkan psikotes_kepribadian
        Schema::table('soal', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });
        
        Schema::table('soal', function (Blueprint $table) {
            $table->enum('tipe', [
                'pilihan_ganda', 
                'jawaban_ganda', 
                'esai', 
                'benar_salah',
                'psikotes_kepribadian'
            ])->default('pilihan_ganda')->after('pertanyaan');
        });

        // Tabel konfigurasi psikotes kepribadian per tes
        Schema::create('psikotes_kepribadian_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->string('tipe_kepribadian'); // koleris, sanguin, plegmatis, melankolis
            $table->json('nomor_soal'); // [1, 5, 9, 13, 17, 21, 25, 29]
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            
            $table->unique(['tes_id', 'tipe_kepribadian']);
        });

        // Tabel nilai jawaban psikotes (A=1, B=2, C=3, D=4)
        Schema::create('psikotes_nilai_jawaban', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->string('kode_jawaban'); // A, B, C, D
            $table->integer('nilai'); // 1, 2, 3, 4
            $table->timestamps();
            
            $table->unique(['tes_id', 'kode_jawaban']);
        });

        // Tabel hasil psikotes kepribadian
        Schema::create('hasil_psikotes_kepribadian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_tes_id')->constrained('sesi_tes')->onDelete('cascade');
            $table->string('hasil_kepribadian'); // koleris, sanguin, plegmatis, melankolis
            $table->json('detail_nilai'); // {"koleris": 24, "sanguin": 18, ...}
            $table->timestamps();
            
            $table->unique('sesi_tes_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_psikotes_kepribadian');
        Schema::dropIfExists('psikotes_nilai_jawaban');
        Schema::dropIfExists('psikotes_kepribadian_config');
        
        // Revert enum
        Schema::table('soal', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });
        
        Schema::table('soal', function (Blueprint $table) {
            $table->enum('tipe', [
                'pilihan_ganda', 
                'jawaban_ganda', 
                'esai', 
                'benar_salah'
            ])->default('pilihan_ganda')->after('pertanyaan');
        });
    }
};
