<?php

namespace App\Services;

use App\Models\GelombangPendaftaran;
use App\Models\JadwalAlurPeriode;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * JadwalAlurService
 *
 * Sumber tunggal jadwal alur SPMB, granularitas PER GELOMBANG (di dalam tahun ajaran).
 * Tiap gelombang punya jadwal 7 tahap sendiri (tabel jadwal_alur_periode dengan
 * gelombang_pendaftaran_id). Baris dengan gelombang_pendaftaran_id NULL = jadwal
 * tingkat tahun (fallback untuk semua gelombang pada tahun tsb).
 *
 * Rantai fallback saat membaca satu tahap:
 *   1. baris gelombang spesifik  ->  2. baris tahun (gelombang NULL)
 *   ->  3. setting lama global (PengaturanService tahap_X_* / SPMB / ujian).
 *
 * Tahap 1 (Daftar & Isi Biodata) selalu terbuka, tanpa jadwal.
 */
class JadwalAlurService
{
    public const TAHAP_BERJADWAL = [2, 3, 4, 5, 6, 7];

    public function __construct(private PengaturanService $pengaturan) {}

    /**
     * Metadata statis 7 tahap.
     *
     * @return array<int, array{judul:string, icon:string, deskripsi:string}>
     */
    public function metaTahap(): array
    {
        return [
            1 => ['judul' => 'Daftar & Isi Biodata',          'icon' => 'person-plus-fill',       'deskripsi' => 'Pendaftar mengisi biodata & No HP, akun otomatis dibuat, langsung masuk dashboard.'],
            2 => ['judul' => 'Lengkapi Formulir & Berkas',     'icon' => 'file-earmark-text-fill', 'deskripsi' => 'Pendaftar melengkapi formulir dan mengunggah berkas dari dashboard.'],
            3 => ['judul' => 'Pembayaran Formulir',            'icon' => 'credit-card-fill',       'deskripsi' => 'Transfer biaya formulir dan unggah bukti pembayaran.'],
            4 => ['judul' => 'Tes Online',                     'icon' => 'laptop-fill',            'deskripsi' => 'Mengerjakan tes seleksi online sesuai jadwal.'],
            5 => ['judul' => 'Wawancara & Verifikasi Berkas',  'icon' => 'people-fill',            'deskripsi' => 'Wawancara dan verifikasi berkas asli.'],
            6 => ['judul' => 'Pembayaran Pertama',             'icon' => 'wallet2',                'deskripsi' => 'Pelunasan biaya tahap pertama dan unggah bukti.'],
            7 => ['judul' => 'Pengumuman Kelulusan',           'icon' => 'mortarboard-fill',       'deskripsi' => 'Info kelulusan dan status penerimaan.'],
        ];
    }

    /**
     * Jadwal lengkap 7 tahap untuk satu gelombang (untuk form admin & tampilan).
     * Menggabungkan: baris gelombang -> baris tahun (NULL) -> setting lama -> meta statis.
     *
     * @return array<int, array>
     */
    public function jadwalGelombang(int $tahunAjaranId, ?int $gelombangId): array
    {
        // baris spesifik gelombang
        $rowsGel = $gelombangId
            ? JadwalAlurPeriode::where('tahun_ajaran_id', $tahunAjaranId)
                ->where('gelombang_pendaftaran_id', $gelombangId)
                ->get()->keyBy('tahap')
            : collect();

        // baris tingkat tahun (gelombang NULL) sebagai fallback
        $rowsTahun = JadwalAlurPeriode::where('tahun_ajaran_id', $tahunAjaranId)
            ->whereNull('gelombang_pendaftaran_id')
            ->get()->keyBy('tahap');

        $legacy = $this->pengaturan->ambilPengaturanTahapan();
        $meta = $this->metaTahap();
        $hasil = [];

        foreach ($meta as $tahap => $m) {
            $row = $rowsGel->get($tahap);         // prioritas 1
            $rowT = $rowsTahun->get($tahap);      // prioritas 2
            $leg = $legacy["tahap_{$tahap}"] ?? []; // prioritas 3
            $sumber = $row ? 'gelombang' : ($rowT ? 'tahun' : 'warisan');
            $src = $row ?? $rowT;

            $hasil[$tahap] = [
                'tahap' => $tahap,
                'judul' => $m['judul'],
                'icon' => $m['icon'],
                'deskripsi' => $m['deskripsi'],
                'berjadwal' => in_array($tahap, self::TAHAP_BERJADWAL, true),
                'dibuka' => $src->dibuka ?? (bool) ($leg['dibuka'] ?? true),
                'tanggal_buka' => optional($src?->tanggal_buka)->format('Y-m-d') ?? ($leg['tanggal_buka'] ?? ''),
                'waktu_mulai' => $this->jamHm($src->waktu_mulai ?? ($leg['waktu_mulai'] ?? '')),
                'tanggal_tutup' => optional($src?->tanggal_tutup)->format('Y-m-d') ?? ($leg['tanggal_tutup'] ?? ''),
                'waktu_selesai' => $this->jamHm($src->waktu_selesai ?? ($leg['waktu_selesai'] ?? '')),
                'lokasi' => $src->lokasi ?? ($leg['lokasi'] ?? ''),
                'keterangan' => $src->keterangan ?? ($leg['keterangan'] ?? ''),
                'sumber' => $sumber,
            ];
        }

        return $hasil;
    }

