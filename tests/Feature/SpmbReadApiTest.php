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

    public function test_authentication_and_quota(): void
    {
        $this->postJson('/api/v1/bot/query', ['action' => 'get_quota'])->assertUnauthorized();
        $this->withToken('wrong')->postJson('/api/v1/bot/query', ['action' => 'get_quota'])->assertUnauthorized();
        $year = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true, 'kuota_peserta' => 30]);
        $this->applicant($year, ['status_kuota' => 'dalam_kuota']);
        $this->bot(['action' => 'get_quota', 'filters' => ['tahun_ajaran_id' => $year->id]])->assertOk()->assertJsonPath('data.registered', 1)->assertJsonPath('data.quota', 30);
    }

    public function test_statistics_search_detail_and_gender_summary(): void
    {
        $year = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true]);
        $p = $this->applicant($year, ['nomor_pendaftaran' => 'REG-001', 'nama' => 'Siti Aminah'], ['jenis_kelamin' => 'P', 'status_verifikasi' => 'terverifikasi']);
        $this->bot(['action' => 'get_statistics'])->assertOk()->assertJsonPath('data.total', 1);
        $this->bot(['action' => 'search_applicant', 'filters' => ['query' => 'REG-001']])->assertOk()->assertJsonCount(1, 'data');
        $this->bot(['action' => 'search_applicant', 'filters' => ['query' => 'Siti']])->assertOk()->assertJsonCount(1, 'data');
        $this->bot(['action' => 'get_applicant_detail', 'filters' => ['identifier' => (string) $p->id]])->assertOk()->assertJsonPath('data.nama', 'Siti Aminah');
        $this->bot(['action' => 'gender_summary'])->assertOk()->assertJsonPath('data.P', 1);
    }

    public function test_list_filters_and_security_rejection(): void
    {
        $year = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true]);
        $this->applicant($year, ['asal_sekolah' => 'SMP Harapan'], ['alamat_kota' => 'Tangerang', 'alamat_kecamatan' => 'Ciledug', 'status_verifikasi' => 'menunggu']);
        foreach ([['city' => 'Tangerang'], ['district' => 'Ciledug'], ['school' => 'Harapan'], ['verification_status' => 'menunggu'], ['document_status' => 'incomplete'], ['registered_today' => true]] as $filters) {
            $this->bot(['action' => 'list_applicants', 'filters' => $filters])->assertOk()->assertJsonPath('data.meta.total', 1);
        }
        foreach ([null, '', 'unsupported', 'get_quota_typo', 'create_applicant', ['get_quota']] as $action) {
            $this->bot(['action' => $action])
                ->assertStatus(400)
                ->assertJsonPath('message', 'Unsupported action');
        }

        $this->bot(['action' => 'list_applicants', 'filters' => ['city' => "Tangerang'; DROP TABLE peserta;--"]])->assertStatus(422);
    }

    public function test_academic_year_name_filter_variants_and_active_default(): void
    {
        $active = TahunAjaran::create([
            'nama' => '2026/2027',
            'aktif' => true,
            'kuota_peserta' => 30,
        ]);

        $future = TahunAjaran::create([
            'nama' => '2027-2028',
            'aktif' => false,
            'kuota_peserta' => 40,
        ]);

        $this->applicant(
            $active,
            ['nama' => 'Active Applicant'],
            ['jenis_kelamin' => 'P']
        );

        $this->applicant(
            $future,
            ['nama' => 'Future Applicant 1'],
            ['jenis_kelamin' => 'L']
        );

        $this->applicant(
            $future,
            ['nama' => 'Future Applicant 2'],
            ['jenis_kelamin' => 'L']
        );

        // Tanpa tahun harus memakai tahun ajaran aktif, bukan seluruh tahun.
        $this->bot(['action' => 'get_quota'])
            ->assertOk()
            ->assertJsonPath('data.registered', 1)
            ->assertJsonPath('data.quota', 30);

        $this->bot(['action' => 'get_statistics'])
            ->assertOk()
            ->assertJsonPath('data.total', 1);

        $this->bot(['action' => 'gender_summary'])
            ->assertOk()
            ->assertJsonPath('data.P', 1)
            ->assertJsonPath('data.L', 0);

        // Format tahun fleksibel tetapi semuanya menuju tahun yang sama.
        foreach (['2027/2028', '2027-2028', '2027 2028'] as $year) {
            $this->bot([
                'action' => 'get_quota',
                'filters' => ['tahun_ajaran' => $year],
            ])
                ->assertOk()
                ->assertJsonPath('data.registered', 2)
                ->assertJsonPath('data.quota', 40);
        }

        $this->bot([
            'action' => 'get_statistics',
            'filters' => ['tahun_ajaran' => '2027/2028'],
        ])
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $this->bot([
            'action' => 'gender_summary',
            'filters' => ['tahun_ajaran' => '2027-2028'],
        ])
            ->assertOk()
            ->assertJsonPath('data.L', 2)
            ->assertJsonPath('data.P', 0);

        // Tahun valid tetapi belum tersedia tidak boleh fallback ke tahun aktif.
        $this->bot([
            'action' => 'get_quota',
            'filters' => ['tahun_ajaran' => '2028/2029'],
        ])
            ->assertStatus(404)
            ->assertJsonPath(
                'message',
                'Tahun ajaran 2028-2029 belum tersedia'
            );

        // Format/rentang tidak valid harus ditolak.
        $this->bot([
            'action' => 'get_quota',
            'filters' => ['tahun_ajaran' => '2027/2029'],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid tahun_ajaran format');
    }

    public function test_list_applicants_filters_explicit_year_and_defaults_to_active_year(): void
    {
        $active = TahunAjaran::create(['nama' => '2026/2027', 'aktif' => true]);
        $future = TahunAjaran::create(['nama' => '2027/2028', 'aktif' => false]);

        $this->applicant($active, ['nama' => 'Active Applicant']);
        $this->applicant($future, ['nama' => 'Future Applicant']);

        $this->bot([
            'action' => 'list_applicants',
            'filters' => ['tahun_ajaran' => '2027/2028'],
        ])
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.nama', 'Future Applicant');

        $this->bot(['action' => 'list_applicants'])
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.nama', 'Active Applicant');
    }

    public function test_audit_log_is_generated(): void
    {
        @unlink(storage_path('logs/data-bot-audit.log'));
        $this->bot(['action' => 'get_statistics'], ['X-Telegram-User-Id' => '123'])->assertOk();
        $this->assertStringContainsString('123', (string) file_get_contents(storage_path('logs/data-bot-audit.log')));
    }

    private function bot(array $payload, array $headers = []) { return $this->withToken($this->token)->postJson('/api/v1/bot/query', $payload, $headers); }
    private function applicant(TahunAjaran $year, array $attributes = [], array $form = []): Peserta
    {
        $p = Peserta::withoutGlobalScopes()->create(array_merge(['nomor_pendaftaran' => 'REG-'.fake()->unique()->numerify('####'), 'tahun_ajaran_id' => $year->id, 'nama' => 'Applicant', 'email' => fake()->unique()->safeEmail(), 'password' => 'password', 'telepon' => '081'.fake()->unique()->numerify('#########')], $attributes));
        FormulirSpmb::create(array_merge(['peserta_id' => $p->id, 'nama_lengkap' => $p->nama, 'status_verifikasi' => 'draft'], $form));
        return $p;
    }
}
