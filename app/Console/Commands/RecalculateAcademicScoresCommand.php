<?php

namespace App\Console\Commands;

use App\Models\GayaBelajarConfig;
use App\Models\MbtiConfig;
use App\Models\ProfilingConfig;
use App\Models\PsikotesKepribadianConfig;
use App\Models\SesiTes;
use App\Services\PenilaianService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAcademicScoresCommand extends Command
{
    protected $signature = 'hasil:hitung-ulang-akademik
                            {--apply : Simpan nilai dan status jawaban hasil perhitungan ulang}
                            {--peserta= : Batasi ke ID peserta tertentu}
                            {--tes= : Batasi ke ID tes tertentu}';

    protected $description = 'Hitung ulang sesi tes akademik selesai tanpa mengubah hasil psikometri atau sesi timeout';

    public function handle(PenilaianService $penilaianService): int
    {
        $apply = (bool) $this->option('apply');
        $psikometriTesIds = $this->psikometriTesIds();

        $query = SesiTes::query()
            ->with(['tes:id,nama,nilai_lulus', 'peserta:id,nama'])
            ->where('status', 'selesai')
            ->whereHas('jawabanPeserta')
            ->when(
                $psikometriTesIds->isNotEmpty(),
                fn($q) => $q->whereNotIn('tes_id', $psikometriTesIds)
            )
            ->when(
                $this->option('peserta'),
                fn($q, $pesertaId) => $q->where('peserta_id', $pesertaId)
            )
            ->when(
                $this->option('tes'),
                fn($q, $tesId) => $q->where('tes_id', $tesId)
            )
            ->orderBy('id');

        $sesiList = $query->get();
        if ($sesiList->isEmpty()) {
            $this->warn('Tidak ada sesi akademik selesai yang sesuai filter.');

            return Command::SUCCESS;
        }

        $berubah = 0;
        $tetap = 0;
        $contohPerubahan = [];

        foreach ($sesiList as $sesi) {
            $nilaiLama = (float) $sesi->nilai;

            if ($apply) {
                $nilaiBaru = DB::transaction(function () use ($penilaianService, $sesi) {
                    $nilai = $penilaianService->hitungNilai($sesi, true);
                    $updateData = ['nilai' => $nilai];

                    if ($sesi->status_verifikasi_tes !== 'diloloskan') {
                        if ($nilai < (float) $sesi->tes->nilai_lulus) {
                            $updateData['status_verifikasi_tes'] = 'menunggu';
                        } else {
                            $updateData['status_verifikasi_tes'] = null;
                            $updateData['catatan_verifikasi'] = null;
                            $updateData['diverifikasi_oleh'] = null;
                            $updateData['diverifikasi_pada'] = null;
                        }
                    }

                    $sesi->update($updateData);

                    return $nilai;
                });
            } else {
                $nilaiBaru = $penilaianService->hitungNilai($sesi, false);
            }

            if (abs($nilaiLama - $nilaiBaru) < 0.005) {
                $tetap++;
                continue;
            }

            $berubah++;
            if (count($contohPerubahan) < 20) {
                $contohPerubahan[] = [
                    $sesi->id,
                    $sesi->peserta?->nama ?? '-',
                    $sesi->tes?->nama ?? '-',
                    number_format($nilaiLama, 2),
                    number_format($nilaiBaru, 2),
                ];
            }
        }

        $mode = $apply ? 'APPLY' : 'DRY RUN';
        $this->info("Mode: {$mode}");
        $this->line("Diproses: {$sesiList->count()} sesi");
        $this->line("Nilai berubah: {$berubah} sesi");
        $this->line("Nilai tetap: {$tetap} sesi");

        if ($contohPerubahan !== []) {
            $this->table(
                ['Sesi', 'Peserta', 'Tes', 'Nilai lama', 'Nilai baru'],
                $contohPerubahan
            );
        }

        if (!$apply) {
            $this->warn('Belum ada data yang diubah. Jalankan kembali dengan --apply setelah memeriksa hasil.');
        }

        return Command::SUCCESS;
    }

    private function psikometriTesIds()
    {
        return collect()
            ->concat(PsikotesKepribadianConfig::query()->pluck('tes_id'))
            ->concat(MbtiConfig::query()->pluck('tes_id'))
            ->concat(GayaBelajarConfig::query()->where('aktif', true)->pluck('tes_id'))
            ->concat(ProfilingConfig::query()->where('aktif', true)->pluck('tes_id'))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
    }
}
