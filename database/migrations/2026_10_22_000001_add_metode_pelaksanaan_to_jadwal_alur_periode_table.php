<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_alur_periode', function (Blueprint $table) {
            $table->string('metode_pelaksanaan', 20)
                ->default('online')
                ->after('lokasi');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_alur_periode', function (Blueprint $table) {
            $table->dropColumn('metode_pelaksanaan');
        });
    }
};
