<?php

namespace App\Helpers;

/**
 * Helper class for normalizing Reff ID Pelanggan
 * Ensures consistent format across imports and manual entry
 */
class ReffIdHelper
{
    /**
     * Normalize Reff ID to ensure consistent format
     *
     * Rules:
     * - Uppercase all characters (for alphanumeric IDs)
     * - Auto-pad purely numeric IDs to 8 digits with leading zeros
     * - Trim whitespace
     *
     * Examples:
     * - "442142" → "00442142" (padded to 8 digits)
     * - "00442142" → "00442142" (already 8 digits)
     * - "abc001" → "ABC001" (alphanumeric, uppercased, no padding)
     * - "ABC001" → "ABC001" (already uppercase)
     * - "  123  " → "00000123" (trimmed and padded)
     *
     * @param string|null $reffId Raw Reff ID input
     * @return string|null Normalized Reff ID or null if empty
     */
    public static function normalize(?string $reffId): ?string
    {
        if ($reffId === null) {
            return null;
        }

        // Trim whitespace
        $str = trim((string)$reffId);

        // Return null if empty string
        if ($str === '') {
            return null;
        }

        // Uppercase first (for alphanumeric IDs like ABC001)
        $str = strtoupper($str);

        // Auto-pad to 8 digits ONLY if purely numeric
        if (ctype_digit($str) && strlen($str) < 8) {
            $str = str_pad($str, 8, '0', STR_PAD_LEFT);
        }

        return $str;
    }

    /**
     * Check if a Reff ID is valid
     *
     * @param string|null $reffId
     * @return bool
     */
    public static function isValid(?string $reffId): bool
    {
        if ($reffId === null || trim($reffId) === '') {
            return false;
        }

        $normalized = self::normalize($reffId);

        // Must contain only alphanumeric characters after normalization
        return $normalized !== null && preg_match('/^[A-Z0-9]+$/', $normalized);
    }
}
