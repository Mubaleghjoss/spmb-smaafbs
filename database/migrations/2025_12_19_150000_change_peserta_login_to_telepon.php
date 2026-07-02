<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengubah login peserta dari email ke telepon
     */
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            // Hapus unique constraint dari email
            $table->dropUnique(['email']);
            
            // Ubah email menjadi nullable
            $table->string('email')->nullable()->change();
        });
        
        // Update telepon yang null menjadi string kosong dulu
        \DB::table('peserta')->whereNull('telepon')->update(['telepon' => '']);
        
        Schema::table('peserta', function (Blueprint $table) {
            // Ubah telepon menjadi not nullable dan unique
            $table->string('telepon')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            // Kembalikan telepon ke nullable
            $table->dropUnique(['telepon']);
            $table->string('telepon')->nullable()->change();
        });
        
        Schema::table('peserta', function (Blueprint $table) {
            // Kembalikan email ke unique
            $table->string('email')->nullable(false)->unique()->change();
        });
    }
};
