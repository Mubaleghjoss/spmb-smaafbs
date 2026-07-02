<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\TokenGlobalService;
use App\Models\Peserta;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private TokenGlobalService $tokenGlobalService
    ) {}

    /**
     * Tampilkan form login admin/operator
     */
    public function formLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login admin/operator
     */
    public function masuk(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        // Cek apakah akun terkunci
        if ($this->authService->cekKunciAkun($request->email)) {
            $sisaMenit = $this->authService->waktuSisaKunci($request->email);
            return back()
                ->withInput(['email' => $request->email])
                ->withErrors([
                    'email' => "Akun terkunci. Silakan coba lagi dalam {$sisaMenit} menit."
                ]);
        }

        $pengguna = $this->authService->autentikasi($request->email, $request->password);

        if (!$pengguna) {
            $attempts = $this->authService->ambilJumlahPercobaan($request->email);
            $remaining = 3 - $attempts;
            
            $message = 'Email atau password salah.';
            if ($remaining > 0 && $remaining < 3) {
                $message .= " Sisa percobaan: {$remaining}x";
            }

            return back()
                ->withInput(['email' => $request->email])
                ->withErrors(['email' => $message]);
        }

        $this->authService->buatSesi($pengguna);

        // Redirect semua role ke admin dashboard (akses menu diatur via middleware)
        return redirect()->route('admin.dashboard');
    }

    /**
     * Tampilkan form login peserta dengan token
     */
    public function formLoginToken(): View
    {
        return view('auth.login-token');
    }

    /**
     * Proses login peserta dengan token untuk langsung ujian
     */
    public function masukDenganToken(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string|min:6|max:20',
            'nomor_pendaftaran' => 'required|string',
        ], [
            'token.required' => 'Token wajib diisi',
            'token.min' => 'Token minimal 6 karakter',
            'token.max' => 'Token maksimal 20 karakter',
            'nomor_pendaftaran.required' => 'Nomor pendaftaran wajib diisi',
        ]);

        // Cari peserta berdasarkan nomor pendaftaran
        $peserta = Peserta::where('nomor_pendaftaran', $request->nomor_pendaftaran)->first();

        if (!$peserta) {
            return back()
                ->withInput()
                ->withErrors(['nomor_pendaftaran' => 'Nomor pendaftaran tidak ditemukan']);
        }

        // Coba autentikasi dengan token global terlebih dahulu
        $tokenGlobal = $this->authService->autentikasiTokenGlobal($request->token);

        if ($tokenGlobal) {
            // Catat penggunaan token global
            $this->tokenGlobalService->catatPenggunaan(
                $tokenGlobal,
                $peserta,
                $request->ip(),
                $request->userAgent()
            );

            // Buat sesi peserta dengan token global
            $this->authService->buatSesiPesertaTokenGlobal($peserta, $tokenGlobal);

            // Redirect ke daftar ujian (peserta bisa pilih tes mana yang mau dikerjakan)
            return redirect()->route('ujian.index')
                ->with('sukses', 'Login berhasil dengan token global. Silakan pilih tes yang ingin dikerjakan.');
        }

        // Jika bukan token global, coba token biasa (per-tes)
        $result = $this->authService->autentikasiToken($request->token);

        if (!$result) {
            return back()
                ->withInput()
                ->withErrors(['token' => 'Token tidak valid atau sudah kedaluwarsa']);
        }

        // Buat sesi peserta untuk ujian
        $this->authService->buatSesiPesertaUjian($peserta, $result['token'], $result['tes']);

        // Redirect langsung ke halaman konfirmasi ujian
        return redirect()->route('ujian.konfirmasi', $result['tes']->id);
    }

    /**
     * Logout
     */
    public function keluar(Request $request): RedirectResponse
    {
        $this->authService->hapusSesi();
        return redirect()->route('beranda')->with('success', 'Berhasil keluar');
    }

    /**
     * Logout peserta
     */
    public function keluarPeserta(Request $request): RedirectResponse
    {
        $this->authService->hapusSesiPeserta();
        return redirect()->route('beranda')->with('success', 'Berhasil keluar');
    }
}
