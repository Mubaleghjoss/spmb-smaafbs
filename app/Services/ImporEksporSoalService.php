<?php

namespace App\Services;

use App\Models\Soal;
use App\Models\Jawaban;
use App\Models\Topik;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Service untuk impor dan ekspor soal
 * Kebutuhan: 2.3, 2.4, 2.6
 */
class ImporEksporSoalService
{
    /**
     * Impor soal dari file Excel
     * Format Template: Nomor | Pertanyaan | Tipe | Topik | Bobot | Jawaban A | Jawaban B | Jawaban C | Jawaban D | Jawaban Benar | Pembahasan
     * Format Ekspor: ID | Nomor | Pertanyaan | Tipe | Topik | Bobot | Jawaban A | Jawaban B | Jawaban C | Jawaban D | Jawaban Benar | Pembahasan | Status
     */
    public function imporDariExcel(string $filePath, int $penggunaId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Skip header row
        $header = array_shift($rows);

        // Deteksi format berdasarkan header
        $headerFirst = strtoupper(trim($header[0] ?? ''));
        $isExportFormat = $headerFirst === 'ID';
        // Deteksi kolom nomor - bisa "NOMOR*", "NOMOR", atau "ID"
        $hasNomorColumn = str_starts_with($headerFirst, 'NOMOR') || $headerFirst === 'ID';

        $hasil = [
            'sukses' => 0,
            'gagal' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $baris = $index + 2; // +2 karena skip header dan index mulai dari 0

            // Skip baris kosong
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $this->validasiBarisExcel($row, $baris, $isExportFormat, $hasNomorColumn);
                $this->simpanSoalDariExcel($row, $penggunaId, $isExportFormat, $hasNomorColumn);
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
    private function validasiBarisExcel(array $row, int $baris, bool $isExportFormat = false, bool $hasNomorColumn = true): void
    {
        // Kolom pertanyaan berdasarkan format
        // Export: ID(0) | Nomor(1) | Pertanyaan(2) ...
        // Template: Nomor(0) | Pertanyaan(1) ...
        $pertanyaanCol = $isExportFormat ? 2 : ($hasNomorColumn ? 1 : 0);
        $tipeCol = $isExportFormat ? 3 : ($hasNomorColumn ? 2 : 1);

        if (empty($row[$pertanyaanCol])) {
            throw new \Exception('Pertanyaan tidak boleh kosong');
        }

        $tipeValid = ['pilihan_ganda', 'jawaban_ganda', 'esai', 'benar_salah'];
        if (!empty($row[$tipeCol]) && !in_array($row[$tipeCol], $tipeValid)) {
            throw new \Exception('Tipe soal tidak valid: ' . $row[$tipeCol]);
        }
    }

    /**
     * Simpan soal dari baris Excel
     * Mendukung hingga 8 pilihan jawaban (A-H)
     */
    private function simpanSoalDariExcel(array $row, int $penggunaId, bool $isExportFormat = false, bool $hasNomorColumn = true): Soal
    {
        return DB::transaction(function () use ($row, $penggunaId, $isExportFormat, $hasNomorColumn) {
            // Kolom berdasarkan format
            // Export: ID(0) | Nomor(1) | Pertanyaan(2) | Tipe(3) | Topik(4) | Bobot(5) | JawA(6) | JawB(7) | JawC(8) | JawD(9) | JawE(10) | JawF(11) | JawG(12) | JawH(13) | Benar(14) | Pembahasan(15) | Status(16)
            // Template: Nomor(0) | Pertanyaan(1) | Tipe(2) | Topik(3) | Bobot(4) | JawA(5) | JawB(6) | JawC(7) | JawD(8) | JawE(9) | JawF(10) | JawG(11) | JawH(12) | Benar(13) | Pembahasan(14)
            
            if ($isExportFormat) {
                $nomorCol = 1;
                $pertanyaanCol = 2;
                $tipeCol = 3;
                $topikCol = 4;
                $bobotCol = 5;
                $jawabanACol = 6;
                $jawabanBenarCol = 14;
                $pembahasanCol = 15;
            } else {
                $nomorCol = 0;
                $pertanyaanCol = 1;
                $tipeCol = 2;
                $topikCol = 3;
                $bobotCol = 4;
                $jawabanACol = 5;
                $jawabanBenarCol = 13;
                $pembahasanCol = 14;
            }

            // Ambil nomor urutan
            $urutan = !empty($row[$nomorCol]) ? (int) $row[$nomorCol] : 0;

            // Cari atau buat topik
            $topikId = null;
            if (!empty($row[$topikCol])) {
                $topik = Topik::firstOrCreate(['nama' => trim($row[$topikCol])]);
                $topikId = $topik->id;
            }

            // Buat soal
            $soal = Soal::create([
                'pertanyaan' => $row[$pertanyaanCol],
                'tipe' => $row[$tipeCol] ?? 'pilihan_ganda',
                'topik_id' => $topikId,
                'bobot' => $row[$bobotCol] ?? 1,
                'pembahasan' => $row[$pembahasanCol] ?? null,
                'urutan' => $urutan,
                'aktif' => true,
                'dibuat_oleh' => $penggunaId,
            ]);

            // Simpan jawaban - mendukung hingga 8 pilihan (A-H)
            $jawabanBenar = strtoupper(trim($row[$jawabanBenarCol] ?? 'A'));
            $opsiJawaban = [
                'A' => $jawabanACol, 
                'B' => $jawabanACol + 1, 
                'C' => $jawabanACol + 2, 
                'D' => $jawabanACol + 3,
                'E' => $jawabanACol + 4,
                'F' => $jawabanACol + 5,
                'G' => $jawabanACol + 6,
                'H' => $jawabanACol + 7,
            ];

            foreach ($opsiJawaban as $huruf => $kolom) {
                if (!empty($row[$kolom])) {
                    Jawaban::create([
                        'soal_id' => $soal->id,
                        'isi_jawaban' => $row[$kolom],
                        'benar' => str_contains($jawabanBenar, $huruf),
                        'urutan' => ord($huruf) - ord('A'),
                    ]);
                }
            }

            return $soal;
        });
    }


    /**
     * Ekspor soal ke Excel
     * Mendukung hingga 8 pilihan jawaban (A-H)
     */
    public function eksporKeExcel(?int $topikId = null): string
    {
        $query = Soal::with(['topik', 'jawaban']);

        if ($topikId) {
            $query->where('topik_id', $topikId);
        }

        $soalList = $query->orderBy('urutan')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header dengan kolom Nomor - mendukung hingga 8 pilihan jawaban
        $headers = [
            'ID', 'Nomor', 'Pertanyaan', 'Tipe', 'Topik', 'Bobot',
            'Jawaban A', 'Jawaban B', 'Jawaban C', 'Jawaban D',
            'Jawaban E', 'Jawaban F', 'Jawaban G', 'Jawaban H',
            'Jawaban Benar', 'Pembahasan', 'Status'
        ];
        $sheet->fromArray($headers, null, 'A1');

        // Style header
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);

        // Data
        $row = 2;
        foreach ($soalList as $soal) {
            $jawaban = $soal->jawaban->sortBy('urutan');
            $jawabanBenar = $jawaban->where('benar', true)->map(fn($j) => chr(65 + $j->urutan))->implode(',');

            $data = [
                $soal->id,
                $soal->urutan ?? $row - 1,
                strip_tags($soal->pertanyaan),
                $soal->tipe,
                $soal->topik?->nama ?? '',
                $soal->bobot,
                $jawaban->get(0)?->isi_jawaban ?? '',
                $jawaban->get(1)?->isi_jawaban ?? '',
                $jawaban->get(2)?->isi_jawaban ?? '',
                $jawaban->get(3)?->isi_jawaban ?? '',
                $jawaban->get(4)?->isi_jawaban ?? '',
                $jawaban->get(5)?->isi_jawaban ?? '',
                $jawaban->get(6)?->isi_jawaban ?? '',
                $jawaban->get(7)?->isi_jawaban ?? '',
                $jawabanBenar,
                strip_tags($soal->pembahasan ?? ''),
                $soal->aktif ? 'Aktif' : 'Nonaktif',
            ];

            $sheet->fromArray($data, null, "A{$row}");
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temp file
        $filename = 'ekspor_soal_' . date('Y-m-d_His') . '.xlsx';
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
     * Mendukung hingga 8 pilihan jawaban (A-H)
     */
    public function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Soal');

        // Header dengan kolom Nomor - mendukung hingga 8 pilihan jawaban (A-H)
        $headers = [
            'Nomor*', 'Pertanyaan*', 'Tipe', 'Topik', 'Bobot',
            'Jawaban A*', 'Jawaban B*', 'Jawaban C', 'Jawaban D',
            'Jawaban E', 'Jawaban F', 'Jawaban G', 'Jawaban H',
            'Jawaban Benar*', 'Pembahasan'
        ];
        $sheet->fromArray($headers, null, 'A1');

        // Style header
        $headerStyle = $sheet->getStyle('A1:O1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setARGB('FF4CAF50');
        $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');

        // Contoh 1: Pilihan Ganda 4 opsi (1 jawaban benar)
        $contoh1 = [
            '1',
            'Apa ibu kota Indonesia?',
            'pilihan_ganda',
            'Pengetahuan Umum',
            '1',
            'Jakarta',
            'Bandung',
            'Surabaya',
            'Medan',
            '', '', '', '',
            'A',
            'Jakarta adalah ibu kota Indonesia sejak kemerdekaan.'
        ];
        $sheet->fromArray($contoh1, null, 'A2');

        // Contoh 2: Pilihan Ganda 6 opsi
        $contoh2 = [
            '2',
            'Manakah planet terbesar di tata surya?',
            'pilihan_ganda',
            'Astronomi',
            '1',
            'Merkurius',
            'Venus',
            'Bumi',
            'Mars',
            'Jupiter',
            'Saturnus',
            '', '',
            'E',
            'Jupiter adalah planet terbesar di tata surya kita.'
        ];
        $sheet->fromArray($contoh2, null, 'A3');

        // Contoh 3: Jawaban Ganda dengan 5 opsi
        $contoh3 = [
            '3',
            'Manakah yang termasuk negara ASEAN? (Pilih semua yang benar)',
            'jawaban_ganda',
            'Pengetahuan Umum',
            '2',
            'Indonesia',
            'Malaysia',
            'Jepang',
            'Thailand',
            'Vietnam',
            '', '', '',
            'A,B,D,E',
            'Indonesia, Malaysia, Thailand, dan Vietnam adalah anggota ASEAN.'
        ];
        $sheet->fromArray($contoh3, null, 'A4');

        // Contoh 4: Benar/Salah
        $contoh4 = [
            '4',
            'Matahari terbit dari arah barat.',
            'benar_salah',
            'Pengetahuan Umum',
            '1',
            'Benar',
            'Salah',
            '', '', '', '', '', '',
            'B',
            'Matahari terbit dari arah timur, bukan barat.'
        ];
        $sheet->fromArray($contoh4, null, 'A5');

        // Contoh 5: Pilihan Ganda 8 opsi (maksimal)
        $contoh5 = [
            '5',
            'Pilih bilangan prima di bawah ini:',
            'jawaban_ganda',
            'Matematika',
            '2',
            '2',
            '4',
            '7',
            '9',
            '11',
            '15',
            '17',
            '21',
            'A,C,E,G',
            '2, 7, 11, dan 17 adalah bilangan prima.'
        ];
        $sheet->fromArray($contoh5, null, 'A6');

        // Warna baris contoh
        $sheet->getStyle('A2:O2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A2:O2')->getFill()->getStartColor()->setARGB('FFE8F5E9');
        
        $sheet->getStyle('A3:O3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A3:O3')->getFill()->getStartColor()->setARGB('FFFFF3E0');
        
        $sheet->getStyle('A4:O4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A4:O4')->getFill()->getStartColor()->setARGB('FFE3F2FD');
        
        $sheet->getStyle('A5:O5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A5:O5')->getFill()->getStartColor()->setARGB('FFE8F5E9');
        
        $sheet->getStyle('A6:O6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A6:O6')->getFill()->getStartColor()->setARGB('FFFFF3E0');

        // Instruksi
        $sheet->setCellValue('A8', 'INSTRUKSI PENGISIAN:');
        $sheet->getStyle('A8')->getFont()->setBold(true);
        $sheet->getStyle('A8')->getFont()->setSize(12);
        
        $sheet->setCellValue('A9', '1. Kolom dengan tanda * wajib diisi');
        $sheet->setCellValue('A10', '2. Kolom "Nomor" menentukan urutan soal saat ditampilkan di tes (1, 2, 3, dst)');
        $sheet->setCellValue('A11', '3. Tipe soal yang tersedia:');
        $sheet->setCellValue('A12', '   - pilihan_ganda : Soal dengan 1 jawaban benar');
        $sheet->setCellValue('A13', '   - jawaban_ganda : Soal dengan lebih dari 1 jawaban benar');
        $sheet->setCellValue('A14', '   - benar_salah   : Soal benar/salah');
        $sheet->setCellValue('A15', '   - esai          : Soal essay (tidak perlu isi jawaban A-H)');
        $sheet->setCellValue('A16', '4. Kolom "Jawaban Benar":');
        $sheet->setCellValue('A17', '   - Untuk pilihan_ganda: isi dengan huruf jawaban benar (contoh: A atau E)');
        $sheet->setCellValue('A18', '   - Untuk jawaban_ganda: isi dengan huruf jawaban benar dipisah koma (contoh: A,B,D,E)');
        $sheet->setCellValue('A19', '   - Untuk benar_salah: isi A untuk Benar, B untuk Salah');
        $sheet->setCellValue('A20', '5. Topik akan dibuat otomatis jika belum ada di sistem');
        $sheet->setCellValue('A21', '6. Hapus baris contoh (baris 2-6) sebelum mengimpor data Anda');
        $sheet->setCellValue('A22', '7. Kolom Jawaban E-H bersifat opsional, kosongkan jika tidak diperlukan');

        // Style instruksi
        $sheet->getStyle('A8:A22')->getFont()->setName('Consolas');

        // Auto-size columns
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set column B width for instructions
        $sheet->getColumnDimension('B')->setWidth(80);

        // Save to temp file
        $filename = 'template_impor_soal.xlsx';
        $path = storage_path('app/public/exports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    /**
     * Impor soal dari file Word (format sederhana)
     * Format: Setiap soal dipisahkan dengan baris kosong
     * Baris 1: Pertanyaan
     * Baris 2-5: A. Jawaban, B. Jawaban, dst
     * Baris 6: Jawaban: A
     */
    public function imporDariWord(string $filePath, int $penggunaId, ?int $topikId = null): array
    {
        $content = $this->bacaFileWord($filePath);
        $soalBlocks = $this->parseWordContent($content);

        $hasil = [
            'sukses' => 0,
            'gagal' => 0,
            'errors' => [],
        ];

        foreach ($soalBlocks as $index => $block) {
            $nomor = $index + 1;

            try {
                $this->simpanSoalDariWord($block, $penggunaId, $topikId);
                $hasil['sukses']++;
            } catch (\Exception $e) {
                $hasil['gagal']++;
                $hasil['errors'][] = "Soal {$nomor}: " . $e->getMessage();
            }
        }

        return $hasil;
    }

    /**
     * Baca konten file Word
     */
    private function bacaFileWord(string $filePath): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
        $content = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $content .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $content .= $child->getText();
                        }
                    }
                    $content .= "\n";
                }
            }
        }

