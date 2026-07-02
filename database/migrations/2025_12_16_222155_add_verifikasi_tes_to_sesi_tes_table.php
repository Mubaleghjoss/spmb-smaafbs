<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_tes', function (Blueprint $table) {
            // Status verifikasi untuk peserta yang tidak lulus
            // null = belum perlu verifikasi (lulus otomatis atau belum selesai)
            // menunggu = menunggu keputusan admin
            // diloloskan = admin meloloskan meski tidak lulus
            // ditolak = admin menolak, peserta tidak bisa lanjut
            $table->string('status_verifikasi_tes')->nullable()->after('status');
            $table->text('catatan_verifikasi')->nullable()->after('status_verifikasi_tes');
            $table->foreignId('diverifikasi_oleh')->nullable()->after('catatan_verifikasi')
                  ->constrained('pengguna')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable()->after('diverifikasi_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_tes', function (Blueprint $table) {
            $table->dropForeign(['diverifikasi_oleh']);
            $table->dropColumn(['status_verifikasi_tes', 'catatan_verifikasi', 'diverifikasi_oleh', 'diverifikasi_pada']);
        });
    }
};
