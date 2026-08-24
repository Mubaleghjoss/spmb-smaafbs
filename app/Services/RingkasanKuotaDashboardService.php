<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan kuota penerimaan untuk dashboard admin.
 *
 * Hanya membaca data — tidak mengubah status kuota apa pun.
 * Rincian mengikuti pola "Rekap Data Formulir" di halaman peserta, ditambah
 * pecahan laki-laki/perempuan per kelompok.
 */
class RingkasanKuotaDashboardService
{
    /**
     * @return array{
     *   periode: TahunAjaran|null,
     *   periode_label: string,
     *   kuota: array<string,mixed>,
     *   rekap: array<string, array{label:string,param:string,items:\Illuminate\Support\Collection,total_peserta:int}>
     * }
     */
    public function untukPeriode(int|string|null $tahunAjaranId): array
    {
        $tahun = $tahunAjaranId ? TahunAjaran::find($tahunAjaranId) : null;

        return [
            'periode' => $tahun,
            'periode_label' => $tahun?->nama ?? 'Semua Periode',
            'kuota' => app(KuotaPendaftaranService::class)->ringkasanTahun($tahun),
            'rekap' => $this->rekap($tahun?->id),
        ];
    }

    /**
     * Rekap peserta per Asal SMP / Kelompok / Desa / Daerah,
     * lengkap dengan pecahan gender & status kuota.
     *
     * @return array<string, array<string,mixed>>
     */
    private function rekap(?int $tahunAjaranId): array
    {
        $configs = [
            'asal_sekolah_smp' => [
                'label' => 'Asal Sekolah SMP',
                'ikon' => 'mortarboard',
                'param' => 'asal_sekolah_smp',
                'expression' => "COALESCE(NULLIF(formulir_spmb.asal_sekolah, ''), NULLIF(peserta.asal_sekolah, ''), 'Belum Diisi')",
            ],
            'kelompok' => [
                'label' => 'Kelompok',
                'ikon' => 'people',
                'param' => 'kelompok',
                'expression' => "COALESCE(NULLIF(formulir_spmb.kelompok, ''), 'Belum Diisi')",
            ],
            'desa' => [
                'label' => 'Desa',
                'ikon' => 'geo-alt',
                'param' => 'desa',
                'expression' => "COALESCE(NULLIF(formulir_spmb.desa, ''), 'Belum Diisi')",
            ],
            'daerah' => [
                'label' => 'Daerah',
                'ikon' => 'map',
                'param' => 'daerah',
                'expression' => "COALESCE(NULLIF(formulir_spmb.daerah, ''), 'Belum Diisi')",
            ],
        ];

        $rekap = [];

        foreach ($configs as $key => $config) {
            $base = Peserta::query()
                ->leftJoin('formulir_spmb', 'formulir_spmb.peserta_id', '=', 'peserta.id')
                ->selectRaw("{$config['expression']} as nama")
                ->selectRaw('peserta.id as peserta_id')
                ->selectRaw('peserta.status_kuota as status_kuota')
                ->selectRaw('formulir_spmb.jenis_kelamin as jenis_kelamin');

            if ($tahunAjaranId) {
                $base->where('peserta.tahun_ajaran_id', $tahunAjaranId);
            }

            $items = DB::query()
                ->fromSub($base, 'r')
                ->select('nama')
                ->selectRaw('COUNT(peserta_id) as jumlah')
                ->selectRaw('SUM(CASE WHEN status_kuota = ? THEN 1 ELSE 0 END) as dalam_kuota', [Peserta::STATUS_KUOTA_DALAM])
                ->selectRaw('SUM(CASE WHEN status_kuota = ? THEN 1 ELSE 0 END) as waiting_list', [Peserta::STATUS_KUOTA_WAITING])
                ->selectRaw("SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as laki_laki")
                ->selectRaw("SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as perempuan")
                ->groupBy('nama')
                ->orderByDesc('jumlah')
                ->orderBy('nama')
                ->limit(10)
                ->get()
                ->each(fn($i) => $i->filter_value = $i->nama);

            $rekap[$key] = [
                ...$config,
                'items' => $items,
                'total_grup' => $items->count(),
                'total_peserta' => (int) $items->sum('jumlah'),
            ];
        }

        return $rekap;
    }
}
