<?php

namespace App\Events;

use App\Models\Peserta;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class ApplicantLifecycleChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Peserta $peserta,
        public readonly string $eventType,
        public readonly array $context = [],
    ) {}

    public function payload(): array
    {
        $this->peserta->loadMissing('tahunAjaran', 'tahapanSpmb');
        $name = trim((string) $this->peserta->nama);
        $maskedName = $name === '' ? 'Pendaftar' : mb_substr($name, 0, 1).str_repeat('*', max(1, mb_strlen($name) - 1));

        return [
            'event_id' => $this->dedupeKey(),
            'dedupe_key' => $this->dedupeKey(),
            'event_type' => $this->eventType,
            'occurred_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'test_run_id' => $this->peserta->test_run_id,
            'is_test' => (bool) $this->peserta->is_test,
            'registration_number' => $this->peserta->nomor_pendaftaran,
            'name' => $maskedName,
            'academic_year' => $this->peserta->tahunAjaran?->nama,
            ...array_intersect_key($this->context, array_flip([
                'completed_stage', 'old_stage', 'new_stage', 'stage_name', 'progress', 'cause',
                'old_status', 'new_status',
            ])),
        ];
    }

    public function dedupeKey(): string
    {
        $parts = [$this->eventType, $this->peserta->getKey()];
        foreach (['completed_stage', 'old_stage', 'new_stage', 'old_status', 'new_status'] as $key) {
            if (array_key_exists($key, $this->context)) $parts[] = (string) $this->context[$key];
        }
        return hash('sha256', implode('|', $parts));
    }
}
