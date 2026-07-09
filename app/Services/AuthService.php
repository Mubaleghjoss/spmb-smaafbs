<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\Peserta;
use App\Models\Token;
use App\Models\TokenGlobal;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AuthService
{
    private const MAX_LOGIN_ATTEMPTS = 3;
    private const LOCKOUT_MINUTES = 30;

    private function lupakanDataSesiPeserta(): void
    {
        session()->forget([
            'peserta_id',
            'peserta_nama',
            'peserta_nomor',
            'token_id',
            'tes_id',
            'token_global_id',
            'ujian_mode',
        ]);
    }

    /**
     * Autentikasi pengguna dengan email dan password
     */
    public function autentikasi(string $email, string $password): ?Pengguna
    {
        $result = $this->autentikasiDenganStatus($email, $password);

        return $result['pengguna'];
    }

    /**
     * Autentikasi pengguna sekaligus mengembalikan alasan gagal.
     *
     * @return array{status:string,pengguna:?Pengguna}
     */
    public function autentikasiDenganStatus(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $pengguna = Pengguna::where('email', $email)->first();

        if (! $pengguna) {
            $pengguna = $this->pulihkanPenggunaDefaultJikaCocok($email, $password);

            if (! $pengguna) {
                $this->catatGagalLogin($email);

                return ['status' => 'not_found', 'pengguna' => null];
            }
        }

        if ($pengguna->dikunci_sampai && $pengguna->dikunci_sampai > now()) {
            return ['status' => 'locked', 'pengguna' => null];
        }

        if (! $pengguna->aktif) {
            return ['status' => 'inactive', 'pengguna' => null];
        }

        if (!$pengguna || !Hash::check($password, $pengguna->password)) {
            $this->catatGagalLogin($email);

            return ['status' => 'invalid_password', 'pengguna' => null];
        }

        $this->resetGagalLogin($email);

        return ['status' => 'success', 'pengguna' => $pengguna];
    }

    private function pulihkanPenggunaDefaultJikaCocok(string $email, string $password): ?Pengguna
    {
        if (! config('auth.default_pengguna.auto_repair', false)) {
            return null;
        }

        foreach (config('auth.default_pengguna.accounts', []) as $account) {
            $defaultEmail = strtolower(trim((string) ($account['email'] ?? '')));
            $defaultPassword = (string) ($account['password'] ?? '');

            if ($defaultEmail === '' || $defaultPassword === '') {
                continue;
            }

            if (! hash_equals($defaultEmail, $email) || ! hash_equals($defaultPassword, $password)) {
                continue;
            }

            $pengguna = Pengguna::query()->create([
                'nama' => $account['nama'] ?: 'Pengguna SPMB',
                'email' => $defaultEmail,
                'password' => $defaultPassword,
                'peran' => in_array($account['peran'] ?? 'operator', ['admin', 'operator'], true)
                    ? $account['peran']
                    : 'operator',
                'aktif' => true,
                'percobaan_login' => 0,
                'dikunci_sampai' => null,
            ]);

            Cache::forget('login_attempts_' . md5($defaultEmail));

            return $pengguna;
        }

        return null;
    }

    /**
     * Autentikasi peserta dengan token
     */
    public function autentikasiToken(string $kodeToken): ?array
    {
        $token = Token::where('kode', $kodeToken)
            ->where('terpakai', false)
            ->where('kedaluwarsa', '>', now())
            ->with('tes')
            ->first();

        if (!$token || !$token->tes) {
            return null;
        }

        return [
            'token' => $token,
            'tes' => $token->tes,
        ];
    }

    /**
     * Autentikasi peserta dengan token global
     * Token global bisa dipakai banyak peserta untuk semua tes
     */
    public function autentikasiTokenGlobal(string $kodeToken): ?TokenGlobal
    {
        $token = TokenGlobal::where('kode', $kodeToken)->first();

        if (!$token || !$token->masihValid()) {
            return null;
        }

        return $token;
    }

    /**
     * Buat sesi untuk pengguna
     */
    public function buatSesi(Pengguna $pengguna): void
    {
        $this->lupakanDataSesiPeserta();
        Auth::guard('pengguna')->login($pengguna);
        session()->regenerate();
    }

    /**
     * Buat sesi untuk peserta (login biasa)
     */
    public function buatSesiPeserta(Peserta $peserta, ?Token $token = null): void
    {
        Auth::guard('pengguna')->logout();
        $this->lupakanDataSesiPeserta();

        $sessionData = [
            'peserta_id' => $peserta->id,
            'peserta_nama' => $peserta->nama,
        ];

        if ($token) {
            $sessionData['token_id'] = $token->id;
            $sessionData['tes_id'] = $token->tes_id;
        }

        session($sessionData);
        session()->regenerate();
    }

    /**
     * Buat sesi untuk peserta ujian (login dengan token)
     */
    public function buatSesiPesertaUjian(Peserta $peserta, Token $token, $tes): void
    {
        Auth::guard('pengguna')->logout();
        $this->lupakanDataSesiPeserta();

        session([
            'peserta_id' => $peserta->id,
            'peserta_nama' => $peserta->nama,
            'token_id' => $token->id,
            'tes_id' => $tes->id,
            'ujian_mode' => true,
        ]);
        session()->regenerate();
    }

    /**
     * Buat sesi untuk peserta ujian dengan token global
     */
    public function buatSesiPesertaTokenGlobal(Peserta $peserta, TokenGlobal $tokenGlobal): void
    {
        Auth::guard('pengguna')->logout();
        $this->lupakanDataSesiPeserta();

        session([
            'peserta_id' => $peserta->id,
            'peserta_nama' => $peserta->nama,
            'token_global_id' => $tokenGlobal->id,
            'ujian_mode' => true,
        ]);
        session()->regenerate();
    }

    /**
     * Hapus sesi
     */
    public function hapusSesi(): void
    {
        Auth::guard('pengguna')->logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Hapus sesi peserta
     */
    public function hapusSesiPeserta(): void
    {
        $this->lupakanDataSesiPeserta();
        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Cek apakah akun terkunci
     */
    public function cekKunciAkun(string $email): bool
    {
        $pengguna = Pengguna::where('email', $email)->first();

        if (!$pengguna) {
            return false;
        }

        if ($pengguna->dikunci_sampai && $pengguna->dikunci_sampai > now()) {
            return true;
        }

        return false;
    }

    /**
     * Ambil waktu sisa kunci akun
     */
    public function waktuSisaKunci(string $email): ?int
    {
        $pengguna = Pengguna::where('email', $email)->first();

        if (!$pengguna || !$pengguna->dikunci_sampai) {
            return null;
        }

        $sisa = now()->diffInMinutes($pengguna->dikunci_sampai, false);
        return $sisa > 0 ? $sisa : null;
    }

    /**
     * Catat percobaan login gagal
     */
    private function catatGagalLogin(string $email): void
    {
        $key = 'login_attempts_' . md5($email);
        $attempts = Cache::get($key, 0) + 1;

        Cache::put($key, $attempts, now()->addMinutes(self::LOCKOUT_MINUTES));

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $pengguna = Pengguna::where('email', $email)->first();
            if ($pengguna) {
                $pengguna->update([
                    'dikunci_sampai' => now()->addMinutes(self::LOCKOUT_MINUTES)
                ]);
            }
        }
    }

    /**
     * Reset counter gagal login
     */
    private function resetGagalLogin(string $email): void
    {
        $key = 'login_attempts_' . md5($email);
        Cache::forget($key);

        $pengguna = Pengguna::where('email', $email)->first();
        if ($pengguna && $pengguna->dikunci_sampai) {
            $pengguna->update(['dikunci_sampai' => null]);
        }
    }

    /**
     * Ambil jumlah percobaan login
     */
    public function ambilJumlahPercobaan(string $email): int
    {
        $key = 'login_attempts_' . md5($email);
        return Cache::get($key, 0);
    }
}
