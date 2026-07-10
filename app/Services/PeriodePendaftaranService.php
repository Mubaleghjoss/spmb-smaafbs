<?php

namespace App\Services;

use App\Models\GelombangPendaftaran;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeriodePendaftaranService
{
    public function pilihanPublik(): Collection
    {
        return TahunAjaran::query()
            ->aktif()
            ->whereHas('gelombangPendaftaran', fn($query) => $query->tersedia())
            ->with([
                'gelombangPendaftaran' => fn($query) => $query
                    ->tersedia()
                    ->orderBy('tanggal_buka')
                    ->orderBy('nama'),
            ])
            ->orderByDesc('default')
            ->orderByDesc('nama')
            ->get();
    }

    public function tahunDefault(): ?TahunAjaran
    {
        return TahunAjaran::query()
            ->where('default', true)
            ->first() ?? TahunAjaran::query()->aktif()->orderByDesc('nama')->first();
    }

    public function kategoriDefault(): array
    {
        $tahun = $this->tahunDefault();
        $gelombang = $tahun?->gelombangPendaftaran()
            ->tersedia()
            ->orderBy('tanggal_buka')
            ->orderBy('id')
            ->first();

        $gelombang ??= $tahun?->gelombangPendaftaran()
            ->where('aktif', true)
            ->orderByDesc('tanggal_buka')
            ->orderBy('id')
            ->first();

        return [
            'tahun_ajaran_id' => $tahun?->id,
            'gelombang_pendaftaran_id' => $gelombang?->id,
            'jenis_pendaftaran' => 'siswa_baru',
            'kelas_tujuan' => 10,
        ];
    }

    public function jadwalPublikBerikutnya(): ?GelombangPendaftaran
    {
        return GelombangPendaftaran::query()
            ->with('tahunAjaran')
            ->where('aktif', true)
            ->whereHas('tahunAjaran', fn($query) => $query->aktif())
            ->get()
            ->filter(fn(GelombangPendaftaran $gelombang) => $gelombang->mulaiPendaftaran()?->gt(now()))
            ->sortBy(fn(GelombangPendaftaran $gelombang) => $gelombang->mulaiPendaftaran()?->timestamp ?? PHP_INT_MAX)
            ->first();
    }

    public function validasiKategori(array $data, bool $wajibSedangDibuka = false): array
    {
        $tahun = TahunAjaran::query()->find($data['tahun_ajaran_id'] ?? null);
        $gelombang = GelombangPendaftaran::query()
            ->with('tahunAjaran')
            ->find($data['gelombang_pendaftaran_id'] ?? null);
        $jenis = $data['jenis_pendaftaran'] ?? null;
        $kelas = (int) ($data['kelas_tujuan'] ?? 0);
        $errors = [];

        if (!$tahun) {
            $errors['tahun_ajaran_id'] = 'Tahun ajaran yang dipilih tidak ditemukan.';
        }

        if (!$gelombang || !$tahun || (int) $gelombang->tahun_ajaran_id !== (int) $tahun->id) {
            $errors['gelombang_pendaftaran_id'] = 'Gelombang tidak sesuai dengan tahun ajaran yang dipilih.';
        }

        if ($wajibSedangDibuka && $tahun && !$tahun->aktif) {
            $errors['tahun_ajaran_id'] = 'Tahun ajaran yang dipilih sedang tidak aktif.';
        }

        if ($wajibSedangDibuka && $gelombang && !$gelombang->sedangDibuka()) {
            $errors['gelombang_pendaftaran_id'] = 'Gelombang pendaftaran yang dipilih sedang tidak dibuka.';
        }

        if (!in_array($jenis, ['siswa_baru', 'pindahan'], true)) {
            $errors['jenis_pendaftaran'] = 'Jenis pendaftaran tidak valid.';
        }

        if ($jenis === 'siswa_baru') {
            $kelas = 10;
        } elseif (!in_array($kelas, [10, 11], true)) {
            $errors['kelas_tujuan'] = 'Peserta pindahan hanya dapat memilih kelas 10 atau kelas 11.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'tahun_ajaran_id' => (int) $tahun->id,
            'gelombang_pendaftaran_id' => (int) $gelombang->id,
            'jenis_pendaftaran' => $jenis,
            'kelas_tujuan' => $kelas,
        ];
    }

    public function jadikanDefault(TahunAjaran $tahun): void
    {
        DB::transaction(function () use ($tahun) {
            TahunAjaran::query()->where('id', '!=', $tahun->id)->update(['default' => false]);
            $tahun->update(['aktif' => true, 'default' => true]);

            app(PengaturanService::class)->simpan('tahun_ajaran', $tahun->nama);
        });
    }

    public function normalisasiNamaTahun(string $nama): string
    {
        $nama = str_replace('/', '-', trim($nama));

        if (!preg_match('/^(\d{4})-(\d{4})$/', $nama, $matches)
            || (int) $matches[2] !== (int) $matches[1] + 1) {
            throw ValidationException::withMessages([
                'nama' => 'Tahun ajaran harus berformat YYYY-YYYY dan berurutan, contoh 2026-2027.',
            ]);
        }

        return $nama;
    }
}
