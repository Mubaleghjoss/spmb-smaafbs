<?php

namespace Tests\Unit;

use App\Services\FormulirSpmbService;
use PHPUnit\Framework\TestCase;

class FormulirSeragamDanKontakContractTest extends TestCase
{
    public function test_daerah_efektif_menghubungkan_pilihan_dan_input_manual_ke_kolom_daerah(): void
    {
        $this->assertSame('Tangerang Kota', FormulirSpmbService::daerahEfektif([
            'domisili_biaya' => 'dalam_tangerang_kota',
        ]));
        $this->assertSame('Tangerang Barat', FormulirSpmbService::daerahEfektif([
            'domisili_biaya' => 'luar_tangerang_kota',
            'nama_daerah_luar' => ' Tangerang Barat ',
        ]));
        $this->assertNull(FormulirSpmbService::daerahEfektif([
            'ukuran_baju' => 'M',
        ]));
    }

    public function test_daftar_ukuran_seragam_hanya_mengizinkan_enam_ukuran_yang_disetujui(): void
    {
        $this->assertSame(['S', 'M', 'L', 'XL', 'XXL', 'XXXL'], FormulirSpmbService::ukuranSeragam());
    }

    public function test_kontrak_formulir_mewajibkan_data_fisik_dan_ukuran_seragam(): void
    {
        $rules = (new \ReflectionClass(FormulirSpmbService::class))
            ->getMethod('validasi')
            ->getDeclaringClass()
            ->getName();
        $source = file_get_contents(dirname(__DIR__, 2).'/app/Services/FormulirSpmbService.php');

        foreach (['lingkar_dada', 'lingkar_pinggang', 'lingkar_kepala', 'panjang_celana', 'tinggi_badan', 'berat_badan'] as $field) {
            $this->assertStringContainsString("'{$field}' => 'required|numeric", $source);
        }
        $this->assertStringContainsString("'ukuran_baju' => 'required|in:S,M,L,XL,XXL,XXXL'", $source);
        $this->assertStringContainsString("'ukuran_celana' => 'required|in:S,M,L,XL,XXL,XXXL'", $source);
        $this->assertSame(FormulirSpmbService::class, $rules);
    }

    public function test_admin_detail_memuat_tautan_whatsapp_dan_data_ki_kelompok(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/peserta/show.blade.php');

        $this->assertStringContainsString('PengaturanService::nomorWhatsAppInternasional', $view);
        $this->assertStringContainsString('https://wa.me/', $view);
        $this->assertStringContainsString('Nama KI Kelompok', $view);
        $this->assertStringContainsString('No. HP KI Kelompok', $view);
        $this->assertStringNotContainsString('@php($suratOrtu', $view);
        $this->assertStringContainsString('@endphp', $view);
    }

    public function test_ekspor_biodata_adalah_workbook_excel_dengan_data_seragam_dan_ki(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/Admin/PesertaController.php');
        $export = file_get_contents(dirname(__DIR__, 2).'/app/Exports/BiodataPesertaExport.php');

        $this->assertStringContainsString('Excel::download(', $controller);
        $this->assertStringContainsString('new BiodataPesertaExport', $controller);
        $this->assertStringContainsString(".xlsx'", $controller);
        foreach (['Ukuran Baju', 'Ukuran Celana', 'Nama KI Kelompok', 'No. HP KI Kelompok'] as $heading) {
            $this->assertStringContainsString("'{$heading}'", $export);
        }
    }

    public function test_surat_ortu_memisahkan_nama_dan_nomor_ki_dan_mewajibkannya(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/peserta/wawancara-info.blade.php');
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/Peserta/DashboardSpmbController.php');

        $this->assertStringContainsString('name="sp_ortu[nama_ki]"', $view);
        $this->assertStringContainsString('name="sp_ortu[no_hp_ki]"', $view);
        $this->assertStringContainsString('required', $view);
        $this->assertStringContainsString("'sp_ortu.nama_ki' => 'required|string|max:100'", $controller);
        $this->assertStringContainsString("'sp_ortu.no_hp_ki' => 'required|string|max:20'", $controller);
    }
}
