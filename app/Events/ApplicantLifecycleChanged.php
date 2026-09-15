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
        $this->peserta->loadMissing('formulirSpmb', 'tahunAjaran');

        return [
            'event_id' => sprintf('%s-%s-%s', $this->eventType, $this->peserta->id, now()->format('YmdHisv')),
            'event_type' => $this->eventType,
            'occurred_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'test_run_id' => $this->peserta->test_run_id,
            'is_test' => (bool) $this->peserta->is_test,
            'applicant' => [
                'id' => $this->peserta->id,
                'registration_number' => $this->peserta->nomor_pendaftaran,
                'name' => $this->peserta->nama,
                'phone' => $this->peserta->telepon,
                'academic_year_id' => $this->peserta->tahun_ajaran_id,
                'quota_status' => $this->peserta->status_kuota,
                'verification_status' => $this->peserta->formulirSpmb?->status_verifikasi,
                'current_stage' => $this->peserta->tahapanSpmb?->tahap_saat_ini,
            ],
            'context' => $this->context,
        ];
    }
}
