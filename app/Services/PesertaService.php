<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\Grup;
use App\Models\TahapanSpmb;
use App\Models\LogTahapanSpmb;
use App\Helpers\NomorPendaftaranHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Service untuk manajemen peserta
 * Kebutuhan: 3.1, 3.3, 3.7
 */
class PesertaService
{
    /**
     * Ambil peserta dengan filter dan paginasi
     */
    public function ambilDenganFilter(array $filter = [], int $perHalaman = 15): LengthAwarePaginator
    {
        $query = Peserta::with([
            'tahapanSpmb',
            'grup',
            'tahunAjaran',
            'gelombangPendaftaran',
            'formulirSpmb',
        ]);

        // Filter berdasarkan grup
        if (!empty($filter['grup_id'])) {
            $query->whereHas('grup', fn($q) => $q->where('grup.id', $filter['grup_id']));
        }

        // Filter berdasarkan tahapan
        if (!empty($filter['tahap'])) {
            $query->whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', $filter['tahap']));
        }

        if (!empty($filter['tahun_ajaran_id'])) {
            $query->where('tahun_ajaran_id', $filter['tahun_ajaran_id']);
        }

        if (!empty($filter['gelombang_pendaftaran_id'])) {
            $query->where('gelombang_pendaftaran_id', $filter['gelombang_pendaftaran_id']);
        }

        if (!empty($filter['jenis_pendaftaran'])) {
            $query->where('jenis_pendaftaran', $filter['jenis_pendaftaran']);
        }

        if (!empty($filter['kelas_tujuan'])) {
            $query->where('kelas_tujuan', $filter['kelas_tujuan']);
        }

        if (!empty($filter['status_kuota'])) {
            $query->where('status_kuota', $filter['status_kuota']);
        }

        if (!empty($filter['asal_sekolah_smp'])) {
            $asalSekolah = $filter['asal_sekolah_smp'];
            if ($this->filterKosong($asalSekolah)) {
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNull('asal_sekolah')
                            ->orWhere('asal_sekolah', '');
                    })->whereDoesntHave('formulirSpmb', function ($sub) {
                        $sub->whereNotNull('asal_sekolah')
                            ->where('asal_sekolah', '<>', '');
                    });
                });
            } else {
                $query->where(function ($q) use ($asalSekolah) {
                    $q->where('asal_sekolah', 'like', "%{$asalSekolah}%")
                        ->orWhereHas('formulirSpmb', fn($sub) => $sub->where('asal_sekolah', 'like', "%{$asalSekolah}%"));
                });
            }
        }

        foreach (['kelompok', 'desa', 'daerah'] as $field) {
            if (!empty($filter[$field])) {
                $value = $filter[$field];
                if ($this->filterKosong($value)) {
                    $query->whereDoesntHave('formulirSpmb', function ($sub) use ($field) {
                        $sub->whereNotNull($field)
                            ->where($field, '<>', '');
                    });
                } else {
                    $query->whereHas('formulirSpmb', fn($sub) => $sub->where($field, 'like', "%{$value}%"));
                }
            }
        }

        // Filter berdasarkan status (aktif/dihapus)
        if (isset($filter['dengan_dihapus']) && $filter['dengan_dihapus']) {
            $query->withTrashed();
        }

        // Pencarian
        if (!empty($filter['cari'])) {
            $cari = $filter['cari'];
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$cari}%")
                  ->orWhere('email', 'like', "%{$cari}%")
                  ->orWhere('asal_sekolah', 'like', "%{$cari}%")
                  ->orWhereHas('formulirSpmb', function ($sub) use ($cari) {
                      $sub->where('asal_sekolah', 'like', "%{$cari}%")
                          ->orWhere('kelompok', 'like', "%{$cari}%")
                          ->orWhere('desa', 'like', "%{$cari}%")
                          ->orWhere('daerah', 'like', "%{$cari}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perHalaman);
    }

    public function rekapFormulir(array $filter = []): array
    {
        $configs = [
            'asal_sekolah_smp' => [
                'label' => 'Asal Sekolah SMP',
                'param' => 'asal_sekolah_smp',
                'expression' => "COALESCE(NULLIF(formulir_spmb.asal_sekolah, ''), NULLIF(peserta.asal_sekolah, ''), 'Belum Diisi')",
            ],
            'kelompok' => [
                'label' => 'Nama Kelompok',
                'param' => 'kelompok',
                'expression' => "COALESCE(NULLIF(formulir_spmb.kelompok, ''), 'Belum Diisi')",
            ],
            'desa' => [
                'label' => 'Nama Desa',
                'param' => 'desa',
                'expression' => "COALESCE(NULLIF(formulir_spmb.desa, ''), 'Belum Diisi')",
            ],
            'daerah' => [
                'label' => 'Nama Daerah',
                'param' => 'daerah',
                'expression' => "COALESCE(NULLIF(formulir_spmb.daerah, ''), 'Belum Diisi')",
            ],
        ];

        $rekap = [];

        foreach ($configs as $key => $config) {
            $expression = $config['expression'];
            $baseQuery = $this->queryRekapFormulir($filter)
                ->selectRaw("{$expression} as nama")
                ->selectRaw('peserta.id as peserta_id')
                ->selectRaw('peserta.status_kuota as status_kuota');

            $query = DB::query()
                ->fromSub($baseQuery, 'rekap_formulir')
                ->select('nama')
                ->selectRaw('COUNT(peserta_id) as jumlah')
                ->selectRaw("SUM(CASE WHEN status_kuota = ? THEN 1 ELSE 0 END) as dalam_kuota", [Peserta::STATUS_KUOTA_DALAM])
                ->selectRaw("SUM(CASE WHEN status_kuota = ? THEN 1 ELSE 0 END) as waiting_list", [Peserta::STATUS_KUOTA_WAITING])
                ->groupBy('nama')
                ->orderByDesc('jumlah')
                ->orderBy('nama')
                ->limit(10)
                ->get()
                ->each(function ($item) {
                    $item->filter_value = $item->nama;
                });

            $rekap[$key] = [
                ...$config,
                'items' => $query,
                'total_grup' => $query->count(),
                'total_peserta' => $query->sum('jumlah'),
            ];
        }

        return $rekap;
    }

    private function queryRekapFormulir(array $filter)
    {
        $query = Peserta::query()
            ->leftJoin('formulir_spmb', 'formulir_spmb.peserta_id', '=', 'peserta.id');

        if (!empty($filter['dengan_dihapus'])) {
            $query->withTrashed();
        }

        if (!empty($filter['grup_id'])) {
            $query->whereExists(function ($sub) use ($filter) {
                $sub->selectRaw('1')
                    ->from('grup_peserta')
                    ->whereColumn('grup_peserta.peserta_id', 'peserta.id')
                    ->where('grup_peserta.grup_id', $filter['grup_id']);
            });
        }

        if (!empty($filter['tahap'])) {
            $query->whereExists(function ($sub) use ($filter) {
                $sub->selectRaw('1')
                    ->from('tahapan_spmb')
                    ->whereColumn('tahapan_spmb.peserta_id', 'peserta.id')
                    ->where('tahapan_spmb.tahap_saat_ini', $filter['tahap']);
            });
        }

        foreach (['tahun_ajaran_id', 'gelombang_pendaftaran_id', 'jenis_pendaftaran', 'kelas_tujuan', 'status_kuota'] as $field) {
            if (!empty($filter[$field])) {
                $query->where("peserta.{$field}", $filter[$field]);
            }
        }

        if (!empty($filter['asal_sekolah_smp'])) {
            $asalSekolah = $filter['asal_sekolah_smp'];
            if ($this->filterKosong($asalSekolah)) {
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNull('peserta.asal_sekolah')
                            ->orWhere('peserta.asal_sekolah', '');
                    })->where(function ($sub) {
                        $sub->whereNull('formulir_spmb.asal_sekolah')
                            ->orWhere('formulir_spmb.asal_sekolah', '');
                    });
                });
            } else {
                $query->where(function ($q) use ($asalSekolah) {
                    $q->where('peserta.asal_sekolah', 'like', "%{$asalSekolah}%")
                        ->orWhere('formulir_spmb.asal_sekolah', 'like', "%{$asalSekolah}%");
                });
            }
        }

        foreach (['kelompok', 'desa', 'daerah'] as $field) {
            if (!empty($filter[$field])) {
                if ($this->filterKosong($filter[$field])) {
                    $query->where(function ($q) use ($field) {
                        $q->whereNull("formulir_spmb.{$field}")
                            ->orWhere("formulir_spmb.{$field}", '');
                    });
                } else {
                    $query->where("formulir_spmb.{$field}", 'like', "%{$filter[$field]}%");
                }
            }
        }

        if (!empty($filter['cari'])) {
            $cari = $filter['cari'];
            $query->where(function ($q) use ($cari) {
                $q->where('peserta.nama', 'like', "%{$cari}%")
                    ->orWhere('peserta.nomor_pendaftaran', 'like', "%{$cari}%")
                    ->orWhere('peserta.email', 'like', "%{$cari}%")
                    ->orWhere('peserta.asal_sekolah', 'like', "%{$cari}%")
                    ->orWhere('formulir_spmb.asal_sekolah', 'like', "%{$cari}%")
                    ->orWhere('formulir_spmb.kelompok', 'like', "%{$cari}%")
                    ->orWhere('formulir_spmb.desa', 'like', "%{$cari}%")
                    ->orWhere('formulir_spmb.daerah', 'like', "%{$cari}%");
            });
        }

        return $query;
    }

    private function filterKosong(string $value): bool
    {
        return trim($value) === 'Belum Diisi';
    }

    /**
     * Cari peserta berdasarkan ID
     */
    public function cariById(int $id): ?Peserta
    {
        return Peserta::with(['tahapanSpmb', 'grup', 'formulirSpmb', 'pembayaran'])->find($id);
    }

    /**
     * Buat peserta baru
     */
    public function buat(array $data): Peserta
    {
        return DB::transaction(function () use ($data) {
            $periodeService = app(PeriodePendaftaranService::class);
            $data = array_merge($periodeService->kategoriDefault(), $data);
            $data = array_merge($data, $periodeService->validasiKategori($data));

            // Generate nomor pendaftaran jika tidak ada
            if (empty($data['nomor_pendaftaran'])) {
                $data['nomor_pendaftaran'] = NomorPendaftaranHelper::generate();
            }

            $kuota = app(KuotaPendaftaranService::class)
                ->siapkanAtributPesertaBaru($data['tahun_ajaran_id']);

            // Simpan password plain untuk ditampilkan ke admin
            $plainPassword = $data['password'] ?? 'password123';

            // Password akan otomatis di-hash oleh model cast 'hashed'
            $peserta = Peserta::create([
                'nomor_pendaftaran' => $data['nomor_pendaftaran'],
                'tahun_ajaran_id' => $data['tahun_ajaran_id'],
                'gelombang_pendaftaran_id' => $data['gelombang_pendaftaran_id'],
                'jenis_pendaftaran' => $data['jenis_pendaftaran'],
                'kelas_tujuan' => $data['kelas_tujuan'],
                'status_kuota' => $kuota['status_kuota'],
                'urutan_kuota' => $kuota['urutan_kuota'],
                'nama' => $data['nama'],
                'email' => $data['email'],
                'password' => $plainPassword,
                'password_temp' => $plainPassword, // Simpan password sementara
                'telepon' => $data['telepon'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'asal_sekolah' => $data['asal_sekolah'] ?? null,
            ]);

            // Buat tahapan SPMB
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 2, // Langsung ke tahap 2 (Isi Formulir)
                'tahap_1_selesai' => true,
            ]);

            // Assign ke grup jika ada
            if (!empty($data['grup_id'])) {
                $peserta->grup()->attach($data['grup_id']);
            }

            return $peserta->load(['tahapanSpmb', 'grup']);
        });
    }


    /**
     * Perbarui peserta
     */
    public function perbarui(Peserta $peserta, array $data): Peserta
    {
        return DB::transaction(function () use ($peserta, $data) {
            $tahunLama = $peserta->tahun_ajaran_id;
            $updateData = [
                'nama' => $data['nama'] ?? $peserta->nama,
                'email' => $data['email'] ?? $peserta->email,
                'telepon' => $data['telepon'] ?? $peserta->telepon,
                'alamat' => $data['alamat'] ?? $peserta->alamat,
                'asal_sekolah' => $data['asal_sekolah'] ?? $peserta->asal_sekolah,
                'tahun_ajaran_id' => $data['tahun_ajaran_id'] ?? $peserta->tahun_ajaran_id,
                'gelombang_pendaftaran_id' => $data['gelombang_pendaftaran_id'] ?? $peserta->gelombang_pendaftaran_id,
                'jenis_pendaftaran' => $data['jenis_pendaftaran'] ?? $peserta->jenis_pendaftaran,
                'kelas_tujuan' => $data['kelas_tujuan'] ?? $peserta->kelas_tujuan,
            ];

            if ((int) $tahunLama !== (int) $updateData['tahun_ajaran_id']) {
                $updateData['urutan_kuota'] = null;
            }

            // Update password jika ada (akan otomatis di-hash oleh model cast 'hashed')
            if (!empty($data['password'])) {
                $updateData['password'] = $data['password'];
            }

            $peserta->update($updateData);

            if ((int) $tahunLama !== (int) $updateData['tahun_ajaran_id']) {
                app(KuotaPendaftaranService::class)->rekalkulasiTahunBanyak([
                    $tahunLama,
                    $updateData['tahun_ajaran_id'],
                ]);
            }

            // Update grup jika ada
            if (isset($data['grup_id'])) {
                $peserta->grup()->sync($data['grup_id'] ? [$data['grup_id']] : []);
            }

            return $peserta->fresh(['tahapanSpmb', 'grup']);
        });
    }

    /**
     * Hapus peserta (soft delete)
     */
    public function hapus(Peserta $peserta): bool
    {
        $tahunId = $peserta->tahun_ajaran_id;
        $deleted = $peserta->delete();

        if ($deleted) {
            app(KuotaPendaftaranService::class)->rekalkulasiTahun((int) $tahunId);
        }

        return $deleted;
    }

    /**
     * Restore peserta yang dihapus
     */
    public function restore(int $id): ?Peserta
    {
        $peserta = Peserta::withTrashed()->find($id);
        if ($peserta && $peserta->trashed()) {
            $tahunId = $peserta->tahun_ajaran_id;
            $peserta->restore();
            app(KuotaPendaftaranService::class)->rekalkulasiTahun((int) $tahunId);
            return $peserta;
        }
        return null;
    }

    /**
     * Hapus permanen peserta
     */
    public function hapusPermanen(int $id): bool
    {
        $peserta = Peserta::withTrashed()->find($id);
        if ($peserta) {
            $tahunId = $peserta->tahun_ajaran_id;
            $deleted = $peserta->forceDelete();

            if ($deleted) {
                app(KuotaPendaftaranService::class)->rekalkulasiTahun((int) $tahunId);
            }

            return $deleted;
        }
        return false;
    }

    /**
     * Ambil statistik peserta
     */
    public function ambilStatistik(): array
    {
        return [
            'total' => Peserta::count(),
            'aktif' => Peserta::count(),
            'dihapus' => Peserta::onlyTrashed()->count(),
            'per_tahap' => [
                1 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 1))->count(),
                2 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 2))->count(),
                3 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 3))->count(),
                4 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 4))->count(),
                5 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 5))->count(),
                6 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 6))->count(),
                7 => Peserta::whereHas('tahapanSpmb', fn($q) => $q->where('tahap_saat_ini', 7))->count(),
            ],
        ];
    }

    /**
     * Assign peserta ke grup
     */
    public function assignKeGrup(Peserta $peserta, int $grupId): void
    {
        $peserta->grup()->syncWithoutDetaching([$grupId]);
    }

    /**
     * Hapus peserta dari grup
     */
    public function hapusDariGrup(Peserta $peserta, int $grupId): void
    {
        $peserta->grup()->detach($grupId);
    }

    /**
     * Bulk assign peserta ke grup
     */
    public function bulkAssignKeGrup(array $pesertaIds, int $grupId): int
    {
        $count = 0;
        foreach ($pesertaIds as $pesertaId) {
            $peserta = Peserta::find($pesertaId);
            if ($peserta) {
                $peserta->grup()->syncWithoutDetaching([$grupId]);
                $count++;
            }
        }
        return $count;
    }

    public function buatPeserta(array $data): Peserta
    {
        return $this->buat($data);
    }

    public function bulkPerbaruiKategori(array $pesertaIds, array $kategori): int
    {
        return DB::transaction(function () use ($pesertaIds, $kategori) {
            $peserta = Peserta::query()
                ->whereIn('id', $pesertaIds)
                ->get(['id', 'tahun_ajaran_id']);
            $tahunLama = $peserta->pluck('tahun_ajaran_id')->filter()->all();
            $tahunBaru = (int) $kategori['tahun_ajaran_id'];
            $pindahTahunIds = $peserta
                ->filter(fn(Peserta $item) => (int) $item->tahun_ajaran_id !== $tahunBaru)
                ->pluck('id')
                ->all();
            $tetapTahunIds = $peserta
                ->filter(fn(Peserta $item) => (int) $item->tahun_ajaran_id === $tahunBaru)
                ->pluck('id')
                ->all();

            $count = 0;

            if ($tetapTahunIds !== []) {
                $count += Peserta::query()
                    ->whereIn('id', $tetapTahunIds)
                    ->update([
                        ...$kategori,
                        'updated_at' => now(),
                    ]);
            }

            if ($pindahTahunIds !== []) {
                $count += Peserta::query()
                    ->whereIn('id', $pindahTahunIds)
                    ->update([
                        ...$kategori,
                        'urutan_kuota' => null,
                        'updated_at' => now(),
                    ]);
            }

            app(KuotaPendaftaranService::class)->rekalkulasiTahunBanyak([
                ...$tahunLama,
                $tahunBaru,
            ]);

            return $count;
        });
    }

    /**
     * Pindahkan peserta ke tahap tertentu.
     */
    public function pindahkanTahap(
        Peserta $peserta,
        int $tahapBaru,
        ?int $adminId = null,
        bool $luluskanFinal = false,
        string $aksi = 'manual_update',
        ?string $skGelombangId = null
    ): void {
        DB::transaction(function () use ($peserta, $tahapBaru, $adminId, $luluskanFinal, $aksi, $skGelombangId): void {
            $tahapan = $peserta->tahapanSpmb;
            if (!$tahapan) {
                $tahapan = $peserta->tahapanSpmb()->create([
                    'tahap_saat_ini' => 1,
                    'tahap_1_selesai' => true,
                ]);
            }

            $tahapLama = (int) $tahapan->tahap_saat_ini;
            $statusLama = $tahapan->{"tahap_{$tahapBaru}_selesai"} ?? false;

            $tahapan->tahap_saat_ini = $tahapBaru;
            for ($i = 1; $i <= 7; $i++) {
                $kolom = "tahap_{$i}_selesai";
                $tahapan->$kolom = $i < $tahapBaru;
            }

            if ($tahapBaru === 7 && $luluskanFinal) {
                $tahapan->tahap_7_selesai = true;
                $tahapan->status_kelulusan = 'lulus';
                $tahapan->sk_gelombang_kelulusan = $skGelombangId;
            } elseif ($tahapBaru === 7) {
                $tahapan->status_kelulusan = 'menunggu';
                $tahapan->sk_gelombang_kelulusan = null;
            } else {
                $tahapan->status_kelulusan = 'menunggu';
                $tahapan->sk_gelombang_kelulusan = null;
            }

            $tahapan->save();

            LogTahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap' => $tahapBaru,
                'aksi' => $aksi,
                'status_lama' => (bool) $statusLama,
                'status_baru' => (bool) ($tahapan->{"tahap_{$tahapBaru}_selesai"} ?? false),
                'pesan' => "Tahap diubah dari {$tahapLama} ke {$tahapBaru}" . ($luluskanFinal ? ' dan ditandai lulus.' : '.'),
                'admin_id' => $adminId,
            ]);
        });
    }

    /**
     * Pindahkan beberapa peserta ke tahap tertentu.
     */
    public function bulkPindahkanTahap(
        array $pesertaIds,
        int $tahapBaru,
        ?int $adminId = null,
        bool $luluskanFinal = false,
        ?string $skGelombangId = null
    ): int
    {
        $count = 0;

        DB::transaction(function () use ($pesertaIds, $tahapBaru, $adminId, $luluskanFinal, $skGelombangId, &$count): void {
            foreach ($pesertaIds as $pesertaId) {
                $peserta = Peserta::find($pesertaId);
                if (!$peserta) {
                    continue;
                }

                $this->pindahkanTahap($peserta, $tahapBaru, $adminId, $luluskanFinal, 'bulk_update', $skGelombangId);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Reset password peserta
     * Password akan otomatis di-hash oleh model cast 'hashed'
     */
    public function resetPassword(Peserta $peserta, string $passwordBaru = null): string
    {
        $password = $passwordBaru ?? substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
        $peserta->update([
            'password' => $password,
            'password_temp' => $password, // Simpan password sementara
        ]);
        return $password;
    }

    /**
     * Update password peserta secara manual oleh admin
     */
    public function updatePasswordManual(Peserta $peserta, string $password): void
    {
        $peserta->update([
            'password' => $password,
            'password_temp' => $password,
        ]);
    }
}
