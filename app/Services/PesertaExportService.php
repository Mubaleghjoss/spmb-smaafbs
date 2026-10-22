<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PesertaExportService
{
    /** @return list<string> */
    public static function columns(): array
    {
        return [
            'No', 'ID Peserta', 'No Pendaftaran', 'Nama Peserta', 'Email Peserta', 'Telepon Peserta',
            'Alamat Peserta', 'Asal Sekolah Peserta', 'Foto Peserta', 'Tahun Ajaran', 'Gelombang Pendaftaran',
            'Jenis Pendaftaran', 'Kelas Tujuan', 'Kelas Penempatan', 'Status Kuota', 'Urutan Kuota',
            'Peserta Uji Coba', 'Dibuat Pada', 'Diperbarui Pada',
            'Tahapan - Tahap Saat Ini', 'Tahapan - Tahap 1 Selesai', 'Tahapan - Tahap 2 Selesai',
            'Tahapan - Tahap 3 Selesai', 'Tahapan - Tahap 4 Selesai', 'Tahapan - Tahap 5 Selesai',
            'Tahapan - Tahap 6 Selesai', 'Tahapan - Tahap 7 Selesai', 'Tahapan - Status Kelulusan',
            'Data Formulir - Nama Lengkap', 'Data Formulir - Tempat Lahir', 'Data Formulir - Provinsi Lahir',
            'Data Formulir - Tanggal Lahir', 'Data Formulir - Jenis Kelamin', 'Data Formulir - Agama',
            'Data Formulir - Tinggi Badan', 'Data Formulir - Berat Badan', 'Data Formulir - Lingkar Kepala',
            'Data Formulir - Lingkar Dada', 'Data Formulir - Lingkar Pinggang', 'Data Formulir - Panjang Celana',
            'Data Formulir - Hobi', 'Data Formulir - Cita-cita', 'Data Formulir - Prestasi',
            'Data Formulir - Jumlah Saudara', 'Data Formulir - Alamat', 'Data Formulir - Kelurahan',
            'Data Formulir - Kecamatan', 'Data Formulir - Kota/Kabupaten', 'Data Formulir - Provinsi',
            'Data Formulir - Desa', 'Data Formulir - Daerah', 'Data Formulir - Kelompok',
            'Data Formulir - Domisili Biaya', 'Data Formulir - Nama Daerah Luar',
            'Data Formulir - Telepon', 'Data Formulir - Telepon Rumah', 'Data Formulir - Email',
            'Data Formulir - Nama Ayah', 'Data Formulir - Pekerjaan Ayah', 'Data Formulir - Pendidikan Ayah',
            'Data Formulir - Telepon Ayah', 'Data Formulir - Nama Ibu', 'Data Formulir - Pekerjaan Ibu',
            'Data Formulir - Pendidikan Ibu', 'Data Formulir - Telepon Ibu', 'Data Formulir - Asal Sekolah',
            'Data Formulir - Alamat Sekolah', 'Data Formulir - Kontak Sekolah',
            'Data Formulir - Telepon Kontak Sekolah', 'Data Formulir - NISN', 'Data Formulir - Tanggal Daftar',
            'Data Formulir - Status Verifikasi', 'Data Formulir - Catatan Verifikasi',
            'Data Formulir - File KK', 'Data Formulir - File Akta', 'Data Formulir - File Ijazah',
            'Data Formulir - File BPJS', 'Data Formulir - File KTP Ayah', 'Data Formulir - File KTP Ibu',
            'Data Formulir - File Mutasi Sekolah', 'Data Formulir - File Mutasi Dapodik', 'Data Formulir - Foto',
            'Pembayaran - Formulir - Status', 'Pembayaran - Formulir - Nominal',
            'Pembayaran - Formulir - Nomor Kwitansi', 'Pembayaran - Formulir - Bukti File',
            'Pembayaran - Formulir - Diverifikasi Pada', 'Pembayaran - Formulir - Catatan',
            'Pembayaran - Pelunasan - Status', 'Pembayaran - Pelunasan - Nominal',
            'Pembayaran - Pelunasan - Nomor Kwitansi', 'Pembayaran - Pelunasan - Bukti File',
            'Pembayaran - Pelunasan - Diverifikasi Pada', 'Pembayaran - Pelunasan - Catatan',
            'Tes - Nama Tes', 'Tes - Nilai', 'Tes - Status', 'Tes - Status Verifikasi',
            'Tes - Waktu Mulai', 'Tes - Waktu Selesai', 'Tes - Diverifikasi Pada', 'Tes - Catatan Verifikasi',
            'Wawancara - Tanggal Umum', 'Wawancara - Interviewer Umum', 'Wawancara - Tanggal Orang Tua',
            'Wawancara - Interviewer Orang Tua', 'Wawancara - Jawaban Orang Tua (JSON)',
            'Wawancara - Catatan Orang Tua', 'Wawancara - Tanggal Siswa', 'Wawancara - Interviewer Siswa',
            'Wawancara - Jawaban Siswa (JSON)', 'Wawancara - Catatan Siswa',
            'Wawancara - Verifikasi Berkas (JSON)', 'Wawancara - Hasil', 'Wawancara - Catatan Interviewer',
            'Wawancara - File Tes Pegon', 'Wawancara - File Voice Quran', 'Wawancara - Surat Quran Random',
            'Wawancara - Diisi Peserta Pada', 'Wawancara - Diverifikasi Pada', 'Tanggal Daftar Peserta',
        ];
    }

    /** @return array<string, string> */
    public static function downloadHeaders(string $filename): array
    {
        return [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
    }

    public function download(Collection $peserta, string $filename): BinaryFileResponse
    {
        $directory = storage_path('app/exports');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory . '/' . uniqid('peserta-', true) . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Peserta');

        $columns = self::columns();
        $sheet->fromArray($columns, null, 'A1');

        $rowNumber = 2;
        foreach ($peserta as $index => $pesertaItem) {
            $sheet->fromArray(self::row($pesertaItem, $index + 1), null, 'A' . $rowNumber++);
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
        $lastRow = max(1, $rowNumber - 1);
        $headerRange = 'A1:' . $lastColumn . '1';
        $dataRange = 'A1:' . $lastColumn . $lastRow;

        $sheet->freezePane('A2');
        $sheet->setAutoFilter($dataRange);
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B7C9D6']]],
        ]);
        $sheet->getStyle('A2:' . $lastColumn . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A2:' . $lastColumn . $lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);

        for ($column = 1; $column <= count($columns); $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getColumnDimension($letter)->setWidth(self::columnWidth($columns[$column - 1]));
        }

        $spreadsheet->getProperties()
            ->setCreator('SMA AFBS')
            ->setTitle('Data Peserta SPMB Lengkap')
            ->setSubject('Export data peserta SPMB');

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($path, $filename, self::downloadHeaders($filename))->deleteFileAfterSend(true);
    }

    /** @return list<mixed> */
    public static function row(object $p, int $number): array
    {
        $f = $p->formulirSpmb;
        $t = $p->tahapanSpmb;
        $payments = $p->pembayaran ?? collect();
        $formPayment = $payments->firstWhere('jenis', 'formulir');
        $completionPayment = $payments->firstWhere('jenis', 'pertama');
        $test = ($p->sesiTes ?? collect())->whereIn('status', ['selesai', 'timeout'])->sortByDesc('created_at')->first();
        $w = $p->wawancara;

        return [
            $number, $p->id, $p->nomor_pendaftaran, $p->nama, $p->email, $p->telepon, $p->alamat,
            $p->asal_sekolah, $p->foto, $p->tahunAjaran?->nama, $p->gelombangPendaftaran?->nama,
            $p->jenis_pendaftaran_label, $p->kelas_tujuan ? 'Kelas ' . $p->kelas_tujuan : null,
            $p->kelas_penempatan, $p->status_kuota_label, $p->urutan_kuota, $p->is_test ? 'Ya' : 'Tidak',
            self::dateTime($p->created_at), self::dateTime($p->updated_at),
            $t?->tahap_saat_ini, ...self::stageValues($t), $t?->status_kelulusan,
            $f?->nama_lengkap, $f?->tempat_lahir, $f?->provinsi_lahir, self::date($f?->tanggal_lahir),
            $f?->jenis_kelamin === 'L' ? 'Laki-laki' : ($f?->jenis_kelamin === 'P' ? 'Perempuan' : $f?->jenis_kelamin),
            $f?->agama, $f?->tinggi_badan, $f?->berat_badan, $f?->lingkar_kepala, $f?->lingkar_dada,
            $f?->lingkar_pinggang, $f?->panjang_celana, $f?->hobi, $f?->cita_cita, $f?->prestasi,
            $f?->jumlah_saudara, $f?->alamat, $f?->alamat_kelurahan, $f?->alamat_kecamatan, $f?->alamat_kota,
            $f?->alamat_provinsi, $f?->desa, $f?->daerah, $f?->kelompok, $f?->domisili_biaya, $f?->nama_daerah_luar,
            $f?->telepon, $f?->telp_rumah, $f?->email, $f?->nama_ayah, $f?->pekerjaan_ayah, $f?->pendidikan_ayah,
            $f?->telepon_ayah, $f?->nama_ibu, $f?->pekerjaan_ibu, $f?->pendidikan_ibu, $f?->telepon_ibu,
            $f?->asal_sekolah, $f?->alamat_sekolah, $f?->nama_kontak_sekolah, $f?->telepon_kontak_sekolah,
            $f?->nisn, self::date($f?->tanggal_daftar), $f?->status_verifikasi, $f?->catatan_verifikasi,
            $f?->file_kk, $f?->file_akta, $f?->file_ijazah, $f?->file_bpjs, $f?->file_ktp_ayah, $f?->file_ktp_ibu,
            $f?->file_mutasi_sekolah, $f?->file_mutasi_dapodik, $f?->foto,
            self::paymentValue($formPayment, 'status'), self::paymentValue($formPayment, 'nominal'),
            self::paymentValue($formPayment, 'nomor_kwitansi'), self::paymentValue($formPayment, 'bukti_file'),
            self::dateTime(self::paymentValue($formPayment, 'diverifikasi_pada')), self::paymentValue($formPayment, 'catatan'),
            self::paymentValue($completionPayment, 'status'), self::paymentValue($completionPayment, 'nominal'),
            self::paymentValue($completionPayment, 'nomor_kwitansi'), self::paymentValue($completionPayment, 'bukti_file'),
            self::dateTime(self::paymentValue($completionPayment, 'diverifikasi_pada')), self::paymentValue($completionPayment, 'catatan'),
            $test?->tes?->nama, $test?->nilai, $test?->status, $test?->status_verifikasi_tes,
            self::dateTime($test?->waktu_mulai), self::dateTime($test?->waktu_selesai), self::dateTime($test?->diverifikasi_pada),
            $test?->catatan_verifikasi, self::date($w?->tanggal_wawancara), $w?->nama_interviewer,
            self::date($w?->tanggal_wawancara_ortu), $w?->interviewer_ortu, self::json($w?->jawaban_ortu), $w?->catatan_ortu,
            self::date($w?->tanggal_wawancara_siswa), $w?->interviewer_siswa, self::json($w?->jawaban_siswa), $w?->catatan_siswa,
            self::json($w?->verifikasi_berkas), $w?->hasil_wawancara, $w?->catatan_interviewer, $w?->file_tes_pegon,
            $w?->file_voice_quran, $w?->surat_quran_random, self::dateTime($w?->diisi_peserta_pada), self::dateTime($w?->diverifikasi_pada),
            self::dateTime($p->created_at),
        ];
    }

    /** @return list<string> */
    private static function stageValues(?object $t): array
    {
        return array_map(
            static fn (int $stage): string => ($t?->{'tahap_' . $stage . '_selesai'} ?? false) ? 'Ya' : 'Tidak',
            range(1, 7)
        );
    }

    private static function paymentValue(?object $payment, string $field): mixed
    {
        return $payment?->{$field};
    }

    private static function date(mixed $value): ?string
    {
        return $value ? $value->format('d/m/Y') : null;
    }

    private static function dateTime(mixed $value): ?string
    {
        return $value ? $value->format('d/m/Y H:i') : null;
    }

    private static function json(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function columnWidth(string $column): int
    {
        if (str_contains($column, '(JSON)')) {
            return 35;
        }
        if (str_contains($column, 'Alamat') || str_contains($column, 'Catatan') || str_contains($column, 'Prestasi')) {
            return 28;
        }
        if (str_contains($column, 'Nama') || str_contains($column, 'Sekolah') || str_contains($column, 'Pekerjaan')) {
            return 24;
        }
        return 18;
    }
}
