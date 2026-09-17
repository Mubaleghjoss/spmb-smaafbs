<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimSpmbRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tim_spmb_role_can_be_saved(): void
    {
        $pengguna = Pengguna::factory()->create([
            'peran' => 'tim_spmb',
        ]);

        $this->assertDatabaseHas('pengguna', [
            'id' => $pengguna->id,
            'peran' => 'tim_spmb',
        ]);
        $this->assertTrue($pengguna->adalahTimSpmb());
        $this->assertTrue($pengguna->punyaAksesAdmin());
    }
}
