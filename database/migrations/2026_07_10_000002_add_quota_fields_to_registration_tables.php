<?php

use App\Models\Peserta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tahun_ajaran', 'kuota_peserta')) {
            Schema::table('tahun_ajaran', function (Blueprint $table) {
                $table->unsignedInteger('kuota_peserta')->nullable()->after('default');
            });
        }

        Schema::table('peserta', function (Blueprint $table) {
            if (! Schema::hasColumn('peserta', 'status_kuota')) {
                $table->string('status_kuota', 30)
                    ->default(Peserta::STATUS_KUOTA_DALAM)
                    ->after('kelas_penempatan');
            }

            if (! Schema::hasColumn('peserta', 'urutan_kuota')) {
                $table->unsignedInteger('urutan_kuota')->nullable()->after('status_kuota');
            }
        });

        Schema::table('peserta', function (Blueprint $table) {
            $table->index(['tahun_ajaran_id', 'status_kuota'], 'peserta_tahun_status_kuota_index');
            $table->index(['tahun_ajaran_id', 'urutan_kuota'], 'peserta_tahun_urutan_kuota_index');
        });

        $urutanPerTahun = [];

        $pesertaLama = DB::table('peserta')
            ->select(['id', 'tahun_ajaran_id'])
            ->orderBy('tahun_ajaran_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($pesertaLama as $row) {
            $tahunId = (int) ($row->tahun_ajaran_id ?? 0);
            $urutanPerTahun[$tahunId] = ($urutanPerTahun[$tahunId] ?? 0) + 1;

            DB::table('peserta')
                ->where('id', $row->id)
                ->update([
                    'status_kuota' => Peserta::STATUS_KUOTA_DALAM,
                    'urutan_kuota' => $urutanPerTahun[$tahunId],
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('peserta', function (Blueprint $table) {
            if (Schema::hasColumn('peserta', 'status_kuota')) {
                $table->dropIndex('peserta_tahun_status_kuota_index');
            }

            if (Schema::hasColumn('peserta', 'urutan_kuota')) {
                $table->dropIndex('peserta_tahun_urutan_kuota_index');
            }
        });

        Schema::table('peserta', function (Blueprint $table) {
            if (Schema::hasColumn('peserta', 'status_kuota')) {
                $table->dropColumn('status_kuota');
            }

            if (Schema::hasColumn('peserta', 'urutan_kuota')) {
                $table->dropColumn('urutan_kuota');
            }
        });

        if (Schema::hasColumn('tahun_ajaran', 'kuota_peserta')) {
            Schema::table('tahun_ajaran', function (Blueprint $table) {
                $table->dropColumn('kuota_peserta');
            });
        }
    }
};
