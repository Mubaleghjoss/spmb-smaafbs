<?php

namespace App\Services;

use App\Models\JadwalAlurPeriode;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * JadwalAlurService
 *
 * Sumber tunggal jadwal alur SPMB PER PERIODE (tahun ajaran).
 * Tiap tahun ajaran punya jadwal 7 tahap sendiri (tabel jadwal_alur_periode).
 *
 * Fallback: bila baris per-periode belum ada untuk suatu tahap, memakai
 * setting lama (PengaturanService: tahap_X_* / SPMB / ujian) agar data existing
 * tidak hilang saat pertama kali fitur ini dipakai.
 *
 * Tahap 1 (Daftar & Isi Biodata) tidak punya jadwal — selalu terbuka.
 */
class JadwalAlurService
{
    /** Tahap yang punya jadwal manual. */
    public const TAHAP_BERJADWAL = [2, 3, 4, 5, 6, 7];

    public function __construct(private PengaturanService $pengaturan) {}

    /**
     * Metadata statis 7 tahap (judul, ikon, deskripsi singkat).
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
     * Ambil jadwal lengkap 7 tahap untuk satu tahun ajaran (untuk form admin & tampilan).
     * Menggabungkan baris DB per-periode + fallback setting lama + meta statis.
     *
     * @return array<int, array>
     */
    public function jadwalPeriode(int $tahunAjaranId): array
    {
        $rows = JadwalAlurPeriode::where('tahun_ajaran_id', $tahunAjaranId)
            ->get()
            ->keyBy('tahap');

        $legacy = $this->pengaturan->ambilPengaturanTahapan();
        $meta = $this->metaTahap();
        $hasil = [];

        foreach ($meta as $tahap => $m) {
            $row = $rows->get($tahap);
            $leg = $legacy["tahap_{$tahap}"] ?? [];

            $hasil[$tahap] = [
                'tahap' => $tahap,
                'judul' => $m['judul'],
                'icon' => $m['icon'],
                'deskripsi' => $m['deskripsi'],
                'berjadwal' => in_array($tahap, self::TAHAP_BERJADWAL, true),
                'dibuka' => $row->dibuka ?? (bool) ($leg['dibuka'] ?? true),
                'tanggal_buka' => optional($row?->tanggal_buka)->format('Y-m-d') ?? ($leg['tanggal_buka'] ?? ''),
                'waktu_mulai' => $this->jamHm($row->waktu_mulai ?? ($leg['waktu_mulai'] ?? '')),
                'tanggal_tutup' => optional($row?->tanggal_tutup)->format('Y-m-d') ?? ($leg['tanggal_tutup'] ?? ''),
                'waktu_selesai' => $this->jamHm($row->waktu_selesai ?? ($leg['waktu_selesai'] ?? '')),
                'lokasi' => $row->lokasi ?? ($leg['lokasi'] ?? ''),
                'keterangan' => $row->keterangan ?? ($leg['keterangan'] ?? ''),
                'sumber' => $row ? 'periode' : 'warisan',
            ];
        }

        return $hasil;
    }

    /**
     * Simpan jadwal 7 tahap untuk satu periode (dari form admin).
     *
     * @param array<int, array> $data  keyed by tahap
     */
    public function simpanJadwalPeriode(int $tahunAjaranId, array $data): void
    {
        foreach (self::TAHAP_BERJADWAL as $tahap) {
            $c = $data[$tahap] ?? [];

            JadwalAlurPeriode::updateOrCreate(
                ['tahun_ajaran_id' => $tahunAjaranId, 'tahap' => $tahap],
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

        Cache::forget($this->cacheKey($tahunAjaranId));
    }

    /**
     * Status akses satu tahap untuk peserta pada tahun ajaran tertentu.
     * Mengembalikan: dibuka(bool), alasan, tanggal_buka(label), jadwal_label, keterangan.
     */
    public function statusTahap(int $tahunAjaranId, int $tahap): array
    {
        $hasil = ['dibuka' => true, 'alasan' => null, 'tanggal_buka' => null, 'jadwal_label' => null, 'keterangan' => null];

        if (! in_array($tahap, self::TAHAP_BERJADWAL, true)) {
            return $hasil; // tahap 1 selalu terbuka
        }

        $j = $this->jadwalPeriode($tahunAjaranId)[$tahap] ?? null;
        if (! $j) {
            return $hasil;
        }

        $mulai = $this->gabung($j['tanggal_buka'], $j['waktu_mulai'], false);
        $selesai = $this->gabung($j['tanggal_tutup'], $j['waktu_selesai'], true);
        $mulaiLabel = $mulai ? $this->formatLabel($mulai, ! empty($j['waktu_mulai'])) : null;
        $selesaiLabel = $selesai ? $this->formatLabel($selesai, ! empty($j['waktu_selesai'])) : null;

        $hasil['tanggal_buka'] = $mulaiLabel;
        $hasil['keterangan'] = $j['keterangan'] ?: null;
        $hasil['jadwal_label'] = $this->labelJadwal($j['judul'], $mulaiLabel, $selesaiLabel);

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
     * Bangun daftar jadwal publik (untuk halaman /jadwal) dari jadwal periode.
     * Menghasilkan struktur mirip ambilJadwal() lama agar view tetap kompatibel.
     *
     * @return array<int, array{kegiatan:string, icon:string, tanggal:string, status:string, keterangan:string}>
     */
    public function jadwalPublik(int $tahunAjaranId): array
    {
        $jadwal = $this->jadwalPeriode($tahunAjaranId);
        $out = [];

        foreach ($jadwal as $tahap => $j) {
            if ($tahap === 1) {
                continue; // langkah daftar tidak perlu baris jadwal publik
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

            // status untuk badge di halaman publik
            if (! $j['dibuka']) {
                $status = 'info';
                $ket = 'Ditutup';
            } elseif ($mulai && now()->lt($mulai)) {
                $status = 'akan_datang';
                $ket = 'Akan Datang';
            } elseif ($selesai && now()->gt($selesai)) {
                $status = 'info';
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
            ];
        }

        return $out;
    }

    /* ================= helpers ================= */

    private function cacheKey(int $tahunAjaranId): string
    {
        return "jadwal_alur_periode_{$tahunAjaranId}";
    }

    private function jamHm(?string $v): string
    {
        if (empty($v)) {
            return '';
        }
        // "14:30:00" -> "14:30"
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

    private function labelJadwal(string $judul, ?string $mulai, ?string $selesai): ?string
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
