<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_ajaran', function (Blueprint $table) {
            if (! Schema::hasColumn('tahun_ajaran', 'kuota_laki_laki')) {
                $table->unsignedInteger('kuota_laki_laki')->nullable()->after('kuota_peserta');
            }

            if (! Schema::hasColumn('tahun_ajaran', 'kuota_perempuan')) {
                $table->unsignedInteger('kuota_perempuan')->nullable()->after('kuota_laki_laki');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tahun_ajaran', function (Blueprint $table) {
            if (Schema::hasColumn('tahun_ajaran', 'kuota_perempuan')) {
                $table->dropColumn('kuota_perempuan');
            }

            if (Schema::hasColumn('tahun_ajaran', 'kuota_laki_laki')) {
                $table->dropColumn('kuota_laki_laki');
            }
        });
    }
};
