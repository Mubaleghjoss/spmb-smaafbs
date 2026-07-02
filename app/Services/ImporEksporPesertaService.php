<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Helpers\NomorPendaftaranHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Service untuk impor dan ekspor peserta
 * Kebutuhan: 3.2
 */
class ImporEksporPesertaService
{
    /**
     * Impor peserta dari file Excel
     * Format: Nama | Email | Telepon | Alamat | Asal Sekolah
     */
    public function imporDariExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Skip header row
        array_shift($rows);

        $hasil = [
            'sukses' => 0,
            'gagal' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            // Skip baris kosong
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $this->validasiBarisExcel($row, $baris);
                $this->simpanPesertaDariExcel($row);
                $hasil['sukses']++;
            } catch (\Exception $e) {
                $hasil['gagal']++;
                $hasil['errors'][] = "Baris {$baris}: " . $e->getMessage();
            }
        }

        return $hasil;
    }

    /**
     * Validasi baris Excel
     */
    private function validasiBarisExcel(array $row, int $baris): void
    {
        if (empty($row[0])) {
            throw new \Exception('Nama tidak boleh kosong');
        }

        if (empty($row[1])) {
            throw new \Exception('Email tidak boleh kosong');
        }

        if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Format email tidak valid');
        }

        // Cek email sudah ada
        if (Peserta::where('email', $row[1])->exists()) {
            throw new \Exception('Email sudah terdaftar');
        }
    }

    /**
     * Simpan peserta dari baris Excel
     */
    private function simpanPesertaDariExcel(array $row): Peserta
    {
        return DB::transaction(function () use ($row) {
            // Password akan otomatis di-hash oleh model cast 'hashed'
            $peserta = Peserta::create([
                'nomor_pendaftaran' => NomorPendaftaranHelper::generate(),
                'nama' => $row[0],
                'email' => $row[1],
                'password' => 'password123',
                'telepon' => $row[2] ?? null,
                'alamat' => $row[3] ?? null,
                'asal_sekolah' => $row[4] ?? null,
            ]);

            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 1,
                'tahap_1_selesai' => true,
            ]);

            return $peserta;
        });
    }

    /**
     * Ekspor peserta ke Excel
     */
    public function eksporKeExcel(?int $grupId = null): string
    {
        $query = Peserta::with(['tahapanSpmb', 'grup']);

        if ($grupId) {
            $query->whereHas('grup', fn($q) => $q->where('grup.id', $grupId));
        }

        $pesertaList = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = [
            'No. Pendaftaran', 'Nama', 'Email', 'Telepon', 
            'Alamat', 'Asal Sekolah', 'Grup', 'Tahap Saat Ini', 'Terdaftar'
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        // Data
        $row = 2;
        foreach ($pesertaList as $peserta) {
            $data = [
                $peserta->nomor_pendaftaran,
                $peserta->nama,
                $peserta->email,
                $peserta->telepon ?? '',
                $peserta->alamat ?? '',
                $peserta->asal_sekolah ?? '',
                $peserta->grup->pluck('nama')->implode(', '),
                $peserta->tahap_saat_ini,
                $peserta->created_at->format('d/m/Y H:i'),
            ];

            $sheet->fromArray($data, null, "A{$row}");
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temp file
        $filename = 'ekspor_peserta_' . date('Y-m-d_His') . '.xlsx';
        $path = storage_path('app/public/exports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    /**
     * Generate template Excel untuk impor
     */
    public function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['Nama*', 'Email*', 'Telepon', 'Alamat', 'Asal Sekolah'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        // Contoh data
        $contoh = ['Ahmad Fauzi', 'ahmad@email.com', '08123456789', 'Jl. Contoh No. 1', 'SMP Negeri 1'];
        $sheet->fromArray($contoh, null, 'A2');

        // Instruksi
        $sheet->setCellValue('A4', 'INSTRUKSI:');
        $sheet->setCellValue('A5', '- Kolom dengan tanda * wajib diisi');
        $sheet->setCellValue('A6', '- Email harus unik (belum terdaftar)');
        $sheet->setCellValue('A7', '- Password default: password123');

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'template_impor_peserta.xlsx';
        $path = storage_path('app/public/exports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }
}
