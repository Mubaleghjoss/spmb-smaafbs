<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Support\PesertaPhoneNormalizer;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ActiveCsrfMiddlewareForTest extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}

class PesertaAkunSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private Peserta $peserta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->peserta = Peserta::factory()->create([
            'telepon' => '081234567890',
            'password' => 'CurrentPass!123',
        ]);
    }

    public function test_phone_normalizer_accepts_supported_forms_only(): void
    {
        $this->assertSame('081234567890', PesertaPhoneNormalizer::normalize('081234567890'));
        $this->assertSame('081234567890', PesertaPhoneNormalizer::normalize('6281234567890'));
        $this->assertSame('081234567890', PesertaPhoneNormalizer::normalize('+62 812-3456-7890'));
        $this->assertNull(PesertaPhoneNormalizer::normalize('08123abc7890'));
        $this->assertNull(PesertaPhoneNormalizer::normalize('081234567'));
    }

    public function test_account_endpoints_require_participant_session(): void
    {
        $this->post(route('peserta.akun.username'), [
            'username' => '081234567891', 'current_password' => 'CurrentPass!123',
        ])->assertRedirect(route('peserta.login'));

        $this->post(route('peserta.akun.password'), [
            'current_password' => 'CurrentPass!123', 'password' => 'NewStrongPass!123', 'password_confirmation' => 'NewStrongPass!123',
        ])->assertRedirect(route('peserta.login'));
    }

    public function test_missing_session_participant_is_cleared_and_redirected(): void
    {
        $this->withSession(['peserta_id' => 999999])
            ->post(route('peserta.akun.password'), [
                'current_password' => 'CurrentPass!123', 'password' => 'NewStrongPass!123', 'password_confirmation' => 'NewStrongPass!123',
            ])
            ->assertRedirect(route('peserta.login'))
            ->assertSessionMissing('peserta_id');
    }

    public function test_participant_can_change_username_with_canonical_normalization_and_regenerated_session(): void
    {
        $oldSessionId = $this->app['session']->getId();
        $this->withSession(['peserta_id' => $this->peserta->id])
            ->post(route('peserta.akun.username'), [
                'username' => '+62 812-3456-7891', 'current_password' => 'CurrentPass!123',
            ])
            ->assertSessionHas('success');

        $this->assertNotSame($oldSessionId, $this->app['session']->getId());
        $this->assertDatabaseHas('peserta', ['id' => $this->peserta->id, 'telepon' => '081234567891']);
        $this->assertTrue($this->withSession([])->post(route('peserta.login.proses'), [
            'telepon' => '62 812-3456-7891', 'password' => 'CurrentPass!123',
        ])->isRedirection());
    }

    public function test_participant_cannot_change_another_participant(): void
    {
        $other = Peserta::factory()->create(['telepon' => '081234567891', 'password' => 'OtherPass!123']);
        $this->withSession(['peserta_id' => $other->id])
            ->post(route('peserta.akun.username'), [
                'username' => '081234567892', 'current_password' => 'CurrentPass!123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('peserta', ['id' => $other->id, 'telepon' => '081234567891']);
    }

    public function test_username_must_be_unique_and_duplicate_constraint_is_safe(): void
    {
        Peserta::factory()->create(['telepon' => '081234567891']);

        $this->withSession(['peserta_id' => $this->peserta->id])
            ->post(route('peserta.akun.username'), [
                'username' => '62-812-3456-7891', 'current_password' => 'CurrentPass!123',
            ])
            ->assertSessionHasErrors('username')
            ->assertDontSee('SQLSTATE');
    }

    public function test_duplicate_database_constraint_during_save_returns_validation_error(): void
    {
        $created = false;
        Peserta::saving(function (Peserta $model) use (&$created): void {
            if ($created || ! $model->isDirty('telepon') || $model->getKey() === null) {
                return;
            }

            $created = true;
            Peserta::factory()->create(['telepon' => $model->telepon]);
        });

        $this->withSession(['peserta_id' => $this->peserta->id])
            ->post(route('peserta.akun.username'), [
                'username' => '081234567891', 'current_password' => 'CurrentPass!123',
            ])
            ->assertSessionHasErrors('username')
            ->assertDontSee('SQLSTATE');
    }

    public function test_wrong_current_password_does_not_change_credentials(): void
    {
        $this->withSession(['peserta_id' => $this->peserta->id])
            ->post(route('peserta.akun.password'), [
                'current_password' => 'wrong-password', 'password' => 'NewStrongPass!123', 'password_confirmation' => 'NewStrongPass!123',
            ])->assertSessionHasErrors('current_password');

        $this->peserta->refresh();
        $this->assertTrue(Hash::check('CurrentPass!123', $this->peserta->password));
    }

    public function test_password_requires_confirmation_and_strong_policy(): void
    {
        $this->withSession(['peserta_id' => $this->peserta->id])->post(route('peserta.akun.password'), [
            'current_password' => 'CurrentPass!123', 'password' => 'weak-password', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['password']);
    }

    public function test_password_is_hashed_old_password_is_invalid_and_session_regenerates(): void
    {
        $oldSessionId = $this->app['session']->getId();
        $this->withSession(['peserta_id' => $this->peserta->id])
            ->post(route('peserta.akun.password'), [
                'current_password' => 'CurrentPass!123', 'password' => 'NewStrongPass!123', 'password_confirmation' => 'NewStrongPass!123',
            ])->assertSessionHas('success');

        $this->assertNotSame($oldSessionId, $this->app['session']->getId());
        $this->peserta->refresh();
        $this->assertTrue(Hash::check('NewStrongPass!123', $this->peserta->password));
        $this->assertFalse(Hash::check('CurrentPass!123', $this->peserta->password));
        $this->assertDatabaseMissing('peserta', ['password' => 'NewStrongPass!123']);
    }

    public function test_login_normalizes_all_supported_phone_forms(): void
    {
        foreach (['081234567890', '6281234567890', '+62 812-3456-7890', '08 1234-567-890'] as $phone) {
            $this->post(route('peserta.login.proses'), ['telepon' => $phone, 'password' => 'CurrentPass!123'])
                ->assertRedirect(route('peserta.dashboard'));
            $this->post(route('peserta.logout'));
        }
    }

    public function test_route_has_throttle_and_separate_account_action_buckets(): void
    {
        $usernameRoute = Route::getRoutes()->getByName('peserta.akun.username');
        $passwordRoute = Route::getRoutes()->getByName('peserta.akun.password');
        $this->assertContains('throttle:6,1', $usernameRoute->middleware());
        $this->assertContains('throttle:6,1', $passwordRoute->middleware());

        RateLimiter::hit('peserta-account:'.$this->peserta->id.':127.0.0.1:username:testing', 60);
        $this->assertFalse(RateLimiter::tooManyAttempts('peserta-account:'.$this->peserta->id.':127.0.0.1:password:testing', 5));
    }

    public function test_route_throttle_returns_429_after_limit(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->withSession(['peserta_id' => $this->peserta->id])
                ->post(route('peserta.akun.password'), [
                    'current_password' => 'wrong-password', 'password' => 'NewStrongPass!123', 'password_confirmation' => 'NewStrongPass!123',
                ]);
        }

        $this->withSession(['peserta_id' => $this->peserta->id])
            ->post(route('peserta.akun.password'), [
                'current_password' => 'wrong-password', 'password' => 'NewStrongPass!123', 'password_confirmation' => 'NewStrongPass!123',
            ])
            ->assertStatus(429);
    }

    public function test_post_without_csrf_token_is_rejected_by_active_csrf_middleware(): void
    {
        Route::post('/__test/akun-csrf', fn () => response('ok'))->middleware([StartSession::class, ActiveCsrfMiddlewareForTest::class]);
        $this->withoutExceptionHandling();
        $this->expectException(TokenMismatchException::class);

        $this->withSession(['peserta_id' => $this->peserta->id])
            ->post('/__test/akun-csrf');
    }

    public function test_dashboard_has_csrf_forms_and_never_renders_password(): void
    {
        $response = $this->withSession(['peserta_id' => $this->peserta->id])->get(route('peserta.dashboard'));

        $response->assertOk()
            ->assertSee('Akun Saya')
            ->assertSee(route('peserta.akun.username'), false)
            ->assertSee(route('peserta.akun.password'), false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('CurrentPass!123')
            ->assertDontSee('NewStrongPass!123');
    }
}
