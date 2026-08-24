<?php

namespace App\Services;

/**
 * Konteks JALUR PENDAFTARAN aktif untuk seluruh halaman admin.
 *
 * Bekerja seperti PeriodeContextService, tetapi TIDAK punya mode "Semua Jalur":
 * data peserta siswa baru dan pindahan tidak pernah dicampur di halaman kerja.
 *
 *  - null           : admin belum memilih jalur -> halaman kerja menampilkan
 *                     kartu pengarah "Pilih Jalur Pendaftaran", bukan data campur.
 *  - 'siswa_baru'   : data disaring ke jalur siswa baru.
 *  - 'pindahan'     : data disaring ke jalur siswa pindahan.
 *
 * Kelas satu-satunya sumber aturan rombel per jalur (kelasDiizinkan) agar aturan
 * "pindahan hanya kelas 10 & 11, kelas 12 belum diizinkan" tidak tersebar dan
 * jadi tidak konsisten antar form/impor/API.
 */
class JalurContextService
{
    /** Kunci session penyimpan jalur aktif admin. */
    public const SESSION_KEY = 'jalur_aktif_jenis_pendaftaran';

    public const SISWA_BARU = 'siswa_baru';
    public const PINDAHAN = 'pindahan';

    /** Kelas tujuan yang diizinkan per jalur (kebijakan sekolah). */
    private const KELAS_PER_JALUR = [
        self::SISWA_BARU => [10],
        self::PINDAHAN => [10, 11],
    ];

    /**
     * Jalur yang sedang aktif, atau null bila admin belum memilih.
     */
    public function jenis(): ?string
    {
        $nilai = session(self::SESSION_KEY);

        return $this->valid($nilai) ? $nilai : null;
    }

    /**
     * Apakah admin belum memilih jalur.
     */
    public function belumMemilih(): bool
    {
        return $this->jenis() === null;
    }

    /**
     * Tetapkan jalur aktif. null / nilai tidak valid mengembalikan ke keadaan
     * "belum memilih" (bukan "semua jalur").
     */
    public function set(?string $jenis): void
    {
        if ($this->valid($jenis)) {
            session([self::SESSION_KEY => $jenis]);

            return;
        }

        session()->forget(self::SESSION_KEY);
    }

    /**
     * Label untuk switcher header.
     */
    public function label(): string
    {
        return match ($this->jenis()) {
            self::SISWA_BARU => 'Siswa Baru',
            self::PINDAHAN => 'Siswa Pindahan',
            default => 'Pilih Jalur Pendaftaran',
        };
    }

    /**
     * Label singkat untuk header halaman / nama berkas ekspor.
     */
    public function labelSingkat(): string
    {
        return match ($this->jenis()) {
            self::SISWA_BARU => 'Siswa Baru',
            self::PINDAHAN => 'Pindahan',
            default => 'Semua',
        };
    }

    /**
     * Pilihan untuk dropdown switcher (bernomor sesuai kebijakan tampilan).
     *
     * @return array<int, array{nomor:int, nilai:string, label:string, ikon:string}>
     */
    public function pilihan(): array
    {
        return [
            ['nomor' => 1, 'nilai' => self::SISWA_BARU, 'label' => 'Siswa Baru', 'ikon' => 'person-plus'],
            ['nomor' => 2, 'nilai' => self::PINDAHAN, 'label' => 'Siswa Pindahan', 'ikon' => 'arrow-left-right'],
        ];
    }

    /**
     * Terapkan filter jalur pada query peserta bila jalur sudah dipilih.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string  $kolom  nama kolom berkualifikasi (mis. 'peserta.jenis_pendaftaran')
     */
    public function terapkan($query, string $kolom = 'jenis_pendaftaran')
    {
        $jenis = $this->jenis();

        if ($jenis !== null) {
            $query->where($kolom, $jenis);
        }

        return $query;
    }

    /**
     * Kelas tujuan yang diizinkan untuk suatu jalur.
     * SUMBER TUNGGAL aturan rombel — dipakai validasi controller, form, dan impor.
     *
     * @return array<int, int>
     */
    public static function kelasDiizinkan(?string $jenis): array
    {
        return self::KELAS_PER_JALUR[$jenis] ?? [10, 11];
    }

    /**
     * Aturan validasi Laravel untuk kelas_tujuan berdasarkan jalur.
     * Contoh hasil: 'required|integer|in:10,11'
     */
    public static function aturanKelas(?string $jenis): string
    {
        return 'required|integer|in:' . implode(',', self::kelasDiizinkan($jenis));
    }

    /**
     * Label daftar kelas untuk pesan kesalahan / keterangan form.
     */
    public static function labelKelasDiizinkan(?string $jenis): string
    {
        $kelas = array_map(fn($k) => "Kelas {$k}", self::kelasDiizinkan($jenis));

        return implode(' atau ', $kelas);
    }

    private function valid(mixed $nilai): bool
    {
        return in_array($nilai, [self::SISWA_BARU, self::PINDAHAN], true);
    }
}
