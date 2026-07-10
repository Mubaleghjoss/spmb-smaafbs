<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gelombang_pendaftaran', function (Blueprint $table) {
            if (!Schema::hasColumn('gelombang_pendaftaran', 'waktu_buka')) {
                $table->time('waktu_buka')->nullable()->after('tanggal_buka');
            }

            if (!Schema::hasColumn('gelombang_pendaftaran', 'waktu_tutup')) {
                $table->time('waktu_tutup')->nullable()->after('tanggal_tutup');
            }
        });

        $pengaturan = DB::table('pengaturan')
            ->whereIn('kunci', ['waktu_buka', 'waktu_tutup'])
            ->pluck('nilai', 'kunci');

        $waktuBuka = $this->normalisasiWaktu($pengaturan['waktu_buka'] ?? null);
        $waktuTutup = $this->normalisasiWaktu($pengaturan['waktu_tutup'] ?? null);

        if ($waktuBuka) {
            DB::table('gelombang_pendaftaran')
                ->whereNull('waktu_buka')
                ->update(['waktu_buka' => $waktuBuka]);
        }

        if ($waktuTutup) {
            DB::table('gelombang_pendaftaran')
                ->whereNull('waktu_tutup')
                ->update(['waktu_tutup' => $waktuTutup]);
        }
    }

    public function down(): void
    {
        Schema::table('gelombang_pendaftaran', function (Blueprint $table) {
            if (Schema::hasColumn('gelombang_pendaftaran', 'waktu_buka')) {
                $table->dropColumn('waktu_buka');
            }

            if (Schema::hasColumn('gelombang_pendaftaran', 'waktu_tutup')) {
                $table->dropColumn('waktu_tutup');
            }
        });
    }

    private function normalisasiWaktu(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) ? $value : null;
    }
};
