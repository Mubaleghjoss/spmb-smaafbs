<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Models\LogTahapanSpmb;
use App\Models\Pengguna;
use App\Enums\TahapanSpmb as TahapanSpmbEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MonitoringSpmbService
{
    public function __construct(private SpmbService $spmbService) {}

    /**
     * Ambil statistik dashboard monitoring
     */
    public function ambilStatistikDashboard(): array
    {
        $totalPeserta = Peserta::count();
        $pesertaPerTahap = $this->hitungPesertaPerTahap();
        
        return [
            'total_peserta' => $totalPeserta,
            'peserta_per_tahap' => $pesertaPerTahap,
            'tahap_labels' => $this->ambilLabelTahap(),
            'persentase_per_tahap' => $this->hitungPersentasePerTahap($pesertaPerTahap, $totalPeserta),
            'peserta_baru_hari_ini' => Peserta::whereDate('created_at', today())->count(),
            'peserta_baru_minggu_ini' => Peserta::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }

    /**
     * Hitung peserta per tahap
     */
    public function hitungPesertaPerTahap(): array
    {
        $hasil = [];
        for ($i = 1; $i <= 7; $i++) {
            $hasil[$i] = TahapanSpmb::where('tahap_saat_ini', $i)->count();
        }
        // Tambahkan peserta yang sudah selesai (tahap 7 selesai)
        $hasil['selesai'] = TahapanSpmb::where('tahap_7_selesai', true)->count();
        return $hasil;
    }

    /**
     * Ambil label tahap
     */
    private function ambilLabelTahap(): array
    {
        return [
            1 => TahapanSpmbEnum::BUAT_AKUN->label(),
            2 => TahapanSpmbEnum::BAYAR_FORMULIR->label(),
            3 => TahapanSpmbEnum::ISI_FORMULIR->label(),
            4 => TahapanSpmbEnum::TES_ONLINE->label(),
            5 => TahapanSpmbEnum::WAWANCARA->label(),
            6 => TahapanSpmbEnum::BAYAR_PERTAMA->label(),
            7 => TahapanSpmbEnum::RESMI_DITERIMA->label(),
        ];
    }

    /**
     * Hitung persentase per tahap
     */
    private function hitungPersentasePerTahap(array $pesertaPerTahap, int $total): array
    {
        if ($total === 0) return array_fill(1, 7, 0);
        
        $hasil = [];
        for ($i = 1; $i <= 7; $i++) {
            $hasil[$i] = round(($pesertaPerTahap[$i] ?? 0) / $total * 100, 1);
        }
        return $hasil;
    }

    /**
     * Ambil daftar peserta dengan filter
     */
    public function ambilDaftarPeserta(array $filter, int $perHalaman = 15): LengthAwarePaginator
    {
        $query = Peserta::with(['tahapanSpmb', 'formulirSpmb']);

        if (!empty($filter['tahap'])) {
            $query->whereHas('tahapanSpmb', function ($q) use ($filter) {
                $q->where('tahap_saat_ini', $filter['tahap']);
            });
        }

        if (!empty($filter['cari'])) {
            $query->where(function ($q) use ($filter) {
                $q->where('nama', 'like', "%{$filter['cari']}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$filter['cari']}%");
            });
        }

        return $query->latest()->paginate($perHalaman);
    }

    /**
     * Update status tahapan peserta
     */
    public function updateStatusTahapan(Peserta $peserta, int $tahap, bool $selesai, Pengguna $admin): void
    {
        $tahapan = $peserta->tahapanSpmb;
        
        if (!$tahapan) {
            $tahapan = TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 1,
                'tahap_1_selesai' => true,
            ]);
        }

        $kolom = "tahap_{$tahap}_selesai";
        $statusLama = $tahapan->$kolom ?? false;
        
        DB::transaction(function () use ($tahapan, $kolom, $tahap, $selesai, $peserta, $admin, $statusLama) {
            $tahapan->$kolom = $selesai;
            
            // Update tahap saat ini
            if ($selesai && $tahap >= $tahapan->tahap_saat_ini && $tahap < 7) {
                $tahapan->tahap_saat_ini = $tahap + 1;
            } elseif (!$selesai && $tahap < $tahapan->tahap_saat_ini) {
                $tahapan->tahap_saat_ini = $tahap;
            }
            
            $tahapan->save();

            // Log perubahan
            LogTahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap' => $tahap,
                'aksi' => 'manual_update',
                'status_lama' => $statusLama,
                'status_baru' => $selesai,
                'admin_id' => $admin->id,
            ]);
        });
    }

    /**
     * Bulk update tahapan untuk beberapa peserta
     */
    public function bulkUpdateTahapan(array $pesertaIds, int $tahap, bool $selesai, Pengguna $admin): int
    {
        $count = 0;
        
        DB::transaction(function () use ($pesertaIds, $tahap, $selesai, $admin, &$count) {
            foreach ($pesertaIds as $pesertaId) {
                $peserta = Peserta::find($pesertaId);
                if ($peserta) {
                    $this->updateStatusTahapan($peserta, $tahap, $selesai, $admin);
                    $count++;
                }
            }
        });
        
        return $count;
    }

    /**
     * Ambil log perubahan tahapan
     */
    public function ambilLogPerubahan(array $filter = [], int $perHalaman = 20): LengthAwarePaginator
    {
        $query = LogTahapanSpmb::with(['peserta', 'admin']);

        if (!empty($filter['peserta_id'])) {
            $query->where('peserta_id', $filter['peserta_id']);
        }

        if (!empty($filter['tahap'])) {
            $query->where('tahap', $filter['tahap']);
        }

        if (!empty($filter['tanggal_dari'])) {
            $query->whereDate('created_at', '>=', $filter['tanggal_dari']);
        }

        if (!empty($filter['tanggal_sampai'])) {
            $query->whereDate('created_at', '<=', $filter['tanggal_sampai']);
        }

        return $query->latest()->paginate($perHalaman);
    }

    /**
     * Ekspor data peserta ke array (untuk Excel)
     */
    public function eksporDataPeserta(array $filter = []): Collection
    {
        $query = Peserta::with(['tahapanSpmb', 'formulirSpmb']);

        if (!empty($filter['tahap'])) {
            $query->whereHas('tahapanSpmb', function ($q) use ($filter) {
                $q->where('tahap_saat_ini', $filter['tahap']);
            });
        }

        return $query->get()->map(function ($peserta) {
            $tahapan = $peserta->tahapanSpmb;
            return [
                'nomor_pendaftaran' => $peserta->nomor_pendaftaran,
                'nama' => $peserta->nama,
                'email' => $peserta->email,
                'telepon' => $peserta->telepon,
                'asal_sekolah' => $peserta->asal_sekolah,
                'tahap_saat_ini' => $tahapan?->tahap_saat_ini ?? 1,
                'tahap_1' => $tahapan?->tahap_1_selesai ? 'Ya' : 'Tidak',
                'tahap_2' => $tahapan?->tahap_2_selesai ? 'Ya' : 'Tidak',
                'tahap_3' => $tahapan?->tahap_3_selesai ? 'Ya' : 'Tidak',
                'tahap_4' => $tahapan?->tahap_4_selesai ? 'Ya' : 'Tidak',
                'tahap_5' => $tahapan?->tahap_5_selesai ? 'Ya' : 'Tidak',
                'tahap_6' => $tahapan?->tahap_6_selesai ? 'Ya' : 'Tidak',
                'tahap_7' => $tahapan?->tahap_7_selesai ? 'Ya' : 'Tidak',
                'tanggal_daftar' => $peserta->created_at->format('d/m/Y'),
            ];
        });
    }
}
