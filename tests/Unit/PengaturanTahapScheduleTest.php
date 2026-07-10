<?php

namespace Tests\Unit;

use App\Services\PengaturanService;
use Carbon\Carbon;
use Tests\TestCase;

class PengaturanTahapScheduleTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tahap_dibuka_di_dalam_rentang_jadwal(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        $status = app(PengaturanService::class)->statusAksesTahap(5, [
            'dibuka' => true,
            'tanggal_buka' => '2026-07-10',
            'waktu_mulai' => '08:00',
            'tanggal_tutup' => '2026-07-10',
            'waktu_selesai' => '11:30',
        ]);

        $this->assertTrue($status['dibuka']);
        $this->assertNull($status['alasan']);
        $this->assertStringContainsString('10 Juli 2026 08:00 WIB', $status['jadwal_label']);
    }

    public function test_tahap_ditutup_setelah_batas_waktu(): void
    {
        Carbon::setTestNow('2026-07-10 12:00:00');

        $status = app(PengaturanService::class)->statusAksesTahap(5, [
            'dibuka' => true,
            'tanggal_buka' => '2026-07-10',
            'waktu_mulai' => '08:00',
            'tanggal_tutup' => '2026-07-10',
            'waktu_selesai' => '11:30',
        ]);

        $this->assertFalse($status['dibuka']);
        $this->assertSame('Sudah ditutup pada 10 Juli 2026 11:30 WIB', $status['alasan']);
    }

    public function test_toggle_admin_menutup_tahap_tanpa_jadwal(): void
    {
        $status = app(PengaturanService::class)->statusAksesTahap(6, [
            'dibuka' => false,
        ]);

        $this->assertFalse($status['dibuka']);
        $this->assertSame('Tahap ini sedang ditutup oleh admin.', $status['alasan']);
    }
}
