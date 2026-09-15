<?php

namespace App\Console\Commands;

use App\Models\Peserta;
use App\Services\SpmbReadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestCleanupCommand extends Command
{
    protected $signature = 'spmb:test-cleanup {test_run_id : Exact test run marker} {--apply : Permanently remove the marked records (staging/testing only)} {--yes : Skip the confirmation prompt}';
    protected $description = 'Preview and safely clean synthetic applicants by exact test_run_id';

    public function handle(SpmbReadService $readService): int
    {
        $runId = (string) $this->argument('test_run_id');
        if (! preg_match('/^[A-Z0-9][A-Z0-9._-]{7,99}$/', $runId)) {
            $this->error('Invalid test_run_id format.');
            return self::INVALID;
        }

        $query = Peserta::withoutGlobalScopes()->where('test_run_id', $runId)->where('is_test', true);
        $records = (clone $query)->with('tahunAjaran')->get();
        $this->table(['id', 'nomor_pendaftaran', 'nama', 'tahun_ajaran_id', 'status_kuota'], $records->map(fn (Peserta $p) => [$p->id, $p->nomor_pendaftaran, $p->nama, $p->tahun_ajaran_id, $p->status_kuota])->all());
        $this->info('Preview: '.$records->count().' exact synthetic record(s) matched. No changes made.');

        if (! $this->option('apply')) {
            return self::SUCCESS;
        }
        if (! in_array(app()->environment(), ['local', 'testing', 'staging'], true)) {
            $this->error('Cleanup is blocked outside local/testing/staging.');
            return self::FAILURE;
        }
        if (! $this->option('yes') && ! $this->confirm('Permanently remove only these exact synthetic records?')) {
            return self::SUCCESS;
        }

        $before = $this->statistics($readService, $records);
        DB::transaction(function () use ($query): void {
            // Physical delete lets the existing foreign-key cascades remove related test data.
            $query->get()->each(fn (Peserta $peserta) => $peserta->forceDelete());
        });
        $after = $this->statistics($readService, $records);
        $remaining = Peserta::withoutGlobalScopes()->where('test_run_id', $runId)->where('is_test', true)->count();

        $this->line(json_encode(['before' => $before, 'after' => $after, 'remaining_exact_matches' => $remaining], JSON_PRETTY_PRINT));
        return $remaining === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function statistics(SpmbReadService $service, $records): array
    {
        return $records->groupBy('tahun_ajaran_id')->mapWithKeys(fn ($group, $yearId) => [
            (string) $yearId => ['quota' => $service->getQuota((int) $yearId), 'statistics' => $service->getStatistics((int) $yearId)],
        ])->all();
    }
}
