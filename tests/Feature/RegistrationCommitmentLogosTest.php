<?php

namespace Tests\Feature;

use App\Models\GelombangPendaftaran;
use App\Services\PengaturanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationCommitmentLogosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PengaturanService::class)->simpanBanyak([
            'pendaftaran_buka' => '1',
            'tanggal_buka' => now()->subDay()->toDateString(),
            'tanggal_tutup' => now()->addDay()->toDateString(),
            'popup_persetujuan_aktif' => '1',
        ]);

        GelombangPendaftaran::query()->update([
            'tanggal_buka' => now()->subDay(),
            'tanggal_tutup' => now()->addDay(),
            'aktif' => true,
        ]);
    }

    public function test_registration_commitment_modal_contains_local_logos_and_preserves_remote_popup_contract(): void
    {
        $response = $this->get(route('daftar'));

        $response->assertOk()
            ->assertSee('id="modalKomitmen"', false)
            ->assertSee('id="modalKomitmenLabel"', false)
            ->assertSee('alt="Logo SMA Al-Furqon Boarding School (SMA AFBS)"', false)
            ->assertSee('alt="Logo Yayasan Dar Al Furqon Al Hakim"', false)
            ->assertSee('alt="Logo Lembaga Dakwah Islam Indonesia (LDII)"', false)
            ->assertSee('<figcaption>SMA Al Furqon Boarding School (SMA AFBS)</figcaption>', false)
            ->assertSee('<figcaption>Yayasan Dar Al Furqon Al Hakim</figcaption>', false)
            ->assertSee('<figcaption>Lembaga Dakwah Islam Indonesia (LDII)</figcaption>', false)
            ->assertSee(asset('images/logo-commitment-sma-afbs.png'), false)
            ->assertSee(asset('images/logo-yayasan-dar-al-furqon-al-hakim.jpg'), false)
            ->assertSee(asset('images/logo-commitment-ldii.png'), false)
            ->assertSee('SMA AFBS yang dikelola oleh Lembaga Dakwah Islam Indonesia (LDII)', false)
            ->assertSee('x-on:komitmen-disetujui.window="setuju = true"', false)
            ->assertSee('id="tombolSetujuKomitmen"', false)
            ->assertDontSee('https://smaafbs.sch.id/', false)
            ->assertDontSee('https://ldiijabar.or.id/', false);
    }
}
