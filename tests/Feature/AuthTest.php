<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Peserta;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $pengguna = Pengguna::create([
            'nama' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $result = $this->authService->autentikasi('admin@test.com', 'password123');

        $this->assertNotNull($result);
        $this->assertEquals($pengguna->id, $result->id);
    }

    public function test_login_default_admin_memulihkan_pengguna_saat_tabel_kosong(): void
    {
        config([
            'auth.default_pengguna.auto_repair' => true,
            'auth.default_pengguna.accounts' => [[
                'email' => 'admin@smaalfurqon.sch.id',
                'password' => 'admin123',
                'nama' => 'Administrator',
                'peran' => 'admin',
            ]],
        ]);

        $result = $this->authService->autentikasiDenganStatus('admin@smaalfurqon.sch.id', 'admin123');

        $this->assertSame('success', $result['status']);
        $this->assertNotNull($result['pengguna']);
        $this->assertDatabaseHas('pengguna', [
            'email' => 'admin@smaalfurqon.sch.id',
            'nama' => 'Administrator',
            'peran' => 'admin',
            'aktif' => true,
        ]);
        $this->assertTrue(Hash::check('admin123', $result['pengguna']->password));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        Pengguna::create([
            'nama' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'peran' => 'admin',
            'aktif' => true,
        ]);

        $result = $this->authService->autentikasi('admin@test.com', 'wrongpassword');

        $this->assertNull($result);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        Pengguna::create([
            'nama' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'peran' => 'admin',
            'aktif' => false,
        ]);

        $result = $this->authService->autentikasi('admin@test.com', 'password123');

        $this->assertNull($result);
    }

    public function test_account_gets_locked_after_3_failed_attempts(): void
    {
        $pengguna = Pengguna::create([
            'nama' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'peran' => 'admin',
            'aktif' => true,
        ]);

        // 3 failed attempts
        $this->authService->autentikasi('admin@test.com', 'wrong1');
        $this->authService->autentikasi('admin@test.com', 'wrong2');
        $this->authService->autentikasi('admin@test.com', 'wrong3');

        // Account should be locked
        $this->assertTrue($this->authService->cekKunciAkun('admin@test.com'));

        // Even correct password should fail
        $result = $this->authService->autentikasi('admin@test.com', 'password123');
        $this->assertNull($result);
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    public function test_peserta_login_page_is_accessible(): void
    {
        $response = $this->get(route('peserta.login'));
        $response->assertStatus(200);
    }

    public function test_token_login_page_is_accessible(): void
    {
        $response = $this->get(route('login.token'));
        $response->assertStatus(200);
    }
}
