<?php

namespace Database\Factories;

use App\Models\Tes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model Tes
 */
class TesFactory extends Factory
{
    protected $model = Tes::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(3, true),
            'keterangan' => fake()->optional()->paragraph(),
            'durasi_menit' => fake()->numberBetween(30, 120),
            'nilai_lulus' => fake()->numberBetween(50, 80),
            'mulai' => null,
            'selesai' => null,
            'acak_soal' => fake()->boolean(),
            'acak_jawaban' => fake()->boolean(),
            'tampilkan_nilai' => true,
            'tampilkan_pembahasan' => false,
            'status' => 'draft',
        ];
    }

    /**
     * Tes dengan status aktif
     */
    public function aktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aktif',
        ]);
    }

    /**
     * Tes dengan status selesai
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
        ]);
    }
}
