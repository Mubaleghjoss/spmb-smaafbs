<?php

namespace Tests\Feature;

use App\Models\FormulirSpmb;
use App\Models\Pembayaran;
use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Models\TahunAjaran;
use App\Services\FormulirSpmbService;
use App\Services\KuotaPendaftaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservasiKuotaFormulirTest extends TestCase
{
    use RefreshDatabase;

    public function test_bukti_formulir_menunggu_mereservasi_kuota_dengan_urutan_waktu_upload(): void
    {
        $tahun = TahunAjaran::create([
            'nama' => '2099-2100',
            'aktif' => true,
            'default' => true,
            'kuota_peserta' => 1,
            'kunci_kuota' => false,
        ]);

        $pesertaPertama = $this->buatPesertaSiapBayar($tahun, 'Peserta Pertama');
        $pesertaKedua = $this->buatPesertaSiapBayar($tahun, 'Peserta Kedua');

        Pembayaran::create([
            'peserta_id' => $pesertaPertama->id,
            'jenis' => 'formulir',
            'bukti_file' => 'pembayaran/formulir/pertama.jpg',
            'nominal' => 200000,
            'status' => 'menunggu',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        Pembayaran::create([
            'peserta_id' => $pesertaKedua->id,
            'jenis' => 'formulir',
            'bukti_file' => 'pembayaran/formulir/kedua.jpg',
            'nominal' => 200000,
            'status' => 'menunggu',
        ]);

        app(KuotaPendaftaranService::class)->rekalkulasiTahun($tahun->id);

        $this->assertSame(Peserta::STATUS_KUOTA_DALAM, $pesertaPertama->fresh()->status_kuota);
        $this->assertSame(1, $pesertaPertama->fresh()->urutan_kuota);
        $this->assertSame(Peserta::STATUS_KUOTA_WAITING, $pesertaKedua->fresh()->status_kuota);
        $this->assertSame(2, $pesertaKedua->fresh()->urutan_kuota);
    }

    public function test_penolakan_bukti_formulir_melepas_kuota_dan_mempromosikan_waiting_list(): void
    {
        $tahun = TahunAjaran::create([
            'nama' => '2100-2101',
            'aktif' => true,
            'default' => true,
            'kuota_peserta' => 1,
            'kunci_kuota' => false,
        ]);

        $pesertaPertama = $this->buatPesertaSiapBayar($tahun, 'Peserta Pertama');
        $pesertaKedua = $this->buatPesertaSiapBayar($tahun, 'Peserta Kedua');

        $buktiPertama = Pembayaran::create([
            'peserta_id' => $pesertaPertama->id,
            'jenis' => 'formulir',
            'bukti_file' => 'pembayaran/formulir/pertama.jpg',
            'nominal' => 200000,
            'status' => 'menunggu',
        ]);
        Pembayaran::create([
            'peserta_id' => $pesertaKedua->id,
            'jenis' => 'formulir',
            'bukti_file' => 'pembayaran/formulir/kedua.jpg',
            'nominal' => 200000,
            'status' => 'menunggu',
        ]);

        $kuota = app(KuotaPendaftaranService::class);
        $kuota->rekalkulasiTahun($tahun->id);
        $buktiPertama->update(['status' => 'ditolak', 'catatan' => 'Nominal atau bukti transfer tidak sesuai.']);
        $kuota->rekalkulasiTahun($tahun->id);

        $this->assertSame(Peserta::STATUS_KUOTA_BELUM_LENGKAP, $pesertaPertama->fresh()->status_kuota);
        $this->assertSame(Peserta::STATUS_KUOTA_DALAM, $pesertaKedua->fresh()->status_kuota);
        $this->assertSame(1, $pesertaKedua->fresh()->urutan_kuota);
    }

    public function test_upload_bukti_formulir_langsung_mereservasi_urutan_kuota(): void
    {
        Storage::fake('public');
        $tahun = TahunAjaran::create([
            'nama' => '2102-2103',
            'aktif' => true,
            'default' => true,
            'kuota_peserta' => 1,
        ]);
        $peserta = $this->buatPesertaSiapBayar($tahun, 'Peserta Upload');

        app(\App\Services\PembayaranService::class)->uploadBukti(
            $peserta,
            'formulir',
            UploadedFile::fake()->image('bukti.png'),
            500000,
        );

        $this->assertSame(Peserta::STATUS_KUOTA_DALAM, $peserta->fresh()->status_kuota);
        $this->assertSame(1, $peserta->fresh()->urutan_kuota);
    }

    public function test_formulir_lengkap_otomatis_menyelesaikan_tahap_dua_dan_membuka_tahap_tiga(): void
    {
        $tahun = TahunAjaran::create([
            'nama' => '2101-2102',
            'aktif' => true,
            'default' => true,
        ]);
        $peserta = Peserta::factory()->create([
            'tahun_ajaran_id' => $tahun->id,
            'jenis_pendaftaran' => Peserta::JENIS_SISWA_BARU,
        ]);
        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 2,
            'tahap_1_selesai' => true,
        ]);
        $formulir = FormulirSpmb::create([
            'peserta_id' => $peserta->id,
            'nama_lengkap' => $peserta->nama,
            'tempat_lahir' => 'Tangerang',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'asal_sekolah' => 'SMP Contoh',
            'nama_ayah' => 'Ayah Contoh',
            'nama_ibu' => 'Ibu Contoh',
            'status_verifikasi' => 'draft',
        ]);

        app(FormulirSpmbService::class)->submit($peserta);

        $this->assertSame('terkirim', $formulir->fresh()->status_verifikasi);
        $this->assertTrue((bool) $peserta->fresh()->tahapanSpmb->tahap_2_selesai);
        $this->assertSame(3, $peserta->fresh()->tahapanSpmb->tahap_saat_ini);
    }

    private function buatPesertaSiapBayar(TahunAjaran $tahun, string $nama): Peserta
    {
        $peserta = Peserta::factory()->create([
            'nama' => $nama,
            'tahun_ajaran_id' => $tahun->id,
            'jenis_pendaftaran' => Peserta::JENIS_SISWA_BARU,
            'status_kuota' => Peserta::STATUS_KUOTA_BELUM_LENGKAP,
            'urutan_kuota' => null,
        ]);

        FormulirSpmb::create([
            'peserta_id' => $peserta->id,
            'nama_lengkap' => $nama,
            'jenis_kelamin' => 'L',
            'status_verifikasi' => 'terverifikasi',
        ]);

        TahapanSpmb::create([
            'peserta_id' => $peserta->id,
            'tahap_saat_ini' => 3,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => true,
        ]);

        return $peserta;
    }
}
