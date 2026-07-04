<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 20)->unique();
            $table->boolean('aktif')->default(true);
            $table->boolean('default')->default(false);
            $table->timestamps();
        });

        Schema::create('gelombang_pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->string('nama', 100);
            $table->date('tanggal_buka')->nullable();
            $table->date('tanggal_tutup')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['tahun_ajaran_id', 'nama']);
            $table->index(['tahun_ajaran_id', 'aktif']);
        });

        $tahunAjaranId = DB::table('tahun_ajaran')->insertGetId([
            'nama' => '2026-2027',
            'aktif' => true,
            'default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pengaturan = DB::table('pengaturan')
            ->whereIn('kunci', ['tanggal_buka', 'tanggal_tutup'])
            ->pluck('nilai', 'kunci');

        $gelombangId = DB::table('gelombang_pendaftaran')->insertGetId([
            'tahun_ajaran_id' => $tahunAjaranId,
            'nama' => 'Gelombang 1',
            'tanggal_buka' => $pengaturan['tanggal_buka'] ?? null,
            'tanggal_tutup' => $pengaturan['tanggal_tutup'] ?? null,
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('peserta', function (Blueprint $table) {
            $table->foreignId('tahun_ajaran_id')
                ->nullable()
                ->after('nomor_pendaftaran')
                ->constrained('tahun_ajaran')
                ->restrictOnDelete();
            $table->foreignId('gelombang_pendaftaran_id')
                ->nullable()
                ->after('tahun_ajaran_id')
                ->constrained('gelombang_pendaftaran')
                ->restrictOnDelete();
            $table->string('jenis_pendaftaran', 30)->nullable()->after('gelombang_pendaftaran_id');
            $table->unsignedTinyInteger('kelas_tujuan')->nullable()->after('jenis_pendaftaran');

            $table->index(['tahun_ajaran_id', 'gelombang_pendaftaran_id'], 'peserta_periode_index');
            $table->index(['jenis_pendaftaran', 'kelas_tujuan'], 'peserta_jenis_kelas_index');
        });

        DB::table('peserta')->update([
            'tahun_ajaran_id' => $tahunAjaranId,
            'gelombang_pendaftaran_id' => $gelombangId,
            'jenis_pendaftaran' => 'siswa_baru',
            'kelas_tujuan' => 10,
        ]);

        DB::table('pengaturan')->updateOrInsert(
            ['kunci' => 'tahun_ajaran'],
            [
                'nilai' => '2026-2027',
                'grup' => 'branding',
                'tipe' => 'text',
                'keterangan' => 'Tahun ajaran default SPMB',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            $table->dropForeign(['tahun_ajaran_id']);
            $table->dropForeign(['gelombang_pendaftaran_id']);
            $table->dropIndex('peserta_periode_index');
            $table->dropIndex('peserta_jenis_kelas_index');
            $table->dropColumn([
                'tahun_ajaran_id',
                'gelombang_pendaftaran_id',
                'jenis_pendaftaran',
                'kelas_tujuan',
            ]);
        });

        Schema::dropIfExists('gelombang_pendaftaran');
        Schema::dropIfExists('tahun_ajaran');
    }
};
