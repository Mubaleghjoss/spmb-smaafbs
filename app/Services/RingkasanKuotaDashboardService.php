<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\TahunAjaran;
use App\Services\JalurContextService;
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
        $kuotaSvc = app(KuotaPendaftaranService::class);

        return [
            'periode' => $tahun,
            'periode_label' => $tahun?->nama ?? 'Semua Periode',
            'kuota' => $kuotaSvc->ringkasanTahun($tahun),
            'rekap' => $this->rekap($tahun?->id),

            // Dashboard adalah SATU-SATUNYA halaman lintas jalur: pecahan per
            // jalur ditampilkan berdampingan agar admin tetap melihat gambaran
            // menyeluruh tanpa perlu mode "semua jalur" di halaman kerja.
            'per_jalur' => $this->perJalur($tahun?->id),
        ];
    }

    /**
     * Hitungan pendaftar & status kuota per jalur (siswa baru / pindahan),
     * termasuk pecahan kelas tujuan untuk jalur pindahan (kelas 10 & 11).
     *
     * @return array<string, array<string,mixed>>
     */
    private function perJalur(?int $tahunAjaranId): array
    {
        $hitung = function (?string $jenis, ?int $kelas = null) use ($tahunAjaranId): array {
            $q = Peserta::query();

            if ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId);
            }
            if ($jenis) {
                $q->where('jenis_pendaftaran', $jenis);
            }
            if ($kelas) {
                $q->where('kelas_tujuan', $kelas);
            }

            $rows = (clone $q)
                ->selectRaw('status_kuota, COUNT(*) as n')
                ->groupBy('status_kuota')
                ->pluck('n', 'status_kuota');

            return [
                'total' => (int) $rows->sum(),
                'dalam_kuota' => (int) ($rows[Peserta::STATUS_KUOTA_DALAM] ?? 0),
                'waiting_list' => (int) ($rows[Peserta::STATUS_KUOTA_WAITING] ?? 0),
                'belum_lengkap' => (int) ($rows[Peserta::STATUS_KUOTA_BELUM_LENGKAP] ?? 0),
            ];
        };

        return [
            'siswa_baru' => [
                'label' => 'Siswa Baru',
                'ikon' => 'person-plus',
                'warna' => 'success',
                'keterangan' => 'Kelas 10',
                'angka' => $hitung(JalurContextService::SISWA_BARU),
            ],
            'pindahan' => [
                'label' => 'Siswa Pindahan',
                'ikon' => 'arrow-left-right',
                'warna' => 'primary',
                'keterangan' => 'Kelas 10 & 11',
                'angka' => $hitung(JalurContextService::PINDAHAN),
                'kelas' => [
                    10 => $hitung(JalurContextService::PINDAHAN, 10),
                    11 => $hitung(JalurContextService::PINDAHAN, 11),
                ],
            ],
        ];
    }

    /**
     * Rekap peserta per Asal SMP / Kelompok / Desa / Daerah,
     * lengkap dengan pecahan gender & status kuota.
     *
     * @return array<string, array<string,mixed>>
     */
    private function rekap(?int $tahunAjaranId, ?string $jenisPendaftaran = null): array
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

            if ($jenisPendaftaran) {
                $base->where('peserta.jenis_pendaftaran', $jenisPendaftaran);
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
