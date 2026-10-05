<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegistrationCommitmentLogosTest extends TestCase
{
    public function test_registration_commitment_modal_contains_all_three_responsive_logos_and_flow_button(): void
    {
        $response = $this->get(route('daftar'));

        $response->assertOk()
            ->assertSee('id="modalKomitmen"', false)
            ->assertSee('alt="Logo SMA Al-Furqon Boarding School (SMA AFBS)"', false)
            ->assertSee('alt="Logo Yayasan Dar Al Furqon Al Hakim"', false)
            ->assertSee('alt="Logo Lembaga Dakwah Islam Indonesia (LDII)"', false)
            ->assertSee('<figcaption>SMA Al Furqon Boarding School (SMA AFBS)</figcaption>', false)
            ->assertSee('<figcaption>Yayasan Dar Al Furqon Al Hakim</figcaption>', false)
            ->assertSee('<figcaption>Lembaga Dakwah Islam Indonesia (LDII)</figcaption>', false)
            ->assertSee(asset('images/logo-commitment-sma-afbs.png'), false)
            ->assertSee(asset('images/logo-yayasan-dar-al-furqon-al-hakim.jpg'), false)
            ->assertSee(asset('images/logo-commitment-ldii.png'), false)
            ->assertDontSee(asset('icons/icon-512.png'), false)
            ->assertDontSee(asset('icons/icon-192.png'), false)
            ->assertDontSee('https://smaafbs.sch.id/', false)
            ->assertDontSee('https://ldiijabar.or.id/', false)
            ->assertSee('id="tombolSetujuKomitmen"', false)
            ->assertSee('x-on:komitmen-disetujui.window="setuju = true"', false);
    }
}
