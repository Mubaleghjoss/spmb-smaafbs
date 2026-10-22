<?php

namespace Tests\Unit;

use App\Services\PesertaExportService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PesertaExportServiceTest extends TestCase
{
    public function test_export_schema_contains_complete_participant_data_groups(): void
    {
        $columns = PesertaExportService::columns();

        foreach ([
            'No Pendaftaran',
            'Nama Peserta',
            'Tahun Ajaran',
            'Data Formulir - Nama Lengkap',
            'Data Formulir - File KK',
            'Tahapan - Tahap Saat Ini',
            'Pembayaran - Formulir - Status',
            'Pembayaran - Pelunasan - Nominal',
            'Tes - Nilai',
            'Wawancara - Hasil',
            'Wawancara - Jawaban Orang Tua (JSON)',
            'Tanggal Daftar Peserta',
        ] as $required) {
            self::assertContains($required, $columns);
        }

        self::assertGreaterThanOrEqual(70, count($columns));
    }

    public function test_xlsx_response_has_excel_download_headers(): void
    {
        $headers = PesertaExportService::downloadHeaders('peserta.xlsx');

        self::assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $headers['Content-Type']
        );
        self::assertStringContainsString('peserta.xlsx', $headers['Content-Disposition']);
    }

    public function test_row_has_exactly_one_value_for_each_export_column(): void
    {
        $peserta = (object) [
            'id' => 1,
            'nomor_pendaftaran' => 'SPMB-TEST',
            'nama' => 'Peserta Test',
            'email' => null,
            'telepon' => null,
            'alamat' => null,
            'asal_sekolah' => null,
            'foto' => null,
            'tahunAjaran' => null,
            'gelombangPendaftaran' => null,
            'jenis_pendaftaran_label' => 'Siswa Baru',
            'kelas_tujuan' => null,
            'kelas_penempatan' => null,
            'status_kuota_label' => 'Belum dihitung',
            'urutan_kuota' => null,
            'is_test' => false,
            'created_at' => null,
            'updated_at' => null,
            'formulirSpmb' => null,
            'tahapanSpmb' => null,
            'pembayaran' => new Collection(),
            'sesiTes' => new Collection(),
            'wawancara' => null,
        ];

        self::assertCount(count(PesertaExportService::columns()), PesertaExportService::row($peserta, 1));
    }
}
