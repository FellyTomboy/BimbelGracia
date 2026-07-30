<?php

declare(strict_types=1);

namespace App\Helpers;

class WhatsappHelper
{
    /**
     * Convert phone number from 08 format to 628 format for WA.me links.
     * If number already starts with 62, return as is.
     */
    public static function toWaFormat(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        // Remove all non-digit characters
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        // If starts with 0, replace with 62
        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        // If doesn't start with 62, prepend 62
        if (!str_starts_with($digits, '62')) {
            return '62' . $digits;
        }

        return $digits;
    }
}