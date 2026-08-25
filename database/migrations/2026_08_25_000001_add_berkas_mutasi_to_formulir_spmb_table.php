<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berkas mutasi untuk jalur SISWA PINDAHAN (boleh menyusul):
 *  42. Surat mutasi dari sekolah
 *  43. Surat mutasi dari Dapodik
 *
 * Aman: ADD COLUMN nullable, tidak menyentuh data existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            if (!Schema::hasColumn('formulir_spmb', 'file_mutasi_sekolah')) {
                $table->string('file_mutasi_sekolah')->nullable()->after('file_ktp_ayah');
            }
            if (!Schema::hasColumn('formulir_spmb', 'file_mutasi_dapodik')) {
                $table->string('file_mutasi_dapodik')->nullable()->after('file_mutasi_sekolah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            foreach (['file_mutasi_sekolah', 'file_mutasi_dapodik'] as $kolom) {
                if (Schema::hasColumn('formulir_spmb', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
