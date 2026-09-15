<?php

namespace Tests\Feature;

use App\Models\FormulirSpmb;
use App\Models\Peserta;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpmbReadApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-data-bot-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.spmb_data_bot.token' => $this->token]);
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->postJson('/api/v1/bot/query', ['action' => 'get_quota'])->assertUnauthorized();
    }

    public function test_all_six_allowlisted_actions_are_read_only_and_authenticated(): void
    {
        $year = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true, 'kuota_peserta' => 30]);
        $participant = $this->applicant($year, ['nomor_pendaftaran' => 'REG-001', 'nama' => 'Siti Aminah'], ['jenis_kelamin' => 'P']);
        $before = Peserta::withoutGlobalScopes()->count();

        $this->bot(['action' => 'get_quota', 'filters' => ['tahun_ajaran_id' => $year->id]])->assertOk();
        $this->bot(['action' => 'get_statistics'])->assertOk();
        $this->bot(['action' => 'gender_summary'])->assertOk();
        $this->bot(['action' => 'list_registered_today'])->assertOk();
        $this->bot(['action' => 'search_applicant', 'filters' => ['query' => 'REG-001']])->assertOk()->assertJsonCount(1, 'data');
        $this->bot(['action' => 'get_applicant_detail', 'filters' => ['identifier' => (string) $participant->id]])->assertOk();

        $this->assertSame($before, Peserta::withoutGlobalScopes()->count());
        $this->assertDatabaseCount('formulir_spmb', 1);
    }

    public function test_invalid_actions_are_rejected_without_fallback(): void
    {
        foreach ([null, '', 'get_quota_typo', 'list_applicants', 'create_applicant', ['get_quota']] as $action) {
            $this->bot(['action' => $action])
                ->assertStatus(400)
                ->assertExactJson(['ok' => false, 'message' => 'Unsupported action']);
        }
    }

    public function test_audit_log_is_generated(): void
    {
        @unlink(storage_path('logs/data-bot-audit.log'));
        $this->bot(['action' => 'get_statistics'], ['X-Telegram-User-Id' => '123'])->assertOk();
        $this->assertStringContainsString('123', (string) file_get_contents(storage_path('logs/data-bot-audit.log')));
    }

    private function bot(array $payload, array $headers = [])
    {
        return $this->withToken($this->token)->postJson('/api/v1/bot/query', $payload, $headers);
    }

    private function applicant(TahunAjaran $year, array $attributes = [], array $form = []): Peserta
    {
        $participant = Peserta::withoutGlobalScopes()->create(array_merge([
            'nomor_pendaftaran' => 'REG-'.fake()->unique()->numerify('####'),
            'tahun_ajaran_id' => $year->id,
            'nama' => 'Applicant',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'telepon' => '08123456789',
        ], $attributes));
        FormulirSpmb::create(array_merge([
            'peserta_id' => $participant->id,
            'nama_lengkap' => $participant->nama,
            'status_verifikasi' => 'draft',
        ], $form));
        return $participant;
    }
}
