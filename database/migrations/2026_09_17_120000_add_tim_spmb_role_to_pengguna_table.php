<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyamakan schema produksi dengan peran yang sudah didukung aplikasi.
     *
     * MySQL/MariaDB memakai ENUM sehingga perlu ALTER TABLE eksplisit. SQLite
     * menyimpan enum sebagai tipe teks, sehingga tidak ada perubahan schema.
     */
    public function up(): void
    {
        if (! Schema::hasTable('pengguna')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE `pengguna` MODIFY `peran` ENUM('admin', 'operator', 'tim_spmb') NOT NULL DEFAULT 'operator'"
            );
        }
    }

    /**
     * Rollback hanya aman bila belum ada akun tim_spmb.
     */
    public function down(): void
    {
        if (! Schema::hasTable('pengguna') || Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('pengguna')->where('peran', 'tim_spmb')->exists()) {
            throw new RuntimeException('Rollback dibatalkan: masih ada akun dengan peran tim_spmb.');
        }

        DB::statement(
            "ALTER TABLE `pengguna` MODIFY `peran` ENUM('admin', 'operator') NOT NULL DEFAULT 'operator'"
        );
    }
};
