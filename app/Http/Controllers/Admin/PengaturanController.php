<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PengaturanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Controller untuk pengaturan sistem
 * Kebutuhan: 8.1, 8.2, 8.4, 8.5, 8.6
 */
class PengaturanController extends Controller
{
    public function __construct(
        private PengaturanService $pengaturanService
    ) {}

    /**
     * Halaman pengaturan umum
     */
    public function index()
    {
        $branding = $this->pengaturanService->ambilBranding();
        $spmb = $this->pengaturanService->ambilSpmb();
        $ujian = $this->pengaturanService->ambilUjian();

        return view('admin.pengaturan.index', compact('branding', 'spmb', 'ujian'));
    }

    /**
     * Halaman pengaturan branding
     */
    public function branding()
    {
        $branding = $this->pengaturanService->ambilBranding();
        return view('admin.pengaturan.branding', compact('branding'));
    }

    /**
     * Simpan pengaturan branding
     */
    public function simpanBranding(Request $request)
    {
        $request->validate([
            'nama_institusi' => 'required|string|max:255',
            'nama_singkat' => 'required|string|max:50',
            'alamat' => 'nullable|string|max:500',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'warna_primer' => 'nullable|string|max:7',
            'warna_sekunder' => 'nullable|string|max:7',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'favicon' => 'nullable|image|mimes:png,ico|max:512',
        ]);

        $this->pengaturanService->simpanBranding($request->except(['logo', 'favicon', 'tahun_ajaran']));

        if ($request->hasFile('logo')) {
            $this->pengaturanService->uploadLogo($request->file('logo'));
        }

        if ($request->hasFile('favicon')) {
            $this->pengaturanService->uploadFavicon($request->file('favicon'));
        }

        return back()->with('success', 'Pengaturan branding berhasil disimpan.');
    }

    /**
     * Halaman pengaturan email
     */
    public function email()
    {
        $email = $this->pengaturanService->ambilEmail();
        return view('admin.pengaturan.email', compact('email'));
    }

