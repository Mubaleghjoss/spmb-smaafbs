<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['tarik', 'push']);
            $table->enum('status', ['berhasil', 'gagal', 'konflik']);
            $table->string('server_url');
            $table->text('ringkasan');
            $table->json('perubahan')->nullable();
            $table->json('konflik')->nullable();
            $table->boolean('konflik_resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
