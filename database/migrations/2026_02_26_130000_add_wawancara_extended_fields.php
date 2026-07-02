<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            $table->json('surat_pernyataan_siswa')->nullable()->after('diisi_peserta_pada');
            $table->json('surat_pernyataan_ortu')->nullable()->after('surat_pernyataan_siswa');
            $table->string('file_tes_pegon')->nullable()->after('surat_pernyataan_ortu');
            $table->string('file_voice_quran')->nullable()->after('file_tes_pegon');
            $table->string('surat_quran_random')->nullable()->after('file_voice_quran');
        });
    }

    public function down(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            $table->dropColumn([
                'surat_pernyataan_siswa',
                'surat_pernyataan_ortu',
                'file_tes_pegon',
                'file_voice_quran',
                'surat_quran_random',
            ]);
        });
    }
};
