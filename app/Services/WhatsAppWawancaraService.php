<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\Wawancara;

class WhatsAppWawancaraService
{
    public const CUSTOM_NOTE_LABEL = 'Catatan tambahan dari Tim SPMB';

    /**
     * @return array<int, array{label:string,nama:string,number:string,message:string}>
     */
    public function draftsFor(Peserta $peserta): array
    {
        $formulir = $peserta->formulirSpmb;
        $wawancara = $peserta->wawancara;
        $contacts = [
            ['label' => 'WA Anak', 'nama' => $peserta->nama, 'nomor' => $peserta->telepon],
            ['label' => 'WA Ayah', 'nama' => $formulir?->nama_ayah ?: 'Ayah/Wali', 'nomor' => $formulir?->telepon_ayah],
            ['label' => 'WA Ibu', 'nama' => $formulir?->nama_ibu ?: 'Ibu/Wali', 'nomor' => $formulir?->telepon_ibu],
        ];

        $drafts = [];
        $seen = [];
        foreach ($contacts as $contact) {
            $number = self::normalizeNumber($contact['nomor']);
            if (! $number || isset($seen[$number])) {
                continue;
            }

            $seen[$number] = true;
            $drafts[] = [
                'label' => $contact['label'],
                'nama' => $contact['nama'],
                'number' => $number,
                'message' => $this->buildMessage($peserta, $wawancara),
            ];
        }

        return $drafts;
    }

    public function buildMessage(Peserta $peserta, ?Wawancara $wawancara, ?string $catatanTambahan = null): string
    {
        $missing = $this->missingSteps($wawancara);
        $lines = [
            "Assalamu'alaikum warahmatullahi wabarakatuh.",
            '',
            'Kami dari Tim SPMB SMA Al Furqon Boarding School.',
            '',
            'Terkait proses wawancara dan verifikasi Tahap 5 untuk:',
            'Nama calon peserta didik: ' . $peserta->nama,
            '',
        ];

        if ($missing !== []) {
            $lines[] = 'Mohon dilengkapi langkah berikut secara berurutan:';
            foreach ($missing as $step) {
                $lines[] = '- ' . $step;
            }
        } else {
            $lines[] = 'Secara sistem, langkah utama Tahap 5 sudah terisi.';
            $lines[] = 'Mohon tetap menunggu arahan Tim SPMB apabila ada perbaikan atau verifikasi tambahan.';
        }

        $lines = array_merge($lines, [
            '',
            'Silakan membuka akun SPMB dan melengkapi atau memperbaiki data sesuai arahan.',
            'Jika ada kendala, mohon balas pesan ini agar dapat kami bantu.',
        ]);

        $catatanTambahan = trim((string) $catatanTambahan);
        if ($catatanTambahan !== '') {
            $lines = array_merge($lines, ['', self::CUSTOM_NOTE_LABEL . ':', $catatanTambahan]);
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    public function missingSteps(?Wawancara $wawancara): array
    {
        if (! $wawancara) {
            return [
                'Form wawancara dan verifikasi Tahap 5 belum diisi.',
                'Rekaman bacaan Al-Qur\'an belum kami terima.',
                'Tes Pegon belum kami terima.',
                'Surat pernyataan siswa dan orang tua/wali belum kami terima.',
            ];
        }

        $missing = [];
        if (! $wawancara->file_voice_quran) {
            $missing[] = 'Rekaman bacaan Al-Qur\'an belum kami terima.';
        }
        if (! $wawancara->file_tes_pegon) {
            $missing[] = 'Tes Pegon belum kami terima.';
        }
        if (empty($wawancara->surat_pernyataan_siswa)) {
            $missing[] = 'Surat pernyataan siswa belum kami terima.';
        }
        if (empty($wawancara->surat_pernyataan_ortu)) {
            $missing[] = 'Surat pernyataan orang tua/wali belum kami terima.';
        }

        return $missing;
    }

    public static function normalizeNumber(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . ltrim($digits, '0');
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62' . ltrim($digits, '0');
        }

        return preg_match('/^62\d{7,14}$/', $digits) ? $digits : null;
    }
}
