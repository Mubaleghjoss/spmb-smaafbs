<?php

namespace App\Http\Middleware;

use App\Models\Peserta;
use App\Services\PengaturanService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanTahapSpmbDibuka
{
    public function __construct(private PengaturanService $pengaturanService) {}

    public function handle(Request $request, Closure $next, string $tahap): Response
    {
        $nomorTahap = (int) $tahap;
        $peserta = Peserta::with('tahapanSpmb')->find(session('peserta_id'));

        if (!$peserta) {
            return redirect()->route('peserta.login')
                ->with('error', 'Sesi peserta tidak ditemukan. Silakan login kembali.');
        }

        $kolomSelesai = "tahap_{$nomorTahap}_selesai";
        if ($peserta->tahapanSpmb?->$kolomSelesai) {
            return $next($request);
        }

        $status = $this->pengaturanService->statusAksesTahap($nomorTahap);
        if (!($status['dibuka'] ?? true)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', $status['alasan'] ?? 'Tahap ini belum dibuka oleh admin.');
        }

        return $next($request);
    }
}
