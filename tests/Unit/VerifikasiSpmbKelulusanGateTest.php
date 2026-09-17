<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\VerifikasiSpmbController;
use App\Models\TahapanSpmb;
use PHPUnit\Framework\TestCase;

class VerifikasiSpmbKelulusanGateTest extends TestCase
{
    public function test_menyebutkan_tahap_aktif_bila_peserta_belum_tiba_di_tahap_tujuh(): void
    {
        $this->assertSame(
            'Peserta masih berada di Tahap 4. Selesaikan Tahap 4 terlebih dahulu sebelum menetapkan kelulusan.',
            $this->alasan(new TahapanSpmb(['tahap_saat_ini' => 4, 'tahap_6_selesai' => false]))
        );
    }

    public function test_menyebutkan_pembayaran_tahap_enam_bila_belum_lunas(): void
    {
        $this->assertSame(
            'Tahap 6 (pembayaran) peserta belum lunas/terverifikasi. Selesaikan Tahap 6 terlebih dahulu sebelum menetapkan kelulusan.',
            $this->alasan(new TahapanSpmb(['tahap_saat_ini' => 7, 'tahap_6_selesai' => false]))
        );
    }

    public function test_tidak_memberi_alasan_bila_peserta_siap_ditetapkan(): void
    {
        $this->assertNull($this->alasan(new TahapanSpmb(['tahap_saat_ini' => 7, 'tahap_6_selesai' => true])));
    }

    public function test_mutator_kelulusan_gagal_tanpa_mengubah_status_bila_tahap_empat_belum_selesai(): void
    {
        $tahapan = new TahapanSpmb([
            'tahap_saat_ini' => 4,
            'tahap_6_selesai' => false,
            'status_kelulusan' => 'menunggu',
        ]);
        $peserta = new \App\Models\Peserta;
        $peserta->setRelation('tahapanSpmb', $tahapan);

        $method = new \ReflectionMethod(VerifikasiSpmbController::class, 'terapkanKelulusan');
        $method->setAccessible(true);
        $class = new \ReflectionClass(VerifikasiSpmbController::class);
        $controller = $class->newInstanceWithoutConstructor();

        $this->assertFalse($method->invoke($controller, $peserta, 'lulus', null));
        $this->assertSame('menunggu', $tahapan->status_kelulusan);
    }

    private function alasan(TahapanSpmb $tahapan): ?string
    {
        $method = new \ReflectionMethod(VerifikasiSpmbController::class, 'alasanBelumSiapKeputusanKelulusan');
        $method->setAccessible(true);

        $class = new \ReflectionClass(VerifikasiSpmbController::class);
        $controller = $class->newInstanceWithoutConstructor();

        return $method->invoke($controller, $tahapan);
    }
}
