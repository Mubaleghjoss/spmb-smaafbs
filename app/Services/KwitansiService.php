<?php

namespace App\Services;

use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk mengelola kwitansi pembayaran
 */
class KwitansiService
{
    private PengaturanService $pengaturanService;

    public function __construct(PengaturanService $pengaturanService)
    {
        $this->pengaturanService = $pengaturanService;
    }

    /**
     * Generate nomor kwitansi unik
     * Format: KWT/YYYY/MM/NNNN
     */
    public function generateNomorKwitansi(): string
    {
        $tahun = date('Y');
        $bulan = date('m');
        $prefix = "KWT/{$tahun}/{$bulan}/";

        // Ambil nomor urut terakhir untuk bulan ini
        $lastNumber = Pembayaran::where('nomor_kwitansi', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(nomor_kwitansi, -4) AS UNSIGNED) DESC')
            ->value('nomor_kwitansi');

        if ($lastNumber) {
            // Extract nomor urut dari nomor kwitansi terakhir
            $lastSeq = (int) substr($lastNumber, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Ambil data lengkap kwitansi untuk pembayaran
     */
    public function ambilKwitansi(Pembayaran $pembayaran): ?array
    {
        // Hanya return data jika pembayaran sudah terverifikasi
        if ($pembayaran->status !== 'terverifikasi') {
            return null;
        }

        // Jika belum ada nomor kwitansi, generate dan simpan
        if (empty($pembayaran->nomor_kwitansi)) {
            $nomorKwitansi = $this->generateNomorKwitansi();
            $pembayaran->update(['nomor_kwitansi' => $nomorKwitansi]);
            $pembayaran->refresh();
        }

        $pembayaran->load(['peserta', 'verifikator']);
        $template = $this->ambilTemplate();
        $branding = $this->pengaturanService->ambilBranding();

        return [
            // Data Kwitansi
            'nomor_kwitansi' => $pembayaran->nomor_kwitansi,
            'tanggal_bayar' => $pembayaran->created_at,
            'tanggal_verifikasi' => $pembayaran->diverifikasi_pada,
            
            // Data Peserta
            'nama_peserta' => $pembayaran->peserta->nama,
            'nomor_pendaftaran' => $pembayaran->peserta->nomor_pendaftaran,
            
            // Data Pembayaran
            'jenis_pembayaran' => $pembayaran->jenis === 'formulir' ? 'Pembayaran Formulir SPMB' : 'Pembayaran Pertama',
            'nominal' => $pembayaran->nominal ?? $this->pengaturanService->ambil('biaya_formulir', 0),
            
            // Data Verifikator
            'nama_verifikator' => $pembayaran->verifikator?->nama ?? 'Admin',
            
            // Template
            'template' => $template,
            
            // Branding
            'branding' => $branding,
        ];
    }

    /**
     * Ambil template kwitansi dari pengaturan
     */
    public function ambilTemplate(): array
    {
        return $this->pengaturanService->ambilTemplateKwitansi();
    }

    /**
     * Simpan template kwitansi
     */
    public function simpanTemplate(array $data): void
    {
        $this->pengaturanService->simpanTemplateKwitansi($data);
    }

    /**
     * Generate dan simpan nomor kwitansi ke pembayaran
     */
    public function generateDanSimpan(Pembayaran $pembayaran): string
    {
        $nomorKwitansi = $this->generateNomorKwitansi();
        $pembayaran->update(['nomor_kwitansi' => $nomorKwitansi]);
        return $nomorKwitansi;
    }
}
