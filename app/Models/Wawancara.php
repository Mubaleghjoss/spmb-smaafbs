<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wawancara extends Model
{
    use HasFactory;

    protected $table = 'wawancara';

    protected $fillable = [
        'peserta_id',
        'tanggal_wawancara',
        'nama_interviewer',
        'kelompok',
        // Wawancara Orang Tua
        'tanggal_wawancara_ortu',
        'interviewer_ortu',
        'jawaban_ortu',
        'catatan_ortu',
        // Wawancara Siswa
        'tanggal_wawancara_siswa',
        'interviewer_siswa',
        'jawaban_siswa',
        'catatan_siswa',
        // Tanda Tangan
        'tanda_tangan_peserta',
        'tanda_tangan_ortu',
        'diisi_peserta_pada',
        // Extended 6-step
        'surat_pernyataan_siswa',
        'surat_pernyataan_ortu',
        'file_tes_pegon',
        'file_voice_quran',
        'surat_quran_random',
        // Verifikasi
        'verifikasi_berkas',
        'catatan_interviewer',
        'hasil_wawancara',
        'diverifikasi_oleh',
        'diverifikasi_pada',
    ];

    protected $casts = [
        'tanggal_wawancara' => 'date',
        'tanggal_wawancara_ortu' => 'date',
        'tanggal_wawancara_siswa' => 'date',
        'jawaban_ortu' => 'array',
        'jawaban_siswa' => 'array',
        'verifikasi_berkas' => 'array',
        'diverifikasi_pada' => 'datetime',
        'diisi_peserta_pada' => 'datetime',
        'surat_pernyataan_siswa' => 'array',
        'surat_pernyataan_ortu' => 'array',
    ];

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diverifikasi_oleh');
    }

    /**
     * Daftar pertanyaan untuk orang tua/wali
     * Ambil dari pengaturan DB, fallback ke default hardcoded
     */
    public static function pertanyaanOrtu(): array
    {
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $custom = $pengaturanService->ambil('wawancara_pertanyaan_ortu');
        
        if ($custom) {
            $decoded = is_string($custom) ? json_decode($custom, true) : $custom;
            if (!empty($decoded)) {
                return $decoded;
            }
        }

        return self::defaultPertanyaanOrtu();
    }

    /**
     * Daftar pertanyaan untuk calon siswa
     * Ambil dari pengaturan DB, fallback ke default hardcoded
     */
    public static function pertanyaanSiswa(): array
    {
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $custom = $pengaturanService->ambil('wawancara_pertanyaan_siswa');
        
        if ($custom) {
            $decoded = is_string($custom) ? json_decode($custom, true) : $custom;
            if (!empty($decoded)) {
                return $decoded;
            }
        }

        return self::defaultPertanyaanSiswa();
    }

    /**
     * Default pertanyaan orang tua
     */
    public static function defaultPertanyaanOrtu(): array
    {
        return [
            1 => 'Kegiatan Sehari-hari anda? apakah bekerja atau usaha? Pekerjaannya atau usahanya apa?',
            2 => 'Mengapa anda mendaftarkan anak anda ke SMA AFBS? Apakah keinginan dari orangtua ataukah dari anak?',
            3 => 'Jelaskan secara singkat seputar keluarga (Jumlah dan Kondisi), tempat sambung, dapukan, dan domisili.',
            4 => 'Apakah ada pelanggaran yang dilakukan anak anda di rumah atau sekolah sebelumnya? apakah anda mengetahuinya?',
            5 => 'Prestasi atau hobi apa yang anda ketahui dari anak anda.',
            6 => 'Apakah anak anda memiliki riwayat penyakit bawaan atau yang pernah diderita? serta apakah dia memiliki pantangan dalam makanan?',
            7 => 'Apakah anda sebagai orang tua sanggup untuk membiayai anak anda selama bersekolah di SMA AFBS?',
            8 => 'Sebagai tanda ikatan kontrak belajar, apakah anda sebagai siswa dan orang tua bersedia mengisi dan menandatangani surat pernyataan?',
        ];
    }

    /**
     * Default pertanyaan siswa
     */
    public static function defaultPertanyaanSiswa(): array
    {
        return [
            1 => 'Apa tujuan dan motivasi anda ingin sekolah di SMA AFBS?',
            2 => 'Siapa yang mendorong anda untuk bersekolah di SMA AFBS? Apakah karena kemauan sendiri ataukah karena kemauan orang tua atau pula karena orang lain (teman, saudara, dll)?',
            3 => 'Apakah anda pernah mengikuti kompetisi atau kegiatan yang menghasilkan prestasi buat anda?',
            4 => 'Apakah anda punya Riwayat penyakit yang sering kambuh atau belum sembuh? Atau pernah mengalami penyakit berat?',
            5 => 'Boleh sebutkan alamat sambung (masjid) anda? Siapa nama Bapak KI kelompok anda? Sebutkan 5 bab?',
            6 => 'Apakah di sekolah SMP lalu pernah menjumpai hal-hal yang menjadikan ketidak nyamanan untuk anda? (misal pembulian / pelanggaran / kena sanksi dll)',
            7 => 'Sebagai tanda ikatan kontrak belajar, apakah anda sebagai siswa bersedia mengisi dan menandatangani surat pernyataan?',
        ];
    }

    /**
     * Daftar berkas yang perlu diverifikasi
     */
    public static function daftarBerkas(): array
    {
        return [
            'kk' => 'Kartu Keluarga (KK)',
            'akta' => 'Akta Kelahiran',
            'ijazah' => 'Ijazah/SKL',
            'bpjs' => 'Kartu BPJS/KIS',
            'ktp_ayah' => 'KTP Ayah',
            'ktp_ibu' => 'KTP Ibu',
            'foto' => 'Pas Foto 3x4',
        ];
    }

    /**
     * Poin surat pernyataan siswa
     * Ambil dari pengaturan DB, fallback ke default hardcoded
     */
    public static function suratPernyataanSiswaPoin(): array
    {
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $custom = $pengaturanService->ambil('surat_pernyataan_siswa_poin');
        
        if ($custom) {
            $decoded = is_string($custom) ? json_decode($custom, true) : $custom;
            if (!empty($decoded)) {
                return $decoded;
            }
        }

        return self::defaultSuratPernyataanSiswaPoin();
    }

    /**
     * Poin surat pernyataan orangtua
     */
    public static function suratPernyataanOrtuPoin(): array
    {
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $custom = $pengaturanService->ambil('surat_pernyataan_ortu_poin');
        
        if ($custom) {
            $decoded = is_string($custom) ? json_decode($custom, true) : $custom;
            if (!empty($decoded)) {
                return $decoded;
            }
        }

        return self::defaultSuratPernyataanOrtuPoin();
    }

    /**
     * Soal tes pegon
     */
    public static function teksPegon(): array
    {
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $custom = $pengaturanService->ambil('teks_ujian_pegon');
        
        if ($custom) {
            $decoded = is_string($custom) ? json_decode($custom, true) : $custom;
            if (!empty($decoded)) {
                return $decoded;
            }
        }

        return self::defaultTeksPegon();
    }

    /**
     * Default poin surat pernyataan siswa
     */
    public static function defaultSuratPernyataanSiswaPoin(): array
    {
        return [
            1 => 'Bersedia menaati, mengikuti dan melaksanakan Tata Tertib yang telah ditetapkan, baik Peraturan Akademik & Non Akademik SMA AFBS yang dikelola oleh Lembaga Dakwah Islam Indonesia (LDII) melalui Yayasan Dar Al Furqon Al Hakim. Bersedia tinggal dan berkegiatan di Asrama.',
            2 => 'Bersedia tidak dijenguk dan pulang selama 1 (satu) bulan awal sekolah, dengan ketentuan sebagai berikut:
a. Setelah 1 bulan boleh dijenguk hanya pada hari Minggu jam 09.00 s.d 15.00 (Minggu ke-1 & ke-3).
b. Pulang hanya pada saat hari libur yang sudah ditentukan.
c. Izin pulang selain yang diatur poin (b) hanya diberikan untuk hal-hal yang bersifat darurat dan dapat dibuktikan.
d. Pengajuan izin melalui Waka Boarding dan Waka Kesiswaan.',
            3 => 'Apabila sakit bersedia menerima perawatan yang diberikan oleh pihak sekolah maksimal 3 hari, dengan ketentuan berikut:
a. Pemberitahuan sakit kepada orang tua, minimal 3 x 24 jam atau melihat kondisi siswa/i.
b. Siswa/i diizinkan pulang apabila dibutuhkan penanganan khusus.
c. Memberi informasi yang jujur terkait riwayat kesehatan siswa/i.',
            4 => 'Apabila terjadi perselisihan/permasalahan, bersedia diselesaikan secara kekeluargaan dan tanpa melibatkan pihak lain.',
            5 => 'Bersedia menerima sanksi apabila melanggar Peraturan yang sudah ditetapkan, baik sanksi secara lisan maupun tertulis bahkan dikeluarkan dari SMA AFBS.',
        ];
    }

    /**
     * Default poin surat pernyataan orangtua
     */
    public static function defaultSuratPernyataanOrtuPoin(): array
    {
        return [
            1 => 'Pelunasan biaya pendaftaran SPMB sebesar Rp 1.950.000,- paling lambat tanggal 1 Desember 2025.',
            2 => 'Jika biaya pada poin 1 tidak dapat terpenuhi dalam batas waktu yang telah ditetapkan maka siswa tersebut dianggap mengundurkan diri dari SPMB SMA AFBS dan kuotanya akan diberikan kepada pihak lain.',
            3 => 'Biaya sebesar Rp 150.000,- yang telah diberikan sebagai biaya formulir pendaftaran tidak dapat dikembalikan.',
            4 => 'Bersedia membayar SPP Bulanan sebesar Rp 1.500.000,- secara tertib paling lambat tanggal 5 setiap bulannya.',
            5 => 'Bersedia membayar biaya tahunan sebesar Rp 3.600.000,- per tahun.',
            6 => 'Kami siap mengikuti aturan sekolah SMA Al Furqon Boarding School, pondok dan yayasan serta siap menerima konsekuensinya.',
        ];
    }

    /**
     * Default teks/soal tes pegon
     */
    public static function defaultTeksPegon(): array
    {
        return [
            1 => "Assalamu'alaikum Warohmatullahi Wabarokatuh",
            2 => 'Bismillahirrohmanirrohim',
            3 => "Alhamdulilllahirobbil 'Alamin",
            4 => 'Ada 7 dosa yang merusak amal :',
            5 => 'Syirik kepada Allah',
            6 => 'Sihir',
            7 => 'Membunuh jiwa yang telah di haromkan Allah kecuali yang hak',
            8 => 'Makan riba',
            9 => 'Makan harta anak yatim',
            10 => 'Berpaling ( lari ) pada hari perang',
            11 => 'Menuduh zina pada perempuan yang terjaga lagi lupa ( tidak ada keinginan untuk berbuat zina )',
        ];
    }
}
