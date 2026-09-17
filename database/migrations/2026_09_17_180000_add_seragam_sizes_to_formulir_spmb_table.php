<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            // Nullable only for historical compatibility; application validation
            // requires both fields whenever a participant saves their form.
            $table->string('ukuran_baju', 5)->nullable()->after('panjang_celana');
            $table->string('ukuran_celana', 5)->nullable()->after('ukuran_baju');
        });
    }

    public function down(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->dropColumn(['ukuran_baju', 'ukuran_celana']);
        });
    }
};
