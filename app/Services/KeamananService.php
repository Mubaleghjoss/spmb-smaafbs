<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

/**
 * Service untuk keamanan aplikasi
 * Kebutuhan: 10.1, 10.2, 10.3, 10.6
 */
class KeamananService
{
    /**
     * Daftar tag HTML yang diizinkan
     */
    private const ALLOWED_TAGS = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td><img><a><span><div>';

    /**
     * Daftar ekstensi file yang diizinkan untuk upload
     */
    private const ALLOWED_EXTENSIONS = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'audio' => ['mp3', 'wav', 'ogg'],
    ];

    /**
     * Ukuran maksimum file (dalam bytes)
     */
    private const MAX_FILE_SIZES = [
        'image' => 5 * 1024 * 1024, // 5MB
        'document' => 10 * 1024 * 1024, // 10MB
        'audio' => 20 * 1024 * 1024, // 20MB
    ];

    /**
     * Sanitasi input teks untuk mencegah XSS
     * Kebutuhan: 10.1
     */
    public function sanitasiTeks(string $input): string
    {
        // Hapus null bytes
        $input = str_replace(chr(0), '', $input);

        // Encode HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Hapus karakter kontrol kecuali newline dan tab
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        return trim($input);
    }

    /**
     * Sanitasi HTML untuk konten rich text (soal, pembahasan)
     * Kebutuhan: 10.1
     */
    public function sanitasiHtml(string $input): string
    {
        // Hapus null bytes
        $input = str_replace(chr(0), '', $input);

        // Strip tags kecuali yang diizinkan
        $input = strip_tags($input, self::ALLOWED_TAGS);

        // Hapus event handlers JavaScript
        $input = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);

        // Hapus javascript: protocol
        $input = preg_replace('/javascript\s*:/i', '', $input);

        // Hapus data: protocol kecuali untuk gambar
        $input = preg_replace('/data\s*:(?!image\/)/i', '', $input);

        return $input;
    }

    /**
     * Validasi dan sanitasi input array
     */
    public function sanitasiArray(array $input): array
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

    /**
     * Validasi file upload
     * Kebutuhan: 10.2
     */
    public function validasiFile(UploadedFile $file, string $tipe = 'image'): array
    {
        $errors = [];

        // Cek ekstensi
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = self::ALLOWED_EXTENSIONS[$tipe] ?? [];
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Ekstensi file tidak diizinkan. Diizinkan: " . implode(', ', $allowedExtensions);
        }

        // Cek ukuran
        $maxSize = self::MAX_FILE_SIZES[$tipe] ?? 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            $errors[] = "Ukuran file terlalu besar. Maksimum: " . $this->formatBytes($maxSize);
        }

        // Cek MIME type
        $mimeType = $file->getMimeType();
        if (!$this->validasiMimeType($mimeType, $tipe)) {
            $errors[] = "Tipe file tidak valid.";
        }

        // Cek konten file untuk gambar
        if ($tipe === 'image' && empty($errors)) {
            if (!$this->validasiKontenGambar($file)) {
                $errors[] = "File bukan gambar yang valid.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validasi MIME type
     */
    private function validasiMimeType(string $mimeType, string $tipe): bool
    {
        $allowedMimes = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
        ];

        return in_array($mimeType, $allowedMimes[$tipe] ?? []);
    }

    /**
     * Validasi konten gambar
     */
    private function validasiKontenGambar(UploadedFile $file): bool
    {
        try {
            $imageInfo = getimagesize($file->getRealPath());
            return $imageInfo !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format bytes ke human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Enkripsi data sensitif
     * Kebutuhan: 10.3
     */
    public function enkripsi(string $data): string
    {
        return Crypt::encryptString($data);
    }

    /**
     * Dekripsi data sensitif
     * Kebutuhan: 10.3
     */
    public function dekripsi(string $encryptedData): ?string
    {
        try {
            return Crypt::decryptString($encryptedData);
        } catch (\Exception $e) {
            $this->logError('Gagal dekripsi data', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Hash data untuk perbandingan
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Verifikasi hash
     */
    public function verifikasiHash(string $data, string $hash): bool
    {
        return hash_equals($hash, $this->hash($data));
    }

    /**
     * Log error dengan aman (tanpa data sensitif)
     * Kebutuhan: 10.6
     */
    public function logError(string $message, array $context = []): void
    {
        // Hapus data sensitif dari context
        $safeContext = $this->hapusDataSensitif($context);
        
        Log::error($message, $safeContext);
    }

    /**
     * Log warning dengan aman
     */
    public function logWarning(string $message, array $context = []): void
    {
        $safeContext = $this->hapusDataSensitif($context);
        Log::warning($message, $safeContext);
    }

    /**
     * Log info dengan aman
     */
    public function logInfo(string $message, array $context = []): void
    {
        $safeContext = $this->hapusDataSensitif($context);
        Log::info($message, $safeContext);
    }

    /**
     * Hapus data sensitif dari array
     */
    private function hapusDataSensitif(array $data): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'api_key',
            'secret', 'credit_card', 'cvv', 'pin', 'ssn',
            'mail_password', 'db_password',
        ];

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

    /**
     * Validasi input untuk mencegah SQL injection
     * (Laravel sudah handle ini via Eloquent, tapi untuk raw query)
     */
    public function sanitasiSql(string $input): string
    {
        // Escape karakter berbahaya
        $input = addslashes($input);
        
        // Hapus komentar SQL
        $input = preg_replace('/--.*$|\/\*.*?\*\//s', '', $input);
        
        return $input;
    }

    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validasi rate limiting
     */
    public function cekRateLimit(string $key, int $maxAttempts = 5, int $decayMinutes = 1): bool
    {
        $cacheKey = 'rate_limit:' . $key;
        $attempts = cache()->get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        cache()->put($cacheKey, $attempts + 1, now()->addMinutes($decayMinutes));
        return true;
    }

    /**
     * Reset rate limit
     */
    public function resetRateLimit(string $key): void
    {
        cache()->forget('rate_limit:' . $key);
    }
}
