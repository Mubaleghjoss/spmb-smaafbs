<?php

namespace App\Services;

use App\Enums\StatusPembayaran;
use App\Models\Pembayaran;
use App\Models\Peserta;
use Illuminate\Support\Collection;

class TahapEnamPembayaranService
{
    /** @return array{tagihan: float, terverifikasi: float, sisa: float, lunas: bool} */
    public function ringkasan(Peserta $peserta): array
    {
        $tagihan = (float) (app(PengaturanService::class)->ambilSpmb()['biaya_pelunasan'] ?? 0);
        if ($tagihan <= 0) {
            return ['tagihan' => 0.0, 'terverifikasi' => 0.0, 'sisa' => 0.0, 'lunas' => false];
        }

        $terverifikasi = min($tagihan, (float) Pembayaran::query()
            ->where('peserta_id', $peserta->id)
            ->where('jenis', 'pertama')
            ->where('status', StatusPembayaran::TERVERIFIKASI->value)
            ->sum('nominal'));
        $sisa = max(0, $tagihan - $terverifikasi);

        return [
            'tagihan' => $tagihan,
            'terverifikasi' => $terverifikasi,
            'sisa' => $sisa,
            'lunas' => $tagihan > 0 && $sisa <= 0,
        ];
    }

    /** @return Collection<int, Pembayaran> */
    public function riwayat(Peserta $peserta): Collection
    {
        return Pembayaran::query()
            ->where('peserta_id', $peserta->id)
            ->where('jenis', 'pertama')
            ->with('verifikator')
            ->latest()
            ->get();
    }
}
