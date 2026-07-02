<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->decimal('lingkar_dada', 5, 1)->nullable()->after('lingkar_kepala');
            $table->decimal('lingkar_pinggang', 5, 1)->nullable()->after('lingkar_dada');
            $table->decimal('panjang_celana', 5, 1)->nullable()->after('lingkar_pinggang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->dropColumn(['lingkar_dada', 'lingkar_pinggang', 'panjang_celana']);
        });
    }
};
