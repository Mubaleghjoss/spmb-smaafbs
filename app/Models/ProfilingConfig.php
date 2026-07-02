<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfilingConfig extends Model
{
    protected $table = 'profiling_config';

    protected $fillable = [
        'tes_id',
        'aktif',
        'jumlah_soal',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }

    public function mapping(): HasMany
    {
        return $this->hasMany(ProfilingMapping::class, 'tes_id', 'tes_id');
    }

    public function pilarDeskripsi(): HasMany
    {
        return $this->hasMany(ProfilingPilarDeskripsi::class, 'tes_id', 'tes_id');
    }

    /**
     * Daftar pilar dengan informasi lengkap
     */
    public static function pilarList(): array
    {
        return [
            'kreatif' => [
                'nama' => 'Kreatif',
                'kode_qx' => 'CQ',
                'nama_qx' => 'Creativity Quotient',
                'warna' => 'warning',
                'icon' => 'lightbulb',
                'deskripsi' => 'Kemampuan berpikir out of the box dan menghasilkan ide-ide baru yang orisinal. Anda memiliki imajinasi yang kaya dan mampu melihat solusi dari sudut pandang yang berbeda.',
                'kekuatan' => 'Inovatif, imajinatif, mampu berpikir lateral, suka bereksperimen, tidak takut mencoba hal baru',
                'saran_pengembangan' => 'Terus asah kreativitas dengan mencoba hobi baru, belajar seni, atau mengikuti workshop kreatif. Jangan takut untuk mengekspresikan ide-ide unik Anda.',
            ],
            'emosional' => [
                'nama' => 'Emosional',
                'kode_qx' => 'EQ',
                'nama_qx' => 'Emotional Quotient',
                'warna' => 'danger',
                'icon' => 'heart',
                'deskripsi' => 'Kesadaran diri, empati, dan kemampuan mengelola hubungan antarmanusia. Anda peka terhadap perasaan orang lain dan mampu membangun hubungan yang harmonis.',
                'kekuatan' => 'Empatik, peka terhadap perasaan, komunikator yang baik, mampu mengelola emosi, pandai membangun hubungan',
                'saran_pengembangan' => 'Kembangkan kemampuan mendengar aktif, latih kesadaran diri melalui refleksi atau meditasi, dan terus bangun hubungan positif dengan orang-orang di sekitar Anda.',
            ],
            'aksi' => [
                'nama' => 'Aksi',
                'kode_qx' => 'AQ',
                'nama_qx' => 'Adversity Quotient',
                'warna' => 'success',
                'icon' => 'lightning',
                'deskripsi' => 'Ketangguhan menghadapi rintangan dan kemampuan mengubah hambatan menjadi peluang. Anda tidak mudah menyerah dan selalu mencari jalan keluar dari setiap masalah.',
                'kekuatan' => 'Tangguh, pantang menyerah, berorientasi pada solusi, berani mengambil risiko, adaptif terhadap perubahan',
                'saran_pengembangan' => 'Latih ketahanan mental dengan menghadapi tantangan secara bertahap, belajar dari kegagalan, dan tetap fokus pada tujuan jangka panjang.',
            ],
            'logika' => [
                'nama' => 'Logika',
                'kode_qx' => 'IQ',
                'nama_qx' => 'Intelligence Quotient',
                'warna' => 'primary',
                'icon' => 'cpu',
                'deskripsi' => 'Kemampuan kognitif, penalaran abstrak, dan pemecahan masalah teknis. Anda memiliki kemampuan analitis yang kuat dan mampu memproses informasi dengan cepat.',
                'kekuatan' => 'Analitis, logis, sistematis, mampu memecahkan masalah kompleks, cepat memahami konsep baru',
                'saran_pengembangan' => 'Terus asah kemampuan berpikir kritis dengan membaca, memecahkan puzzle, atau belajar hal-hal baru yang menantang secara intelektual.',
            ],
            'spiritual' => [
                'nama' => 'Spiritual',
                'kode_qx' => 'SQ',
                'nama_qx' => 'Spiritual Quotient',
                'warna' => 'info',
                'icon' => 'stars',
                'deskripsi' => 'Pencarian makna, nilai-nilai moral, dan visi hidup jangka panjang. Anda memiliki kesadaran akan tujuan hidup yang lebih besar dan dipandu oleh nilai-nilai yang kuat.',
                'kekuatan' => 'Bermakna, memiliki visi jangka panjang, berprinsip, bijaksana, mampu melihat gambaran besar',
                'saran_pengembangan' => 'Luangkan waktu untuk refleksi diri, perkuat nilai-nilai spiritual melalui ibadah dan kontemplasi, serta terlibat dalam kegiatan yang bermakna bagi orang lain.',
            ],
        ];
    }

    /**
     * Default mapping jawaban untuk 30 soal
     * Setiap jawaban memberikan skor ke pilar:
     * A=Kreatif(CQ), B=Emosional(EQ), C=Aksi(AQ), D=Logika(IQ), E=Spiritual(SQ)
     */
    public static function defaultMapping(): array
    {
        $mapping = [];
        for ($i = 1; $i <= 30; $i++) {
            $mapping[$i] = [
                'a' => 'kreatif',
                'b' => 'emosional',
                'c' => 'aksi',
                'd' => 'logika',
                'e' => 'spiritual',
            ];
        }
        return $mapping;
    }
}
