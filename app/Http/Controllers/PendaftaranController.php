<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Helpers\NomorPendaftaranHelper;
use App\Services\PengaturanService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PendaftaranController extends Controller
{
    public function __construct(
        private PengaturanService $pengaturanService
    ) {}

    /**
     * Tampilkan form pendaftaran
     */
    public function form(): View
    {
        $spmb = $this->pengaturanService->ambilSpmb();
        $branding = $this->pengaturanService->ambilBranding();
        
        // Cek apakah pendaftaran dibuka
        $pendaftaranDibuka = $spmb['pendaftaran_buka'] ?? false;
        $tanggalBuka = $spmb['tanggal_buka'] ?? null;
        $tanggalTutup = $spmb['tanggal_tutup'] ?? null;
        
        // Cek berdasarkan tanggal jika ada
        $now = Carbon::now();
        $pesanTutup = null;
        
        if (!$pendaftaranDibuka) {
            $pesanTutup = 'Pendaftaran SPMB saat ini sedang ditutup.';
        } elseif ($tanggalBuka && $now < Carbon::parse($tanggalBuka)) {
            $pendaftaranDibuka = false;
            $pesanTutup = 'Pendaftaran SPMB akan dibuka pada tanggal ' . Carbon::parse($tanggalBuka)->translatedFormat('d F Y') . '.';
        } elseif ($tanggalTutup && $now > Carbon::parse($tanggalTutup)->endOfDay()) {
            $pendaftaranDibuka = false;
            $pesanTutup = 'Pendaftaran SPMB telah ditutup pada tanggal ' . Carbon::parse($tanggalTutup)->translatedFormat('d F Y') . '.';
        }
        
        $syaratKetentuan = $this->pengaturanService->ambilSyaratKetentuan();
        
        return view('public.daftar', compact('pendaftaranDibuka', 'pesanTutup', 'spmb', 'branding', 'syaratKetentuan'));
    }

    /**
     * Proses pendaftaran peserta baru
     */
    public function proses(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => 'required|string|max:20|unique:peserta,telepon',
            'asal_sekolah' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'setuju' => 'required|accepted',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi',
            'telepon.required' => 'Nomor HP/WhatsApp wajib diisi',
            'telepon.unique' => 'Nomor HP sudah terdaftar',
            'asal_sekolah.required' => 'Asal sekolah wajib diisi',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'setuju.required' => 'Anda harus menyetujui syarat dan ketentuan',
        ]);

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
}
