<?php

namespace App\Services;

use App\Enums\StatusPembayaran;
use App\Exceptions\DuplicatePembayaranException;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Peserta;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PembayaranService
{
    private SpmbService $spmbService;

    private KompresGambarService $kompresGambar;

    private ?KuotaPendaftaranService $kuotaService;

    public function __construct(
        SpmbService $spmbService,
        ?KompresGambarService $kompresGambar = null,
        ?KuotaPendaftaranService $kuotaService = null,
    ) {
        $this->spmbService = $spmbService;
        $this->kompresGambar = $kompresGambar ?? app(KompresGambarService::class);
        $this->kuotaService = $kuotaService;
    }

    public function uploadBukti(Peserta $peserta, string $jenis, UploadedFile $file, ?float $nominal = null): Pembayaran
    {
        $pembayaran = DB::transaction(function () use ($peserta, $jenis, $file, $nominal): Pembayaran {
            $pesertaTerkunci = Peserta::query()
                ->whereKey($peserta->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = Pembayaran::where('peserta_id', $pesertaTerkunci->id)
                ->where('jenis', $jenis)
                ->whereIn('status', [
                    StatusPembayaran::MENUNGGU->value,
                    ...($jenis === 'formulir' ? [StatusPembayaran::TERVERIFIKASI->value] : []),
                ])
                ->latest('id')
                ->first();

            if ($existing) {
                app(LogAktivitasService::class)->catat(
                    'pembayaran.upload_ditolak_duplikat',
                    LogAktivitas::KAT_PEMBAYARAN,
                    "Menolak upload bukti pembayaran {$jenis} karena masih ada pembayaran aktif.",
                    $pesertaTerkunci,
                    [
                        'jenis' => $jenis,
                        'pembayaran_aktif_id' => $existing->id,
                        'pembayaran_aktif_status' => $existing->status,
                    ],
                    $pesertaTerkunci->tahun_ajaran_id,
                );

                throw new DuplicatePembayaranException(
                    $jenis === 'formulir'
                        ? 'Bukti pembayaran formulir sudah pernah diupload dan tidak dapat diupload ulang.'
                        : 'Masih ada bukti pelunasan yang menunggu verifikasi.'
                );
            }

            $path = $this->kompresGambar->simpan($file, "pembayaran/{$jenis}");
            $pembayaranBaru = Pembayaran::create([
                'peserta_id' => $pesertaTerkunci->id,
                'jenis' => $jenis,
                'bukti_file' => $path,
                'nominal' => $nominal,
                'status' => StatusPembayaran::MENUNGGU->value,
            ]);

            app(LogAktivitasService::class)->catat(
                'pembayaran.upload_bukti',
                LogAktivitas::KAT_PEMBAYARAN,
                "Upload bukti pembayaran {$jenis} berhasil dibuat.",
                $pesertaTerkunci,
                ['jenis' => $jenis, 'pembayaran_id' => $pembayaranBaru->id, 'status' => $pembayaranBaru->status],
                $pesertaTerkunci->tahun_ajaran_id,
            );

            return $pembayaranBaru;
        });

        // Bukti formulir aktif langsung mengamankan posisi berdasarkan waktu
        // upload. Akses tes tetap menunggu verifikasi panitia (Tahap 3).
        if ($jenis === 'formulir' && $peserta->tahun_ajaran_id) {
            ($this->kuotaService ?? app(KuotaPendaftaranService::class))
                ->rekalkulasiTahun($peserta->tahun_ajaran_id);
        }

        return $pembayaran;
    }

    public function verifikasi(Pembayaran $pembayaran, Pengguna $admin): void
    {
        DB::transaction(function () use ($pembayaran, $admin) {
            $nomorKwitansi = $pembayaran->jenis === 'formulir'
                ? app(KwitansiService::class)->generateNomorKwitansi()
                : null;

            $pembayaran->update([
                'status' => StatusPembayaran::TERVERIFIKASI->value,
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
                'nomor_kwitansi' => $nomorKwitansi,
            ]);
            if ($pembayaran->jenis === 'formulir') {
                $this->spmbService->selesaikanTahapan($pembayaran->peserta, 3, $admin->id);
                return;
            }

            $ringkasan = app(TahapEnamPembayaranService::class)->ringkasan($pembayaran->peserta);
            if ($ringkasan['lunas']) {
                $this->spmbService->selesaikanTahapan($pembayaran->peserta, 6, $admin->id);
            }
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
        if (! empty($filter['jenis'])) {
            $query->where('jenis', $filter['jenis']);
        }
        if (! empty($filter['status'])) {
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
