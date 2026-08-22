<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah dimensi GELOMBANG ke jadwal alur.
     * Sebelumnya jadwal per tahun ajaran; sekarang tiap gelombang punya timeline 7 tahap sendiri.
     * gelombang_pendaftaran_id NULL = jadwal tingkat tahun (fallback untuk semua gelombang).
     */
    public function up(): void
    {
        Schema::table('jadwal_alur_periode', function (Blueprint $table) {
            $table->foreignId('gelombang_pendaftaran_id')
                ->nullable()
                ->after('tahun_ajaran_id')
                ->constrained('gelombang_pendaftaran')
                ->cascadeOnDelete();
        });

        // Ganti unique lama (tahun_ajaran_id, tahap) -> (tahun_ajaran_id, gelombang_pendaftaran_id, tahap).
        // SQLite tidak bisa drop unique index bernama otomatis dgn mudah lewat Schema; pakai raw yang aman.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // index unik lama biasanya bernama jadwal_alur_periode_tahun_ajaran_id_tahap_unique
            try { Schema::getConnection()->statement('DROP INDEX IF EXISTS jadwal_alur_periode_tahun_ajaran_id_tahap_unique'); } catch (\Throwable $e) {}
            try {
                Schema::getConnection()->statement(
                    'CREATE UNIQUE INDEX IF NOT EXISTS jap_tahun_gel_tahap_unique ON jadwal_alur_periode (tahun_ajaran_id, gelombang_pendaftaran_id, tahap)'
                );
            } catch (\Throwable $e) {}
        } else {
            try {
                Schema::table('jadwal_alur_periode', function (Blueprint $table) {
                    $table->dropUnique('jadwal_alur_periode_tahun_ajaran_id_tahap_unique');
                });
            } catch (\Throwable $e) {}
            try {
                Schema::table('jadwal_alur_periode', function (Blueprint $table) {
                    $table->unique(['tahun_ajaran_id', 'gelombang_pendaftaran_id', 'tahap'], 'jap_tahun_gel_tahap_unique');
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            try { Schema::getConnection()->statement('DROP INDEX IF EXISTS jap_tahun_gel_tahap_unique'); } catch (\Throwable $e) {}
        } else {
            try {
                Schema::table('jadwal_alur_periode', function (Blueprint $table) {
                    $table->dropUnique('jap_tahun_gel_tahap_unique');
                });
            } catch (\Throwable $e) {}
        }

        Schema::table('jadwal_alur_periode', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gelombang_pendaftaran_id');
        });
    }
};
