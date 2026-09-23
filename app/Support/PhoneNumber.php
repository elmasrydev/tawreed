<?php

namespace App\Support;

/**
 * Egyptian mobile numbers arrive in several shapes (+20, 0020, leading zero,
 * spaces). The platform stores one canonical form so a number identifies
 * exactly one account.
 */
class PhoneNumber
{
    public static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        // Strip the Egyptian country code in either of its written forms.
        $digits = preg_replace('/^(?:0020|20)/', '', $digits) ?? $digits;

        // Local numbers are stored with their leading zero: 01012345678.
        return str_starts_with($digits, '0') ? $digits : '0'.$digits;
    }

    public static function isValidEgyptianMobile(string $raw): bool
    {
        return (bool) preg_match('/^01[0125]\d{8}$/', self::normalize($raw));
    }
}
