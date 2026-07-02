<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;

class PenggunaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Password akan otomatis di-hash oleh model cast 'hashed'
     */
    public function run(): void
    {
        // Admin default
        Pengguna::updateOrCreate(
            ['email' => 'admin@smaalfurqon.sch.id'],
            [
                'nama' => 'Administrator',
                'password' => 'admin123',
                'peran' => 'admin',
                'aktif' => true,
            ]
        );

        // Operator default
        Pengguna::updateOrCreate(
            ['email' => 'operator@smaalfurqon.sch.id'],
            [
                'nama' => 'Operator SPMB',
                'password' => 'operator123',
                'peran' => 'operator',
                'aktif' => true,
            ]
        );
    }
}
