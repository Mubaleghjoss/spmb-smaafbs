<?php

namespace App\Support;

final class PesertaPhoneNormalizer
{
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/[^0-9+\s().-]/', $value)) {
            return null;
        }

        $value = preg_replace('/[\s().-]+/', '', $value) ?? '';
        if (str_starts_with($value, '+62')) {
            $value = '0' . substr($value, 3);
        } elseif (str_starts_with($value, '62')) {
            $value = '0' . substr($value, 2);
        } elseif (str_starts_with($value, '+')) {
            return null;
        }

        return preg_match('/^08[0-9]{8,13}$/', $value) ? $value : null;
    }
}
