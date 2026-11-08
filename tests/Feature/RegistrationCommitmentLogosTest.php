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
            'popup_persetujuan_judul' => 'Judul Popup Custom',
            'popup_persetujuan_teks' => "Teks popup custom baris pertama.\nBaris kedua.",
        ]);

        GelombangPendaftaran::query()->update([
            'tanggal_buka' => now()->subDay(),
            'tanggal_tutup' => now()->addDay(),
            'aktif' => true,
        ]);
    }

    public function test_registration_commitment_modal_contains_local_logos_and_text_only_header(): void
    {
        $response = $this->get(route('daftar'));
        $html = $response->getContent();
        $headerStart = strpos($html, '<div class="komitmen-hero"');
        $headerEnd = $headerStart === false ? false : strpos($html, '</div>', $headerStart);
        $header = ($headerStart !== false && $headerEnd !== false)
            ? substr($html, $headerStart, $headerEnd - $headerStart)
            : '';
        $modalStart = strpos($html, '<div class="modal fade modal-komitmen"');
        $modalEnd = $modalStart === false ? false : strpos($html, '<div class="modal fade" id="modalSK"', $modalStart);
        $modal = ($modalStart !== false && $modalEnd !== false)
            ? substr($html, $modalStart, $modalEnd - $modalStart)
            : '';

        $response->assertOk()
            ->assertSee('id="modalKomitmen"', false)
            ->assertSee('id="modalKomitmenLabel"', false)
            ->assertSee('SEBELUM MEMULAI PENDAFTARAN', false)
            ->assertDontSee('Komitmen Pendaftaran', false)
            ->assertDontSee('bi-shield-check', false)
            ->assertSee('alt="Logo SMA Al-Furqon Boarding School (SMA AFBS)"', false)
            ->assertSee('alt="Logo Yayasan Dar Al Furqon Al Hakim"', false)
            ->assertSee('alt="Logo Lembaga Dakwah Islam Indonesia (LDII)"', false)
            ->assertSee('<figcaption>SMA Al Furqon Boarding School (SMA AFBS)</figcaption>', false)
            ->assertSee('<figcaption>Yayasan Dar Al Furqon Al Hakim</figcaption>', false)
            ->assertSee('<figcaption>Lembaga Dakwah Islam Indonesia (LDII)</figcaption>', false)
            ->assertSee(asset('images/logo-commitment-sma-afbs.png'), false)
            ->assertSee(asset('images/logo-yayasan-dar-al-furqon-al-hakim.jpg'), false)
            ->assertSee(asset('images/logo-commitment-ldii.png'), false)
            ->assertSee('x-on:komitmen-disetujui.window="setuju = true"', false)
            ->assertSee('id="tombolSetujuKomitmen"', false)
            ->assertDontSee('https://smaafbs.sch.id/', false)
            ->assertDontSee('https://ldiijabar.or.id/', false);

        $this->assertNotSame('', $header);
        $this->assertNotSame('', $modal);
        $this->assertStringContainsString('Judul Popup Custom', $modal);
        $this->assertStringContainsString('Teks popup custom baris pertama.', $modal);
        $this->assertStringContainsString('Baris kedua.', $modal);
        $this->assertStringNotContainsString('Komitmen Calon Peserta Didik', $modal);
        $this->assertStringNotContainsString('SMA AFBS yang dikelola oleh Lembaga Dakwah Islam Indonesia (LDII) melalui Yayasan Dar Al Furqon Al Hakim. Bersedia tinggal dan berkegiatan di Asrama.', $modal);
        $this->assertStringNotContainsString('<img', $header);
        $this->assertStringNotContainsString('background-image', $header);
        $this->assertStringNotContainsString('popup_persetujuan_gambar', $header);
    }
}
