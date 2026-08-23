<?php

namespace App\Services;

use App\Models\FormulirSpmb;
use App\Models\Peserta;

/**
 * Sumber data identitas untuk Surat Pernyataan (Tahap 5).
 *
 * Prinsip: data identitas (nama, tempat & tanggal lahir, alamat, orang tua,
 * desa/daerah/kelompok, asal sekolah) TIDAK disalin ke tabel wawancara.
 * Setiap kali surat ditampilkan/dicetak, data dibaca LANGSUNG dari formulir
 * biodata (Tahap 2). Jadi begitu peserta memperbaiki formulir, surat pernyataan
 * dan PDF-nya otomatis ikut benar tanpa perlu mengisi ulang.
 *
 * Nilai lama yang sudah tersimpan di wawancara tetap dipakai sebagai cadangan
 * bila kolom formulir masih kosong, supaya data peserta terdahulu tidak hilang.
 */
class DataSuratPernyataanService
{
    /**
     * Data identitas hasil pembacaan formulir biodata.
     *
     * @param  bool  $pakaiDataAkun  sertakan data akun peserta sebagai cadangan
     *                               terakhir (nama/alamat/telepon/asal sekolah).
     * @return array{siswa: array<string,string>, ortu: array<string,string>}
     */
    public function dariFormulir(Peserta $peserta, ?FormulirSpmb $formulir = null, bool $pakaiDataAkun = true): array
    {
        $formulir ??= $peserta->formulirSpmb;
        $bersih = fn ($v): string => trim((string) $v);

        // Alamat dirangkai dari bagian yang terisi saja.
        $bagianAlamat = array_filter([
            $formulir?->alamat,
            $formulir?->alamat_kelurahan,
            $formulir?->alamat_kecamatan,
            $formulir?->alamat_kota,
            $formulir?->alamat_provinsi,
        ], fn ($v) => $bersih($v) !== '');

        $alamat = implode(', ', array_map($bersih, $bagianAlamat));
        if ($alamat === '' && $pakaiDataAkun) {
            $alamat = $bersih($peserta->alamat);
        }

        // "Kediri, 15 Januari 2010"
        $tempatTglLahir = collect([
            $bersih($formulir?->tempat_lahir),
            $formulir?->tanggal_lahir
                ? $formulir->tanggal_lahir->locale('id')->translatedFormat('d F Y')
                : '',
        ])->filter()->implode(', ');

        $namaOrtu = $bersih($formulir?->nama_ayah) ?: $bersih($formulir?->nama_ibu);
        $telpOrtu = $bersih($formulir?->telepon_ayah)
            ?: $bersih($formulir?->telepon_ibu)
            ?: $bersih($formulir?->telp_rumah)
            ?: ($pakaiDataAkun ? $bersih($peserta->telepon) : '');
        $namaSiswa = $bersih($formulir?->nama_lengkap)
            ?: ($pakaiDataAkun ? $bersih($peserta->nama) : '');
        $asalSekolah = $bersih($formulir?->asal_sekolah)
            ?: ($pakaiDataAkun ? $bersih($peserta->asal_sekolah) : '');

        return [
            'siswa' => [
                'nama_lengkap' => $namaSiswa,
                'tempat_tgl_lahir' => $tempatTglLahir,
                'alamat' => $alamat,
                'nama_ortu' => $namaOrtu,
                'no_telp_ortu' => $telpOrtu,
            ],
            'ortu' => [
                'nama_lengkap' => $namaOrtu,
                'alamat' => $alamat,
                'kelompok' => $bersih($formulir?->kelompok),
                'desa' => $bersih($formulir?->desa),
                'daerah' => $bersih($formulir?->daerah),
                'no_hp' => $telpOrtu,
                'nama_siswa' => $namaSiswa,
                'asal_sekolah' => $asalSekolah,
            ],
        ];
    }

    /**
     * Data siap tampil/cetak dengan urutan sumber:
     *   1. Formulir biodata (sumber utama — selalu terbaru)
     *   2. Salinan lama di wawancara (kompatibilitas data peserta terdahulu)
     *   3. Data akun peserta (cadangan terakhir)
     *
     * @return array{siswa: array<string,string>, ortu: array<string,string>}
     */
    public function untukSurat(Peserta $peserta, ?FormulirSpmb $formulir = null): array
    {
        // Tanpa data akun dulu, supaya salinan lama diprioritaskan di atas
        // fallback akun yang sering kurang lengkap.
        $dariFormulir = $this->dariFormulir($peserta, $formulir, pakaiDataAkun: false);
        $dariAkun = $this->dariFormulir($peserta, $formulir, pakaiDataAkun: true);
        $wawancara = $peserta->wawancara;

        $gabung = function (array $utama, array $salinanLama, array $cadanganAkun): array {
            foreach ($utama as $kunci => $nilai) {
                if (trim((string) $nilai) !== '') {
                    continue;
                }
                $lama = trim((string) ($salinanLama[$kunci] ?? ''));
                $utama[$kunci] = $lama !== ''
                    ? $lama
                    : trim((string) ($cadanganAkun[$kunci] ?? ''));
            }
            return $utama;
        };

        return [
            'siswa' => $gabung(
                $dariFormulir['siswa'],
                $wawancara?->surat_pernyataan_siswa ?? [],
                $dariAkun['siswa'],
            ),
            'ortu' => $gabung(
                $dariFormulir['ortu'],
                $wawancara?->surat_pernyataan_ortu ?? [],
                $dariAkun['ortu'],
            ),
        ];
    }

    /**
     * Daftar kolom identitas yang sudah TIDAK disimpan lagi ke wawancara
     * (dipakai saat membersihkan input agar tidak membuat salinan baru).
     *
     * @return array{siswa: string[], ortu: string[]}
     */
    public function kolomIkutFormulir(): array
    {
        return [
            'siswa' => ['nama_lengkap', 'tempat_tgl_lahir', 'alamat', 'nama_ortu', 'no_telp_ortu'],
            'ortu' => ['nama_lengkap', 'alamat', 'kelompok', 'desa', 'daerah', 'no_hp', 'nama_siswa', 'asal_sekolah'],
        ];
    }
}
