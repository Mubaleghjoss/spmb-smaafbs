<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('peserta', 'kelas_penempatan')) {
            Schema::table('peserta', function (Blueprint $table) {
                $table->string('kelas_penempatan', 20)->nullable()->after('kelas_tujuan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('peserta', 'kelas_penempatan')) {
            Schema::table('peserta', function (Blueprint $table) {
                $table->dropColumn('kelas_penempatan');
            });
        }
    }
};
