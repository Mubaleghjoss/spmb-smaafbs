<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Support\PesertaPhoneNormalizer;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AkunController extends Controller
{
    public function ubahUsername(Request $request): RedirectResponse
    {
        $peserta = $this->pesertaSaatIni();
        if (! $peserta) {
            return $this->redirectSesiInvalid();
        }

        if (! $this->izinMencoba($request, $peserta, 'username')) {
            return back()->withErrors(['username' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.'])->withInput($request->only('username'));
        }

        $username = PesertaPhoneNormalizer::normalize($request->input('username'));
        $request->merge(['username' => $username ?? '']);
        $data = $request->validate([
            'username' => ['required', 'string', 'max:15', 'regex:/^08[0-9]{8,13}$/', Rule::unique('peserta', 'telepon')->ignore($peserta->getKey())],
            'current_password' => ['required', 'string'],
        ], [
            'username.regex' => 'Username harus berupa nomor HP 10–15 digit dan diawali 08.',
            'username.unique' => 'Username tersebut sudah digunakan.',
            'current_password.required' => 'Password saat ini wajib diisi.',
        ]);

        if (! Hash::check($data['current_password'], (string) $peserta->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.'])->withInput(['username' => $data['username']]);
        }

        try {
            $peserta->forceFill(['telepon' => $data['username']])->save();
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKey($exception)) {
                throw $exception;
            }

            return back()->withErrors(['username' => 'Username tersebut sudah digunakan.'])->withInput(['username' => $data['username']]);
        }

        $request->session()->regenerate();

        return back()->with('success', 'Username berhasil diubah. Password tidak ikut ditampilkan atau diubah.');
    }

    public function ubahPassword(Request $request): RedirectResponse
    {
        $peserta = $this->pesertaSaatIni();
        if (! $peserta) {
            return $this->redirectSesiInvalid();
        }

        if (! $this->izinMencoba($request, $peserta, 'password')) {
            return back()->withErrors(['current_password' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.']);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if (! Hash::check($data['current_password'], (string) $peserta->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        $peserta->forceFill(['password' => Hash::make($data['password'])])->save();
        $request->session()->regenerate();

        return back()->with('success', 'Password berhasil diubah. Simpan password baru dengan aman.');
    }

    private function pesertaSaatIni(): ?Peserta
    {
        $id = session('peserta_id');

        return $id ? Peserta::find($id) : null;
    }

    private function redirectSesiInvalid(): RedirectResponse
    {
        session()->forget(['peserta_id', 'peserta_nama', 'peserta_nomor']);

        return redirect()->route('peserta.login')->with('error', 'Sesi peserta tidak ditemukan. Silakan login kembali.');
    }

    private function rateLimitKey(Request $request, Peserta $peserta, string $action): string
    {
        return sprintf('peserta-account:%s:%s:%s:%s', $peserta->getKey(), $request->ip() ?: 'unknown', $action, app()->environment());
    }

    private function izinMencoba(Request $request, Peserta $peserta, string $action): bool
    {
        $key = $this->rateLimitKey($request, $peserta, $action);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            || (($exception->errorInfo[1] ?? null) === 1062);
    }
}
