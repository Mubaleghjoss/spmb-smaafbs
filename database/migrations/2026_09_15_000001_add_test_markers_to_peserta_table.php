<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta', function (Blueprint $table): void {
            $table->string('test_run_id', 100)->nullable()->index()->after('nomor_pendaftaran');
            $table->boolean('is_test')->default(false)->index()->after('test_run_id');
        });
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table): void {
            $table->dropIndex(['peserta_test_run_id_index']);
            $table->dropIndex(['peserta_is_test_index']);
            $table->dropColumn(['test_run_id', 'is_test']);
        });
    }
};
