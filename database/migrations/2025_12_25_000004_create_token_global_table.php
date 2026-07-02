<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('token_global', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama')->nullable(); // Nama/label token (misal: "Token Gelombang 1")
            $table->text('keterangan')->nullable();
            $table->datetime('mulai')->nullable(); // Waktu mulai berlaku
            $table->datetime('selesai')->nullable(); // Waktu berakhir
            $table->boolean('aktif')->default(true);
            $table->integer('jumlah_penggunaan')->default(0); // Counter berapa kali dipakai
            $table->timestamps();
        });
        
        // Tabel pivot untuk menghubungkan token global dengan tes
        Schema::create('token_global_tes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_global_id')->constrained('token_global')->onDelete('cascade');
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['token_global_id', 'tes_id']);
        });
        
        // Log penggunaan token global
        Schema::create('token_global_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_global_id')->constrained('token_global')->onDelete('cascade');
            $table->foreignId('peserta_id')->constrained('peserta')->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_global_log');
        Schema::dropIfExists('token_global_tes');
        Schema::dropIfExists('token_global');
    }
};
