<?php

namespace Tests\Feature;

use App\Events\ApplicantLifecycleChanged;
use App\Listeners\SendApplicantLifecycleWebhook;
use App\Models\Peserta;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyntheticTestingSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_event_posts_signed_webhook_with_test_markers(): void
    {
        config([
            'services.spmb_data_bot.webhook_url' => 'https://bot.example.test/webhook',
            'services.spmb_data_bot.webhook_secret' => 'secret',
        ]);
        Http::fake();
        $year = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true]);
        $peserta = Peserta::withoutGlobalScopes()->create([
            'nomor_pendaftaran' => 'SIM-001',
            'test_run_id' => 'PROD-TEST-20260915-001',
            'is_test' => true,
            'tahun_ajaran_id' => $year->id,
            'nama' => 'Simulasi',
            'email' => 'simulasi@example.test',
            'password' => 'secret',
            'telepon' => '089900000001',
        ]);

        ApplicantLifecycleChanged::dispatch($peserta, 'account_created');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bot.example.test/webhook'
                && $request->hasHeader('X-SPMB-Webhook-Signature', 'sha256='.hash_hmac('sha256', $request->header('X-SPMB-Webhook-Timestamp')[0].'.'.$request->body(), 'secret'))
                && $request['is_test'] === true
                && $request['test_run_id'] === 'PROD-TEST-20260915-001';
        });
    }

    public function test_disabled_webhook_does_not_block_lifecycle_handling(): void
    {
        config([
            'services.spmb_data_bot.webhook_url' => '',
            'services.spmb_data_bot.webhook_secret' => '',
        ]);
        Log::fake();
        $peserta = new Peserta(['id' => 42]);

        (new SendApplicantLifecycleWebhook())->handle(new ApplicantLifecycleChanged($peserta, 'account_created'));

        Log::assertLogged('warning', function ($message, $context) {
            return $message === 'SPMB lifecycle webhook disabled: missing URL or secret'
                && $context['event_type'] === 'account_created'
                && $context['applicant_id'] === 42;
        });
    }

    public function test_webhook_http_failure_does_not_block_lifecycle_handling(): void
    {
        config([
            'services.spmb_data_bot.webhook_url' => 'https://bot.example.test/webhook',
            'services.spmb_data_bot.webhook_secret' => 'secret',
        ]);
        Http::fake(['https://bot.example.test/webhook' => Http::response([], 503)]);
        Log::fake();
        $peserta = new Peserta(['id' => 43, 'nama' => 'Applicant']);

        (new SendApplicantLifecycleWebhook())->handle(new ApplicantLifecycleChanged($peserta, 'account_created'));

        Http::assertSentCount(1);
        Log::assertLogged('error', function ($message, $context) {
            return $message === 'SPMB lifecycle webhook failed'
                && $context['event_type'] === 'account_created'
                && $context['applicant_id'] === 43
                && isset($context['error_class'])
                && !isset($context['error']);
        });
    }

    public function test_invalid_payload_encoding_does_not_throw_or_send(): void
    {
        config([
            'services.spmb_data_bot.webhook_url' => 'https://bot.example.test/webhook',
            'services.spmb_data_bot.webhook_secret' => 'secret',
        ]);
        Http::fake();
        Log::fake();
        $peserta = new Peserta(['id' => 44, 'nama' => 'Invalid '.chr(0xB1).' payload']);

        (new SendApplicantLifecycleWebhook())->handle(new ApplicantLifecycleChanged($peserta, 'account_created'));

        Http::assertNothingSent();
        Log::assertLogged('error', function ($message, $context) {
            return $message === 'SPMB lifecycle webhook failed'
                && $context['event_type'] === 'account_created'
                && $context['applicant_id'] === 44;
        });
    }

    public function test_cleanup_preview_does_not_change_exact_synthetic_records(): void
    {
        $year = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true]);
        $peserta = Peserta::withoutGlobalScopes()->create([
            'nomor_pendaftaran' => 'SIM-002',
            'test_run_id' => 'PROD-TEST-20260915-001',
            'is_test' => true,
            'tahun_ajaran_id' => $year->id,
            'nama' => 'Simulasi',
            'email' => 'simulasi2@example.test',
            'password' => 'secret',
            'telepon' => '089900000001',
        ]);

        Artisan::call('spmb:test-cleanup', ['test_run_id' => 'PROD-TEST-20260915-001']);

        $this->assertDatabaseHas('peserta', ['id' => $peserta->id, 'test_run_id' => 'PROD-TEST-20260915-001', 'is_test' => 1]);
    }
}
