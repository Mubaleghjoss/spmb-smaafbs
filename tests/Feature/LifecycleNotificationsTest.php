<?php

namespace Tests\Feature;

use App\Events\ApplicantLifecycleChanged;
use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Models\TahunAjaran;
use App\Services\SpmbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LifecycleNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function peserta(): Peserta
    {
        $year = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true]);
        return Peserta::withoutGlobalScopes()->create([
            'nomor_pendaftaran' => 'LIFE-'.uniqid(), 'tahun_ajaran_id' => $year->id,
            'nama' => 'Budi Santoso', 'email' => uniqid().'@example.test', 'password' => 'secret',
        ]);
    }

    public function test_event_dispatches_after_commit(): void
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Events\ShouldDispatchAfterCommit::class, new ApplicantLifecycleChanged(new Peserta(['id' => 1]), 'applicant_registered'));
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
