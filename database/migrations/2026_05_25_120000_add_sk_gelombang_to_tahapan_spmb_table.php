<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tahapan_spmb', 'sk_gelombang_kelulusan')) {
            Schema::table('tahapan_spmb', function (Blueprint $table) {
                $table->string('sk_gelombang_kelulusan')->nullable()->after('status_kelulusan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tahapan_spmb', 'sk_gelombang_kelulusan')) {
            Schema::table('tahapan_spmb', function (Blueprint $table) {
                $table->dropColumn('sk_gelombang_kelulusan');
            });
        }
    }
};