        return $content;
    }

    /**
     * Parse konten Word menjadi blok soal
     */
    private function parseWordContent(string $content): array
    {
        // Split by double newline or numbered pattern
        $blocks = preg_split('/\n\s*\n|\n(?=\d+[\.\)])/s', trim($content));
        return array_filter($blocks, fn($b) => !empty(trim($b)));
    }

    /**
     * Simpan soal dari blok Word
     */
    private function simpanSoalDariWord(string $block, int $penggunaId, ?int $topikId): Soal
    {
        $lines = array_filter(array_map('trim', explode("\n", $block)));

        if (count($lines) < 2) {
            throw new \Exception('Format soal tidak valid');
        }

        return DB::transaction(function () use ($lines, $penggunaId, $topikId) {
            // Baris pertama adalah pertanyaan (hapus nomor jika ada)
            $pertanyaan = preg_replace('/^\d+[\.\)]\s*/', '', $lines[0]);

            // Parse jawaban
            $jawaban = [];
            $jawabanBenar = [];

            foreach ($lines as $line) {
                // Cek format jawaban: A. xxx atau A) xxx
                if (preg_match('/^([A-E])[\.\)]\s*(.+)$/i', $line, $matches)) {
                    $huruf = strtoupper($matches[1]);
                    $jawaban[$huruf] = $matches[2];
                }

                // Cek jawaban benar: Jawaban: A atau Kunci: A
                if (preg_match('/^(?:Jawaban|Kunci|Answer):\s*([A-E,\s]+)/i', $line, $matches)) {
                    $jawabanBenar = array_map('trim', explode(',', strtoupper($matches[1])));
                }
            }

            if (empty($jawaban)) {
                throw new \Exception('Tidak ditemukan pilihan jawaban');
            }

            // Buat soal
            $soal = Soal::create([
                'pertanyaan' => $pertanyaan,
                'tipe' => count($jawabanBenar) > 1 ? 'jawaban_ganda' : 'pilihan_ganda',
                'topik_id' => $topikId,
                'bobot' => 1,
                'aktif' => true,
                'dibuat_oleh' => $penggunaId,
            ]);

            // Simpan jawaban
            $urutan = 0;
            foreach ($jawaban as $huruf => $isi) {
                Jawaban::create([
                    'soal_id' => $soal->id,
                    'isi_jawaban' => $isi,
                    'benar' => in_array($huruf, $jawabanBenar),
                    'urutan' => $urutan++,
                ]);
            }

            return $soal;
        });
    }
}
