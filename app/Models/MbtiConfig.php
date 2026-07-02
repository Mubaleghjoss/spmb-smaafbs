<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MbtiConfig extends Model
{
    protected $table = 'mbti_config';

    protected $fillable = [
        'tes_id',
        'dimensi',
        'soal_bagian_1',
        'soal_bagian_2',
        'soal_bagian_3',
        'label_a',
        'label_b',
        'deskripsi_a',
        'deskripsi_b',
    ];

    protected $casts = [
        'soal_bagian_1' => 'array',
        'soal_bagian_2' => 'array',
        'soal_bagian_3' => 'array',
    ];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(Tes::class);
    }

    /**
     * Daftar dimensi MBTI
     */
    public static function dimensiList(): array
    {
        return [
            'EI' => [
                'nama' => 'Extraversion vs Introversion',
                'label_a' => 'E',
                'label_b' => 'I',
                'deskripsi_a' => 'Extraversion - Mendapat energi dari interaksi sosial',
                'deskripsi_b' => 'Introversion - Mendapat energi dari waktu sendiri',
            ],
            'SN' => [
                'nama' => 'Sensing vs iNtuition',
                'label_a' => 'S',
                'label_b' => 'N',
                'deskripsi_a' => 'Sensing - Fokus pada fakta dan detail konkret',
                'deskripsi_b' => 'iNtuition - Fokus pada pola dan kemungkinan',
            ],
            'TF' => [
                'nama' => 'Thinking vs Feeling',
                'label_a' => 'T',
                'label_b' => 'F',
                'deskripsi_a' => 'Thinking - Membuat keputusan berdasarkan logika',
                'deskripsi_b' => 'Feeling - Membuat keputusan berdasarkan nilai dan perasaan',
            ],
            'JP' => [
                'nama' => 'Judging vs Perceiving',
                'label_a' => 'J',
                'label_b' => 'P',
                'deskripsi_a' => 'Judging - Menyukai struktur dan perencanaan',
                'deskripsi_b' => 'Perceiving - Menyukai fleksibilitas dan spontanitas',
            ],
        ];
    }

    /**
     * Default mapping soal MBTI (100 soal)
     * Bagian I: 1-60 (15 soal per dimensi)
     * Bagian II: 61-96 (9 soal per dimensi)
     * Bagian III: 97-100 (1 soal per dimensi)
     */
    public static function defaultMapping(): array
    {
        return [
            'EI' => [
                'soal_bagian_1' => [1, 5, 9, 13, 17, 21, 25, 29, 33, 37, 41, 45, 49, 53, 57],
                'soal_bagian_2' => [61, 65, 69, 73, 77, 81, 85, 89, 93],
                'soal_bagian_3' => [97],
            ],
            'SN' => [
                'soal_bagian_1' => [2, 6, 10, 14, 18, 22, 26, 30, 34, 38, 42, 46, 50, 54, 58],
                'soal_bagian_2' => [62, 66, 70, 74, 78, 82, 86, 90, 94],
                'soal_bagian_3' => [98],
            ],
            'TF' => [
                'soal_bagian_1' => [3, 7, 11, 15, 19, 23, 27, 31, 35, 39, 43, 47, 51, 55, 59],
                'soal_bagian_2' => [63, 67, 71, 75, 79, 83, 87, 91, 95],
                'soal_bagian_3' => [99],
            ],
            'JP' => [
                'soal_bagian_1' => [4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60],
                'soal_bagian_2' => [64, 68, 72, 76, 80, 84, 88, 92, 96],
                'soal_bagian_3' => [100],
            ],
        ];
    }

    /**
     * Daftar 16 tipe MBTI dengan deskripsi default dalam Bahasa Indonesia
     */
    public static function tipeMbtiList(): array
    {
        return [
            'ISTJ' => [
                'nama' => 'Si Perencana (The Inspector)',
                'deskripsi' => 'Pribadi yang bertanggung jawab, teliti, dan sangat dapat diandalkan. Anda menghargai tradisi, loyalitas, dan komitmen. Anda bekerja secara sistematis dan terorganisir, selalu menyelesaikan tugas tepat waktu. Anda adalah orang yang jujur, tegas dalam prinsip, dan menjadi tulang punggung dalam organisasi atau keluarga.',
                'kekuatan' => 'Disiplin, dapat diandalkan, teliti, jujur, bertanggung jawab, pekerja keras',
                'kelemahan' => 'Kaku, sulit menerima perubahan, terlalu serius, kurang ekspresif',
                'karir_cocok' => 'Akuntan, Auditor, Manajer Operasional, Hakim, Polisi, Militer, Administrator'
            ],
            'ISFJ' => [
                'nama' => 'Si Pelindung (The Protector)',
                'deskripsi' => 'Pribadi yang hangat, perhatian, dan sangat setia. Anda memiliki kepedulian tinggi terhadap perasaan dan kebutuhan orang lain. Anda adalah pendengar yang baik dan selalu siap membantu. Anda menghargai harmoni dan stabilitas, serta memiliki ingatan yang kuat tentang detail kehidupan orang-orang terdekat.',
                'kekuatan' => 'Perhatian, setia, sabar, teliti, dapat diandalkan, penolong',
                'kelemahan' => 'Terlalu mengalah, sulit menolak, memendam perasaan, menghindari konflik',
                'karir_cocok' => 'Perawat, Guru SD, Konselor, Pustakawan, Asisten Administrasi, Pekerja Sosial'
            ],
            'INFJ' => [
                'nama' => 'Si Penasihat (The Advocate)',
                'deskripsi' => 'Pribadi yang idealis, visioner, dan penuh inspirasi. Anda memiliki intuisi yang sangat kuat dan mampu memahami orang lain secara mendalam. Anda dipandu oleh nilai-nilai moral yang kuat dan memiliki visi untuk membuat dunia menjadi lebih baik. Anda adalah pemikir yang mendalam dan kreatif.',
                'kekuatan' => 'Intuitif, empatik, visioner, kreatif, berprinsip, inspiratif',
                'kelemahan' => 'Perfeksionis, terlalu idealis, sensitif terhadap kritik, mudah kelelahan',
                'karir_cocok' => 'Psikolog, Penulis, Konselor, Guru, Aktivis Sosial, Seniman, Peneliti'
            ],
            'INTJ' => [
                'nama' => 'Si Ahli Strategi (The Architect)',
                'deskripsi' => 'Pribadi yang strategis, mandiri, dan sangat ambisius. Anda adalah pemikir jangka panjang yang analitis dengan standar tinggi. Anda memiliki kemampuan luar biasa dalam merencanakan dan mengeksekusi ide-ide kompleks. Anda percaya diri dengan visi Anda dan bekerja keras untuk mewujudkannya.',
                'kekuatan' => 'Strategis, mandiri, analitis, percaya diri, inovatif, determinasi tinggi',
                'kelemahan' => 'Arogan, tidak sabar, terlalu kritis, sulit mengekspresikan emosi',
                'karir_cocok' => 'Ilmuwan, Insinyur, Programmer, Pengacara, Manajer Proyek, Konsultan Bisnis'
            ],
            'ISTP' => [
                'nama' => 'Si Pengrajin (The Craftsman)',
                'deskripsi' => 'Pribadi yang praktis, observan, dan sangat analitis. Anda suka memecahkan masalah secara langsung dan hands-on. Anda memiliki kemampuan teknis yang baik dan senang memahami cara kerja sesuatu. Anda tenang dalam situasi krisis dan mampu bertindak cepat dengan efektif.',
                'kekuatan' => 'Praktis, tenang, fleksibel, analitis, efisien, mahir secara teknis',
                'kelemahan' => 'Sulit berkomitmen, kurang ekspresif, tidak suka rutinitas, terlalu mandiri',
                'karir_cocok' => 'Mekanik, Teknisi, Pilot, Atlet, Programmer, Forensik, Ahli Bedah'
            ],
            'ISFP' => [
                'nama' => 'Si Seniman (The Composer)',
                'deskripsi' => 'Pribadi yang sensitif, kreatif, dan penuh kasih sayang. Anda menghargai keindahan dan harmoni dalam segala hal. Anda hidup di saat ini dan menikmati pengalaman sensorik. Anda adalah orang yang hangat dan peduli, meskipun cenderung pendiam dan tidak suka menjadi pusat perhatian.',
                'kekuatan' => 'Kreatif, sensitif, fleksibel, setia, penuh kasih, menghargai keindahan',
                'kelemahan' => 'Terlalu sensitif, menghindari konflik, sulit merencanakan jangka panjang, mudah stres',
                'karir_cocok' => 'Seniman, Desainer, Fotografer, Perawat, Terapis, Chef, Musisi'
            ],
            'INFP' => [
                'nama' => 'Si Mediator (The Mediator)',
                'deskripsi' => 'Pribadi yang idealis, empatik, dan sangat kreatif. Anda dipandu oleh nilai-nilai dan prinsip yang kuat. Anda memiliki imajinasi yang kaya dan kemampuan untuk memahami emosi orang lain secara mendalam. Anda mencari makna dan tujuan dalam segala hal yang Anda lakukan.',
                'kekuatan' => 'Empatik, kreatif, idealis, setia pada nilai, pendengar yang baik, imajinatif',
                'kelemahan' => 'Terlalu idealis, sensitif, sulit mengambil keputusan, menghindari konflik',
                'karir_cocok' => 'Penulis, Psikolog, Konselor, Guru, Seniman, Pekerja Sosial, Jurnalis'
            ],
            'INTP' => [
                'nama' => 'Si Pemikir (The Thinker)',
                'deskripsi' => 'Pribadi yang logis, inovatif, dan sangat ingin tahu. Anda menikmati teori dan ide-ide abstrak. Anda memiliki pikiran yang tajam dan kemampuan analitis yang luar biasa. Anda selalu mencari pemahaman yang lebih dalam tentang bagaimana segala sesuatu bekerja.',
                'kekuatan' => 'Analitis, inovatif, objektif, logis, mandiri, pemecah masalah',
                'kelemahan' => 'Terlalu teoritis, tidak praktis, kurang peka sosial, perfeksionis',
                'karir_cocok' => 'Ilmuwan, Programmer, Matematikawan, Filsuf, Arsitek, Analis Data'
            ],
            'ESTP' => [
                'nama' => 'Si Pengusaha (The Persuader)',
                'deskripsi' => 'Pribadi yang energik, pragmatis, dan sangat observan. Anda suka aksi dan pengalaman langsung. Anda adalah orang yang spontan, berani mengambil risiko, dan pandai membaca situasi. Anda memiliki kemampuan persuasi yang kuat dan senang berada di tengah keramaian.',
                'kekuatan' => 'Energik, adaptif, persuasif, berani, praktis, pandai bersosialisasi',
                'kelemahan' => 'Impulsif, tidak sabar, kurang sensitif, sulit berkomitmen jangka panjang',
                'karir_cocok' => 'Pengusaha, Sales, Marketing, Atlet, Paramedis, Polisi, Event Organizer'
            ],
            'ESFP' => [
                'nama' => 'Si Penghibur (The Performer)',
                'deskripsi' => 'Pribadi yang spontan, energik, dan sangat ramah. Anda menikmati hidup dan senang menghibur orang lain. Anda adalah pusat perhatian yang alami dengan kemampuan untuk membuat suasana menjadi menyenangkan. Anda hidup di saat ini dan menghargai pengalaman baru.',
                'kekuatan' => 'Ramah, spontan, optimis, praktis, mudah bergaul, menghibur',
                'kelemahan' => 'Mudah bosan, kurang fokus, menghindari konflik, impulsif',
                'karir_cocok' => 'Entertainer, Event Planner, Sales, Guru TK, Tour Guide, Aktor, MC'
            ],
            'ENFP' => [
                'nama' => 'Si Juara (The Champion)',
                'deskripsi' => 'Pribadi yang antusias, kreatif, dan sangat optimis. Anda melihat potensi di mana-mana dan mampu menginspirasi orang lain. Anda memiliki energi yang menular dan kemampuan untuk menghubungkan ide-ide yang berbeda. Anda adalah komunikator yang hebat dengan imajinasi yang kaya.',
                'kekuatan' => 'Antusias, kreatif, empatik, optimis, komunikatif, inspiratif',
                'kelemahan' => 'Mudah teralihkan, terlalu idealis, tidak terorganisir, overthinking',
                'karir_cocok' => 'Jurnalis, Konsultan, Psikolog, Guru, Pengusaha, Public Relations, Penulis'
            ],
            'ENTP' => [
                'nama' => 'Si Pendebat (The Debater)',
                'deskripsi' => 'Pribadi yang cerdas, inovatif, dan suka tantangan intelektual. Anda menikmati debat dan eksplorasi ide-ide baru. Anda memiliki pikiran yang cepat dan kemampuan untuk melihat berbagai perspektif. Anda adalah pemikir yang tidak konvensional dan senang mempertanyakan status quo.',
                'kekuatan' => 'Cerdas, inovatif, karismatik, adaptif, visioner, pemecah masalah',
                'kelemahan' => 'Argumentatif, tidak sensitif, sulit fokus, menunda-nunda',
                'karir_cocok' => 'Pengacara, Pengusaha, Konsultan, Ilmuwan, Jurnalis, Politisi, Inventor'
            ],
            'ESTJ' => [
                'nama' => 'Si Eksekutif (The Director)',
                'deskripsi' => 'Pribadi yang terorganisir, logis, dan sangat tegas. Anda adalah pemimpin alami yang efisien dan berorientasi pada hasil. Anda menghargai tradisi, aturan, dan struktur. Anda memiliki kemampuan untuk mengorganisir orang dan sumber daya untuk mencapai tujuan.',
                'kekuatan' => 'Terorganisir, tegas, dapat diandalkan, pekerja keras, pemimpin alami',
                'kelemahan' => 'Kaku, tidak fleksibel, terlalu dominan, kurang peka emosi',
                'karir_cocok' => 'Manajer, Eksekutif, Hakim, Militer, Polisi, Administrator, Bankir'
            ],
            'ESFJ' => [
                'nama' => 'Si Pengasuh (The Caregiver)',
                'deskripsi' => 'Pribadi yang hangat, kooperatif, dan sangat loyal. Anda sangat peduli dengan kebutuhan dan perasaan orang lain. Anda adalah orang yang suka membantu dan menciptakan harmoni dalam kelompok. Anda menghargai tradisi dan memiliki kemampuan sosial yang kuat.',
                'kekuatan' => 'Hangat, loyal, kooperatif, praktis, peduli, pandai bersosialisasi',
                'kelemahan' => 'Terlalu mengkhawatirkan pendapat orang, sensitif terhadap kritik, sulit menerima perubahan',
                'karir_cocok' => 'Perawat, Guru, Konselor, HR Manager, Event Planner, Pekerja Sosial'
            ],
            'ENFJ' => [
                'nama' => 'Si Protagonis (The Giver)',
                'deskripsi' => 'Pribadi yang karismatik, empatik, dan sangat inspiratif. Anda adalah pemimpin yang memotivasi dan memberdayakan orang lain. Anda memiliki kemampuan luar biasa untuk memahami dan menginspirasi orang. Anda dipandu oleh keinginan untuk membantu orang lain mencapai potensi terbaik mereka.',
                'kekuatan' => 'Karismatik, empatik, inspiratif, diplomatis, altruistik, komunikatif',
                'kelemahan' => 'Terlalu idealis, sensitif terhadap kritik, terlalu berkorban, perfeksionis',
                'karir_cocok' => 'Guru, Psikolog, Konselor, Manajer HR, Politisi, Motivator, Pekerja Sosial'
            ],
            'ENTJ' => [
                'nama' => 'Si Komandan (The Commander)',
                'deskripsi' => 'Pribadi yang tegas, strategis, dan sangat ambisius. Anda adalah pemimpin alami yang visioner dengan kemampuan untuk mengorganisir dan memimpin orang menuju tujuan besar. Anda memiliki kepercayaan diri yang tinggi dan tidak takut mengambil keputusan sulit.',
                'kekuatan' => 'Tegas, strategis, percaya diri, efisien, visioner, pemimpin alami',
                'kelemahan' => 'Dominan, tidak sabar, kurang peka emosi, terlalu kritis',
                'karir_cocok' => 'CEO, Eksekutif, Pengusaha, Pengacara, Konsultan, Politisi, Manajer'
            ],
        ];
    }
}
