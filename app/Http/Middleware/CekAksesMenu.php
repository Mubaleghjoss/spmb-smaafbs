<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekAksesMenu
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('pengguna')->check()) {
            return redirect()->route('login');
        }

        $pengguna = auth('pengguna')->user();
        $routeName = $request->route()->getName();

        // Cek apakah pengguna bisa akses route ini
        if (!$pengguna->bisaAksesRoute($routeName)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini');
        }

        return $next($request);
    }
}
