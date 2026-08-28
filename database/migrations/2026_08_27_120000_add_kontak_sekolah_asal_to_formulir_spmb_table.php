<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->string('nama_kontak_sekolah')->nullable()->after('alamat_sekolah');
            $table->string('telepon_kontak_sekolah', 20)->nullable()->after('nama_kontak_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->dropColumn(['nama_kontak_sekolah', 'telepon_kontak_sekolah']);
        });
    }
};
