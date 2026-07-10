<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Helpers\NomorPendaftaranHelper;
use App\Services\PengaturanService;
use App\Services\PeriodePendaftaranService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PendaftaranController extends Controller
{
    public function __construct(
        private PengaturanService $pengaturanService,
        private PeriodePendaftaranService $periodePendaftaranService
    ) {}

    /**
     * Tampilkan form pendaftaran
     */
    public function form(): View
    {
        $spmb = $this->pengaturanService->ambilSpmb();
        $branding = $this->pengaturanService->ambilBranding();
        
        [$pendaftaranDibuka, $pesanTutup] = $this->statusPendaftaran($spmb);
        $periodePendaftaran = $this->periodePendaftaranService->pilihanPublik();

        if ($pendaftaranDibuka && $periodePendaftaran->isEmpty()) {
            $pendaftaranDibuka = false;
            $pesanTutup = 'Belum ada tahun ajaran dan gelombang pendaftaran yang sedang dibuka.';
        }

        $tahunDefaultId = $periodePendaftaran->firstWhere('default', true)?->id
            ?? $periodePendaftaran->first()?->id;
        $syaratKetentuan = $this->pengaturanService->ambilSyaratKetentuan();
        
        return view('public.daftar', compact(
            'pendaftaranDibuka',
            'pesanTutup',
            'spmb',
            'branding',
            'syaratKetentuan',
            'periodePendaftaran',
            'tahunDefaultId'
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

            // Buat peserta baru
            // Password akan otomatis di-hash oleh model cast 'hashed'
            $peserta = Peserta::create([
                'nomor_pendaftaran' => $nomorPendaftaran,
                'nama' => $request->nama,
                'telepon' => $request->telepon,
                'asal_sekolah' => $request->asal_sekolah,
                'password' => $request->password,
                ...$kategori,
            ]);

            // Buat record tahapan SPMB
            // Tahap 1 (buat akun) otomatis selesai, jadi tahap_saat_ini = 2
            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 2,
                'tahap_1_selesai' => true,
            ]);

            DB::commit();

            return redirect()->route('peserta.login')
                ->with('success', "Pendaftaran berhasil! Nomor pendaftaran Anda: {$nomorPendaftaran}. Silakan login dengan No HP Anda.");

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
        $tanggalBuka = $spmb['tanggal_buka'] ?? null;
        $waktuBuka = $spmb['waktu_buka'] ?? null;
        $tanggalTutup = $spmb['tanggal_tutup'] ?? null;
        $waktuTutup = $spmb['waktu_tutup'] ?? null;
        $now = Carbon::now();

        if (!$dibuka) {
            return [false, 'Pendaftaran SPMB saat ini sedang ditutup.'];
        }

        $mulai = $tanggalBuka
            ? Carbon::parse($tanggalBuka . ' ' . ($waktuBuka ?: '00:00:00'))
            : null;
        $selesai = $tanggalTutup
            ? Carbon::parse($tanggalTutup . ' ' . ($waktuTutup ?: '23:59:59'))
            : null;

        if ($mulai && $now < $mulai) {
            return [
                false,
                'Pendaftaran SPMB akan dibuka pada '
                    . $mulai->translatedFormat($waktuBuka ? 'd F Y H:i' : 'd F Y')
                    . ($waktuBuka ? ' WIB' : '')
                    . '.',
            ];
        }

        if ($selesai && $now > $selesai) {
            return [
                false,
                'Pendaftaran SPMB telah ditutup pada '
                    . $selesai->translatedFormat($waktuTutup ? 'd F Y H:i' : 'd F Y')
                    . ($waktuTutup ? ' WIB' : '')
                    . '.',
            ];
        }

        return [true, null];
    }
}
