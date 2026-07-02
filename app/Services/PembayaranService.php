<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Enums\StatusPembayaran;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PembayaranService
{
    private SpmbService $spmbService;

    public function __construct(SpmbService $spmbService)
    {
        $this->spmbService = $spmbService;
    }

    public function uploadBukti(Peserta $peserta, string $jenis, UploadedFile $file, ?float $nominal = null): Pembayaran
    {
        $path = $file->store("pembayaran/{$jenis}", 'public');
        return Pembayaran::create([
            'peserta_id' => $peserta->id,
            'jenis' => $jenis,
            'bukti_file' => $path,
            'nominal' => $nominal,
            'status' => StatusPembayaran::MENUNGGU->value,
        ]);
    }

    public function verifikasi(Pembayaran $pembayaran, Pengguna $admin): void
    {
        DB::transaction(function () use ($pembayaran, $admin) {
            // Generate nomor kwitansi
            $kwitansiService = app(KwitansiService::class);
            $nomorKwitansi = $kwitansiService->generateNomorKwitansi();
            
            $pembayaran->update([
                'status' => StatusPembayaran::TERVERIFIKASI->value,
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
                'nomor_kwitansi' => $nomorKwitansi,
            ]);
            // Tahap 3 = Bayar Formulir, Tahap 6 = Bayar Pertama (Pelunasan)
            $tahap = $pembayaran->jenis === 'formulir' ? 3 : 6;
            $this->spmbService->selesaikanTahapan($pembayaran->peserta, $tahap, $admin->id);
        });
    }

    public function tolak(Pembayaran $pembayaran, string $alasan, Pengguna $admin): void
    {
        $pembayaran->update([
            'status' => StatusPembayaran::DITOLAK->value,
            'catatan' => $alasan,
            'diverifikasi_oleh' => $admin->id,
            'diverifikasi_pada' => now(),
        ]);
    }

    public function ambilDenganFilter(array $filter, int $perHalaman = 15): LengthAwarePaginator
    {
        $query = Pembayaran::with(['peserta', 'verifikator']);
        if (!empty($filter['jenis'])) {
            $query->where('jenis', $filter['jenis']);
        }
        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }
        return $query->latest()->paginate($perHalaman);
    }

    public function ambilPembayaranPeserta(Peserta $peserta, string $jenis): ?Pembayaran
    {
        return Pembayaran::where('peserta_id', $peserta->id)
            ->where('jenis', $jenis)->latest()->first();
    }

    public function sudahUpload(Peserta $peserta, string $jenis): bool
    {
        return Pembayaran::where('peserta_id', $peserta->id)
            ->where('jenis', $jenis)->exists();
    }

    public function sudahDiverifikasi(Peserta $peserta, string $jenis): bool
    {
        return Pembayaran::where('peserta_id', $peserta->id)
            ->where('jenis', $jenis)
            ->where('status', StatusPembayaran::TERVERIFIKASI->value)->exists();
    }
}
