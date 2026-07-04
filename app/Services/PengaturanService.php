<?php

namespace App\Services;

use App\Models\Pengaturan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Service untuk manajemen pengaturan sistem
 * Kebutuhan: 8.1, 8.2, 8.4
 */
class PengaturanService
{
    private const CACHE_KEY = 'pengaturan_sistem';
    private const CACHE_TTL = 3600; // 1 jam

    /**
     * Ambil semua pengaturan
     */
    public function ambilSemua(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Pengaturan::pluck('nilai', 'kunci')->toArray();
        });
    }

    /**
     * Ambil nilai pengaturan
     */
    public function ambil(string $kunci, mixed $default = null): mixed
    {
        $pengaturan = $this->ambilSemua();
        return $pengaturan[$kunci] ?? $default;
    }

    /**
     * Simpan pengaturan
     */
    public function simpan(string $kunci, mixed $nilai): Pengaturan
    {
        $pengaturan = Pengaturan::updateOrCreate(
            ['kunci' => $kunci],
            ['nilai' => $nilai]
        );

        $this->hapusCache();

        return $pengaturan;
    }

    /**
     * Simpan banyak pengaturan sekaligus
     */
    public function simpanBanyak(array $data): void
    {
        foreach ($data as $kunci => $nilai) {
            Pengaturan::updateOrCreate(
                ['kunci' => $kunci],
                ['nilai' => $nilai]
            );
        }

        $this->hapusCache();
    }

    /**
     * Hapus pengaturan
     */
    public function hapus(string $kunci): bool
    {
        $deleted = Pengaturan::where('kunci', $kunci)->delete();
        $this->hapusCache();
        return $deleted > 0;
    }

    /**
     * Hapus cache pengaturan
     */
    public function hapusCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Ambil pengaturan branding
     * Kebutuhan: 8.1
     */
    public function ambilBranding(): array
    {
        return [
            'nama_institusi' => $this->ambil('nama_institusi', 'SMA AL FURQON BOARDING SCHOOL'),
            'nama_singkat' => $this->ambil('nama_singkat', 'SPMB'),
            'alamat' => $this->ambil('alamat', ''),
            'telepon' => $this->ambil('telepon', ''),
            'email' => $this->ambil('email', ''),
            'website' => $this->ambil('website', ''),
            'logo' => $this->ambil('logo', ''),
            'favicon' => $this->ambil('favicon', ''),
            'warna_primer' => $this->ambil('warna_primer', '#0d6efd'),
            'warna_sekunder' => $this->ambil('warna_sekunder', '#6c757d'),
            'tahun_ajaran' => $this->ambil('tahun_ajaran', date('Y') . '/' . (date('Y') + 1)),
            // Teks Halaman Beranda
            'teks_hero' => $this->ambil('teks_hero', 'Bergabunglah bersama kami untuk menjadi generasi Qurani yang berakhlak mulia, berprestasi, dan siap menghadapi tantangan masa depan.'),
            'teks_cta' => $this->ambil('teks_cta', 'Daftarkan diri Anda sekarang dan mulai perjalanan menuju masa depan yang cerah'),
            // Teks Halaman Alur SPMB
            'teks_alur_spmb' => $this->ambil('teks_alur_spmb', 'Ikuti setiap tahapan untuk menjadi bagian dari keluarga besar'),
        ];
    }

    /**
     * Simpan pengaturan branding
     */
    public function simpanBranding(array $data): void
    {
        $allowedKeys = [
            'nama_institusi', 'nama_singkat', 'alamat', 'telepon',
            'email', 'website', 'warna_primer', 'warna_sekunder',
            'teks_hero', 'teks_cta', 'teks_alur_spmb'
        ];

        $filtered = array_intersect_key($data, array_flip($allowedKeys));
        $this->simpanBanyak($filtered);
    }

    /**
     * Upload dan simpan logo
     */
    public function uploadLogo(UploadedFile $file): string
    {
        // Hapus logo lama
        $logoLama = $this->ambil('logo');
        if ($logoLama && Storage::disk('public')->exists($logoLama)) {
            Storage::disk('public')->delete($logoLama);
        }

        // Upload logo baru
        $path = $file->store('branding', 'public');
        $this->simpan('logo', $path);

        return $path;
    }

    /**
     * Upload dan simpan favicon
     */
    public function uploadFavicon(UploadedFile $file): string
    {
        $faviconLama = $this->ambil('favicon');
        if ($faviconLama && Storage::disk('public')->exists($faviconLama)) {
            Storage::disk('public')->delete($faviconLama);
        }

        $path = $file->store('branding', 'public');
        $this->simpan('favicon', $path);

        return $path;
    }

    /**
     * Ambil pengaturan email/SMTP
     * Kebutuhan: 8.2
     */
    public function ambilEmail(): array
    {
        return [
            'mail_driver' => $this->ambil('mail_driver', 'smtp'),
            'mail_host' => $this->ambil('mail_host', ''),
            'mail_port' => $this->ambil('mail_port', '587'),
            'mail_username' => $this->ambil('mail_username', ''),
            'mail_password' => $this->ambil('mail_password', ''),
            'mail_encryption' => $this->ambil('mail_encryption', 'tls'),
            'mail_from_address' => $this->ambil('mail_from_address', ''),
            'mail_from_name' => $this->ambil('mail_from_name', ''),
        ];
    }

    /**
     * Simpan pengaturan email
     */
    public function simpanEmail(array $data): void
    {
        $allowedKeys = [
            'mail_driver', 'mail_host', 'mail_port', 'mail_username',
            'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name'
        ];

        $filtered = array_intersect_key($data, array_flip($allowedKeys));
        $this->simpanBanyak($filtered);
    }

    /**
     * Test koneksi SMTP
     */
    public function testKoneksiSmtp(): array
    {
        $config = $this->ambilEmail();

        try {
            // Update config runtime
            config([
                'mail.mailers.smtp.host' => $config['mail_host'],
                'mail.mailers.smtp.port' => $config['mail_port'],
                'mail.mailers.smtp.username' => $config['mail_username'],
                'mail.mailers.smtp.password' => $config['mail_password'],
                'mail.mailers.smtp.encryption' => $config['mail_encryption'],
            ]);

            // Test dengan membuat transport
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $config['mail_host'],
                (int) $config['mail_port']
            );

            return [
                'sukses' => true,
                'pesan' => 'Koneksi SMTP berhasil.',
            ];
        } catch (\Exception $e) {
            return [
                'sukses' => false,
                'pesan' => 'Gagal koneksi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil pengaturan SPMB
     */
    public function ambilSpmb(): array
    {
        return [
            'pendaftaran_buka' => $this->ambil('pendaftaran_buka', true),
            'tanggal_buka' => $this->ambil('tanggal_buka', ''),
            'tanggal_tutup' => $this->ambil('tanggal_tutup', ''),
            'biaya_formulir' => $this->ambil('biaya_formulir', 0),
            'biaya_pelunasan' => $this->ambil('biaya_pelunasan', 0),
            'rekening_bank' => $this->ambil('rekening_bank', 'BSI'),
            'nomor_rekening' => $this->ambil('nomor_rekening', ''),
            'nama_rekening' => $this->ambil('nama_rekening', ''),
            'whatsapp_spmb' => $this->ambil('whatsapp_spmb', ''),
            'kontak_tim_spmb' => $this->ambil('kontak_tim_spmb', '[]'),
        ];
    }

    /**
     * Ambil daftar kontak Tim SPMB
     */
    public function ambilKontakTimSpmb(): array
    {
        $kontakJson = $this->ambil('kontak_tim_spmb', '[]');
        $kontak = json_decode($kontakJson, true) ?: [];
        
        // Migrasi dari format lama jika ada
        if (empty($kontak)) {
            $waLama = $this->ambil('whatsapp_spmb', '');
            if (!empty($waLama)) {
                $kontak = [['nama' => 'Tim SPMB', 'whatsapp' => $waLama]];
            }
        }
        
        return $kontak;
    }

    /**
     * Simpan pengaturan SPMB
     */
    public function simpanSpmb(array $data): void
    {
        $allowedKeys = [
            'pendaftaran_buka', 'tanggal_buka', 'tanggal_tutup',
            'biaya_formulir', 'biaya_pelunasan',
            'rekening_bank', 'nomor_rekening', 'nama_rekening',
            'whatsapp_spmb', 'kontak_tim_spmb'
        ];

        $filtered = array_intersect_key($data, array_flip($allowedKeys));
        $this->simpanBanyak($filtered);
    }

    /**
     * Ambil pengaturan ujian
     */
    public function ambilUjian(): array
    {
        return [
            'durasi_default' => $this->ambil('durasi_default', 60),
            'nilai_lulus_default' => $this->ambil('nilai_lulus_default', 60),
            'acak_soal_default' => $this->ambil('acak_soal_default', true),
            'acak_jawaban_default' => $this->ambil('acak_jawaban_default', true),
            'tampilkan_nilai' => $this->ambil('tampilkan_nilai', true),
            'tampilkan_pembahasan' => $this->ambil('tampilkan_pembahasan', false),
            'ujian_dibuka' => (bool) $this->ambil('ujian_dibuka', true),
            'ujian_tanggal_buka' => $this->ambil('ujian_tanggal_buka', ''),
            'ujian_waktu_buka' => $this->ambil('ujian_waktu_buka', ''),
            'ujian_tanggal_tutup' => $this->ambil('ujian_tanggal_tutup', ''),
            'ujian_waktu_tutup' => $this->ambil('ujian_waktu_tutup', ''),
        ];
    }

    /**
     * Status akses global tes online berdasarkan pengaturan ujian.
     */
    public function statusAksesUjian(?array $ujian = null): array
    {
        $ujian ??= $this->ambilUjian();
        $mulai = $this->gabungkanTanggalWaktuUjian(
            $ujian['ujian_tanggal_buka'] ?? '',
            $ujian['ujian_waktu_buka'] ?? '',
            false
        );
        $selesai = $this->gabungkanTanggalWaktuUjian(
            $ujian['ujian_tanggal_tutup'] ?? '',
            $ujian['ujian_waktu_tutup'] ?? '',
            true
        );

        $result = [
            'dibuka' => true,
            'alasan' => null,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'mulai_label' => $mulai ? $this->formatTanggalWaktuUjian($mulai) : null,
            'selesai_label' => $selesai ? $this->formatTanggalWaktuUjian($selesai) : null,
        ];

        if (!($ujian['ujian_dibuka'] ?? true)) {
            $result['dibuka'] = false;
            $result['alasan'] = 'Tes online sedang ditutup oleh admin.';
            return $result;
        }

        $now = now();

        if ($mulai && $now->lt($mulai)) {
            $result['dibuka'] = false;
            $result['alasan'] = 'Tes online dibuka pada ' . $result['mulai_label'] . '.';
            return $result;
        }

        if ($selesai && $now->gt($selesai)) {
            $result['dibuka'] = false;
            $result['alasan'] = 'Tes online ditutup pada ' . $result['selesai_label'] . '.';
            return $result;
        }

        return $result;
    }

    /**
     * Simpan pengaturan ujian
     */
    public function simpanUjian(array $data): void
    {
        $allowedKeys = [
            'durasi_default', 'nilai_lulus_default',
            'acak_soal_default', 'acak_jawaban_default',
            'tampilkan_nilai', 'tampilkan_pembahasan',
            'ujian_dibuka', 'ujian_tanggal_buka', 'ujian_waktu_buka',
            'ujian_tanggal_tutup', 'ujian_waktu_tutup',
        ];

        $filtered = array_intersect_key($data, array_flip($allowedKeys));
        $this->simpanBanyak($filtered);
    }

    private function gabungkanTanggalWaktuUjian(?string $tanggal, ?string $waktu, bool $akhirHari): ?Carbon
    {
        if (empty($tanggal)) {
            return null;
        }

        $jam = $waktu ?: ($akhirHari ? '23:59:59' : '00:00:00');

        return Carbon::parse(trim($tanggal . ' ' . $jam));
    }

    private function formatTanggalWaktuUjian(Carbon $tanggal): string
    {
        return $tanggal->locale('id')->translatedFormat('d F Y H:i') . ' WIB';
    }

    /**
     * Ekspor semua pengaturan ke array
     * Kebutuhan: 8.5
     */
    public function eksporKonfigurasi(): array
    {
        return [
            'versi' => '1.0',
            'tanggal_ekspor' => now()->toIso8601String(),
            'branding' => $this->ambilBranding(),
            'email' => $this->ambilEmail(),
            'spmb' => $this->ambilSpmb(),
            'ujian' => $this->ambilUjian(),
        ];
    }

    /**
     * Impor konfigurasi dari array
     * Kebutuhan: 8.6
     */
    public function imporKonfigurasi(array $data): void
    {
        if (isset($data['branding'])) {
            $this->simpanBranding($data['branding']);
        }

        if (isset($data['email'])) {
            $this->simpanEmail($data['email']);
        }

        if (isset($data['spmb'])) {
            $this->simpanSpmb($data['spmb']);
        }

        if (isset($data['ujian'])) {
            $this->simpanUjian($data['ujian']);
        }
    }

    /**
     * Ambil pengaturan tahapan SPMB
     */
    public function ambilPengaturanTahapan(): array
    {
        $spmb = $this->ambilSpmb();

        return [
            'tahap_2' => [
                'dibuka' => $spmb['pendaftaran_buka'] ?? true,
                'tanggal_buka' => $spmb['tanggal_buka'] ?? '',
                'waktu_mulai' => $this->ambil('tahap_2_waktu_mulai', ''),
                'tanggal_tutup' => $spmb['tanggal_tutup'] ?? '',
                'waktu_selesai' => $this->ambil('tahap_2_waktu_selesai', ''),
                'keterangan' => $this->ambil('tahap_2_keterangan', 'Isi Formulir SPMB mengikuti jadwal pendaftaran.'),
                'otomatis' => true,
            ],
            'tahap_3' => [
                'dibuka' => $this->ambil('tahap_3_dibuka', true),
                'tanggal_buka' => $this->ambil('tahap_3_tanggal_buka', ''),
                'waktu_mulai' => $this->ambil('tahap_3_waktu_mulai', ''),
                'tanggal_tutup' => $this->ambil('tahap_3_tanggal_tutup', ''),
                'waktu_selesai' => $this->ambil('tahap_3_waktu_selesai', ''),
                'keterangan' => $this->ambil('tahap_3_keterangan', ''),
            ],
            'tahap_4' => [
                'tanggal_buka' => $this->ambil('tahap_4_tanggal_buka', ''),
                'tanggal_tutup' => $this->ambil('tahap_4_tanggal_tutup', ''),
                'keterangan' => $this->ambil('tahap_4_keterangan', ''),
            ],
            'tahap_5' => [
                'tanggal_buka' => $this->ambil('tahap_5_tanggal_buka', ''),
                'tanggal_tutup' => $this->ambil('tahap_5_tanggal_tutup', ''),
                'waktu_mulai' => $this->ambil('tahap_5_waktu_mulai', ''),
                'waktu_selesai' => $this->ambil('tahap_5_waktu_selesai', ''),
                'lokasi' => $this->ambil('tahap_5_lokasi', ''),
                'keterangan' => $this->ambil('tahap_5_keterangan', ''),
            ],
            'tahap_6' => [
                'tanggal_buka' => $this->ambil('tahap_6_tanggal_buka', ''),
                'tanggal_tutup' => $this->ambil('tahap_6_tanggal_tutup', ''),
                'keterangan' => $this->ambil('tahap_6_keterangan', ''),
            ],
            'tahap_7' => [
                'tanggal_buka' => $this->ambil('tahap_7_tanggal_buka', ''),
                'tanggal_tutup' => $this->ambil('tahap_7_tanggal_tutup', ''),
                'keterangan' => $this->ambil('tahap_7_keterangan', ''),
                'keterangan_lulus' => $this->ambil('tahap_7_keterangan_lulus', ''),
                'keterangan_tidak_lulus' => $this->ambil('tahap_7_keterangan_tidak_lulus', ''),
            ],
        ];
    }

    /**
     * Simpan pengaturan tahapan SPMB
     */
    public function simpanPengaturanTahapan(array $data): void
    {
        foreach ($data as $tahap => $config) {
            if (is_array($config)) {
                foreach ($config as $key => $value) {
                    $this->simpan("{$tahap}_{$key}", $value);
                }
            }
        }
    }

    /**
     * Upload surat keterangan kelulusan
     */
    public function uploadSuratKelulusan(UploadedFile $file): string
    {
        // Hapus file lama
        $fileLama = $this->ambil('surat_kelulusan');
        if ($fileLama && Storage::disk('public')->exists($fileLama)) {
            Storage::disk('public')->delete($fileLama);
        }

        // Upload file baru
        $path = $file->store('spmb/surat-kelulusan', 'public');
        $this->simpan('surat_kelulusan', $path);

        return $path;
    }

    /**
     * Ambil daftar SK kelulusan per gelombang.
     */
    public function ambilSuratKelulusanGelombang(): array
    {
        $json = $this->ambil('surat_kelulusan_gelombang', '[]');
        $items = json_decode($json, true) ?: [];

        if (empty($items)) {
            $suratLama = $this->ambil('surat_kelulusan', '');
            if (!empty($suratLama)) {
                $items[] = [
                    'id' => 'default',
                    'nama' => 'Umum',
                    'file' => $suratLama,
                ];
            }
        }

        return collect($items)
            ->filter(fn($item) => !empty($item['nama']) && !empty($item['file']))
            ->map(function ($item) {
                return [
                    'id' => $item['id'] ?? (string) Str::uuid(),
                    'nama' => $item['nama'],
                    'file' => $item['file'],
                    'uploaded_at' => $item['uploaded_at'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Simpan daftar SK kelulusan per gelombang.
     */
    public function simpanSuratKelulusanGelombang(array $items): void
    {
        $normalized = collect($items)
            ->filter(fn($item) => !empty($item['nama']) && !empty($item['file']))
            ->map(function ($item) {
                return [
                    'id' => $item['id'] ?? (string) Str::uuid(),
                    'nama' => trim($item['nama']),
                    'file' => $item['file'],
                    'uploaded_at' => !empty($item['uploaded_at']) ? $item['uploaded_at'] : now()->toDateTimeString(),
                ];
            })
            ->values()
            ->all();

        $this->simpan('surat_kelulusan_gelombang', json_encode($normalized, JSON_UNESCAPED_UNICODE));

        if (!empty($normalized[0]['file'])) {
            $this->simpan('surat_kelulusan', $normalized[0]['file']);
        }
    }

    /**
     * Upload SK kelulusan untuk satu gelombang dengan nama file stabil.
     */
    public function uploadSuratKelulusanGelombang(UploadedFile $file, string $namaGelombang): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf');
        $slugGelombang = Str::upper(Str::slug($namaGelombang, '-')) ?: 'GELOMBANG';
        $filename = 'SK-SPMB-SMAAFBS-' . $slugGelombang . '-' . now()->format('YmdHis') . '.' . $extension;

        return $file->storeAs('spmb/surat-kelulusan', $filename, 'public');
    }

    /**
     * Ambil SK kelulusan berdasarkan gelombang yang dipilih peserta.
     */
    public function ambilSuratKelulusanUntukGelombang(?string $gelombangId): ?array
    {
        $items = $this->ambilSuratKelulusanGelombang();

        if (!empty($gelombangId)) {
            $selected = collect($items)->firstWhere('id', $gelombangId);
            if ($selected) {
                return $selected;
            }
        }

        return $items[0] ?? null;
    }

    /**
     * Ambil pengaturan kelulusan
     */
    public function ambilPengaturanKelulusan(): array
    {
        return [
            'keterangan_lulus' => $this->ambil('tahap_7_keterangan_lulus', ''),
            'keterangan_tidak_lulus' => $this->ambil('tahap_7_keterangan_tidak_lulus', ''),
            'surat_kelulusan' => $this->ambil('surat_kelulusan', ''),
            'surat_kelulusan_gelombang' => $this->ambilSuratKelulusanGelombang(),
            'judul_lulus' => $this->ambil('kelulusan_judul_lulus', 'Selamat Bergabung!'),
            'teks_lulus' => $this->ambil('kelulusan_teks_lulus', 'Anda resmi diterima sebagai peserta didik baru'),
            'warna_lulus' => $this->ambil('kelulusan_warna_lulus', '#198754'),
            'judul_tidak_lulus' => $this->ambil('kelulusan_judul_tidak_lulus', 'Pengumuman Kelulusan'),
            'teks_tidak_lulus' => $this->ambil('kelulusan_teks_tidak_lulus', 'Maaf, Anda belum diqodar menjadi peserta didik'),
            'warna_tidak_lulus' => $this->ambil('kelulusan_warna_tidak_lulus', '#dc3545'),
        ];
    }

    /**
     * Simpan pengaturan kelulusan
     */
    public function simpanPengaturanKelulusan(array $data): void
    {
        $allowedKeys = [
            'kelulusan_judul_lulus', 'kelulusan_teks_lulus', 'kelulusan_warna_lulus',
            'kelulusan_judul_tidak_lulus', 'kelulusan_teks_tidak_lulus', 'kelulusan_warna_tidak_lulus'
        ];

        $filtered = array_intersect_key($data, array_flip($allowedKeys));
        $this->simpanBanyak($filtered);
    }

    /**
     * Ambil syarat dan ketentuan SPMB
     */
    public function ambilSyaratKetentuan(): array
    {
        $json = $this->ambil('syarat_ketentuan', '[]');
        $bagian = json_decode($json, true) ?: [];
        
        // Jika kosong, kembalikan default
        if (empty($bagian)) {
            $bagian = $this->defaultSyaratKetentuan();
        }
        
        return $bagian;
    }

    /**
     * Simpan syarat dan ketentuan SPMB
     */
    public function simpanSyaratKetentuan(array $bagian): void
    {
        $this->simpan('syarat_ketentuan', json_encode($bagian, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Ambil template kwitansi
     */
    public function ambilTemplateKwitansi(): array
    {
        $json = $this->ambil('template_kwitansi', '[]');
        $template = json_decode($json, true) ?: [];
        
        // Jika kosong, kembalikan default
        if (empty($template)) {
            $template = $this->defaultTemplateKwitansi();
        }
        
        return $template;
    }

    /**
     * Simpan template kwitansi
     */
    public function simpanTemplateKwitansi(array $data): void
    {
        $this->simpan('template_kwitansi', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Upload gambar untuk kwitansi (logo, watermark, stempel)
     */
    public function uploadGambarKwitansi(UploadedFile $file, string $jenis): string
    {
        $allowedJenis = ['logo', 'watermark', 'stempel'];
        if (!in_array($jenis, $allowedJenis)) {
            throw new \InvalidArgumentException("Jenis gambar tidak valid: {$jenis}");
        }

        // Ambil template saat ini
        $template = $this->ambilTemplateKwitansi();
        $pathKey = $jenis . '_path';

        // Hapus file lama jika ada
        if (!empty($template[$pathKey]) && Storage::disk('public')->exists($template[$pathKey])) {
            Storage::disk('public')->delete($template[$pathKey]);
        }

        // Upload file baru
        $path = $file->store('kwitansi', 'public');

        // Update template
        $template[$pathKey] = $path;
        $this->simpanTemplateKwitansi($template);

        return $path;
    }

    /**
     * Default template kwitansi
     */
    private function defaultTemplateKwitansi(): array
    {
        $branding = $this->ambilBranding();
        
        return [
            // Informasi Institusi
            'nama_institusi' => $branding['nama_institusi'] ?? 'SMA AL FURQON BOARDING SCHOOL',
            'alamat' => $branding['alamat'] ?? '',
            'telepon' => $branding['telepon'] ?? '',
            
            // Judul dan Teks
            'judul_kwitansi' => 'KWITANSI PEMBAYARAN FORMULIR SPMB',
            'teks_footer' => 'Terima kasih atas pembayaran Anda. Kwitansi ini merupakan bukti pembayaran yang sah.',
            
            // Penandatangan
            'nama_penandatangan' => 'Bendahara SPMB',
            'jabatan_penandatangan' => 'Bendahara',
            
            // Logo
            'tampilkan_logo' => true,
            'logo_path' => $branding['logo'] ?? '',
            
            // Watermark
            'tampilkan_watermark' => false,
            'watermark_path' => '',
            'watermark_posisi' => 'center', // center, top-left, top-right, bottom-left, bottom-right
            'watermark_opacity' => 0.15,
            'watermark_ukuran' => 50, // persentase dari lebar kwitansi
            
            // Stempel
            'tampilkan_stempel' => false,
            'stempel_path' => '',
        ];
    }

    /**
     * Default syarat dan ketentuan
     */
    private function defaultSyaratKetentuan(): array
    {
        return [
            [
                'judul' => 'KETENTUAN UMUM',
                'ikon' => 'bi-1-circle',
                'konten' => '<ol class="mb-4">
                    <li class="mb-2">Seleksi Penerimaan Murid Baru (SPMB) SMA Al Furqon Boarding School dilaksanakan secara <strong>jujur, transparan, dan adil</strong> sesuai dengan nilai-nilai Islam dan peraturan yang berlaku.</li>
                    <li class="mb-2">Pendaftaran dilakukan secara online melalui sistem SPMB resmi yang disediakan oleh sekolah.</li>
                    <li class="mb-2">Calon peserta didik wajib mengisi data dengan <strong>benar dan jujur</strong>. Data yang tidak sesuai dapat mengakibatkan pembatalan pendaftaran.</li>
                    <li class="mb-2">Setiap calon peserta didik hanya diperbolehkan memiliki <strong>satu akun pendaftaran</strong>.</li>
                </ol>'
            ],
            [
                'judul' => 'PERSYARATAN PENDAFTARAN',
                'ikon' => 'bi-2-circle',
                'konten' => '<ol class="mb-4">
                    <li class="mb-2">Calon peserta didik adalah lulusan atau calon lulusan SMP/MTs sederajat.</li>
                    <li class="mb-2">Bersedia mengikuti seluruh rangkaian seleksi yang ditetapkan oleh panitia SPMB.</li>
                    <li class="mb-2">Menyiapkan dokumen yang diperlukan:
                        <ul class="mt-2">
                            <li>Kartu Keluarga (KK)</li>
                            <li>Akta Kelahiran</li>
                            <li>Ijazah/Surat Keterangan Lulus (SKL)</li>
                            <li>Kartu BPJS/KIS (jika ada)</li>
                            <li>KTP Orang Tua (Ayah dan Ibu)</li>
                            <li>Pas Foto terbaru</li>
                        </ul>
                    </li>
                    <li class="mb-2">Orang tua/wali bersedia hadir pada saat wawancara sesuai jadwal yang ditentukan.</li>
                </ol>'
            ],
            [
                'judul' => 'TAHAPAN SELEKSI',
                'ikon' => 'bi-3-circle',
                'konten' => '<ol class="mb-4">
                    <li class="mb-2"><strong>Tahap 1 - Pendaftaran Online:</strong> Membuat akun dan mengisi data awal.</li>
                    <li class="mb-2"><strong>Tahap 2 - Pengisian Formulir:</strong> Melengkapi data diri, data orang tua, dan mengunggah dokumen persyaratan.</li>
                    <li class="mb-2"><strong>Tahap 3 - Pembayaran Formulir:</strong> Melakukan pembayaran biaya pendaftaran sesuai ketentuan.</li>
                    <li class="mb-2"><strong>Tahap 4 - Tes Online:</strong> Mengikuti tes akademik dan/atau tes lainnya secara online.</li>
                    <li class="mb-2"><strong>Tahap 5 - Wawancara:</strong> Wawancara dengan orang tua/wali dan calon peserta didik.</li>
                    <li class="mb-2"><strong>Tahap 6 - Pembayaran Awal:</strong> Melakukan pembayaran biaya pendidikan tahap pertama.</li>
                    <li class="mb-2"><strong>Tahap 7 - Pengumuman Kelulusan:</strong> Pengumuman hasil seleksi akhir.</li>
                </ol>'
            ],
            [
                'judul' => 'KOMITMEN KEJUJURAN',
                'ikon' => 'bi-4-circle',
                'konten' => '<div class="bg-light p-3 rounded mb-4">
                    <p class="mb-2">Dengan mendaftar, saya menyatakan:</p>
                    <ul class="mb-0">
                        <li class="mb-2">Akan mengisi seluruh data dengan <strong>jujur dan benar</strong> sesuai dokumen resmi.</li>
                        <li class="mb-2">Akan mengikuti tes dengan <strong>jujur tanpa kecurangan</strong> dalam bentuk apapun.</li>
                        <li class="mb-2">Memahami bahwa <strong>ketidakjujuran</strong> dapat mengakibatkan diskualifikasi.</li>
                        <li class="mb-2">Bersedia menerima keputusan panitia SPMB dengan <strong>lapang dada</strong>.</li>
                    </ul>
                </div>'
            ],
            [
                'judul' => 'KETENTUAN SISTEM',
                'ikon' => 'bi-5-circle',
                'konten' => '<ol class="mb-4">
                    <li class="mb-2">Sistem SPMB online dikelola dengan sebaik-baiknya untuk memberikan pelayanan terbaik.</li>
                    <li class="mb-2">Apabila terjadi <strong>kendala teknis</strong> pada sistem, peserta diharapkan untuk:
                        <ul class="mt-2">
                            <li>Tidak panik dan tetap tenang</li>
                            <li>Menghubungi Tim SPMB melalui WhatsApp yang tersedia</li>
                            <li>Menyampaikan kendala dengan jelas dan sopan</li>
                            <li>Bersama-sama mencari solusi terbaik</li>
                        </ul>
                    </li>
                    <li class="mb-2">Tim SPMB berkomitmen untuk <strong>membantu menyelesaikan setiap kendala</strong> dengan solusi yang adil dan bijaksana.</li>
                    <li class="mb-2">Peserta diharapkan <strong>tidak menyalahkan sistem atau panitia</strong> atas kendala yang terjadi, melainkan bersama-sama mencari jalan keluar terbaik.</li>
                </ol>'
            ],
            [
                'judul' => 'KETENTUAN PEMBAYARAN',
                'ikon' => 'bi-6-circle',
                'konten' => '<ol class="mb-4">
                    <li class="mb-2">Biaya pendaftaran yang telah dibayarkan <strong>tidak dapat dikembalikan</strong> (non-refundable).</li>
                    <li class="mb-2">Pembayaran dilakukan sesuai dengan nominal dan rekening yang ditentukan oleh panitia.</li>
                    <li class="mb-2">Bukti pembayaran wajib diunggah ke sistem untuk verifikasi.</li>
                </ol>'
            ],
            [
                'judul' => 'HAK DAN KEWAJIBAN',
                'ikon' => 'bi-7-circle',
                'konten' => '<div class="row">
                    <div class="col-md-6">
                        <div class="card border-success mb-3">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-check-circle me-2"></i>Hak Peserta
                            </div>
                            <div class="card-body">
                                <ul class="mb-0 small">
                                    <li>Mendapatkan informasi yang jelas tentang SPMB</li>
                                    <li>Mendapatkan pelayanan yang baik dari panitia</li>
                                    <li>Mendapatkan bantuan jika mengalami kendala</li>
                                    <li>Mengetahui hasil seleksi sesuai jadwal</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning">
                                <i class="bi bi-exclamation-circle me-2"></i>Kewajiban Peserta
                            </div>
                            <div class="card-body">
                                <ul class="mb-0 small">
                                    <li>Mengisi data dengan jujur dan benar</li>
                                    <li>Mengikuti seluruh tahapan seleksi</li>
                                    <li>Mematuhi jadwal yang telah ditentukan</li>
                                    <li>Menjaga etika dan sopan santun</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>'
            ],
            [
                'judul' => 'PENUTUP',
                'ikon' => 'bi-8-circle',
                'konten' => '<div class="alert alert-success">
                    <p class="mb-2"><i class="bi bi-heart me-2"></i><strong>Semangat dan Doa Terbaik</strong></p>
                    <p class="mb-0 small">Kami mendoakan semoga seluruh calon peserta didik diberikan kemudahan dan kelancaran dalam mengikuti seleksi ini. Apapun hasilnya, semoga menjadi yang terbaik menurut Allah SWT. <em>"Barangsiapa bertawakal kepada Allah, niscaya Allah akan mencukupkan keperluannya."</em> (QS. At-Talaq: 3)</p>
                </div>
                <div class="text-center mt-4">
                    <p class="text-muted small mb-0">Syarat dan Ketentuan ini berlaku sejak tanggal pendaftaran dibuka.</p>
                    <p class="text-muted small"><strong>Tim SPMB SMA Al Furqon Boarding School</strong></p>
                </div>'
            ],
        ];
    }

    /**
     * Ambil konten alur SPMB
     */
    public function ambilAlurSpmb(): array
    {
        $json = $this->ambil('alur_spmb', '[]');
        $konten = json_decode($json, true) ?: [];
        
        // Jika kosong, kembalikan default
        if (empty($konten)) {
            $konten = $this->defaultAlurSpmb();
        }
        
        return $konten;
    }

    /**
     * Simpan konten alur SPMB
     */
    public function simpanAlurSpmb(array $konten): void
    {
        $this->simpan('alur_spmb', json_encode($konten, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Default konten alur SPMB
     */
    private function defaultAlurSpmb(): array
    {
        return [
            [
                'nomor' => 1,
                'judul' => 'Buat Akun & Login',
                'icon' => 'person-plus-fill',
                'deskripsi' => 'Daftarkan diri Anda dengan mengisi data dasar untuk membuat akun peserta SPMB.',
                'detail' => [
                    'Isi nama lengkap, email, dan nomor telepon',
                    'Buat password untuk akun Anda',
                    'Dapatkan nomor pendaftaran otomatis',
                    'Login ke dashboard peserta'
                ]
            ],
            [
                'nomor' => 2,
                'judul' => 'Isi Formulir PPDB',
                'icon' => 'file-earmark-text-fill',
                'deskripsi' => 'Lengkapi formulir pendaftaran dengan data diri, orang tua, dan asal sekolah.',
                'detail' => [
                    'Data diri lengkap (nama, tempat/tanggal lahir, alamat)',
                    'Data orang tua/wali',
                    'Data asal sekolah dan NISN',
                    'Upload pas foto terbaru'
                ]
            ],
            [
                'nomor' => 3,
                'judul' => 'Transfer & Upload Bukti Pembayaran Formulir',
                'icon' => 'credit-card-fill',
                'deskripsi' => 'Lakukan pembayaran biaya formulir pendaftaran dan upload bukti transfer.',
                'detail' => [
                    'Transfer ke rekening yang ditentukan',
                    'Upload foto/scan bukti transfer',
                    'Tunggu verifikasi dari admin (1x24 jam)'
                ]
            ],
            [
                'nomor' => 4,
                'judul' => 'Test Online',
                'icon' => 'laptop-fill',
                'deskripsi' => 'Ikuti tes seleksi online sesuai jadwal yang ditentukan.',
                'detail' => [
                    'Tes Potensi Akademik',
                    'Tes Baca Tulis Al-Quran',
                    'Gunakan token yang diberikan panitia'
                ]
            ],
            [
                'nomor' => 5,
                'judul' => 'Wawancara & Verifikasi Berkas',
                'icon' => 'people-fill',
                'deskripsi' => 'Hadiri sesi wawancara dan bawa berkas asli untuk diverifikasi.',
                'detail' => [
                    'Wawancara dengan panitia',
                    'Bawa berkas: Akta Kelahiran, KK, Ijazah/SKL',
                    'Rapor semester 1-5',
                    'Surat keterangan sehat dan kelakuan baik'
                ]
            ],
            [
                'nomor' => 6,
                'judul' => 'Upload Bukti Pembayaran Pertama',
                'icon' => 'cash-stack',
                'deskripsi' => 'Lakukan pembayaran tahap pertama dan upload bukti transfer.',
                'detail' => [
                    'Pembayaran sesuai ketentuan',
                    'Upload bukti transfer',
                    'Tunggu verifikasi dari admin'
                ]
            ],
            [
                'nomor' => 7,
                'judul' => 'Info Kelulusan',
                'icon' => 'info-circle-fill',
                'deskripsi' => 'Lihat informasi kelulusan dan status penerimaan Anda.',
                'detail' => [
                    'Cek status kelulusan',
                    'Terima surat penerimaan jika lulus',
                    'Informasi jadwal KBM',
                    'Persiapan masuk sekolah'
                ]
            ],
        ];
    }

    /**
     * Ambil konten jadwal SPMB
     */
    public function ambilJadwal(): array
    {
        $json = $this->ambil('jadwal_spmb', '[]');
        $konten = json_decode($json, true) ?: [];
        
        // Jika kosong, kembalikan default
        if (empty($konten)) {
            $konten = $this->defaultJadwal();
        }
        
        return $konten;
    }

    /**
     * Simpan konten jadwal SPMB
     */
    public function simpanJadwal(array $konten): void
    {
        $this->simpan('jadwal_spmb', json_encode($konten, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Ambil catatan jadwal
     */
    public function ambilCatatanJadwal(): string
    {
        return $this->ambil('catatan_jadwal', 'Jadwal dapat berubah sewaktu-waktu. Pantau terus dashboard peserta Anda untuk informasi terbaru.');
    }

    /**
     * Simpan catatan jadwal
     */
    public function simpanCatatanJadwal(string $catatan): void
    {
        $this->simpan('catatan_jadwal', $catatan);
    }

    /**
     * Default konten jadwal SPMB
     */
    private function defaultJadwal(): array
    {
        $tahun = date('Y');
        return [
            [
                'kegiatan' => 'Pendaftaran Online',
                'icon' => 'calendar-check',
                'tanggal' => "1 Januari - 30 Juni {$tahun}",
                'status' => 'dibuka',
                'keterangan' => 'Dibuka'
            ],
            [
                'kegiatan' => 'Tes Online Gelombang 1',
                'icon' => 'laptop',
                'tanggal' => "15 Maret {$tahun}",
                'status' => 'akan_datang',
                'keterangan' => 'Akan Datang'
            ],
            [
                'kegiatan' => 'Tes Online Gelombang 2',
                'icon' => 'laptop',
                'tanggal' => "15 Mei {$tahun}",
                'status' => 'akan_datang',
                'keterangan' => 'Akan Datang'
            ],
            [
                'kegiatan' => 'Tes Online Gelombang 3',
                'icon' => 'laptop',
                'tanggal' => "30 Juni {$tahun}",
                'status' => 'akan_datang',
                'keterangan' => 'Akan Datang'
            ],
            [
                'kegiatan' => 'Wawancara',
                'icon' => 'people',
                'tanggal' => 'Sesuai jadwal masing-masing',
                'status' => 'info',
                'keterangan' => 'Setelah Tes'
            ],
            [
                'kegiatan' => 'Pengumuman Hasil',
                'icon' => 'megaphone',
                'tanggal' => '7 hari setelah wawancara',
                'status' => 'info',
                'keterangan' => 'Via Dashboard'
            ],
            [
                'kegiatan' => 'Mulai Tahun Ajaran Baru',
                'icon' => 'book',
                'tanggal' => "Juli {$tahun}",
                'status' => 'persiapan',
                'keterangan' => 'Persiapan'
            ],
        ];
    }
}
