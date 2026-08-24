<?php

namespace App\Services;

use App\Models\JawabanPeserta;
use App\Models\Peserta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Pembersih data & berkas peserta untuk penghapusan PERMANEN.
 *
 * Dipakai untuk membersihkan pendaftar yang tidak jadi lanjut atau data hasil
 * uji coba. Berkas dihapus berdasarkan path yang tersimpan di kolom database
 * (bukan menebak nama file), sehingga berkas milik peserta lain maupun berkas
 * pengaturan global (SK kelulusan, branding) tidak pernah tersentuh.
 */
class PesertaPembersihService
{
    /**
     * Kolom berkas per relasi: [label kategori => [relasi, [kolom...]]]
     */
    private const KOLOM_BERKAS_FORMULIR = [
        'file_kk' => 'Kartu Keluarga',
        'file_akta' => 'Akta Kelahiran',
        'file_ijazah' => 'Ijazah / SKL',
        'file_bpjs' => 'BPJS / KIS',
        'file_ktp_ayah' => 'KTP Ayah',
        'file_ktp_ibu' => 'KTP Ibu',
        'foto' => 'Foto Peserta',
    ];

    private const KOLOM_BERKAS_WAWANCARA = [
        'file_tes_pegon' => 'Jawaban Tes Pegon',
        'file_voice_quran' => 'Rekaman Bacaan Quran',
    ];

    /**
     * Ringkasan lengkap data & berkas yang akan hilang bila peserta dihapus.
     *
     * @return array{identitas: array<string,mixed>, data: array<int,array{label:string,jumlah:int|string}>, berkas: array<int,array<string,mixed>>, total_berkas: int, total_ukuran: int, total_ukuran_label: string, peringatan: array<int,string>}
     */
    public function ringkasan(Peserta $peserta): array
    {
        $peserta->loadMissing([
            'tahapanSpmb', 'formulirSpmb', 'wawancara', 'pembayaran',
            'sesiTes.tes', 'tahunAjaran', 'gelombangPendaftaran',
        ]);

        $jumlahJawaban = JawabanPeserta::whereIn(
            'sesi_tes_id',
            $peserta->sesiTes->pluck('id')
        )->count();

        $identitas = [
            'Nama' => $peserta->nama,
            'No. Pendaftaran' => $peserta->nomor_pendaftaran,
            'Telepon / Username' => $peserta->telepon,
            'Periode' => $peserta->tahunAjaran?->nama ?? '-',
            'Gelombang' => $peserta->gelombangPendaftaran?->nama ?? '-',
            'Tahap Saat Ini' => $peserta->tahapanSpmb?->tahap_saat_ini ?? '-',
            'Status Kelulusan' => $peserta->tahapanSpmb?->status_kelulusan ?: 'belum ditetapkan',
            'Terdaftar' => $peserta->created_at?->format('d/m/Y H:i'),
        ];

        $data = [
            ['label' => 'Formulir biodata', 'jumlah' => $peserta->formulirSpmb ? 'Ada' : 'Tidak ada'],
            ['label' => 'Sesi tes online', 'jumlah' => $peserta->sesiTes->count()],
            ['label' => 'Jawaban tes', 'jumlah' => $jumlahJawaban],
            ['label' => 'Data pembayaran', 'jumlah' => $peserta->pembayaran->count()],
            ['label' => 'Data wawancara (jawaban, surat, TTD)', 'jumlah' => $peserta->wawancara ? 'Ada' : 'Tidak ada'],
            ['label' => 'Riwayat tahapan', 'jumlah' => $peserta->logTahapan()->count()],
        ];

        $berkas = $this->kumpulkanBerkas($peserta);
        $totalUkuran = array_sum(array_column($berkas, 'ukuran'));

        $peringatan = [];
        if (($peserta->tahapanSpmb?->status_kelulusan) === 'lulus') {
            $peringatan[] = 'Peserta ini sudah dinyatakan LULUS. Menghapusnya berarti membatalkan data kelulusannya dan kuota periode akan bertambah kembali.';
        }
        if ($peserta->pembayaran->where('status', 'terverifikasi')->isNotEmpty()) {
            $peringatan[] = 'Peserta ini punya pembayaran yang SUDAH TERVERIFIKASI. Bukti pembayaran dan kwitansinya akan hilang.';
        }

        return [
            'identitas' => $identitas,
            'data' => $data,
            'berkas' => $berkas,
            'total_berkas' => count($berkas),
            'total_ukuran' => $totalUkuran,
            'total_ukuran_label' => $this->formatUkuran($totalUkuran),
            'peringatan' => $peringatan,
        ];
    }

