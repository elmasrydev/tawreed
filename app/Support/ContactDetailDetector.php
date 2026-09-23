<?php

namespace App\Support;

/**
 * Suppliers occasionally paste a phone number or email into a quote's free text
 * to take the deal off the platform. The reports screen in the prototype treats
 * that as a violation, so quotes are screened before they are stored.
 */
class ContactDetailDetector
{
    /**
     * Matches Egyptian mobiles written with or without a country code, allowing
     * separators, plus anything shaped like an email address or a messaging handle.
     *
     * @var array<int, string>
     */
    private const PATTERNS = [
        '/(?:\+?20|00?20)?[\s.\-]?0?1[0125](?:[\s.\-]?\d){8}/u',
        '/[\w.+-]+@[\w-]+\.[\w.]{2,}/u',
        '/\b(?:wa\.me|t\.me|whatsapp|telegram)\b/iu',
    ];

    /**
     * @param  array<string, string|null>  $fields  keyed by field name
     * @return array<int, string> the field names that contain contact details
     */
    public function offendingFields(array $fields): array
    {
        $offenders = [];

        foreach ($fields as $name => $value) {
            if (is_string($value) && $this->matches($value)) {
                $offenders[] = $name;
            }
        }

        return $offenders;
    }

    public function matches(string $text): bool
    {
        $normalized = $this->toWesternDigits($text);

        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Arabic-Indic digits are common in Egyptian input and would otherwise slip
     * past a digit-based pattern.
     */
    private function toWesternDigits(string $text): string
    {
        return strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
