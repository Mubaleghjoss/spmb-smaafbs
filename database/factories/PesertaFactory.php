<?php

namespace Database\Factories;

use App\Helpers\NomorPendaftaranHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Peserta>
 */
class PesertaFactory extends Factory
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
            'nomor_pendaftaran' => NomorPendaftaranHelper::generate(),
            'nama' => fake('id_ID')->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'telepon' => fake('id_ID')->phoneNumber(),
            'alamat' => fake('id_ID')->address(),
            'asal_sekolah' => 'SMP ' . fake('id_ID')->city(),
        ];
    }
}
