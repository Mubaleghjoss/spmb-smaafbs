<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal alur SPMB PER PERIODE (tahun ajaran).
     * Satu baris = satu tahap (1..7) untuk satu tahun ajaran.
     * Menggantikan penyimpanan jadwal tahap yang sebelumnya global (setting tahap_X_*).
     * Data lama tetap dipakai sebagai fallback bila baris per-periode belum ada.
     */
    public function up(): void
    {
        Schema::create('jadwal_alur_periode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedTinyInteger('tahap'); // 1..7
            $table->boolean('dibuka')->default(true);
            $table->date('tanggal_buka')->nullable();
            $table->time('waktu_mulai')->nullable();
            $table->date('tanggal_tutup')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->text('keterangan')->nullable(); // note yang dilihat pendaftar di dashboard
            $table->timestamps();

            $table->unique(['tahun_ajaran_id', 'tahap']);
            $table->index('tahun_ajaran_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_alur_periode');
    }
};
