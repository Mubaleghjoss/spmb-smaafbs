<?php

namespace App\Http\Middleware;

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
        if (!session('peserta_id')) {
            return redirect()->route('login.token')
                ->with('error', 'Silakan login terlebih dahulu');
        }

        return $next($request);
    }
}
