<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\Soal;
use App\Models\Jawaban;
use App\Models\Topik;
use App\Models\Tes;
use App\Models\TesSoal;
use App\Models\Token;
use App\Models\Grup;
use App\Models\GrupPeserta;
use App\Models\SesiTes;
use App\Models\JawabanPeserta;
use App\Models\Pengguna;
use App\Exceptions\MigrasiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MigrasiService
{
    protected array $laporan = [
        'soal' => ['sukses' => 0, 'gagal' => 0, 'error' => []],
        'jawaban' => ['sukses' => 0, 'gagal' => 0, 'error' => []],
        'topik' => ['sukses' => 0, 'gagal' => 0, 'error' => []],
        'peserta' => ['sukses' => 0, 'gagal' => 0, 'error' => []],
        'grup' => ['sukses' => 0, 'gagal' => 0, 'error' => []],
        'tes' => ['sukses' => 0, 'gagal' => 0, 'error' => []],
        'sesi_tes' => ['sukses' => 0, 'gagal' => 0, 'error' => []],
        'pengguna' => ['sukses' => 0, 'gagal' => 0, 'error' => []],
    ];

    protected string $koneksiLama = 'mysql_lama';
    protected array $mappingId = [
        'topik' => [],
        'soal' => [],
        'jawaban' => [],
        'peserta' => [],
        'grup' => [],
        'tes' => [],
    ];


    /**
     * Jalankan migrasi lengkap dari database lama
     */
    public function migrasiLengkap(): array
    {
        Log::info('Memulai migrasi data dari sistem lama');

        try {
            DB::beginTransaction();

            // Urutan migrasi penting untuk menjaga integritas referensi
            $this->migrasiPengguna();
            $this->migrasiGrup();
            $this->migrasiTopik();
            $this->migrasiSoal();
            $this->migrasiJawaban();
            $this->migrasiPeserta();
            $this->migrasiTes();
            $this->migrasiSesiTes();

            DB::commit();
            Log::info('Migrasi data selesai', $this->laporan);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Migrasi gagal: ' . $e->getMessage());
            $this->laporan['error_umum'] = $e->getMessage();
        }

        return $this->laporan;
    }

    /**
     * Migrasi pengguna admin/operator
     */
    public function migrasiPengguna(): void
    {
        Log::info('Memulai migrasi pengguna');

        $penggunaLama = DB::connection($this->koneksiLama)
            ->table('user')
            ->get();

        foreach ($penggunaLama as $user) {
            try {
                $peran = $this->mapPeran($user->level);

                Pengguna::updateOrCreate(
                    ['email' => $user->username . '@spmb.local'],
                    [
                        'nama' => $user->nama,
                        'password' => Hash::make('password123'), // Password default
                        'peran' => $peran,
                        'aktif' => true,
                    ]
                );

                $this->laporan['pengguna']['sukses']++;
            } catch (\Exception $e) {
                $this->laporan['pengguna']['gagal']++;
                $this->laporan['pengguna']['error'][] = [
                    'id' => $user->id,
                    'nama' => $user->nama,
                    'pesan' => $e->getMessage(),
                ];
                Log::warning("Gagal migrasi pengguna {$user->id}: " . $e->getMessage());
            }
        }
    }


    /**
     * Migrasi grup peserta
     */
    public function migrasiGrup(): void
    {
        Log::info('Memulai migrasi grup');

        $grupLama = DB::connection($this->koneksiLama)
            ->table('cbt_user_grup')
            ->get();

        foreach ($grupLama as $grup) {
            try {
                $grupBaru = Grup::updateOrCreate(
                    ['nama' => $grup->grup_nama],
                    ['keterangan' => 'Migrasi dari sistem lama']
                );

                $this->mappingId['grup'][$grup->grup_id] = $grupBaru->id;
                $this->laporan['grup']['sukses']++;
            } catch (\Exception $e) {
                $this->laporan['grup']['gagal']++;
                $this->laporan['grup']['error'][] = [
                    'id' => $grup->grup_id,
                    'nama' => $grup->grup_nama,
                    'pesan' => $e->getMessage(),
                ];
                Log::warning("Gagal migrasi grup {$grup->grup_id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Migrasi topik/modul soal
     */
    public function migrasiTopik(): void
    {
        Log::info('Memulai migrasi topik');

        $topikLama = DB::connection($this->koneksiLama)
            ->table('cbt_topik')
            ->get();

        foreach ($topikLama as $topik) {
            try {
                $topikBaru = Topik::updateOrCreate(
                    ['nama' => $topik->topik_nama],
                    [
                        'keterangan' => $topik->topik_detail ?? '',
                        'parent_id' => null,
                    ]
                );

                $this->mappingId['topik'][$topik->topik_id] = $topikBaru->id;
                $this->laporan['topik']['sukses']++;
            } catch (\Exception $e) {
                $this->laporan['topik']['gagal']++;
                $this->laporan['topik']['error'][] = [
                    'id' => $topik->topik_id,
                    'nama' => $topik->topik_nama,
                    'pesan' => $e->getMessage(),
                ];
                Log::warning("Gagal migrasi topik {$topik->topik_id}: " . $e->getMessage());
            }
        }
    }


    /**
     * Migrasi soal
     */
    public function migrasiSoal(): void
    {
        Log::info('Memulai migrasi soal');

        $soalLama = DB::connection($this->koneksiLama)
            ->table('cbt_soal')
            ->get();

        foreach ($soalLama as $soal) {
            try {
                $topikId = $this->mappingId['topik'][$soal->soal_topik_id] ?? null;

                if (!$topikId) {
                    throw new \Exception("Topik tidak ditemukan: {$soal->soal_topik_id}");
                }

                $tipe = $this->mapTipeSoal($soal->soal_tipe);

                $soalBaru = Soal::create([
                    'topik_id' => $topikId,
                    'pertanyaan' => $soal->soal_detail,
                    'tipe' => $tipe,
                    'bobot' => 1,
                    'aktif' => (bool) $soal->soal_aktif,
                    'audio' => $soal->soal_audio,
                    'kunci_jawaban' => $soal->soal_kunci,
                ]);

                $this->mappingId['soal'][$soal->soal_id] = $soalBaru->id;
                $this->laporan['soal']['sukses']++;
            } catch (\Exception $e) {
                $this->laporan['soal']['gagal']++;
                $this->laporan['soal']['error'][] = [
                    'id' => $soal->soal_id,
                    'pesan' => $e->getMessage(),
                ];
                Log::warning("Gagal migrasi soal {$soal->soal_id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Migrasi jawaban
     */
    public function migrasiJawaban(): void
    {
        Log::info('Memulai migrasi jawaban');

        $jawabanLama = DB::connection($this->koneksiLama)
            ->table('cbt_jawaban')
            ->get();

        foreach ($jawabanLama as $jawaban) {
            try {
                $soalId = $this->mappingId['soal'][$jawaban->jawaban_soal_id] ?? null;

                if (!$soalId) {
                    // Skip jika soal tidak berhasil dimigrasi
                    continue;
                }

                $jawabanBaru = Jawaban::create([
                    'soal_id' => $soalId,
                    'isi_jawaban' => $jawaban->jawaban_detail,
                    'benar' => (bool) $jawaban->jawaban_benar,
                    'urutan' => 0,
                ]);

                $this->mappingId['jawaban'][$jawaban->jawaban_id] = $jawabanBaru->id;
                $this->laporan['jawaban']['sukses']++;
            } catch (\Exception $e) {
                $this->laporan['jawaban']['gagal']++;
                $this->laporan['jawaban']['error'][] = [
                    'id' => $jawaban->jawaban_id,
                    'pesan' => $e->getMessage(),
                ];
                Log::warning("Gagal migrasi jawaban {$jawaban->jawaban_id}: " . $e->getMessage());
            }
        }
    }


    /**
     * Migrasi peserta
     */
    public function migrasiPeserta(): void
    {
        Log::info('Memulai migrasi peserta');

        $pesertaLama = DB::connection($this->koneksiLama)
            ->table('cbt_user')
            ->get();

        foreach ($pesertaLama as $user) {
            try {
                $nomorPendaftaran = $this->generateNomorPendaftaran();
                $kategoriPendaftaran = app(PeriodePendaftaranService::class)->kategoriDefault();

                $pesertaBaru = Peserta::create([
                    'nomor_pendaftaran' => $nomorPendaftaran,
                    'nama' => $user->user_firstname ?? $user->user_name,
                    'email' => $user->user_email,
                    'username' => $user->user_name,
                    'password' => Hash::make($user->user_password),
                    'telepon' => null,
                    'alamat' => null,
                    'asal_sekolah' => null,
                    'tempat_lahir' => $user->user_birthplace,
                    'tanggal_lahir' => $user->user_birthdate,
                    ...$kategoriPendaftaran,
                ]);

                $this->mappingId['peserta'][$user->user_id] = $pesertaBaru->id;

                // Assign ke grup jika ada
                $grupId = $this->mappingId['grup'][$user->user_grup_id] ?? null;
                if ($grupId) {
                    GrupPeserta::create([
                        'grup_id' => $grupId,
                        'peserta_id' => $pesertaBaru->id,
                    ]);
                }

                $this->laporan['peserta']['sukses']++;
            } catch (\Exception $e) {
                $this->laporan['peserta']['gagal']++;
                $this->laporan['peserta']['error'][] = [
                    'id' => $user->user_id,
                    'nama' => $user->user_firstname ?? $user->user_name,
                    'pesan' => $e->getMessage(),
                ];
                Log::warning("Gagal migrasi peserta {$user->user_id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Migrasi tes
     */
    public function migrasiTes(): void
    {
        Log::info('Memulai migrasi tes');

        $tesLama = DB::connection($this->koneksiLama)
            ->table('cbt_tes')
            ->get();

        foreach ($tesLama as $tes) {
            try {
                $tesBaru = Tes::create([
                    'pengguna_id' => 1, // Admin default
                    'nama' => $tes->tes_nama,
                    'keterangan' => $tes->tes_detail,
                    'durasi_menit' => $tes->tes_duration_time,
                    'nilai_lulus' => 60,
                    'mulai' => $tes->tes_begin_time,
                    'selesai' => $tes->tes_end_time,
                    'acak_soal' => false,
                    'acak_jawaban' => false,
                    'status' => 'selesai',
                    'skor_benar' => $tes->tes_score_right,
                    'skor_salah' => $tes->tes_score_wrong,
                ]);

                $this->mappingId['tes'][$tes->tes_id] = $tesBaru->id;

                // Migrasi soal yang terkait dengan tes
                $this->migrasiTesSoal($tes->tes_id, $tesBaru->id);

                // Migrasi token jika ada
                $this->migrasiToken($tes->tes_id, $tesBaru->id);

                $this->laporan['tes']['sukses']++;
            } catch (\Exception $e) {
                $this->laporan['tes']['gagal']++;
                $this->laporan['tes']['error'][] = [
                    'id' => $tes->tes_id,
                    'nama' => $tes->tes_nama,
                    'pesan' => $e->getMessage(),
                ];
                Log::warning("Gagal migrasi tes {$tes->tes_id}: " . $e->getMessage());
            }
        }
    }


    /**
     * Migrasi soal yang terkait dengan tes
     */
    protected function migrasiTesSoal(int $tesIdLama, int $tesIdBaru): void
    {
        $tesSoalLama = DB::connection($this->koneksiLama)
            ->table('cbt_tes_topik_set')
            ->where('tset_tes_id', $tesIdLama)
            ->get();

        foreach ($tesSoalLama as $tset) {
            // Ambil soal dari topik yang terkait
            $topikIdLama = $tset->tset_topik_id;
            $topikIdBaru = $this->mappingId['topik'][$topikIdLama] ?? null;

            if (!$topikIdBaru) continue;

            $soalDariTopik = Soal::where('topik_id', $topikIdBaru)
                ->where('aktif', true)
                ->limit($tset->tset_jumlah ?? 10)
                ->get();

            $urutan = 1;
            foreach ($soalDariTopik as $soal) {
                TesSoal::updateOrCreate(
                    ['tes_id' => $tesIdBaru, 'soal_id' => $soal->id],
                    ['urutan' => $urutan++]
                );
            }
        }
    }

    /**
     * Migrasi token tes
     */
    protected function migrasiToken(int $tesIdLama, int $tesIdBaru): void
    {
        $tokenLama = DB::connection($this->koneksiLama)
            ->table('cbt_tes_token')
            ->where('token_tes_id', $tesIdLama)
            ->get();

        foreach ($tokenLama as $token) {
            try {
                Token::create([
                    'tes_id' => $tesIdBaru,
                    'kode' => $token->token_isi,
                    'kedaluwarsa' => $token->token_expired ?? now()->addDays(7),
                    'terpakai' => (bool) ($token->token_used ?? false),
                ]);
            } catch (\Exception $e) {
                Log::warning("Gagal migrasi token: " . $e->getMessage());
            }
        }
    }

    /**
     * Migrasi sesi tes (riwayat ujian)
     */
    public function migrasiSesiTes(): void
    {
        Log::info('Memulai migrasi sesi tes');

        $sesiLama = DB::connection($this->koneksiLama)
            ->table('cbt_tes_user')
            ->get();

        foreach ($sesiLama as $sesi) {
            try {
                $tesId = $this->mappingId['tes'][$sesi->tesuser_tes_id] ?? null;
                $pesertaId = $this->mappingId['peserta'][$sesi->tesuser_user_id] ?? null;

                if (!$tesId || !$pesertaId) continue;

                $sesiBaru = SesiTes::create([
                    'tes_id' => $tesId,
                    'peserta_id' => $pesertaId,
                    'token_id' => null,
                    'waktu_mulai' => $sesi->tesuser_start_time,
                    'waktu_selesai' => $sesi->tesuser_end_time,
                    'nilai' => $sesi->tesuser_score ?? 0,
                    'status' => $sesi->tesuser_end_time ? 'selesai' : 'aktif',
                    'urutan_soal' => null,
                ]);

                // Migrasi jawaban peserta
                $this->migrasiJawabanPeserta($sesi->tesuser_id, $sesiBaru->id);

                $this->laporan['sesi_tes']['sukses']++;
            } catch (\Exception $e) {
                $this->laporan['sesi_tes']['gagal']++;
                $this->laporan['sesi_tes']['error'][] = [
                    'id' => $sesi->tesuser_id,
                    'pesan' => $e->getMessage(),
                ];
                Log::warning("Gagal migrasi sesi tes {$sesi->tesuser_id}: " . $e->getMessage());
            }
        }
    }


    /**
     * Migrasi jawaban peserta
     */
    protected function migrasiJawabanPeserta(int $tesuserId, int $sesiTesId): void
    {
        $jawabanLama = DB::connection($this->koneksiLama)
            ->table('cbt_tes_soal')
            ->where('tessoal_tesuser_id', $tesuserId)
            ->get();

        foreach ($jawabanLama as $jawaban) {
            try {
                // Ambil jawaban yang dipilih
                $jawabanDipilih = DB::connection($this->koneksiLama)
                    ->table('cbt_tes_soal_jawaban')
                    ->where('soaljawaban_tessoal_id', $jawaban->tessoal_id)
                    ->first();

                $soalId = $this->mappingId['soal'][$jawaban->tessoal_soal_id] ?? null;
                $jawabanId = $jawabanDipilih 
                    ? ($this->mappingId['jawaban'][$jawabanDipilih->soaljawaban_jawaban_id] ?? null)
                    : null;

                if (!$soalId) continue;

                JawabanPeserta::create([
                    'sesi_tes_id' => $sesiTesId,
                    'soal_id' => $soalId,
                    'jawaban_id' => $jawabanId,
                    'jawaban_esai' => $jawaban->tessoal_essay_answer ?? null,
                    'benar' => (bool) ($jawaban->tessoal_correct ?? false),
                ]);
            } catch (\Exception $e) {
                Log::warning("Gagal migrasi jawaban peserta: " . $e->getMessage());
            }
        }
    }

    /**
     * Map peran dari sistem lama ke sistem baru
     */
    protected function mapPeran(string $level): string
    {
        return match ($level) {
            'admin' => 'admin',
            'operator-soal', 'operator-tes' => 'operator',
            default => 'operator',
        };
    }

    /**
     * Map tipe soal dari sistem lama ke sistem baru
     */
    protected function mapTipeSoal(int $tipe): string
    {
        return match ($tipe) {
            1 => 'pilihan_ganda',
            2 => 'esai',
            3 => 'jawaban_singkat',
            default => 'pilihan_ganda',
        };
    }

    /**
     * Generate nomor pendaftaran unik
     */
    protected function generateNomorPendaftaran(): string
    {
        $tahun = date('Y');
        $prefix = "SPMB-{$tahun}-";
        
        $lastPeserta = Peserta::where('nomor_pendaftaran', 'like', $prefix . '%')
            ->orderBy('nomor_pendaftaran', 'desc')
            ->first();

        if ($lastPeserta) {
            $lastNumber = (int) substr($lastPeserta->nomor_pendaftaran, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Ambil laporan migrasi
     */
    public function ambilLaporan(): array
    {
        return $this->laporan;
    }

    /**
     * Validasi integritas data setelah migrasi
     */
    public function validasiMigrasi(): array
    {
        $hasil = [];

        // Hitung jumlah data di sistem lama
        $jumlahLama = [
            'soal' => DB::connection($this->koneksiLama)->table('cbt_soal')->count(),
            'jawaban' => DB::connection($this->koneksiLama)->table('cbt_jawaban')->count(),
            'topik' => DB::connection($this->koneksiLama)->table('cbt_topik')->count(),
            'peserta' => DB::connection($this->koneksiLama)->table('cbt_user')->count(),
            'grup' => DB::connection($this->koneksiLama)->table('cbt_user_grup')->count(),
            'tes' => DB::connection($this->koneksiLama)->table('cbt_tes')->count(),
            'sesi_tes' => DB::connection($this->koneksiLama)->table('cbt_tes_user')->count(),
        ];

        // Hitung jumlah data di sistem baru
        $jumlahBaru = [
            'soal' => Soal::count(),
            'jawaban' => Jawaban::count(),
            'topik' => Topik::count(),
            'peserta' => Peserta::count(),
            'grup' => Grup::count(),
            'tes' => Tes::count(),
            'sesi_tes' => SesiTes::count(),
        ];

        foreach ($jumlahLama as $tabel => $jumlah) {
            $hasil[$tabel] = [
                'lama' => $jumlah,
                'baru' => $jumlahBaru[$tabel],
                'selisih' => $jumlah - $jumlahBaru[$tabel],
                'status' => $jumlah === $jumlahBaru[$tabel] ? 'OK' : 'PERLU_REVIEW',
            ];
        }

        return $hasil;
    }

    /**
     * Reset mapping ID (untuk testing)
     */
    public function resetMapping(): void
    {
        $this->mappingId = [
            'topik' => [],
            'soal' => [],
            'jawaban' => [],
            'peserta' => [],
            'grup' => [],
            'tes' => [],
        ];
    }

    /**
     * Generate laporan migrasi ke file
     */
    public function generateLaporanFile(): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "laporan_migrasi_{$timestamp}.json";
        $path = storage_path("logs/{$filename}");

        $laporan = [
            'waktu_migrasi' => Carbon::now()->toIso8601String(),
            'ringkasan' => $this->laporan,
            'validasi' => $this->validasiMigrasi(),
            'mapping_id' => [
                'topik_count' => count($this->mappingId['topik']),
                'soal_count' => count($this->mappingId['soal']),
                'jawaban_count' => count($this->mappingId['jawaban']),
                'peserta_count' => count($this->mappingId['peserta']),
                'grup_count' => count($this->mappingId['grup']),
                'tes_count' => count($this->mappingId['tes']),
            ],
        ];

        file_put_contents($path, json_encode($laporan, JSON_PRETTY_PRINT));
        Log::info("Laporan migrasi disimpan ke: {$path}");

        return $path;
    }

    /**
     * Generate laporan migrasi dalam format HTML
     */
    public function generateLaporanHtml(): string
    {
        $html = '<html><head><title>Laporan Migrasi SPMB</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;margin:20px;}';
        $html .= 'table{border-collapse:collapse;width:100%;margin:20px 0;}';
        $html .= 'th,td{border:1px solid #ddd;padding:8px;text-align:left;}';
        $html .= 'th{background-color:#4CAF50;color:white;}';
        $html .= '.success{color:green;}.error{color:red;}</style></head><body>';
        
        $html .= '<h1>Laporan Migrasi Data SPMB</h1>';
        $html .= '<p>Waktu: ' . Carbon::now()->format('d M Y H:i:s') . '</p>';
        
        $html .= '<h2>Ringkasan Migrasi</h2>';
        $html .= '<table><tr><th>Tabel</th><th>Sukses</th><th>Gagal</th><th>Total</th></tr>';
        
        foreach ($this->laporan as $tabel => $data) {
            if ($tabel === 'error_umum') continue;
            $total = ($data['sukses'] ?? 0) + ($data['gagal'] ?? 0);
            $html .= "<tr><td>{$tabel}</td>";
            $html .= "<td class='success'>{$data['sukses']}</td>";
            $html .= "<td class='error'>{$data['gagal']}</td>";
            $html .= "<td>{$total}</td></tr>";
        }
        $html .= '</table>';

        // Validasi
        $validasi = $this->validasiMigrasi();
        $html .= '<h2>Validasi Integritas</h2>';
        $html .= '<table><tr><th>Tabel</th><th>Data Lama</th><th>Data Baru</th><th>Selisih</th><th>Status</th></tr>';
        
        foreach ($validasi as $tabel => $data) {
            $statusClass = $data['status'] === 'OK' ? 'success' : 'error';
            $html .= "<tr><td>{$tabel}</td>";
            $html .= "<td>{$data['lama']}</td>";
            $html .= "<td>{$data['baru']}</td>";
            $html .= "<td>{$data['selisih']}</td>";
            $html .= "<td class='{$statusClass}'>{$data['status']}</td></tr>";
        }
        $html .= '</table>';

        $html .= '</body></html>';

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "laporan_migrasi_{$timestamp}.html";
        $path = storage_path("logs/{$filename}");
        
        file_put_contents($path, $html);
        Log::info("Laporan HTML migrasi disimpan ke: {$path}");

        return $path;
    }
}
