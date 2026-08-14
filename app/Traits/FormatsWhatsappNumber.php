<?php

declare(strict_types=1);

namespace App\Traits;

trait FormatsWhatsappNumber
{
    /**
     * Normalize phone number to 08XXXXXXXXX format for storage.
     * Only convert to 628 format when generating wa.me links (via WhatsappHelper).
     */
    protected function normalizeWhatsapp(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        if ($digits === '') {
            return null;
        }

        // Keep as 08XXXXXXXXX format in database
        if (strlen($digits) > 13) {
            $digits = substr($digits, -13);
        }

        // Ensure starts with 08
        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        if (! str_starts_with($digits, '0')) {
            return '0'.$digits;
        }

        return $digits;
    }
}
