<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('gelombang_pendaftaran', 'tampilkan_siswa_baru')) {
            Schema::table('gelombang_pendaftaran', function (Blueprint $table) {
                $table->boolean('tampilkan_siswa_baru')->default(true)->after('aktif');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gelombang_pendaftaran', 'tampilkan_siswa_baru')) {
            Schema::table('gelombang_pendaftaran', function (Blueprint $table) {
                $table->dropColumn('tampilkan_siswa_baru');
            });
        }
    }
};
