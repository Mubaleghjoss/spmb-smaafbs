<?php

namespace App\Console\Commands;

use App\Http\Controllers\PendaftaranController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class SyntheticApplicantCommand extends Command
{
    protected $signature = 'spmb:test-applicant
        {test_run_id : Exact synthetic run marker}
        {tahun_ajaran_id : Existing academic year ID}
        {gelombang_pendaftaran_id : Existing registration wave ID}
        {--count=1 : Number of applicants}
        {--jenis=siswa_baru : siswa_baru or pindahan}
        {--kelas=10 : Target class}
        {--gender=L : L or P}';
    protected $description = 'Create synthetic applicants through the real registration controller flow';

    public function handle(): int
    {
        if (! in_array(app()->environment(), ['local', 'testing', 'staging'], true)) {
            $this->error('Synthetic applicants are blocked outside local/testing/staging.');
            return self::FAILURE;
        }
        $runId = (string) $this->argument('test_run_id');
        if (! preg_match('/^[A-Z0-9][A-Z0-9._-]{7,99}$/', $runId)) {
            $this->error('Invalid test_run_id format.');
            return self::INVALID;
        }
        $secret = (string) config('services.spmb.synthetic_secret', '');
        if ($secret === '') {
            $this->error('SPMB_SYNTHETIC_SECRET is required.');
            return self::FAILURE;
        }
        $count = max(1, min(100, (int) $this->option('count')));
        $jenis = (string) $this->option('jenis');
        $gender = (string) $this->option('gender');
        if (! in_array($jenis, ['siswa_baru', 'pindahan'], true) || ! in_array($gender, ['L', 'P'], true)) {
            $this->error('Invalid jenis or gender.');
            return self::INVALID;
        }

        $controller = app(PendaftaranController::class);
        for ($i = 1; $i <= $count; $i++) {
            $phone = '0899'.str_pad((string) ($i + 100000), 7, '0', STR_PAD_LEFT);
            $request = Request::create('/daftar', 'POST', [
                'nama' => "SIMULASI SPMB {$runId} {$i}",
                'telepon' => $phone,
                'tahun_ajaran_id' => (int) $this->argument('tahun_ajaran_id'),
                'gelombang_pendaftaran_id' => (int) $this->argument('gelombang_pendaftaran_id'),
                'jenis_pendaftaran' => $jenis,
                'kelas_tujuan' => (int) $this->option('kelas'),
                'setuju' => '1',
                'tanggal_lahir' => '2010-01-01',
                'tempat_lahir' => 'Tangerang',
                'jenis_kelamin' => $gender,
                'asal_sekolah' => 'SMP SIMULASI',
                'nama_ayah' => 'Ayah Simulasi',
                'nama_ibu' => 'Ibu Simulasi',
                'alamat_kota' => 'Tangerang',
            ]);
            $request->attributes->set('test_run_id', $runId);
            $request->headers->set('X-SPMB-Synthetic-Secret', $secret);
            $response = $controller->proses($request);
            if ($response->getStatusCode() >= 400) {
                $this->error("Applicant {$i} failed with HTTP {$response->getStatusCode()}.");
                return self::FAILURE;
            }
            $this->line("created {$i}/{$count}");
        }

        $this->info("Created {$count} synthetic applicant(s) for {$runId} through registration flow.");
        return self::SUCCESS;
    }
}
