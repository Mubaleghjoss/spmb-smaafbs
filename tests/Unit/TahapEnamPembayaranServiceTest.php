<?php

namespace Tests\Unit;

use App\Enums\StatusPembayaran;
use App\Models\Pembayaran;
use App\Models\Peserta;
use App\Services\TahapEnamPembayaranService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TahapEnamPembayaranServiceTest extends TestCase
{
    public function test_hanya_pembayaran_terverifikasi_mengurangi_sisa_tagihan(): void
    {
        $peserta = Peserta::factory()->create();
        DB::table('pengaturan')->updateOrInsert(
            ['kunci' => 'biaya_pelunasan'],
            ['nilai' => '1950000', 'updated_at' => now(), 'created_at' => now()]
        );
        Pembayaran::create([
            'peserta_id' => $peserta->id,
            'jenis' => 'pertama',
            'bukti_file' => 'pembayaran/pertama/menunggu.jpg',
            'nominal' => 500000,
            'status' => StatusPembayaran::MENUNGGU->value,
        ]);
        Pembayaran::create([
            'peserta_id' => $peserta->id,
            'jenis' => 'pertama',
            'bukti_file' => 'pembayaran/pertama/terverifikasi.jpg',
            'nominal' => 500000,
            'status' => StatusPembayaran::TERVERIFIKASI->value,
        ]);

        $ringkasan = app(TahapEnamPembayaranService::class)->ringkasan($peserta);

        $this->assertSame(1950000.0, $ringkasan['tagihan']);
        $this->assertSame(500000.0, $ringkasan['terverifikasi']);
        $this->assertSame(1450000.0, $ringkasan['sisa']);
        $this->assertFalse($ringkasan['lunas']);
    }
}
