<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->string('pendidikan_ayah')->nullable()->after('pekerjaan_ayah');
            $table->string('pendidikan_ibu')->nullable()->after('pekerjaan_ibu');
        });
    }

    public function down(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->dropColumn(['pendidikan_ayah', 'pendidikan_ibu']);
        });
    }
};
