<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Helpers\NomorPendaftaranHelper;
use App\Models\GelombangPendaftaran;
use App\Services\KuotaPendaftaranService;
use App\Services\PengaturanService;
use App\Services\PeriodePendaftaranService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PendaftaranController extends Controller
{
    public function __construct(
        private PengaturanService $pengaturanService,
        private PeriodePendaftaranService $periodePendaftaranService,
        private KuotaPendaftaranService $kuotaPendaftaranService
    ) {}

    /**
     * Tampilkan form pendaftaran
     */
    public function form(): View
    {
        $spmb = $this->pengaturanService->ambilSpmb();
        $branding = $this->pengaturanService->ambilBranding();
        
        [$pendaftaranDibuka, $pesanTutup] = $this->statusPendaftaran($spmb);
        $periodePendaftaran = $this->periodePendaftaranService->pilihanPublikDenganStatus();
        $jadwalBerikutnya = $this->periodePendaftaranService->jadwalPublikBerikutnya();
        $adaGelombangDibuka = $periodePendaftaran->contains(
            fn($tahun) => $tahun->gelombangPendaftaran->contains(
                fn(GelombangPendaftaran $gelombang) => $gelombang->sedangDibuka()
            )
        );

        if ($pendaftaranDibuka && ! $adaGelombangDibuka) {
            $pendaftaranDibuka = false;
            $pesanTutup = 'Belum ada gelombang pendaftaran yang sedang dibuka.';
        }

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
     * Proses pendaftaran peserta baru
     */
    public function proses(Request $request): RedirectResponse
    {
        [$pendaftaranDibuka, $pesanTutup] = $this->statusPendaftaran(
            $this->pengaturanService->ambilSpmb()
        );

        if (!$pendaftaranDibuka) {
            return back()->withInput()->with('error', $pesanTutup);
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => 'required|string|max:20|unique:peserta,telepon',
            'asal_sekolah' => 'required|string|max:255',
            'tahun_ajaran_id' => 'required|integer|exists:tahun_ajaran,id',
            'gelombang_pendaftaran_id' => 'required|integer|exists:gelombang_pendaftaran,id',
            'jenis_pendaftaran' => 'required|in:siswa_baru,pindahan',
            'kelas_tujuan' => 'required|integer|in:10,11',
            'password' => 'required|string|min:8|confirmed',
            'setuju' => 'required|accepted',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi',
            'telepon.required' => 'Nomor HP/WhatsApp wajib diisi',
            'telepon.unique' => 'Nomor HP sudah terdaftar',
            'asal_sekolah.required' => 'Asal sekolah wajib diisi',
            'tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih',
            'gelombang_pendaftaran_id.required' => 'Gelombang pendaftaran wajib dipilih',
            'jenis_pendaftaran.required' => 'Jenis pendaftaran wajib dipilih',
            'kelas_tujuan.required' => 'Kelas tujuan wajib dipilih',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'setuju.required' => 'Anda harus menyetujui syarat dan ketentuan',
        ]);

        $kategori = $this->periodePendaftaranService->validasiKategori($validated, true);

        try {
            DB::beginTransaction();

            // Generate nomor pendaftaran
            $nomorPendaftaran = NomorPendaftaranHelper::generate();
            $kuota = $this->kuotaPendaftaranService->siapkanAtributPesertaBaru($kategori['tahun_ajaran_id']);

            // Buat peserta baru
            // Password akan otomatis di-hash oleh model cast 'hashed'
            $peserta = Peserta::create([
                'nomor_pendaftaran' => $nomorPendaftaran,
                'nama' => $request->nama,
                'telepon' => $request->telepon,
                'asal_sekolah' => $request->asal_sekolah,
                'password' => $request->password,
                ...$kategori,
                ...$kuota,
            ]);

            // Buat record tahapan SPMB
            // Tahap 1 (buat akun) otomatis selesai, jadi tahap_saat_ini = 2
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 2,
                'tahap_1_selesai' => true,
            ]);

            DB::commit();

            $statusKuota = $peserta->status_kuota === Peserta::STATUS_KUOTA_WAITING
                ? 'Anda masuk Waiting List karena kuota tahun ajaran sudah penuh.'
                : 'Anda masuk kuota pendaftaran.';

            return redirect()->route('peserta.login')
                ->with('success', "Pendaftaran berhasil! Nomor pendaftaran Anda: {$nomorPendaftaran}. {$statusKuota} Silakan login dengan No HP Anda.");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Pendaftaran gagal: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $request->except('password', 'password_confirmation')
            ]);
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat mendaftar: ' . $e->getMessage());
        }
    }

    private function statusPendaftaran(array $spmb): array
    {
        $dibuka = (bool) ($spmb['pendaftaran_buka'] ?? false);

        if (!$dibuka) {
            return [false, 'Pendaftaran SPMB saat ini ditutup oleh admin.'];
        }

        return [true, null];
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
                    $dibuka = $gelombang->sedangDibuka();

                    return [
                        'id' => (string) $gelombang->id,
                        'nama' => $gelombang->nama,
                        'periode' => $gelombang->labelPeriodePendaftaran(),
                        'dibuka' => $dibuka,
                        'status_label' => $dibuka && $ringkasan['penuh']
                            ? 'Kuota Penuh - Waiting List'
                            : $status['label'],
                        'status_class' => $dibuka && $ringkasan['penuh']
                            ? 'warning text-dark'
                            : $status['class'],
                    ];
                })->values(),
            ];
        })->values()->all();
    }
}