    /**
     * Simpan pengaturan email
     */
    public function simpanEmail(Request $request)
    {
        $request->validate([
            'mail_driver' => 'required|in:smtp,sendmail,mailgun',
            'mail_host' => 'required_if:mail_driver,smtp|nullable|string|max:255',
            'mail_port' => 'required_if:mail_driver,smtp|nullable|integer',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|in:tls,ssl,null',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        $this->pengaturanService->simpanEmail($request->all());

        return back()->with('success', 'Pengaturan email berhasil disimpan.');
    }

    /**
     * Test koneksi SMTP
     */
    public function testEmail()
    {
        $result = $this->pengaturanService->testKoneksiSmtp();

        if ($result['sukses']) {
            return back()->with('success', $result['pesan']);
        }

        return back()->with('error', $result['pesan']);
    }

    /**
     * Halaman pengaturan SPMB
     */
    public function spmb()
    {
        $spmb = $this->pengaturanService->ambilSpmb();
        $tahapan = $this->pengaturanService->ambilPengaturanTahapan();
        $statusTahapan = [];
        foreach (range(2, 7) as $tahap) {
            $statusTahapan[$tahap] = $this->pengaturanService->statusAksesTahap(
                $tahap,
                $tahapan["tahap_{$tahap}"] ?? []
            );
        }
        $skGelombang = $this->pengaturanService->ambilSuratKelulusanGelombang();
        return view('admin.pengaturan.spmb', compact('spmb', 'tahapan', 'statusTahapan', 'skGelombang'));
    }

    /**
     * Simpan pengaturan SPMB
     */
    public function simpanSpmb(Request $request)
    {
        $rules = [
            'tanggal_buka' => 'nullable|date',
            'waktu_buka' => 'nullable|date_format:H:i',
            'tanggal_tutup' => 'nullable|date|after_or_equal:tanggal_buka',
            'waktu_tutup' => 'nullable|date_format:H:i',
            'biaya_formulir' => 'nullable|numeric|min:0',
            'biaya_pelunasan' => 'nullable|numeric|min:0',
            'rekening_bank' => 'nullable|string|max:100',
            'nomor_rekening' => 'nullable|string|max:50',
            'nama_rekening' => 'nullable|string|max:255',
            'kontak_tim' => 'nullable|array',
            'kontak_tim.*.nama' => 'nullable|string|max:100',
            'kontak_tim.*.whatsapp' => 'nullable|string|max:20',
            'surat_kelulusan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'sk_gelombang_existing' => 'nullable|array',
            'sk_gelombang_existing.*.nama' => 'nullable|string|max:100',
            'sk_gelombang_existing.*.file_upload' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'sk_gelombang_baru' => 'nullable|array',
            'sk_gelombang_baru.*.nama' => 'nullable|string|max:100',
            'sk_gelombang_baru.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        foreach ([2, 3, 5, 6, 7] as $tahap) {
            $rules["tahap_{$tahap}.dibuka"] = 'nullable|boolean';
            $rules["tahap_{$tahap}.tanggal_buka"] = 'nullable|date';
            $rules["tahap_{$tahap}.waktu_mulai"] = 'nullable|date_format:H:i';
            $rules["tahap_{$tahap}.tanggal_tutup"] = 'nullable|date';
            $rules["tahap_{$tahap}.waktu_selesai"] = 'nullable|date_format:H:i';
            $rules["tahap_{$tahap}.keterangan"] = 'nullable|string|max:1000';
        }

        $request->validate($rules);
        $this->pastikanRentangJadwal(
            $request,
            'tanggal_buka',
            'waktu_buka',
            'tanggal_tutup',
            'waktu_tutup',
            'pendaftaran'
        );

        foreach ([2, 3, 5, 6, 7] as $tahap) {
            $this->pastikanRentangJadwal(
                $request,
                "tahap_{$tahap}.tanggal_buka",
                "tahap_{$tahap}.waktu_mulai",
                "tahap_{$tahap}.tanggal_tutup",
                "tahap_{$tahap}.waktu_selesai",
                "tahap {$tahap}"
            );
        }

        // Upload surat kelulusan jika ada
        if ($request->hasFile('surat_kelulusan')) {
            $this->pengaturanService->uploadSuratKelulusan($request->file('surat_kelulusan'));
        }

        $this->simpanSkKelulusanGelombang($request);

        $data = $request->except(['tahap_2', 'tahap_3', 'tahap_4', 'tahap_5', 'tahap_6', 'tahap_7', '_token', 'kontak_tim']);
        $data['pendaftaran_buka'] = $request->boolean('pendaftaran_buka');
        
        // Proses kontak tim SPMB
        $kontakTim = [];
        if ($request->has('kontak_tim')) {
            foreach ($request->input('kontak_tim') as $kontak) {
                if (!empty($kontak['nama']) || !empty($kontak['whatsapp'])) {
                    $kontakTim[] = [
                        'nama' => $kontak['nama'] ?? '',
                        'whatsapp' => $kontak['whatsapp'] ?? '',
                    ];
                }
            }
        }
        $data['kontak_tim_spmb'] = json_encode($kontakTim);

        $this->pengaturanService->simpanSpmb($data);

        // Auto-generate waktu tahapan jika pendaftaran dibuka dan ada tanggal buka/tutup
        $tanggalBuka = $request->input('tanggal_buka');
        $tanggalTutup = $request->input('tanggal_tutup');
        
        if ($request->boolean('pendaftaran_buka') && $tanggalBuka && $tanggalTutup) {
            $tahapanData = [];
            $currentTahapan = $this->pengaturanService->ambilPengaturanTahapan();
            
            // Hitung durasi total pendaftaran
            $start = \Carbon\Carbon::parse($tanggalBuka);
            $end = \Carbon\Carbon::parse($tanggalTutup);
            $totalDays = $start->diffInDays($end);
            
            // Tahap 2 mengikuti jadwal pendaftaran utama, tahap 3-7 tetap memakai slot otomatis berikutnya.
            $daysPerTahap = max(7, intval($totalDays / 6)); // Minimal 7 hari per tahap
            
            $tahapLabels = [
                3 => 'Upload Bukti Pembayaran Formulir',
                4 => 'Tes Online',
                5 => 'Wawancara & Verifikasi Berkas',
                6 => 'Upload Bukti Pembayaran Pertama',
                7 => 'Resmi Menjadi Peserta Didik',
            ];
            
            $currentDate = $start->copy()->addDays($daysPerTahap);
            
            for ($i = 3; $i <= 7; $i++) {
                $key = "tahap_{$i}";
                $existing = $currentTahapan[$key] ?? [];
                
                // Hanya auto-generate jika belum ada tanggal yang diset
                if (empty($existing['tanggal_buka']) && empty($existing['tanggal_tutup'])) {
                    $tahapanData[$key] = [
                        'tanggal_buka' => $currentDate->format('Y-m-d'),
                        'tanggal_tutup' => $currentDate->copy()->addDays($daysPerTahap)->format('Y-m-d'),
                        'keterangan' => $tahapLabels[$i],
                    ];
                    $currentDate->addDays($daysPerTahap);
                }
            }
            
            if (!empty($tahapanData)) {
                $this->pengaturanService->simpanPengaturanTahapan($tahapanData);
            }
        }

        // Simpan pengaturan tahapan manual (jika ada input dari form)
        $tahapanManual = [];
        for ($i = 2; $i <= 7; $i++) {
            if ($i === 4) {
                continue;
            }
            if ($request->has("tahap_{$i}")) {
                $inputTahap = $request->input("tahap_{$i}");
                if (is_array($inputTahap)) {
                    $tahapanManual["tahap_{$i}"] = $inputTahap;
                }
            }
        }
        if (!empty($tahapanManual)) {
            $this->pengaturanService->simpanPengaturanTahapan($tahapanManual);
        }

        // Simpan pengaturan tampilan kelulusan
        $kelulusanData = [];
        if ($request->filled('kelulusan_judul_lulus')) {
            $kelulusanData['kelulusan_judul_lulus'] = $request->input('kelulusan_judul_lulus');
        }
        if ($request->filled('kelulusan_teks_lulus')) {
            $kelulusanData['kelulusan_teks_lulus'] = $request->input('kelulusan_teks_lulus');
        }
        if ($request->filled('kelulusan_warna_lulus')) {
            $kelulusanData['kelulusan_warna_lulus'] = $request->input('kelulusan_warna_lulus');
        }
        if ($request->filled('kelulusan_judul_tidak_lulus')) {
            $kelulusanData['kelulusan_judul_tidak_lulus'] = $request->input('kelulusan_judul_tidak_lulus');
        }
        if ($request->filled('kelulusan_teks_tidak_lulus')) {
            $kelulusanData['kelulusan_teks_tidak_lulus'] = $request->input('kelulusan_teks_tidak_lulus');
        }
        if ($request->filled('kelulusan_warna_tidak_lulus')) {
            $kelulusanData['kelulusan_warna_tidak_lulus'] = $request->input('kelulusan_warna_tidak_lulus');
        }
        if (!empty($kelulusanData)) {
            $this->pengaturanService->simpanPengaturanKelulusan($kelulusanData);
        }

        session()->flash('success', 'Pengaturan SPMB berhasil disimpan.');
        return redirect()->route('admin.pengaturan.spmb');
    }

    /**
     * Simpan daftar SK kelulusan per gelombang.
     */
    private function simpanSkKelulusanGelombang(Request $request): void
    {
        $items = [];

        foreach ($request->input('sk_gelombang_existing', []) as $index => $row) {
            $fileLama = $row['file'] ?? '';

            if (!empty($row['hapus'])) {
                if ($fileLama && Storage::disk('public')->exists($fileLama)) {
                    Storage::disk('public')->delete($fileLama);
                }
                continue;
            }

            $nama = trim($row['nama'] ?? '');
            if ($nama === '') {
                continue;
            }

            $filePath = $fileLama;
            $uploaded = $request->file("sk_gelombang_existing.{$index}.file_upload");
            if ($uploaded) {
                if ($fileLama && Storage::disk('public')->exists($fileLama)) {
                    Storage::disk('public')->delete($fileLama);
                }
                $filePath = $this->pengaturanService->uploadSuratKelulusanGelombang($uploaded, $nama);
            }

            if ($filePath) {
                $items[] = [
                    'id' => $row['id'] ?? (string) Str::uuid(),
                    'nama' => $nama,
                    'file' => $filePath,
                    'uploaded_at' => $row['uploaded_at'] ?? now()->toDateTimeString(),
                ];
            }
        }

        foreach ($request->input('sk_gelombang_baru', []) as $index => $row) {
            $nama = trim($row['nama'] ?? '');
            $uploaded = $request->file("sk_gelombang_baru.{$index}.file");

            if ($nama === '' || !$uploaded) {
                continue;
            }

            $items[] = [
                'id' => (string) Str::uuid(),
                'nama' => $nama,
                'file' => $this->pengaturanService->uploadSuratKelulusanGelombang($uploaded, $nama),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        $this->pengaturanService->simpanSuratKelulusanGelombang($items);
    }

    /**
     * Halaman pengaturan ujian
     */
    public function ujian()
    {
        $ujian = $this->pengaturanService->ambilUjian();
        $aksesUjian = $this->pengaturanService->statusAksesUjian($ujian);

        return view('admin.pengaturan.ujian', compact('ujian', 'aksesUjian'));
    }

    /**
     * Simpan pengaturan ujian
     */
    public function simpanUjian(Request $request)
    {
        $request->validate([
            'durasi_default' => 'required|integer|min:1|max:300',
            'nilai_lulus_default' => 'required|numeric|min:0|max:100',
            'acak_soal_default' => 'boolean',
            'acak_jawaban_default' => 'boolean',
            'tampilkan_nilai' => 'boolean',
            'tampilkan_pembahasan' => 'boolean',
            'ujian_dibuka' => 'boolean',
            'ujian_tanggal_buka' => 'nullable|date',
            'ujian_waktu_buka' => 'nullable|date_format:H:i',
            'ujian_tanggal_tutup' => 'nullable|date',
            'ujian_waktu_tutup' => 'nullable|date_format:H:i',
        ]);

        if ($request->filled('ujian_waktu_buka') && !$request->filled('ujian_tanggal_buka')) {
            return back()->withInput()->withErrors([
                'ujian_tanggal_buka' => 'Tanggal buka wajib diisi jika waktu buka diisi.',
            ]);
        }

        if ($request->filled('ujian_waktu_tutup') && !$request->filled('ujian_tanggal_tutup')) {
            return back()->withInput()->withErrors([
                'ujian_tanggal_tutup' => 'Tanggal tutup wajib diisi jika waktu tutup diisi.',
            ]);
        }

        if ($request->filled('ujian_tanggal_buka') && $request->filled('ujian_tanggal_tutup')) {
            $mulai = \Carbon\Carbon::parse($request->ujian_tanggal_buka . ' ' . ($request->ujian_waktu_buka ?: '00:00'));
            $selesai = \Carbon\Carbon::parse($request->ujian_tanggal_tutup . ' ' . ($request->ujian_waktu_tutup ?: '23:59'));

            if ($selesai->lt($mulai)) {
                return back()->withInput()->withErrors([
                    'ujian_tanggal_tutup' => 'Jadwal tutup tes online tidak boleh sebelum jadwal buka.',
                ]);
            }
        }

        $data = $request->all();
        $data['acak_soal_default'] = $request->boolean('acak_soal_default');
        $data['acak_jawaban_default'] = $request->boolean('acak_jawaban_default');
        $data['tampilkan_nilai'] = $request->boolean('tampilkan_nilai');
        $data['tampilkan_pembahasan'] = $request->boolean('tampilkan_pembahasan');
        $data['ujian_dibuka'] = $request->boolean('ujian_dibuka');

        $this->pengaturanService->simpanUjian($data);

        return back()->with('sukses', 'Pengaturan ujian berhasil disimpan.');
    }

    /**
     * Ekspor konfigurasi
     */
    public function ekspor()
    {
        $config = $this->pengaturanService->eksporKonfigurasi();
        $filename = 'konfigurasi-spmb-' . date('Y-m-d') . '.json';

        return response()->json($config)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Impor konfigurasi
     */
    public function impor(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:1024',
        ]);

        try {
            $content = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('File JSON tidak valid.');
            }

            $this->pengaturanService->imporKonfigurasi($data);

            return back()->with('success', 'Konfigurasi berhasil diimpor.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal impor: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status pendaftaran (AJAX)
     */
    public function togglePendaftaran(Request $request)
    {
        $status = $request->boolean('status');
        $this->pengaturanService->simpan('pendaftaran_buka', $status);

        return response()->json([
            'sukses' => true,
            'status' => $status,
            'pesan' => $status ? 'Pendaftaran SPMB dibuka' : 'Pendaftaran SPMB ditutup',
        ]);
    }

    /**
     * Halaman pengaturan syarat dan ketentuan
     */
    public function syaratKetentuan()
    {
        $bagian = $this->pengaturanService->ambilSyaratKetentuan();
        return view('admin.pengaturan.syarat-ketentuan', compact('bagian'));
    }

    /**
     * Simpan pengaturan syarat dan ketentuan
     */
    public function simpanSyaratKetentuan(Request $request)
    {
        $request->validate([
            'bagian' => 'required|array',
            'bagian.*.judul' => 'required|string|max:255',
            'bagian.*.ikon' => 'nullable|string|max:50',
            'bagian.*.konten' => 'required|string',
        ], [
            'bagian.required' => 'Minimal harus ada satu bagian syarat dan ketentuan',
            'bagian.*.judul.required' => 'Judul bagian wajib diisi',
            'bagian.*.konten.required' => 'Konten bagian wajib diisi',
        ]);

        $bagian = [];
        foreach ($request->input('bagian') as $item) {
            $bagian[] = [
                'judul' => $item['judul'],
                'ikon' => $item['ikon'] ?? 'bi-circle',
                'konten' => $item['konten'],
            ];
        }

        $this->pengaturanService->simpanSyaratKetentuan($bagian);

        return back()->with('success', 'Syarat dan ketentuan berhasil disimpan.');
    }

    /**
     * Reset syarat dan ketentuan ke default
     */
    public function resetSyaratKetentuan()
    {
        // Hapus pengaturan syarat ketentuan sehingga akan menggunakan default
        $this->pengaturanService->hapus('syarat_ketentuan');

        return redirect()->route('admin.pengaturan.syarat-ketentuan')
            ->with('success', 'Syarat dan ketentuan berhasil direset ke pengaturan default.');
    }

    /**
     * Halaman pengaturan template kwitansi
     */
    public function templateKwitansi()
    {
        $template = $this->pengaturanService->ambilTemplateKwitansi();
        $branding = $this->pengaturanService->ambilBranding();
        $spmb = $this->pengaturanService->ambilSpmb();
        $jadwalTahap = $this->pengaturanService->ambilPengaturanTahapan()['tahap_6'];
        $statusJadwal = $this->pengaturanService->statusAksesTahap(6, $jadwalTahap);
        
        return view('admin.pengaturan.template-kwitansi', compact(
            'template', 'branding', 'spmb', 'jadwalTahap', 'statusJadwal'
        ));
    }

    /**
     * Simpan pengaturan template kwitansi
     */
    public function simpanTemplateKwitansi(Request $request)
    {
        $request->validate([
            'nama_institusi' => 'required|string|max:255',
            'alamat' => 'nullable|string|max:500',
            'telepon' => 'nullable|string|max:20',
            'judul_kwitansi' => 'required|string|max:255',
            'teks_footer' => 'nullable|string|max:500',
            'nama_penandatangan' => 'required|string|max:255',
            'jabatan_penandatangan' => 'nullable|string|max:100',
            'watermark_posisi' => 'nullable|in:center,top-left,top-right,bottom-left,bottom-right',
            'watermark_opacity' => 'nullable|numeric|min:0|max:1',
            'watermark_ukuran' => 'nullable|integer|min:10|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'watermark' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'stempel' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'jadwal.dibuka' => 'nullable|boolean',
            'jadwal.tanggal_buka' => 'nullable|date',
            'jadwal.waktu_mulai' => 'nullable|date_format:H:i',
            'jadwal.tanggal_tutup' => 'nullable|date',
            'jadwal.waktu_selesai' => 'nullable|date_format:H:i',
            'jadwal.keterangan' => 'nullable|string|max:1000',
        ], [
            'nama_institusi.required' => 'Nama institusi wajib diisi',
            'judul_kwitansi.required' => 'Judul kwitansi wajib diisi',
            'nama_penandatangan.required' => 'Nama penandatangan wajib diisi',
            'logo.image' => 'File logo harus berupa gambar',
            'logo.mimes' => 'Format logo harus PNG, JPG, atau JPEG',
            'logo.max' => 'Ukuran logo maksimal 2MB',
            'watermark.image' => 'File watermark harus berupa gambar',
            'watermark.mimes' => 'Format watermark harus PNG, JPG, atau JPEG',
            'watermark.max' => 'Ukuran watermark maksimal 2MB',
            'stempel.image' => 'File stempel harus berupa gambar',
            'stempel.mimes' => 'Format stempel harus PNG, JPG, atau JPEG',
            'stempel.max' => 'Ukuran stempel maksimal 2MB',
        ]);

        $this->pastikanRentangJadwal(
            $request,
            'jadwal.tanggal_buka',
            'jadwal.waktu_mulai',
            'jadwal.tanggal_tutup',
            'jadwal.waktu_selesai',
            'pelunasan'
        );

        // Ambil template saat ini
        $template = $this->pengaturanService->ambilTemplateKwitansi();

        // Update data template
        $template['nama_institusi'] = $request->input('nama_institusi');
        $template['alamat'] = $request->input('alamat', '');
        $template['telepon'] = $request->input('telepon', '');
        $template['judul_kwitansi'] = $request->input('judul_kwitansi');
        $template['teks_footer'] = $request->input('teks_footer', '');
        $template['nama_penandatangan'] = $request->input('nama_penandatangan');
        $template['jabatan_penandatangan'] = $request->input('jabatan_penandatangan', '');
        
        // Toggle settings
        $template['tampilkan_logo'] = $request->boolean('tampilkan_logo');
        $template['tampilkan_watermark'] = $request->boolean('tampilkan_watermark');
        $template['tampilkan_stempel'] = $request->boolean('tampilkan_stempel');
        
        // Watermark settings
        $template['watermark_posisi'] = $request->input('watermark_posisi', 'center');
        $template['watermark_opacity'] = (float) $request->input('watermark_opacity', 0.15);
        $template['watermark_ukuran'] = (int) $request->input('watermark_ukuran', 50);

        // Handle file uploads
        if ($request->hasFile('logo')) {
            $template['logo_path'] = $this->pengaturanService->uploadGambarKwitansi($request->file('logo'), 'logo');
        }
        
        if ($request->hasFile('watermark')) {
            $template['watermark_path'] = $this->pengaturanService->uploadGambarKwitansi($request->file('watermark'), 'watermark');
        }
        
        if ($request->hasFile('stempel')) {
            $template['stempel_path'] = $this->pengaturanService->uploadGambarKwitansi($request->file('stempel'), 'stempel');
        }

        // Simpan template
        $this->pengaturanService->simpanTemplateKwitansi($template);
        $jadwal = $request->input('jadwal', []);
        $jadwal['dibuka'] = $request->boolean('jadwal.dibuka');
        $this->pengaturanService->simpanJadwalTahap(6, $jadwal);

        return back()->with('success', 'Template kwitansi berhasil disimpan.');
    }

    /**
     * Reset template kwitansi ke default
     */
    public function resetTemplateKwitansi()
    {
        $this->pengaturanService->hapus('template_kwitansi');

        return redirect()->route('admin.pengaturan.template-kwitansi')
            ->with('success', 'Template kwitansi berhasil direset ke pengaturan default.');
    }

    // =========================================================================
    // DEPLOYMENT — Dipindah ke DeploymentController
    // =========================================================================
    // Lihat: App\Http\Controllers\Admin\DeploymentController
    // Method yang dipindah:
    //   - downloadProject()
    //   - downloadDatabase()
    //   - importDatabase()
    //   - updateProject()
    //   - clearCache()

    /**
     * Halaman pengaturan pertanyaan wawancara
     */
    public function wawancara()
    {
        // Jika reset
        if (request('reset')) {
            $this->pengaturanService->hapus('wawancara_pertanyaan_ortu');
            $this->pengaturanService->hapus('wawancara_pertanyaan_siswa');
            $this->pengaturanService->hapus('surat_pernyataan_siswa_poin');
            $this->pengaturanService->hapus('surat_pernyataan_ortu_poin');
            $this->pengaturanService->hapus('teks_ujian_pegon');
            return redirect()->route('admin.pengaturan.wawancara')
                ->with('sukses', 'Semua pengaturan wawancara berhasil direset ke default.');
        }

        $pertanyaanOrtu = \App\Models\Wawancara::pertanyaanOrtu();
        $pertanyaanSiswa = \App\Models\Wawancara::pertanyaanSiswa();
        $spSiswaPoin = \App\Models\Wawancara::suratPernyataanSiswaPoin();
        $spOrtuPoin = \App\Models\Wawancara::suratPernyataanOrtuPoin();
        $teksPegon = \App\Models\Wawancara::teksPegon();
        $jadwalTahap = $this->pengaturanService->ambilPengaturanTahapan()['tahap_5'];
        $statusJadwal = $this->pengaturanService->statusAksesTahap(5, $jadwalTahap);

        return view('admin.pengaturan.wawancara', compact(
            'pertanyaanOrtu', 'pertanyaanSiswa', 'spSiswaPoin', 'spOrtuPoin', 'teksPegon',
            'jadwalTahap', 'statusJadwal'
        ));
    }

    /**
     * Simpan pengaturan pertanyaan wawancara
     */
    public function simpanWawancara(Request $request)
    {
        $request->validate([
            'jadwal.dibuka' => 'nullable|boolean',
            'jadwal.tanggal_buka' => 'nullable|date',
            'jadwal.waktu_mulai' => 'nullable|date_format:H:i',
            'jadwal.tanggal_tutup' => 'nullable|date',
            'jadwal.waktu_selesai' => 'nullable|date_format:H:i',
            'jadwal.lokasi' => 'nullable|string|max:255',
            'jadwal.keterangan' => 'nullable|string|max:1000',
        ]);
        $this->pastikanRentangJadwal(
            $request,
            'jadwal.tanggal_buka',
            'jadwal.waktu_mulai',
            'jadwal.tanggal_tutup',
            'jadwal.waktu_selesai',
            'wawancara'
        );

        $pertanyaanOrtu = [];
        if ($request->has('pertanyaan_ortu')) {
            foreach (array_filter($request->input('pertanyaan_ortu')) as $i => $p) {
                $pertanyaanOrtu[$i + 1] = trim($p);
            }
        }

        $pertanyaanSiswa = [];
        if ($request->has('pertanyaan_siswa')) {
            foreach (array_filter($request->input('pertanyaan_siswa')) as $i => $p) {
                $pertanyaanSiswa[$i + 1] = trim($p);
            }
        }

        $spSiswaPoin = [];
        if ($request->has('sp_siswa_poin')) {
            foreach (array_filter($request->input('sp_siswa_poin')) as $i => $p) {
                $spSiswaPoin[$i + 1] = trim($p);
            }
        }

        $spOrtuPoin = [];
        if ($request->has('sp_ortu_poin')) {
            foreach (array_filter($request->input('sp_ortu_poin')) as $i => $p) {
                $spOrtuPoin[$i + 1] = trim($p);
            }
        }

        $teksPegon = [];
        if ($request->has('teks_pegon')) {
            foreach (array_filter($request->input('teks_pegon')) as $i => $p) {
                $teksPegon[$i + 1] = trim($p);
            }
        }

        $this->pengaturanService->simpan('wawancara_pertanyaan_ortu', json_encode($pertanyaanOrtu));
        $this->pengaturanService->simpan('wawancara_pertanyaan_siswa', json_encode($pertanyaanSiswa));
        $this->pengaturanService->simpan('surat_pernyataan_siswa_poin', json_encode($spSiswaPoin));
        $this->pengaturanService->simpan('surat_pernyataan_ortu_poin', json_encode($spOrtuPoin));
        $this->pengaturanService->simpan('teks_ujian_pegon', json_encode($teksPegon));
        $jadwal = $request->input('jadwal', []);
        $jadwal['dibuka'] = $request->boolean('jadwal.dibuka');
        $this->pengaturanService->simpanJadwalTahap(5, $jadwal);

        return redirect()->route('admin.pengaturan.wawancara')
            ->with('sukses', 'Pengaturan wawancara berhasil disimpan.');
    }

    private function pastikanRentangJadwal(
        Request $request,
        string $tanggalBukaKey,
        string $waktuBukaKey,
        string $tanggalTutupKey,
        string $waktuTutupKey,
        string $label
    ): void {
        $tanggalBuka = $request->input($tanggalBukaKey);
        $waktuBuka = $request->input($waktuBukaKey);
        $tanggalTutup = $request->input($tanggalTutupKey);
        $waktuTutup = $request->input($waktuTutupKey);

        if ($waktuBuka && !$tanggalBuka) {
            throw ValidationException::withMessages([
                $tanggalBukaKey => 'Tanggal buka ' . $label . ' wajib diisi jika jam buka diisi.',
            ]);
        }

        if ($waktuTutup && !$tanggalTutup) {
            throw ValidationException::withMessages([
                $tanggalTutupKey => 'Tanggal tutup ' . $label . ' wajib diisi jika jam tutup diisi.',
            ]);
        }

        if (!$tanggalBuka || !$tanggalTutup) {
            return;
        }

        $mulai = \Carbon\Carbon::parse($tanggalBuka . ' ' . ($waktuBuka ?: '00:00'));
        $selesai = \Carbon\Carbon::parse($tanggalTutup . ' ' . ($waktuTutup ?: '23:59'));

        if ($selesai->lt($mulai)) {
            throw ValidationException::withMessages([
                $tanggalTutupKey => 'Jadwal tutup ' . $label . ' tidak boleh sebelum jadwal buka.',
            ]);
        }
    }

    /**
     * Halaman pengaturan alur SPMB
     */
    public function alurSpmb()
    {
        $alurSpmb = $this->pengaturanService->ambilAlurSpmb();
        return view('admin.pengaturan.alur-spmb', compact('alurSpmb'));
    }

    /**
     * Simpan pengaturan alur SPMB
     */
    public function simpanAlurSpmb(Request $request)
    {
        $request->validate([
            'tahapan' => 'required|array',
            'tahapan.*.judul' => 'required|string|max:255',
            'tahapan.*.icon' => 'nullable|string|max:50',
            'tahapan.*.deskripsi' => 'required|string|max:500',
            'tahapan.*.detail' => 'nullable|array',
            'tahapan.*.detail.*' => 'nullable|string|max:255',
        ], [
            'tahapan.required' => 'Minimal harus ada satu tahapan',
            'tahapan.*.judul.required' => 'Judul tahapan wajib diisi',
            'tahapan.*.deskripsi.required' => 'Deskripsi tahapan wajib diisi',
        ]);

        $tahapan = [];
        foreach ($request->input('tahapan') as $index => $item) {
            // Filter detail yang tidak kosong
            $detail = array_filter($item['detail'] ?? [], fn($d) => !empty(trim($d)));
            
            $tahapan[] = [
                'nomor' => $index + 1,
                'judul' => $item['judul'],
                'icon' => $item['icon'] ?? 'circle-fill',
                'deskripsi' => $item['deskripsi'],
                'detail' => array_values($detail),
            ];
        }

        $this->pengaturanService->simpanAlurSpmb($tahapan);

        return back()->with('success', 'Pengaturan alur SPMB berhasil disimpan.');
    }

    /**
     * Reset alur SPMB ke default
     */
    public function resetAlurSpmb()
    {
        $this->pengaturanService->hapus('alur_spmb');

        return redirect()->route('admin.pengaturan.alur-spmb')
            ->with('success', 'Alur SPMB berhasil direset ke pengaturan default.');
    }

    /**
     * Halaman pengaturan jadwal SPMB
     */
    public function jadwal()
    {
        $jadwal = $this->pengaturanService->ambilJadwal();
        $catatan = $this->pengaturanService->ambilCatatanJadwal();
        $branding = $this->pengaturanService->ambilBranding();
        return view('admin.pengaturan.jadwal', compact('jadwal', 'catatan', 'branding'));
    }

    /**
     * Simpan pengaturan jadwal SPMB
     */
    public function simpanJadwal(Request $request)
    {
        $request->validate([
            'jadwal' => 'required|array',
            'jadwal.*.kegiatan' => 'required|string|max:255',
            'jadwal.*.icon' => 'nullable|string|max:50',
            'jadwal.*.tanggal' => 'required|string|max:255',
            'jadwal.*.status' => 'required|in:dibuka,akan_datang,selesai,info,persiapan',
            'jadwal.*.keterangan' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:500',
        ], [
            'jadwal.required' => 'Minimal harus ada satu jadwal',
            'jadwal.*.kegiatan.required' => 'Nama kegiatan wajib diisi',
            'jadwal.*.tanggal.required' => 'Tanggal wajib diisi',
            'jadwal.*.status.required' => 'Status wajib dipilih',
        ]);

        $jadwal = [];
        foreach ($request->input('jadwal') as $item) {
            $jadwal[] = [
                'kegiatan' => $item['kegiatan'],
                'icon' => $item['icon'] ?? 'calendar',
                'tanggal' => $item['tanggal'],
                'status' => $item['status'],
                'keterangan' => $item['keterangan'] ?? '',
            ];
        }

        $this->pengaturanService->simpanJadwal($jadwal);
        
        if ($request->filled('catatan')) {
            $this->pengaturanService->simpanCatatanJadwal($request->input('catatan'));
        }

        return back()->with('success', 'Pengaturan jadwal SPMB berhasil disimpan.');
    }

    /**
     * Reset jadwal SPMB ke default
     */
    public function resetJadwal()
    {
        $this->pengaturanService->hapus('jadwal_spmb');
        $this->pengaturanService->hapus('catatan_jadwal');

        return redirect()->route('admin.pengaturan.jadwal')
            ->with('success', 'Jadwal SPMB berhasil direset ke pengaturan default.');
    }

    /**
     * Halaman reset data peserta
     */
    public function resetData()
    {
        // Hitung statistik data yang akan dihapus
        $stats = [
            'peserta' => \App\Models\Peserta::withTrashed()->count(),
            'formulir' => \App\Models\FormulirSpmb::count(),
            'tahapan' => \App\Models\TahapanSpmb::count(),
            'pembayaran' => \App\Models\Pembayaran::count(),
            'sesi_tes' => \App\Models\SesiTes::count(),
            'jawaban' => \App\Models\JawabanPeserta::count(),
            'wawancara' => \App\Models\Wawancara::count(),
            'log_tahapan' => \App\Models\LogTahapanSpmb::count(),
        ];
        
        return view('admin.pengaturan.reset-data', compact('stats'));
    }

    /**
     * Proses reset data peserta
     */
    public function prosesResetData(Request $request)
    {
        $request->validate([
            'konfirmasi' => 'required|in:RESET',
        ], [
            'konfirmasi.required' => 'Ketik RESET untuk konfirmasi',
            'konfirmasi.in' => 'Ketik RESET dengan huruf kapital untuk konfirmasi',
        ]);

        try {
            set_time_limit(300);
            \DB::beginTransaction();

            // 1. Hapus file upload peserta
            $deletedFiles = $this->hapusFilePeserta();

            // 2. Disable foreign key checks
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // 3. Truncate tabel terkait peserta (urutan penting!)
            $tables = [
                'jawaban_peserta',
                'sesi_tes',
                'hasil_gaya_belajar',
                'hasil_psikotes_kepribadian',
                'hasil_mbti',
                'hasil_profiling',
                'wawancara',
                'peserta_wawancara',
                'pembayaran',
                'log_tahapan_spmb',
                'tahapan_spmb',
                'formulir_spmb',
                'grup_peserta',
                'token_global_log',
                'peserta',
            ];

            $truncatedTables = [];
            foreach ($tables as $table) {
                try {
                    \DB::table($table)->truncate();
                    $truncatedTables[] = $table;
                } catch (\Exception $e) {
                    // Table mungkin tidak ada, skip
                }
            }

            // 4. Re-enable foreign key checks
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

            \DB::commit();

            $message = "Reset data berhasil! ";
            $message .= count($truncatedTables) . " tabel di-reset. ";
            $message .= $deletedFiles . " file dihapus. ";
            $message .= "Auto increment ID sudah di-reset ke 1.";

            return redirect()->route('admin.pengaturan.reset-data')
                ->with('success', $message);

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->with('error', 'Gagal reset data: ' . $e->getMessage());
        }
    }

    /**
     * Helper untuk menghapus file upload peserta
     */
    private function hapusFilePeserta(): int
    {
        $deletedCount = 0;
        $storagePath = storage_path('app/public');

        // Folder yang berisi file peserta
        $folders = [
            'foto',
            'bukti-pembayaran',
            'berkas',
            'formulir',
            'ktp',
            'kk',
            'akta',
            'ijazah',
            'rapor',
            'pas-foto',
        ];

        foreach ($folders as $folder) {
            $folderPath = $storagePath . '/' . $folder;
            if (is_dir($folderPath)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                
                foreach ($files as $file) {
                    if ($file->isFile() && $file->getFilename() !== '.gitignore') {
                        @unlink($file->getPathname());
                        $deletedCount++;
                    }
                }
            }
        }

        return $deletedCount;
    }
}
