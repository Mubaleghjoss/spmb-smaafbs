<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekPeran
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$peran
     */
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        if (!auth('pengguna')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu');
        }

        $pengguna = auth('pengguna')->user();

        if (!in_array($pengguna->peran, $peran)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini');
        }

        return $next($request);
    }
}
