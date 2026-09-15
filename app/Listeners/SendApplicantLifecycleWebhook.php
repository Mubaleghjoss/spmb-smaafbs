<?php

namespace App\Listeners;

use App\Events\ApplicantLifecycleChanged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendApplicantLifecycleWebhook
{
    public function handle(ApplicantLifecycleChanged $event): void
    {
        $url = (string) config('services.spmb_data_bot.webhook_url', '');
        $secret = (string) config('services.spmb_data_bot.webhook_secret', '');
        if ($url === '' || $secret === '') {
            Log::warning('SPMB lifecycle webhook disabled: missing URL or secret', ['event_type' => $event->eventType]);
            return;
        }

        $body = $event->payload();
        $encoded = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$encoded, $secret);

        try {
            Http::asJson()->withHeaders([
                'X-SPMB-Webhook-Timestamp' => $timestamp,
                'X-SPMB-Webhook-Signature' => 'sha256='.$signature,
                'X-SPMB-Event-Type' => $event->eventType,
            ])->timeout((int) config('services.spmb_data_bot.webhook_timeout', 5))
                ->post($url, $body)
                ->throw();
        } catch (\Throwable $exception) {
            Log::error('SPMB lifecycle webhook failed', [
                'event_type' => $event->eventType,
                'applicant_id' => $event->peserta->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
