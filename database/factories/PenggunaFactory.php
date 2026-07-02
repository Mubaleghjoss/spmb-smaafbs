<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pengguna>
 */
class PenggunaFactory extends Factory
{
    /**
     * Define the model's default state.
     * Password akan otomatis di-hash oleh model cast 'hashed'
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake('id_ID')->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'peran' => 'operator',
            'aktif' => true,
        ];
    }

    /**
     * State untuk admin
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => 'admin',
        ]);
    }

    /**
     * State untuk operator
     */
    public function operator(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => 'operator',
        ]);
    }
}
