<?php

namespace App\Helpers;

use App\Models\CalonPelanggan;
use App\Models\SkData;

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
        $str = trim((string) $reffId);

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

    /**
     * Find the original/canonical ID as stored in DB
     * mimicking the smart search logic from CalonPelangganController
     *
     * @param string|null $reffId
     * @return string|null The actual ID in DB or null if not found
     */
    public static function findOriginalId(?string $reffId): ?string
    {
        if (!$reffId)
            return null;

        $id = strtoupper(trim($reffId));

        // 1. Strict search
        $cp = CalonPelanggan::where('reff_id_pelanggan', $id)->first();
        if ($cp) {
            return $cp->reff_id_pelanggan;
        }

        // 2. Fallback: check unpadded numeric
        if (ctype_digit($id)) {
            $cp = CalonPelanggan::whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int) $id])->first();
            if ($cp) {
                return $cp->reff_id_pelanggan;
            }
        }

        // 3. Fallback: Check SK relations
        $sk = SkData::with('calonPelanggan')
            ->where('reff_id_pelanggan', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$sk && ctype_digit($id)) {
            $sk = SkData::with('calonPelanggan')
                ->whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int) $id])
                ->whereNull('deleted_at')
                ->first();
        }

        if ($sk && $sk->calonPelanggan) {
            return $sk->calonPelanggan->reff_id_pelanggan;
        }

        return null;
    }
}
