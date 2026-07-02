<?php

namespace Database\Factories;

use App\Models\Grup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model Grup
 */
class GrupFactory extends Factory
{
    protected $model = Grup::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(2, true),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
