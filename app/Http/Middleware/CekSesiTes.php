<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SesiTes;

class CekSesiTes
{
    /**
     * Handle an incoming request.
     * Middleware untuk memastikan peserta memiliki sesi tes aktif
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pesertaId = session('peserta_id');
        $tesId = session('tes_id');

        if (!$pesertaId || !$tesId) {
            return redirect()->route('login.token')
                ->with('error', 'Sesi tidak valid. Silakan login kembali dengan token.');
        }

        // Cek apakah ada sesi tes aktif
        $sesiTes = SesiTes::where('peserta_id', $pesertaId)
            ->where('tes_id', $tesId)
            ->whereIn('status', ['aktif', 'berlangsung'])
            ->first();

        // Simpan sesi tes ke request untuk digunakan di controller
        if ($sesiTes) {
            $request->merge(['sesi_tes' => $sesiTes]);
        }

        return $next($request);
    }
}
