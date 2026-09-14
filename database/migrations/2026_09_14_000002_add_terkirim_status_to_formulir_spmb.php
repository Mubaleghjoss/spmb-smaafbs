<?php

use App\Services\KuotaPendaftaranService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Production memakai MySQL enum. SQLite/testing menyimpan enum sebagai
        // teks, sehingga tidak membutuhkan ALTER COLUMN khusus.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE formulir_spmb MODIFY status_verifikasi ENUM('draft', 'menunggu', 'terkirim', 'terverifikasi', 'ditolak') NOT NULL DEFAULT 'draft'");
        }

        // Formulir yang sebelumnya sudah dikirim dan menunggu admin otomatis
        // diperlakukan sebagai terkirim: Tahap 2 selesai, Tahap 3 dibuka.
        $pesertaIds = DB::table('formulir_spmb')
            ->where('status_verifikasi', 'menunggu')
            ->pluck('peserta_id');

        if ($pesertaIds->isNotEmpty()) {
            DB::table('formulir_spmb')
                ->whereIn('peserta_id', $pesertaIds)
                ->update([
                    'status_verifikasi' => 'terkirim',
                    'updated_at' => now(),
                ]);

            DB::table('tahapan_spmb')
                ->whereIn('peserta_id', $pesertaIds)
                ->update([
                    'tahap_2_selesai' => true,
                    'tahap_saat_ini' => DB::raw('CASE WHEN tahap_saat_ini < 3 THEN 3 ELSE tahap_saat_ini END'),
                    'updated_at' => now(),
                ]);
        }

        // Bukti formulir lama yang masih aktif juga memperoleh urutan berdasarkan
        // waktu uploadnya saat aturan reservasi baru mulai diberlakukan.
        $tahunAjaranIds = DB::table('peserta')
            ->join('pembayaran', 'pembayaran.peserta_id', '=', 'peserta.id')
            ->where('pembayaran.jenis', 'formulir')
            ->whereIn('pembayaran.status', ['menunggu', 'terverifikasi'])
            ->whereNotNull('peserta.tahun_ajaran_id')
            ->distinct()
            ->pluck('peserta.tahun_ajaran_id');

        $kuotaService = app(KuotaPendaftaranService::class);
        foreach ($tahunAjaranIds as $tahunAjaranId) {
            $kuotaService->rekalkulasiTahun((int) $tahunAjaranId);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            // Kembalikan data baru ke nilai enum lama yang paling setara.
            DB::table('formulir_spmb')
                ->where('status_verifikasi', 'terkirim')
                ->update(['status_verifikasi' => 'terverifikasi', 'updated_at' => now()]);

            DB::statement("ALTER TABLE formulir_spmb MODIFY status_verifikasi ENUM('draft', 'menunggu', 'terverifikasi', 'ditolak') NOT NULL DEFAULT 'draft'");
        }
    }
};
