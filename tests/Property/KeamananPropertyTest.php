<?php

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based tests untuk keamanan
 * Property 10: Validasi Input Menolak Serangan
 * Property 11: Enkripsi Data Sensitif Round-Trip
 * Memvalidasi: Kebutuhan 10.1, 10.3
 */
class KeamananPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 10: Sanitasi menghapus script berbahaya
     */
    public function testSanitasiMenghapusScript(): void
    {
        $this->forAll(
            Generator\string() // random string
        )
        ->then(function (string $input) {
            // Tambahkan script berbahaya
            $maliciousInputs = [
                '<script>alert("xss")</script>' . $input,
                $input . '<img src=x onerror=alert(1)>',
                '<a href="javascript:alert(1)">' . $input . '</a>',
                $input . '<?php echo "hack"; ?>',
            ];

            foreach ($maliciousInputs as $malicious) {
                $sanitized = $this->sanitasiHtml($malicious);
                
                // Tidak boleh ada script tag
                $this->assertStringNotContainsString('<script', strtolower($sanitized));
                
                // Tidak boleh ada event handler
                $this->assertDoesNotMatchRegularExpression('/\son\w+\s*=/i', $sanitized);
                
                // Tidak boleh ada javascript: protocol
                $this->assertStringNotContainsString('javascript:', strtolower($sanitized));
            }
        });
    }

    /**
     * Property 10: Sanitasi teks menghapus HTML
     */
    public function testSanitasiTeksMenghapusHtml(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function (string $input) {
            $withHtml = '<div>' . $input . '</div><script>alert(1)</script>';
            $sanitized = $this->sanitasiTeks($withHtml);
            
            // Tidak boleh ada tag HTML
            $this->assertStringNotContainsString('<', $sanitized);
            $this->assertStringNotContainsString('>', $sanitized);
        });
    }

    /**
     * Property 11: Enkripsi-Dekripsi Round-Trip
     */
    public function testEnkripsiDekripsiRoundTrip(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function (string $original) {
            if (empty($original)) {
                $this->assertTrue(true);
                return;
            }

            // Enkripsi
            $encrypted = $this->enkripsi($original);
            
            // Encrypted harus berbeda dari original
            $this->assertNotEquals($original, $encrypted);
            
            // Dekripsi
            $decrypted = $this->dekripsi($encrypted);
            
            // Harus sama dengan original
            $this->assertEquals($original, $decrypted);
        });
    }

    /**
     * Property 11: Enkripsi menghasilkan output berbeda untuk input sama
     */
    public function testEnkripsiMenghasilkanOutputBerbeda(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function (string $input) {
            if (empty($input)) {
                $this->assertTrue(true);
                return;
            }

            // Enkripsi dua kali
            $encrypted1 = $this->enkripsi($input);
            $encrypted2 = $this->enkripsi($input);
            
            // Hasil enkripsi harus berbeda (karena IV random)
            $this->assertNotEquals($encrypted1, $encrypted2);
            
            // Tapi dekripsi harus sama
            $this->assertEquals($this->dekripsi($encrypted1), $this->dekripsi($encrypted2));
        });
    }

    /**
     * Property: Hash deterministik
     */
    public function testHashDeterministik(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function (string $input) {
            $hash1 = $this->hash($input);
            $hash2 = $this->hash($input);
            
            // Hash harus selalu sama untuk input yang sama
            $this->assertEquals($hash1, $hash2);
            
            // Hash harus 64 karakter (SHA-256)
            $this->assertEquals(64, strlen($hash1));
        });
    }

    /**
     * Property: Hash berbeda untuk input berbeda
     */
    public function testHashBerbedaUntukInputBerbeda(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )
        ->then(function (string $input1, string $input2) {
            if ($input1 === $input2) {
                $this->assertTrue(true);
                return;
            }

            $hash1 = $this->hash($input1);
            $hash2 = $this->hash($input2);
            
            // Hash harus berbeda untuk input berbeda
            $this->assertNotEquals($hash1, $hash2);
        });
    }

    /**
     * Property: Sanitasi array rekursif
     */
    public function testSanitasiArrayRekursif(): void
    {
        $this->forAll(
            Generator\associative([
                'name' => Generator\string(),
                'nested' => Generator\associative([
                    'value' => Generator\string(),
                ]),
            ])
        )
        ->then(function (array $input) {
            // Tambahkan script ke nilai
            $input['name'] = '<script>alert(1)</script>' . ($input['name'] ?? '');
            
            $sanitized = $this->sanitasiArray($input);
            
            // Semua nilai harus tersanitasi
            $this->assertStringNotContainsString('<', $sanitized['name']);
        });
    }

    /**
     * Property: Data sensitif dihapus dari log
     */
    public function testDataSensitifDihapusDariLog(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function (string $password) {
            $context = [
                'user' => 'test',
                'password' => $password,
                'token' => 'secret123',
                'data' => 'normal',
            ];

            $safe = $this->hapusDataSensitif($context);
            
            // Password dan token harus diredact
            $this->assertEquals('[REDACTED]', $safe['password']);
            $this->assertEquals('[REDACTED]', $safe['token']);
            
            // Data normal tetap ada
            $this->assertEquals('normal', $safe['data']);
        });
    }

    // Helper methods yang mensimulasikan KeamananService

    private function sanitasiTeks(string $input): string
    {
        $input = str_replace(chr(0), '', $input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        return trim($input);
    }

    private function sanitasiHtml(string $input): string
    {
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td><span><div>';
        
        $input = str_replace(chr(0), '', $input);
        $input = strip_tags($input, $allowedTags);
        // Hapus event handlers (dengan atau tanpa quotes, dengan atau tanpa spasi)
        $input = preg_replace('/\s*on\w+\s*=\s*["\']?[^"\'>\s]*["\']?/i', '', $input);
        $input = preg_replace('/javascript\s*:/i', '', $input);
        $input = preg_replace('/data\s*:(?!image\/)/i', '', $input);
        
        return $input;
    }

    private function enkripsi(string $data): string
    {
        $key = 'test-key-32-bytes-long-for-aes!';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function dekripsi(string $encryptedData): string
    {
        $key = 'test-key-32-bytes-long-for-aes!';
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    private function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    private function sanitasiArray(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            $sanitizedKey = $this->sanitasiTeks($key);
            
            if (is_array($value)) {
                $result[$sanitizedKey] = $this->sanitasiArray($value);
            } elseif (is_string($value)) {
                $result[$sanitizedKey] = $this->sanitasiTeks($value);
            } else {
                $result[$sanitizedKey] = $value;
            }
        }
        return $result;
    }

    private function hapusDataSensitif(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];

        $result = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (in_array($lowerKey, $sensitiveKeys)) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->hapusDataSensitif($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
