<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            // Info Wawancara Orang Tua (terpisah)
            $table->date('tanggal_wawancara_ortu')->nullable()->after('kelompok');
            $table->string('interviewer_ortu')->nullable()->after('tanggal_wawancara_ortu');
            $table->text('catatan_ortu')->nullable()->after('jawaban_ortu');
            
            // Info Wawancara Siswa (terpisah)
            $table->date('tanggal_wawancara_siswa')->nullable()->after('catatan_ortu');
            $table->string('interviewer_siswa')->nullable()->after('tanggal_wawancara_siswa');
            $table->text('catatan_siswa')->nullable()->after('jawaban_siswa');
        });
    }

    public function down(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_wawancara_ortu',
                'interviewer_ortu',
                'catatan_ortu',
                'tanggal_wawancara_siswa',
                'interviewer_siswa',
                'catatan_siswa',
            ]);
        });
    }
};
