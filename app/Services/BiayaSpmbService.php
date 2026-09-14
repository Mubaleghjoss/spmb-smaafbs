<?php

namespace App\Services;

use App\Models\FormulirSpmb;

class BiayaSpmbService
{
    /**
     * Mengambil rincian biaya yang berlaku berdasarkan pilihan domisili peserta.
     * Tarif lama tetap menjadi fallback agar data lama tidak rusak saat migrasi.
     *
     * @return array{lengkap: bool, domisili: ?string, label_domisili: ?string, formulir: ?int, total: ?int, gambar: ?string}
     */
    public function untukFormulir(array $spmb, ?FormulirSpmb $formulir): array
    {
        $domisili = $formulir?->domisili_biaya;
        if (! $this->domisiliSudahLengkap($formulir)) {
            return [
                'lengkap' => false,
                'domisili' => $domisili,
                'label_domisili' => null,
                'formulir' => null,
                'total' => null,
                'gambar' => null,
            ];
        }

        $dalamKota = $domisili === FormulirSpmb::DOMISILI_DALAM_TANGERANG_KOTA;
        $suffix = $dalamKota ? 'dalam_kota' : 'luar_kota';

        return [
            'lengkap' => true,
            'domisili' => $domisili,
            'label_domisili' => $dalamKota ? 'Dalam Tangerang Kota' : 'Luar Tangerang Kota',
            'formulir' => (int) ($spmb["biaya_formulir_{$suffix}"] ?? $spmb['biaya_formulir'] ?? 0),
            'total' => (int) ($spmb["biaya_total_{$suffix}"] ?? 0),
            'gambar' => $spmb["gambar_rincian_biaya_{$suffix}"] ?? null,
        ];
    }

    public function domisiliSudahLengkap(?FormulirSpmb $formulir): bool
    {
        if (! $formulir || ! in_array($formulir->domisili_biaya, FormulirSpmb::DOMISILI_VALID, true)) {
            return false;
        }

        return $formulir->domisili_biaya !== FormulirSpmb::DOMISILI_LUAR_TANGERANG_KOTA
            || filled($formulir->nama_daerah_luar);
    }
}