    /**
     * Hapus peserta beserta seluruh data anak dan berkasnya.
     *
     * @return array{berhasil: bool, berkas_terhapus: int, berkas_gagal: array<int,string>, byte_dibebaskan: int, byte_dibebaskan_label: string}
     */
    public function hapusPermanen(Peserta $peserta): array
    {
        $peserta->loadMissing(['formulirSpmb', 'wawancara', 'pembayaran', 'sesiTes']);

        // Kumpulkan daftar berkas SEBELUM baris database dihapus.
        $berkas = $this->kumpulkanBerkas($peserta);
        $tahunAjaranId = (int) $peserta->tahun_ajaran_id;
        $pesertaId = $peserta->id;

        DB::transaction(function () use ($peserta) {
            // Urutan mengikuti relasi induk→anak agar tidak melanggar foreign key.
            JawabanPeserta::whereIn('sesi_tes_id', $peserta->sesiTes()->pluck('id'))->delete();
            $peserta->sesiTes()->delete();
            $peserta->pembayaran()->delete();
            $peserta->wawancara()->delete();
            $peserta->formulirSpmb()->delete();
            $peserta->logTahapan()->delete();

            DB::table('peserta_wawancara')->where('peserta_id', $peserta->id)->delete();
            $peserta->grup()->detach();

            $peserta->tahapanSpmb()->delete();

            $peserta->forceDelete();
        });

        // Berkas dihapus SETELAH transaksi sukses, agar tidak ada berkas hilang
        // sementara barisnya masih ada karena rollback.
        $terhapus = 0;
        $gagal = [];
        $byte = 0;

        foreach ($berkas as $b) {
            if (empty($b['path'])) {
                continue;
            }

            try {
                if (Storage::disk('public')->exists($b['path'])) {
                    $byte += (int) $b['ukuran'];
                    Storage::disk('public')->delete($b['path']);
                    $terhapus++;
                }
            } catch (\Throwable $e) {
                $gagal[] = $b['path'];
                Log::warning('Gagal menghapus berkas peserta: ' . $b['path'] . ' — ' . $e->getMessage());
            }
        }

        // Buang direktori formulir peserta bila sudah kosong.
        $this->hapusDirektoriKosong("formulir/{$pesertaId}");

        if ($tahunAjaranId > 0) {
            try {
                app(KuotaPendaftaranService::class)->rekalkulasiTahun($tahunAjaranId);
            } catch (\Throwable $e) {
                Log::warning('Gagal rekalkulasi kuota setelah hapus permanen: ' . $e->getMessage());
            }
        }

        return [
            'berhasil' => true,
            'berkas_terhapus' => $terhapus,
            'berkas_gagal' => $gagal,
            'byte_dibebaskan' => $byte,
            'byte_dibebaskan_label' => $this->formatUkuran($byte),
        ];
    }

    /**
     * Daftar berkas milik peserta berdasarkan kolom database.
     *
     * @return array<int, array{kategori:string,label:string,path:string,nama:string,ukuran:int,ukuran_label:string,url:string|null,ada:bool}>
     */
    private function kumpulkanBerkas(Peserta $peserta): array
    {
        $hasil = [];
        $disk = Storage::disk('public');

        $tambah = function (string $kategori, string $label, ?string $path) use (&$hasil, $disk) {
            if (empty($path)) {
                return;
            }

            // Lewati data URL (tanda tangan base64) — tersimpan di DB, bukan file.
            if (str_starts_with($path, 'data:')) {
                return;
            }

            $ada = $disk->exists($path);
            $ukuran = $ada ? (int) $disk->size($path) : 0;

            $hasil[] = [
                'kategori' => $kategori,
                'label' => $label,
                'path' => $path,
                'nama' => basename($path),
                'ukuran' => $ukuran,
                'ukuran_label' => $this->formatUkuran($ukuran),
                'url' => $ada ? $disk->url($path) : null,
                'ada' => $ada,
            ];
        };

        if ($formulir = $peserta->formulirSpmb) {
            foreach (self::KOLOM_BERKAS_FORMULIR as $kolom => $label) {
                $tambah('Formulir', $label, $formulir->{$kolom} ?? null);
            }
        }

        if ($wawancara = $peserta->wawancara) {
            foreach (self::KOLOM_BERKAS_WAWANCARA as $kolom => $label) {
                $tambah('Wawancara', $label, $wawancara->{$kolom} ?? null);
            }
        }

        foreach ($peserta->pembayaran as $bayar) {
            $tambah('Pembayaran', 'Bukti ' . ($bayar->jenis ?? 'pembayaran'), $bayar->bukti_file ?? null);
        }

        return $hasil;
    }

    private function hapusDirektoriKosong(string $dir): void
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($dir)) {
                return;
            }

            if (empty($disk->files($dir)) && empty($disk->directories($dir))) {
                $disk->deleteDirectory($dir);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menghapus direktori kosong ' . $dir . ': ' . $e->getMessage());
        }
    }

    private function formatUkuran(int $byte): string
    {
        if ($byte <= 0) {
            return '0 KB';
        }

        if ($byte < 1024 * 1024) {
            return round($byte / 1024, 1) . ' KB';
        }

        return round($byte / 1024 / 1024, 2) . ' MB';
    }
}
