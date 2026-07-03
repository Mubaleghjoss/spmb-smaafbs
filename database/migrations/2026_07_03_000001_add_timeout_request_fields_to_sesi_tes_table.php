<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_tes', function (Blueprint $table) {
            $table->string('permohonan_ulang_status')->nullable()->after('catatan_verifikasi');
            $table->string('permohonan_ulang_tipe')->nullable()->after('permohonan_ulang_status');
            $table->text('permohonan_ulang_alasan')->nullable()->after('permohonan_ulang_tipe');
            $table->timestamp('permohonan_ulang_pada')->nullable()->after('permohonan_ulang_alasan');
            $table->unsignedSmallInteger('permohonan_ulang_menit')->nullable()->after('permohonan_ulang_pada');
            $table->text('permohonan_ulang_catatan_admin')->nullable()->after('permohonan_ulang_menit');
            $table->foreignId('permohonan_ulang_diproses_oleh')->nullable()
                ->after('permohonan_ulang_catatan_admin')
                ->constrained('pengguna')
                ->nullOnDelete();
            $table->timestamp('permohonan_ulang_diproses_pada')->nullable()->after('permohonan_ulang_diproses_oleh');

            $table->index(['permohonan_ulang_status', 'status'], 'sesi_tes_permohonan_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_tes', function (Blueprint $table) {
            $table->dropIndex('sesi_tes_permohonan_status_idx');
            $table->dropForeign(['permohonan_ulang_diproses_oleh']);
            $table->dropColumn([
                'permohonan_ulang_status',
                'permohonan_ulang_tipe',
                'permohonan_ulang_alasan',
                'permohonan_ulang_pada',
                'permohonan_ulang_menit',
                'permohonan_ulang_catatan_admin',
                'permohonan_ulang_diproses_oleh',
                'permohonan_ulang_diproses_pada',
            ]);
        });
    }
};
