<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Helpers\NomorPendaftaranHelper;
use App\Models\GelombangPendaftaran;
use App\Services\KuotaPendaftaranService;
use App\Services\PengaturanService;
use App\Services\PeriodePendaftaranService;
use App\Services\FormulirSpmbService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PendaftaranController extends Controller
{
    public function __construct(
        private PengaturanService $pengaturanService,
        private PeriodePendaftaranService $periodePendaftaranService,
        private KuotaPendaftaranService $kuotaPendaftaranService,
        private FormulirSpmbService $formulirService
    ) {}

    /**
     * Tampilkan form pendaftaran
     */
    public function form(): View
    {
        $spmb = $this->pengaturanService->ambilSpmb();
        $branding = $this->pengaturanService->ambilBranding();
        $periodePendaftaran = $this->periodePendaftaranService->pilihanPublikDenganStatus();
        $jadwalBerikutnya = $this->periodePendaftaranService->jadwalPublikBerikutnya();
        $adaGelombangDibuka = $periodePendaftaran->contains(
            fn($tahun) => $tahun->gelombangPendaftaran->contains(
                fn(GelombangPendaftaran $gelombang) => $gelombang->sedangDibuka()
            )
        );
        $pendaftaranDibuka = $adaGelombangDibuka;
        $pesanTutup = $adaGelombangDibuka
            ? null
            : 'Belum ada gelombang pendaftaran yang sedang dibuka.';

        $tahunDefaultId = $periodePendaftaran->firstWhere('default', true)?->id
            ?? $periodePendaftaran->first()?->id;
        $periodePayload = $this->formatPeriodePublik($periodePendaftaran);
        $syaratKetentuan = $this->pengaturanService->ambilSyaratKetentuan();
        
        return view('public.daftar', compact(
            'pendaftaranDibuka',
            'pesanTutup',
            'spmb',
            'branding',
            'syaratKetentuan',
            'periodePendaftaran',
            'periodePayload',
            'tahunDefaultId',
            'jadwalBerikutnya'
        ));
    }

    /**
     * Proses pendaftaran peserta baru (1 langkah: akun + biodata + auto-login)
     *
     * Username & password = No HP yang didaftarkan.
     * Prioritas No HP: HP siswa; jika kosong -> HP ayah; jika kosong -> HP ibu.
     */
    public function proses(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Akun & periode
            'nama' => 'required|string|max:255',
            'telepon' => 'nullable|string|max:20',
            'telepon_ayah' => 'nullable|string|max:20',
            'telepon_ibu' => 'nullable|string|max:20',
            'tahun_ajaran_id' => 'required|integer|exists:tahun_ajaran,id',
            'gelombang_pendaftaran_id' => 'required|integer|exists:gelombang_pendaftaran,id',
            'jenis_pendaftaran' => 'required|in:siswa_baru,pindahan',
            // Kelas mengikuti jalur: siswa baru = 10; pindahan = 10 atau 11
            // (kelas 12 belum dibuka). Sumber tunggal: JalurContextService.
            'kelas_tujuan' => \App\Services\JalurContextService::aturanKelas(
                $request->input('jenis_pendaftaran')
            ),
            'setuju' => 'required|accepted',
            // Biodata inti
            'tanggal_lahir' => 'nullable|date|before:today',
            'tempat_lahir' => 'nullable|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'asal_sekolah' => 'required|string|max:255',
            'nama_ayah' => 'nullable|string|max:255',
            'nama_ibu' => 'nullable|string|max:255',
            'alamat_kota' => 'nullable|string|max:100',
            'nisn' => 'nullable|string|max:20',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi',
            'tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih',
            'gelombang_pendaftaran_id.required' => 'Gelombang pendaftaran wajib dipilih',
            'jenis_pendaftaran.required' => 'Jenis pendaftaran wajib dipilih',
            'kelas_tujuan.required' => 'Kelas tujuan wajib dipilih',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih',
            'asal_sekolah.required' => 'Asal sekolah wajib diisi',
            'setuju.required' => 'Anda harus menyetujui syarat dan ketentuan',
        ]);

        // Tentukan No HP untuk login: siswa -> ayah -> ibu
        $noHp = $this->normalisasiNoHp(
            $request->telepon
                ?: $request->telepon_ayah
                ?: $request->telepon_ibu
        );

        if (empty($noHp)) {
            return back()->withInput()->withErrors([
                'telepon' => 'Isi minimal salah satu No HP (siswa atau orang tua) untuk akun login.',
            ]);
        }

        // Pastikan No HP belum dipakai
        if (Peserta::where('telepon', $noHp)->exists()) {
            return back()->withInput()->withErrors([
                'telepon' => 'No HP ini sudah terdaftar. Silakan login, atau gunakan No HP lain.',
            ]);
        }

        $kategori = $this->periodePendaftaranService->validasiKategori($validated, true);

        try {
            DB::beginTransaction();

            $nomorPendaftaran = NomorPendaftaranHelper::generate();
            $kuota = $this->kuotaPendaftaranService->siapkanAtributPesertaBaru($kategori['tahun_ajaran_id']);

            // Buat peserta — password = No HP (di-hash otomatis oleh cast 'hashed')
            $peserta = Peserta::create([
                'nomor_pendaftaran' => $nomorPendaftaran,
                'nama' => $request->nama,
                'telepon' => $noHp,
                'asal_sekolah' => $request->asal_sekolah,
                'password' => $noHp,
                ...$kategori,
                ...$kuota,
            ]);

            // Buat formulir biodata (draft) dari data awal pendaftaran
            $this->formulirService->simpan($peserta, array_filter([
                'nama_lengkap' => $request->nama,
                'tempat_lahir' => $request->tempat_lahir,
                'tanggal_lahir' => $request->tanggal_lahir,
                'jenis_kelamin' => $request->jenis_kelamin,
                'asal_sekolah' => $request->asal_sekolah,
                'nama_ayah' => $request->nama_ayah,
                'nama_ibu' => $request->nama_ibu,
                'telepon' => $request->telepon,
                'telepon_ayah' => $request->telepon_ayah,
                'telepon_ibu' => $request->telepon_ibu,
                'alamat_kota' => $request->alamat_kota,
                'nisn' => $request->nisn,
                'tanggal_daftar' => now()->format('Y-m-d'),
            ], fn($v) => $v !== null && $v !== ''));

            // Tahapan: akun (1) selesai, sekarang di tahap 2 (Isi/lengkapi formulir)
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 2,
                'tahap_1_selesai' => true,
            ]);

            DB::commit();

            // Auto-login peserta (pakai session yang sama dengan LoginPesertaController)
            \Illuminate\Support\Facades\Auth::guard('pengguna')->logout();
            session()->forget(['token_id', 'tes_id', 'token_global_id', 'ujian_mode']);
            session([
                'peserta_id' => $peserta->id,
                'peserta_nama' => $peserta->nama,
                'peserta_nomor' => $peserta->nomor_pendaftaran,
            ]);
            session()->regenerate();

            $statusKuota = $peserta->status_kuota === Peserta::STATUS_KUOTA_WAITING
                ? ' Anda masuk Waiting List karena kuota sudah penuh.'
                : '';

            return redirect()->route('peserta.dashboard')
                ->with('pendaftaran_berhasil', true)
                ->with('success', "Pendaftaran berhasil! No Pendaftaran Anda: {$nomorPendaftaran}.{$statusKuota}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Pendaftaran gagal: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat mendaftar: ' . $e->getMessage());
        }
    }

    /**
     * Normalisasi No HP Indonesia menjadi format 08xxxxxxxxxx.
     */
    private function normalisasiNoHp(?string $no): string
    {
        $no = preg_replace('/[^0-9+]/', '', (string) $no);
        if ($no === '') {
            return '';
        }
        // +62 / 62 -> 0
        if (str_starts_with($no, '+62')) {
            $no = '0' . substr($no, 3);
        } elseif (str_starts_with($no, '62')) {
            $no = '0' . substr($no, 2);
        } elseif (!str_starts_with($no, '0')) {
            $no = '0' . $no;
        }

        return $no;
    }

    private function formatPeriodePublik($periodePendaftaran): array
    {
        $ringkasanKuota = $this->kuotaPendaftaranService->ringkasanBanyak($periodePendaftaran);

        return $periodePendaftaran->map(function ($tahun) use ($ringkasanKuota) {
            $ringkasan = $ringkasanKuota[$tahun->id] ?? $this->kuotaPendaftaranService->ringkasanTahun($tahun);

            return [
                'id' => (string) $tahun->id,
                'nama' => $tahun->nama,
                'default' => (bool) $tahun->default,
                'kuota' => $ringkasan,
                'gelombang' => $tahun->gelombangPendaftaran->map(function (GelombangPendaftaran $gelombang) use ($ringkasan) {
                    $status = $gelombang->statusPendaftaran();
                    $jadwalDibuka = $gelombang->sedangDibuka();
                    $statusLabel = $jadwalDibuka && $ringkasan['penuh']
                        ? 'Kuota Penuh - Waiting List'
                        : $status['label'];
                    $statusClass = $jadwalDibuka && $ringkasan['penuh']
                        ? 'warning text-dark'
                        : $status['class'];

                    return [
                        'id' => (string) $gelombang->id,
                        'nama' => $gelombang->nama,
                        'periode' => $gelombang->labelPeriodePendaftaran(),
                        'dibuka' => $jadwalDibuka,
                        'jadwal_dibuka' => $jadwalDibuka,
                        'status_label' => $statusLabel,
                        'status_class' => $statusClass,
                    ];
                })->values(),
            ];
        })->values()->all();
    }
}
