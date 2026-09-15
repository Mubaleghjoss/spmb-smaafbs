<?php

namespace Tests\Feature;

use App\Events\ApplicantLifecycleChanged;
use App\Listeners\SendApplicantLifecycleWebhook;
use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Models\TahunAjaran;
use App\Services\SpmbService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LifecycleNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function peserta(): Peserta
    {
        $year = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true]);
        $admin = \App\Models\Pengguna::create([
            'nama' => 'Admin Test', 'email' => uniqid().'@example.test', 'password' => 'secret',
        ]);
        $admin->id = 99;
        $admin->save();

        return Peserta::withoutGlobalScopes()->create([
            'nomor_pendaftaran' => 'LIFE-'.uniqid(), 'tahun_ajaran_id' => $year->id,
            'nama' => 'BudiTes', 'email' => uniqid().'@example.test', 'password' => 'secret', 'telepon' => '081'.random_int(100000000, 999999999),
        ]);
    }

    public function test_event_dispatches_after_commit(): void
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Events\ShouldDispatchAfterCommit::class, new ApplicantLifecycleChanged(new Peserta(['id' => 1]), 'applicant_registered'));
    }

    public function test_listener_signs_and_transmits_the_exact_json_body(): void
    {
        $peserta = $this->peserta();
        $peserta->update(['nama' => 'Élodie']);
        $event = new ApplicantLifecycleChanged($peserta, 'stage_advanced', [
            'completed_stage' => 6,
            'new_stage' => 7,
            'progress' => '6/7',
            'cause' => 'normal',
        ]);
        $secret = 'listener-regression-secret';
        $timestamp = Carbon::create(2026, 9, 16, 12, 34, 56, 'UTC');
        $this->travelTo($timestamp);
        config([
            'services.spmb_data_bot.webhook_url' => 'https://receiver.test/lifecycle',
            'services.spmb_data_bot.webhook_secret' => $secret,
        ]);

        $rawBody = null;
        $requestHeaders = null;
        Http::fake(function ($request) use (&$rawBody, &$requestHeaders) {
            $rawBody = $request->body();
            $requestHeaders = $request->headers();

            return Http::response([], 202);
        });

        $encoded = json_encode($event->payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestampHeader = (string) now()->timestamp;

        try {
            (new SendApplicantLifecycleWebhook())->handle($event);
            $expectedSignature = 'sha256='.hash_hmac('sha256', $timestampHeader.'.'.$rawBody, $secret);
        } finally {
            $this->travelBack();
        }

        $this->assertSame($encoded, $rawBody);
        $this->assertSame('application/json', $requestHeaders['Content-Type'][0]);
        $this->assertSame($timestampHeader, $requestHeaders['X-SPMB-Webhook-Timestamp'][0]);
        $this->assertSame($expectedSignature, $requestHeaders['X-SPMB-Webhook-Signature'][0]);
        $this->assertSame('stage_advanced', $requestHeaders['X-SPMB-Event-Type'][0]);
    }

    public function test_payload_is_minimal_and_name_is_masked(): void
    {
        $peserta = $this->peserta();
        $payload = (new ApplicantLifecycleChanged($peserta, 'applicant_registered', ['phone' => 'secret']))->payload();

        $this->assertSame('B******', $payload['name']);
        $this->assertArrayNotHasKey('phone', $payload);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('context', $payload);
        $this->assertSame($payload['dedupe_key'], $payload['event_id']);
    }

    public function test_stage_transition_is_centralized_and_duplicate_is_suppressed(): void
    {
        $peserta = $this->peserta();
        TahapanSpmb::create(['peserta_id' => $peserta->id, 'tahap_saat_ini' => 2, 'tahap_1_selesai' => true]);
        Event::fake([ApplicantLifecycleChanged::class]);

        app(SpmbService::class)->selesaikanTahapan($peserta, 2);
        app(SpmbService::class)->selesaikanTahapan($peserta, 2);

        Event::assertDispatchedTimes(ApplicantLifecycleChanged::class, 1);
        Event::assertDispatched(function (ApplicantLifecycleChanged $event): bool {
            return $event->eventType === 'stage_advanced'
                && $event->context['completed_stage'] === 2
                && $event->context['cause'] === 'normal'
                && $event->context['progress'] === '2/7';
        });
    }

    public function test_manual_jump_uses_manual_cause(): void
    {
        $peserta = $this->peserta();
        TahapanSpmb::create(['peserta_id' => $peserta->id, 'tahap_saat_ini' => 2, 'tahap_1_selesai' => true]);
        Event::fake([ApplicantLifecycleChanged::class]);
        app(SpmbService::class)->selesaikanTahapan($peserta, 5, 99);

        Event::assertDispatched(fn (ApplicantLifecycleChanged $event): bool => $event->context['cause'] === 'manual_jump');
    }

    public function test_quota_and_graduation_changes_are_separate_events(): void
    {
        $peserta = $this->peserta();
        $tahapan = TahapanSpmb::create(['peserta_id' => $peserta->id, 'tahap_saat_ini' => 7, 'tahap_1_selesai' => true]);
        Event::fake([ApplicantLifecycleChanged::class]);

        $peserta->update(['status_kuota' => Peserta::STATUS_KUOTA_WAITING]);
        $tahapan->update(['status_kelulusan' => 'lulus']);

        Event::assertDispatched(fn (ApplicantLifecycleChanged $event): bool => $event->eventType === 'quota_status_changed');
        Event::assertDispatched(fn (ApplicantLifecycleChanged $event): bool => $event->eventType === 'graduation_status_changed');
        Event::assertNotDispatched(fn (ApplicantLifecycleChanged $event): bool => $event->eventType === 'stage_advanced');
    }
}
