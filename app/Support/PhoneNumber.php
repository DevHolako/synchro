<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize a phone number to standard E.164 format.
     * Handles Moroccan local prefixes (06, 07, 05) converting to +212.
     */
    public static function normalize(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';

        if (str_starts_with($cleaned, '00')) {
            $cleaned = '+'.substr($cleaned, 2);
        }

        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 10) {
            $cleaned = '+212'.substr($cleaned, 1);
        } elseif (! str_starts_with($cleaned, '+') && strlen($cleaned) >= 9) {
            $cleaned = '+'.$cleaned;
        }

        return $cleaned;
    }
}
