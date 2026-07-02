<?php

namespace App\Http\Middleware;

use App\Models\Peserta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekPeserta
{
    /**
     * Handle an incoming request.
     * Middleware untuk memastikan peserta sudah login
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pesertaId = session('peserta_id');

        if (!$pesertaId || !Peserta::whereKey($pesertaId)->exists()) {
            session()->forget([
                'peserta_id',
                'peserta_nama',
                'peserta_nomor',
                'token_id',
                'tes_id',
                'token_global_id',
                'ujian_mode',
            ]);

            return redirect()->route('peserta.login')
                ->with('error', 'Silakan login terlebih dahulu');
        }

        return $next($request);
    }
}
