<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel pivot untuk relasi many-to-many antara grup dan tes
     * Kebutuhan: 1.3 - Group-Test Assignment
     */
    public function up(): void
    {
        Schema::create('grup_tes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grup_id')->constrained('grup')->onDelete('cascade');
            $table->foreignId('tes_id')->constrained('tes')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['grup_id', 'tes_id']);
            $table->index(['tes_id', 'grup_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grup_tes');
    }
};
