<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class LoginPesertaController extends Controller
{
    /**
     * Tampilkan form login peserta
     */
    public function form(): View
    {
        return view('peserta.login');
    }

    /**
     * Proses login peserta
     */
    public function masuk(Request $request): RedirectResponse
    {
        $request->validate([
            'telepon' => 'required|string',
            'password' => 'required|string',
        ], [
            'telepon.required' => 'No HP wajib diisi',
            'password.required' => 'Password wajib diisi',
        ]);

        $peserta = Peserta::where('telepon', $request->telepon)->first();

        if (!$peserta || !Hash::check($request->password, $peserta->password)) {
            return back()
                ->withInput(['telepon' => $request->telepon])
                ->withErrors(['telepon' => 'No HP atau password salah']);
        }

        // Simpan session peserta
        session([
            'peserta_id' => $peserta->id,
            'peserta_nama' => $peserta->nama,
            'peserta_nomor' => $peserta->nomor_pendaftaran,
        ]);
        session()->regenerate();

        return redirect()->route('peserta.dashboard')
            ->with('success', 'Selamat datang, ' . $peserta->nama);
    }

    /**
     * Logout peserta
     */
    public function keluar(Request $request): RedirectResponse
    {
        session()->forget(['peserta_id', 'peserta_nama', 'peserta_nomor']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('beranda')
            ->with('success', 'Berhasil keluar');
    }
}
