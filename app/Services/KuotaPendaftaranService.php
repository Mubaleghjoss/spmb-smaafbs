<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class KuotaPendaftaranService
{
    public function ringkasanTahun(TahunAjaran|int|null $tahunAjaran): array
    {
        $tahun = $tahunAjaran instanceof TahunAjaran
            ? $tahunAjaran
            : TahunAjaran::query()->find($tahunAjaran);

        if (! $tahun) {
            return $this->ringkasanKosong();
        }

        $kuota = (int) ($tahun->kuota_peserta ?? 0);
        $total = Peserta::query()
            ->where('tahun_ajaran_id', $tahun->id)
            ->count();
        $dalamKuota = Peserta::query()
            ->where('tahun_ajaran_id', $tahun->id)
            ->where('status_kuota', Peserta::STATUS_KUOTA_DALAM)
            ->count();
        $waitingList = Peserta::query()
            ->where('tahun_ajaran_id', $tahun->id)
            ->where('status_kuota', Peserta::STATUS_KUOTA_WAITING)
            ->count();

        return [
            'kuota' => $kuota,
            'kuota_label' => $kuota > 0 ? (string) $kuota : 'Tidak dibatasi',
            'total' => $total,
            'dalam_kuota' => $dalamKuota,
            'waiting_list' => $waitingList,
            'sisa' => $kuota > 0 ? max(0, $kuota - $dalamKuota) : null,
            'sisa_label' => $kuota > 0 ? (string) max(0, $kuota - $dalamKuota) : 'Tidak dibatasi',
            'penuh' => $kuota > 0 && $total >= $kuota,
        ];
    }

    public function ringkasanBanyak(iterable $tahunAjaran): array
    {
        $ringkasan = [];

        foreach ($tahunAjaran as $tahun) {
            if ($tahun instanceof TahunAjaran) {
                $ringkasan[$tahun->id] = $this->ringkasanTahun($tahun);
            }
        }

        return $ringkasan;
    }

    public function siapkanAtributPesertaBaru(int $tahunAjaranId): array
    {
        return DB::transaction(function () use ($tahunAjaranId) {
            $tahun = TahunAjaran::query()
                ->whereKey($tahunAjaranId)
                ->lockForUpdate()
                ->firstOrFail();

            $urutanBerikutnya = ((int) Peserta::withTrashed()
                ->where('tahun_ajaran_id', $tahun->id)
                ->max('urutan_kuota')) + 1;
            $kuota = (int) ($tahun->kuota_peserta ?? 0);
            $dalamKuota = Peserta::query()
                ->where('tahun_ajaran_id', $tahun->id)
                ->where('status_kuota', Peserta::STATUS_KUOTA_DALAM)
                ->count();

            return [
                'status_kuota' => $kuota > 0 && $dalamKuota >= $kuota
                    ? Peserta::STATUS_KUOTA_WAITING
                    : Peserta::STATUS_KUOTA_DALAM,
                'urutan_kuota' => $urutanBerikutnya,
            ];
        });
    }

    public function rekalkulasiTahunBanyak(array $tahunAjaranIds): void
    {
        collect($tahunAjaranIds)
            ->filter()
            ->unique()
            ->each(fn($tahunId) => $this->rekalkulasiTahun((int) $tahunId));
    }

    public function rekalkulasiTahun(int $tahunAjaranId): void
    {
        DB::transaction(function () use ($tahunAjaranId) {
            $tahun = TahunAjaran::query()
                ->whereKey($tahunAjaranId)
                ->lockForUpdate()
                ->first();

            if (! $tahun) {
                return;
            }

            $peserta = Peserta::query()
                ->where('tahun_ajaran_id', $tahun->id)
                ->orderByRaw('CASE WHEN urutan_kuota IS NULL THEN 1 ELSE 0 END')
                ->orderBy('urutan_kuota')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $urutanMaksimum = (int) Peserta::withTrashed()
                ->where('tahun_ajaran_id', $tahun->id)
                ->max('urutan_kuota');
            $kuota = (int) ($tahun->kuota_peserta ?? 0);

            $peserta->values()->each(function (Peserta $peserta, int $index) use (&$urutanMaksimum, $kuota) {
                if (! $peserta->urutan_kuota) {
                    $urutanMaksimum++;
                    $peserta->urutan_kuota = $urutanMaksimum;
                }

                $peserta->status_kuota = $kuota > 0 && $index >= $kuota
                    ? Peserta::STATUS_KUOTA_WAITING
                    : Peserta::STATUS_KUOTA_DALAM;

                if ($peserta->isDirty(['status_kuota', 'urutan_kuota'])) {
                    $peserta->save();
                }
            });
        });
    }

    private function ringkasanKosong(): array
    {
        return [
            'kuota' => 0,
            'kuota_label' => 'Tidak dibatasi',
            'total' => 0,
            'dalam_kuota' => 0,
            'waiting_list' => 0,
            'sisa' => null,
            'sisa_label' => 'Tidak dibatasi',
            'penuh' => false,
        ];
    }
}
