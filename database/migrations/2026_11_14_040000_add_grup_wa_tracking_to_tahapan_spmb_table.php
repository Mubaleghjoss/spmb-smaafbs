<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tahapan_spmb', 'grup_wa_status')) {
            return;
        }

        Schema::table('tahapan_spmb', function (Blueprint $table): void {
            $table->string('grup_wa_status', 20)->default('belum')->after('tahap_7_selesai');
            $table->timestamp('grup_wa_ditandai_pada')->nullable()->after('grup_wa_status');
            $table->foreignId('grup_wa_ditandai_oleh')->nullable()->after('grup_wa_ditandai_pada')->constrained('pengguna')->nullOnDelete();
            $table->text('grup_wa_catatan')->nullable()->after('grup_wa_ditandai_oleh');
            $table->index(['grup_wa_status', 'grup_wa_ditandai_pada']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tahapan_spmb', 'grup_wa_status')) {
            return;
        }

        Schema::table('tahapan_spmb', function (Blueprint $table): void {
            $table->dropForeign(['grup_wa_ditandai_oleh']);
            $table->dropIndex(['grup_wa_status', 'grup_wa_ditandai_pada']);
            $table->dropColumn([
                'grup_wa_status',
                'grup_wa_ditandai_pada',
                'grup_wa_ditandai_oleh',
                'grup_wa_catatan',
            ]);
        });
    }
};
