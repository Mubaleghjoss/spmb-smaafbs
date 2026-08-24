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
        $kuotaLakiLaki = (int) ($tahun->kuota_laki_laki ?? 0);
        $kuotaPerempuan = (int) ($tahun->kuota_perempuan ?? 0);
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
        $belumLengkap = Peserta::query()
            ->where('tahun_ajaran_id', $tahun->id)
            ->where('status_kuota', Peserta::STATUS_KUOTA_BELUM_LENGKAP)
            ->count();
        $perGender = $this->ringkasanGenderTahun($tahun);
        $genderPenuh = $kuotaLakiLaki > 0
            && $kuotaPerempuan > 0
            && $perGender['laki_laki']['dalam_kuota'] >= $kuotaLakiLaki
            && $perGender['perempuan']['dalam_kuota'] >= $kuotaPerempuan;

        return [
            'kuota' => $kuota,
            'kuota_label' => $kuota > 0 ? (string) $kuota : 'Tidak dibatasi',
            'kuota_laki_laki' => $kuotaLakiLaki,
            'kuota_laki_laki_label' => $kuotaLakiLaki > 0 ? (string) $kuotaLakiLaki : 'Tidak dibatasi',
            'kuota_perempuan' => $kuotaPerempuan,
            'kuota_perempuan_label' => $kuotaPerempuan > 0 ? (string) $kuotaPerempuan : 'Tidak dibatasi',
            'total' => $total,
            'dalam_kuota' => $dalamKuota,
            'waiting_list' => $waitingList,
            'belum_lengkap' => $belumLengkap,
            'dikunci' => (bool) $tahun->kunci_kuota,
            'sisa' => $kuota > 0 ? max(0, $kuota - $dalamKuota) : null,
            'sisa_label' => $kuota > 0 ? (string) max(0, $kuota - $dalamKuota) : 'Tidak dibatasi',
            'penuh' => ($kuota > 0 && $dalamKuota >= $kuota) || $genderPenuh,
            'laki_laki' => $perGender['laki_laki'],
            'perempuan' => $perGender['perempuan'],
            'belum_isi_gender' => $perGender['belum_isi_gender'],
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

            return [
                // Pendaftar baru BELUM menempati kuota. Kursi baru diambil setelah
                // formulir lengkap + pembayaran pendaftaran (Tahap 3) diverifikasi.
                // Periode yang dikunci memakai perilaku lama agar data historis
                // tidak berubah artinya.
                'status_kuota' => $tahun->kunci_kuota
                    ? $this->statusKuotaCaraLama($tahun)
                    : Peserta::STATUS_KUOTA_BELUM_LENGKAP,
                'urutan_kuota' => $urutanBerikutnya,
            ];
        });
    }

    /**
     * Perilaku lama (sebelum aturan syarat kelengkapan): kursi langsung diambil
     * saat mendaftar. Hanya dipakai untuk periode yang status kuotanya dikunci.
     */
    private function statusKuotaCaraLama(TahunAjaran $tahun): string
    {
        $kuota = (int) ($tahun->kuota_peserta ?? 0);
        $dalamKuota = Peserta::query()
            ->where('tahun_ajaran_id', $tahun->id)
            ->where('status_kuota', Peserta::STATUS_KUOTA_DALAM)
            ->count();

        return $kuota > 0 && $dalamKuota >= $kuota
            ? Peserta::STATUS_KUOTA_WAITING
            : Peserta::STATUS_KUOTA_DALAM;
    }

    public function rekalkulasiTahunBanyak(array $tahunAjaranIds): void
    {
        collect($tahunAjaranIds)
            ->filter()
            ->unique()
            ->each(fn($tahunId) => $this->rekalkulasiTahun((int) $tahunId));
    }

    public function rekalkulasiPeserta(Peserta|int $peserta): void
    {
        $tahunAjaranId = $peserta instanceof Peserta
            ? $peserta->tahun_ajaran_id
            : Peserta::query()->whereKey($peserta)->value('tahun_ajaran_id');

        if ($tahunAjaranId) {
            $this->rekalkulasiTahun((int) $tahunAjaranId);
        }
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

            // Periode yang dikunci: status kuota dibiarkan apa adanya agar aturan
            // baru tidak berlaku surut pada periode yang sudah berjalan.
            if ($tahun->kunci_kuota) {
                return;
            }

            $peserta = Peserta::query()
                ->with([
                    'formulirSpmb:id,peserta_id,jenis_kelamin',
                    'tahapanSpmb:id,peserta_id,tahap_3_selesai,status_kelulusan',
                ])
                ->withExists(['pembayaran as bayar_formulir_ok' => fn($q) => $q
                    ->where('jenis', 'formulir')
                    ->where('status', 'terverifikasi')])
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
            $kuotaGender = [
                'L' => (int) ($tahun->kuota_laki_laki ?? 0),
                'P' => (int) ($tahun->kuota_perempuan ?? 0),
            ];
            $urutanGender = [
                'L' => 0,
                'P' => 0,
            ];
            $dalamKuota = 0;

            $peserta->values()->each(function (Peserta $peserta) use (&$urutanMaksimum, $kuota, $kuotaGender, &$urutanGender, &$dalamKuota) {
                if (! $peserta->urutan_kuota) {
                    $urutanMaksimum++;
                    $peserta->urutan_kuota = $urutanMaksimum;
                }

                // LAPIS 1 — syarat menempati kuota.
                // LAPIS 3 — kursi dilepas bila peserta dinyatakan tidak lulus.
                if (! $this->layakMenempatiKuota($peserta)) {
                    $peserta->status_kuota = Peserta::STATUS_KUOTA_BELUM_LENGKAP;

                    if ($peserta->isDirty(['status_kuota', 'urutan_kuota'])) {
                        $peserta->save();
                    }

                    return;
                }

                $jenisKelamin = $peserta->formulirSpmb?->jenis_kelamin;
                $masukKuotaGender = true;

                if (in_array($jenisKelamin, ['L', 'P'], true)) {
                    $urutanGender[$jenisKelamin]++;

                    if ($kuotaGender[$jenisKelamin] > 0 && $urutanGender[$jenisKelamin] > $kuotaGender[$jenisKelamin]) {
                        $masukKuotaGender = false;
                    }
                }

                $masukKuotaTotal = $kuota <= 0 || $dalamKuota < $kuota;
                $peserta->status_kuota = $masukKuotaGender && $masukKuotaTotal
                    ? Peserta::STATUS_KUOTA_DALAM
                    : Peserta::STATUS_KUOTA_WAITING;

                if ($peserta->status_kuota === Peserta::STATUS_KUOTA_DALAM) {
                    $dalamKuota++;
                }

                if ($peserta->isDirty(['status_kuota', 'urutan_kuota'])) {
                    $peserta->save();
                }
            });
        });
    }

    /**
     * Syarat peserta boleh menempati kuota:
     *  1. Formulir biodata sudah ada, DAN
     *  2. Pembayaran pendaftaran (Tahap 3) terverifikasi — atau Tahap 3 sudah
     *     ditandai selesai oleh Tim SPMB (verifikasi manual dianggap sah), DAN
     *  3. Belum dinyatakan tidak lulus (kursi dilepas bila tidak lulus).
     */
    private function layakMenempatiKuota(Peserta $peserta): bool
    {
        if ($peserta->formulirSpmb === null) {
            return false;
        }

        // Kursi dilepas bila peserta dinyatakan TIDAK LULUS pada tahap mana pun.
        if (($peserta->tahapanSpmb?->status_kelulusan) === 'tidak_lulus') {
            return false;
        }

        $bayarOk = (bool) ($peserta->bayar_formulir_ok ?? false);
        $tahap3Ok = (bool) ($peserta->tahapanSpmb?->tahap_3_selesai ?? false);

        return $bayarOk || $tahap3Ok;
    }

    private function ringkasanKosong(): array
    {
        return [
            'kuota' => 0,
            'kuota_label' => 'Tidak dibatasi',
            'kuota_laki_laki' => 0,
            'kuota_laki_laki_label' => 'Tidak dibatasi',
            'kuota_perempuan' => 0,
            'kuota_perempuan_label' => 'Tidak dibatasi',
            'total' => 0,
            'dalam_kuota' => 0,
            'waiting_list' => 0,
            'belum_lengkap' => 0,
            'dikunci' => false,
            'sisa' => null,
            'sisa_label' => 'Tidak dibatasi',
            'penuh' => false,
            'laki_laki' => $this->ringkasanGenderKosong(),
            'perempuan' => $this->ringkasanGenderKosong(),
            'belum_isi_gender' => [
                'total' => 0,
                'dalam_kuota' => 0,
                'waiting_list' => 0,
            ],
        ];
    }

    private function ringkasanGenderTahun(TahunAjaran $tahun): array
    {
        $rows = Peserta::query()
            ->leftJoin('formulir_spmb', 'formulir_spmb.peserta_id', '=', 'peserta.id')
            ->where('peserta.tahun_ajaran_id', $tahun->id)
            ->selectRaw('formulir_spmb.jenis_kelamin as jenis_kelamin')
            ->selectRaw('COUNT(peserta.id) as total')
            ->selectRaw("SUM(CASE WHEN peserta.status_kuota = ? THEN 1 ELSE 0 END) as dalam_kuota", [Peserta::STATUS_KUOTA_DALAM])
            ->selectRaw("SUM(CASE WHEN peserta.status_kuota = ? THEN 1 ELSE 0 END) as waiting_list", [Peserta::STATUS_KUOTA_WAITING])
            ->selectRaw("SUM(CASE WHEN peserta.status_kuota = ? THEN 1 ELSE 0 END) as belum_lengkap", [Peserta::STATUS_KUOTA_BELUM_LENGKAP])
            ->groupBy('formulir_spmb.jenis_kelamin')
            ->get()
            ->keyBy(fn($row) => $row->jenis_kelamin ?: '-');

        return [
            'laki_laki' => $this->ringkasanGender((int) ($tahun->kuota_laki_laki ?? 0), $rows->get('L')),
            'perempuan' => $this->ringkasanGender((int) ($tahun->kuota_perempuan ?? 0), $rows->get('P')),
            'belum_isi_gender' => [
                'total' => (int) ($rows->get('-')->total ?? 0),
                'dalam_kuota' => (int) ($rows->get('-')->dalam_kuota ?? 0),
                'waiting_list' => (int) ($rows->get('-')->waiting_list ?? 0),
                'belum_lengkap' => (int) ($rows->get('-')->belum_lengkap ?? 0),
            ],
        ];
    }

    private function ringkasanGender(int $kuota, mixed $row): array
    {
        $dalamKuota = (int) ($row->dalam_kuota ?? 0);

        return [
            'kuota' => $kuota,
            'kuota_label' => $kuota > 0 ? (string) $kuota : 'Tidak dibatasi',
            'total' => (int) ($row->total ?? 0),
            'dalam_kuota' => $dalamKuota,
            'waiting_list' => (int) ($row->waiting_list ?? 0),
            'belum_lengkap' => (int) ($row->belum_lengkap ?? 0),
            'sisa' => $kuota > 0 ? max(0, $kuota - $dalamKuota) : null,
            'sisa_label' => $kuota > 0 ? (string) max(0, $kuota - $dalamKuota) : 'Tidak dibatasi',
        ];
    }

    private function ringkasanGenderKosong(): array
    {
        return [
            'kuota' => 0,
            'kuota_label' => 'Tidak dibatasi',
            'total' => 0,
            'dalam_kuota' => 0,
            'waiting_list' => 0,
            'belum_lengkap' => 0,
            'sisa' => null,
            'sisa_label' => 'Tidak dibatasi',
        ];
    }
}