    /**
     * Simpan jadwal 7 tahap untuk satu gelombang (dari form admin).
     *
     * @param array<int, array> $data keyed by tahap
     */
    public function simpanJadwalGelombang(int $tahunAjaranId, ?int $gelombangId, array $data): void
    {
        foreach (self::TAHAP_BERJADWAL as $tahap) {
            $c = $data[$tahap] ?? [];

            JadwalAlurPeriode::updateOrCreate(
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'gelombang_pendaftaran_id' => $gelombangId,
                    'tahap' => $tahap,
                ],
                [
                    'dibuka' => (bool) ($c['dibuka'] ?? false),
                    'tanggal_buka' => $this->tanggalAtauNull($c['tanggal_buka'] ?? null),
                    'waktu_mulai' => $this->jamAtauNull($c['waktu_mulai'] ?? null),
                    'tanggal_tutup' => $this->tanggalAtauNull($c['tanggal_tutup'] ?? null),
                    'waktu_selesai' => $this->jamAtauNull($c['waktu_selesai'] ?? null),
                    'lokasi' => ($c['lokasi'] ?? '') ?: null,
                    'keterangan' => ($c['keterangan'] ?? '') ?: null,
                ]
            );
        }
    }

    /**
     * Status akses satu tahap untuk kombinasi tahun+gelombang tertentu.
     */
    public function statusTahap(int $tahunAjaranId, ?int $gelombangId, int $tahap): array
    {
        $hasil = ['dibuka' => true, 'alasan' => null, 'tanggal_buka' => null, 'jadwal_label' => null, 'keterangan' => null];

        if (! in_array($tahap, self::TAHAP_BERJADWAL, true)) {
            return $hasil;
        }

        $j = $this->jadwalGelombang($tahunAjaranId, $gelombangId)[$tahap] ?? null;
        if (! $j) {
            return $hasil;
        }

        $mulai = $this->gabung($j['tanggal_buka'], $j['waktu_mulai'], false);
        $selesai = $this->gabung($j['tanggal_tutup'], $j['waktu_selesai'], true);
        $mulaiLabel = $mulai ? $this->formatLabel($mulai, ! empty($j['waktu_mulai'])) : null;
        $selesaiLabel = $selesai ? $this->formatLabel($selesai, ! empty($j['waktu_selesai'])) : null;

        $hasil['tanggal_buka'] = $mulaiLabel;
        $hasil['keterangan'] = $j['keterangan'] ?: null;
        $hasil['jadwal_label'] = $this->labelJadwal($mulaiLabel, $selesaiLabel);

        if (! $j['dibuka']) {
            $hasil['dibuka'] = false;
            $hasil['alasan'] = 'Tahap ini sedang ditutup oleh admin.';
            return $hasil;
        }
        if ($mulai && now()->lt($mulai)) {
            $hasil['dibuka'] = false;
            $hasil['alasan'] = 'Dibuka pada ' . $mulaiLabel;
            return $hasil;
        }
        if ($selesai && now()->gt($selesai)) {
            $hasil['dibuka'] = false;
            $hasil['alasan'] = 'Sudah ditutup pada ' . $selesaiLabel;
        }

        return $hasil;
    }

    /**
     * Daftar timeline publik (untuk /jadwal) dari jadwal satu gelombang.
     *
     * @return array<int, array{kegiatan:string, icon:string, tanggal:string, status:string, keterangan:string, catatan:?string}>
     */
    public function jadwalPublik(int $tahunAjaranId, ?int $gelombangId): array
    {
        $jadwal = $this->jadwalGelombang($tahunAjaranId, $gelombangId);
        $out = [];

        foreach ($jadwal as $tahap => $j) {
            if ($tahap === 1) {
                continue;
            }

            $mulai = $this->gabung($j['tanggal_buka'], $j['waktu_mulai'], false);
            $selesai = $this->gabung($j['tanggal_tutup'], $j['waktu_selesai'], true);
            $mulaiLabel = $mulai ? $this->formatLabel($mulai, ! empty($j['waktu_mulai'])) : null;
            $selesaiLabel = $selesai ? $this->formatLabel($selesai, ! empty($j['waktu_selesai'])) : null;

            if ($mulaiLabel && $selesaiLabel) {
                $tanggalTeks = $mulaiLabel . ' – ' . $selesaiLabel;
            } elseif ($mulaiLabel) {
                $tanggalTeks = 'Mulai ' . $mulaiLabel;
            } elseif ($selesaiLabel) {
                $tanggalTeks = 'Sampai ' . $selesaiLabel;
            } else {
                $tanggalTeks = 'Menyusul';
            }

            if (! $j['dibuka']) {
                $status = 'info';
                $ket = 'Ditutup';
            } elseif ($mulai && now()->lt($mulai)) {
                $status = 'akan_datang';
                $ket = 'Akan Datang';
            } elseif ($selesai && now()->gt($selesai)) {
                $status = 'selesai';
                $ket = 'Selesai';
            } else {
                $status = 'dibuka';
                $ket = 'Berlangsung';
            }

            $out[] = [
                'kegiatan' => $j['judul'],
                'icon' => ltrim($j['icon'], 'bi-'),
                'tanggal' => $tanggalTeks,
                'status' => $status,
                'keterangan' => $ket,
                'catatan' => $j['keterangan'] ?: null,
            ];
        }

        return $out;
    }

    /**
     * Gelombang yang ditampilkan di halaman publik: yang sedang dibuka (paling awal),
     * jika tidak ada yang dibuka -> gelombang terbaru pada tahun tsb.
     */
    public function gelombangPublikTerpilih(int $tahunAjaranId): ?GelombangPendaftaran
    {
        $semua = GelombangPendaftaran::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('aktif', true)
            ->orderBy('tanggal_buka')
            ->get();

        if ($semua->isEmpty()) {
            return null;
        }

        $terbuka = $semua->first(fn ($g) => $g->sedangDibuka());

        return $terbuka ?? $semua->last();
    }

    /**
     * Semua gelombang aktif pada tahun (untuk tombol "lihat gelombang lain").
     *
     * @return Collection<int, GelombangPendaftaran>
     */
    public function gelombangTahun(int $tahunAjaranId): Collection
    {
        return GelombangPendaftaran::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('aktif', true)
            ->orderBy('tanggal_buka')
            ->get();
    }

    /* ================= helpers ================= */

    private function jamHm(?string $v): string
    {
        if (empty($v)) {
            return '';
        }
        return substr($v, 0, 5);
    }

    private function tanggalAtauNull(?string $v): ?string
    {
        return ! empty($v) ? $v : null;
    }

    private function jamAtauNull(?string $v): ?string
    {
        return ! empty($v) ? $v : null;
    }

    private function gabung(?string $tanggal, ?string $waktu, bool $akhirHari): ?Carbon
    {
        if (empty($tanggal)) {
            return null;
        }
        $jam = $waktu ?: ($akhirHari ? '23:59:59' : '00:00:00');
        return Carbon::parse(trim($tanggal . ' ' . $jam));
    }

    private function formatLabel(Carbon $tanggal, bool $pakaiJam): string
    {
        return $tanggal->locale('id')->translatedFormat($pakaiJam ? 'd F Y H:i' : 'd F Y')
            . ($pakaiJam ? ' WIB' : '');
    }

    private function labelJadwal(?string $mulai, ?string $selesai): ?string
    {
        if ($mulai && $selesai) {
            return 'Jadwal: ' . $mulai . ' sampai ' . $selesai;
        }
        if ($mulai) {
            return 'Dibuka pada ' . $mulai;
        }
        return $selesai ? 'Ditutup pada ' . $selesai : null;
    }
}
