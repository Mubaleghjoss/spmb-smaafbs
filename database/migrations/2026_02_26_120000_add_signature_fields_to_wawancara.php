<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            $table->longText('tanda_tangan_peserta')->nullable()->after('catatan_siswa');
            $table->longText('tanda_tangan_ortu')->nullable()->after('tanda_tangan_peserta');
            $table->datetime('diisi_peserta_pada')->nullable()->after('tanda_tangan_ortu');
        });
    }

    public function down(): void
    {
        Schema::table('wawancara', function (Blueprint $table) {
            $table->dropColumn([
                'tanda_tangan_peserta',
                'tanda_tangan_ortu',
                'diisi_peserta_pada',
            ]);
        });
    }
};
