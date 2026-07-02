<?php

namespace Tests\Property;

use Eris\Generators;
use Illuminate\Support\Facades\Hash;

class AuthPropertyTest extends PropertyTestCase
{
    /**
     * Property 1: Autentikasi Password Round-Trip
     * Untuk setiap password yang valid, jika di-hash menggunakan bcrypt 
     * kemudian diverifikasi dengan password asli, hasilnya harus selalu true.
     */
    public function test_password_hash_round_trip(): void
    {
        $this->forAll(
            Generators::string()
        )->withMaxSize(50)->then(function ($password) {
            // Skip empty passwords
            if (empty($password)) {
                return true;
            }

            // Hash the password
            $hashed = Hash::make($password);

            // Verify the password
            $this->assertTrue(
                Hash::check($password, $hashed),
                "Password verification failed for: {$password}"
            );

            // Verify wrong password fails
            $wrongPassword = $password . '_wrong';
            $this->assertFalse(
                Hash::check($wrongPassword, $hashed),
                "Wrong password should not verify"
            );

            return true;
        });
    }

    /**
     * Property: Password dengan karakter khusus harus tetap bisa di-hash dan diverifikasi
     */
    public function test_password_with_special_characters(): void
    {
        $specialPasswords = [
            'P@ssw0rd!',
            'Test#123$',
            'Spécial€Char',
            '日本語パスワード',
            'emoji🔐password',
            "with'quote",
            'with"double',
            'with\\backslash',
            'with<html>tags',
            "multi\nline",
        ];

        foreach ($specialPasswords as $password) {
            $hashed = Hash::make($password);
            
            $this->assertTrue(
                Hash::check($password, $hashed),
                "Password verification failed for special password"
            );
        }
    }

    /**
     * Property: Hash yang berbeda untuk password yang sama
     * Setiap kali password di-hash, hasilnya harus berbeda (karena salt)
     */
    public function test_different_hashes_for_same_password(): void
    {
        $this->forAll(
            Generators::string()
        )->withMaxSize(30)->then(function ($password) {
            if (empty($password)) {
                return true;
            }

            $hash1 = Hash::make($password);
            $hash2 = Hash::make($password);

            // Hashes should be different (different salts)
            $this->assertNotEquals($hash1, $hash2);

            // But both should verify correctly
            $this->assertTrue(Hash::check($password, $hash1));
            $this->assertTrue(Hash::check($password, $hash2));

            return true;
        });
    }
}
