<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_alur_periode', function (Blueprint $table) {
            $table->text('panduan_online')->nullable()->after('metode_pelaksanaan');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_alur_periode', function (Blueprint $table) {
            $table->dropColumn('panduan_online');
        });
    }
};
