<?php

namespace Tests\Feature;

use App\Models\FormulirSpmb;
use App\Models\Peserta;
use App\Models\TahunAjaran;
use App\Models\TahapanSpmb;
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

    public function test_strict_action_filter_contract_rejects_unknown_missing_and_invalid_filters(): void
    {
        $cases = [
            [['action' => 'get_quota', 'filters' => ['query' => 'x']], 'Unknown filter: query'],
            [['action' => 'search_applicant'], 'Missing required filter'],
            [['action' => 'list_by_city', 'filters' => []], 'Missing required filter'],
            [['action' => 'list_by_verification_status', 'filters' => ['status' => 'bogus']], 'Invalid status'],
            [['action' => 'list_applicants', 'filters' => ['limit' => 101]], 'Invalid limit'],
            [['action' => 'list_applicants', 'filters' => ['page' => 0]], 'Invalid page'],
            [['action' => 'list_applicants', 'filters' => ['registered_date' => '2026/01/01']], 'Invalid registered_date'],
            [['action' => 'get_quota', 'filters' => ['tahun_ajaran' => 2026]], 'Invalid tahun_ajaran format'],
        ];

        foreach ($cases as [$payload, $message]) {
            $this->bot($payload)->assertStatus(422)->assertJsonPath('message', $message);
        }
    }

    public function test_two_digit_year_jalur_stage_combined_filters_and_pagination(): void
    {
        $year = TahunAjaran::create(['nama' => '2030/2031', 'aktif' => true]);
        $first = $this->applicant($year, ['nama' => 'Siswa Baru 1', 'jenis_pendaftaran' => 'siswa_baru', 'kelas_tujuan' => 10]);
        $second = $this->applicant($year, ['nama' => 'Pindahan 1', 'jenis_pendaftaran' => 'pindahan', 'kelas_tujuan' => 11]);
        $third = $this->applicant($year, ['nama' => 'Pindahan 2', 'jenis_pendaftaran' => 'pindahan', 'kelas_tujuan' => 11]);
        TahapanSpmb::forceCreate(['peserta_id' => $first->id, 'tahap_saat_ini' => 3]);
        TahapanSpmb::forceCreate(['peserta_id' => $second->id, 'tahap_saat_ini' => 4]);
        TahapanSpmb::forceCreate(['peserta_id' => $third->id, 'tahap_saat_ini' => 4]);

        $this->bot(['action' => 'list_applicants', 'filters' => [
            'tahun_ajaran' => '30/31', 'jenis_pendaftaran' => 'pindahan', 'kelas_tujuan' => 11,
            'tahapan' => 4, 'limit' => 1, 'page' => 2,
        ]])->assertOk()->assertJsonPath('data.meta.total', 2)->assertJsonPath('data.meta.per_page', 1)->assertJsonPath('data.meta.current_page', 2);

        $this->bot(['action' => 'get_statistics', 'filters' => [
            'tahun_ajaran_id' => $year->id, 'jenis_pendaftaran' => 'pindahan', 'kelas_tujuan' => 11, 'tahapan' => 4,
        ]])->assertOk()->assertJsonPath('data.total', 2);

        $this->bot(['action' => 'gender_summary', 'filters' => ['tahun_ajaran' => '30/31', 'jenis_pendaftaran' => 'siswa_baru', 'tahapan' => 3]])
            ->assertOk()->assertJsonPath('data.unknown', 1);
    }

    public function test_security_filters_are_whitelist_bound_and_search_is_read_only(): void
    {
        $this->bot(['action' => 'search_applicant', 'filters' => ['query' => "x' UNION SELECT * FROM peserta"]])->assertStatus(422);
        $this->bot(['action' => 'list_applicants', 'filters' => ['nama' => ['nested' => 'value']]])->assertStatus(422);
        $this->bot(['action' => 'list_applicants', 'filters' => ['jenis_pendaftaran' => 'siswa_baru', 'kelas_tujuan' => 11]])->assertStatus(422);
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
