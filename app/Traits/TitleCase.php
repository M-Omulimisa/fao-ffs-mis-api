<?php

namespace App\Traits;

/**
 * Provides a single robust toTitleCase() helper used by model accessors &
 * mutators and the NormalizeNameCasing Artisan command.
 *
 * Handles:
 *  - Standard ASCII names in UPPER / lower / Mixed case
 *  - Zero-width Unicode characters (U+2060 Word Joiner, BOM, ZWJ, ZWNJ, etc.)
 *    that silently prevent ucwords() from capitalising the first letter
 */
trait TitleCase
{
    protected function toTitleCase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Strip Unicode "format" characters (\p{Cf}: zero-width joiners, word
        // joiners, BOM, soft hyphens, etc.) that are invisible but trip up
        // ucwords / trim.
        $clean = preg_replace('/\p{Cf}+/u', '', $value);

        $clean = trim($clean ?? '');

        return $clean !== '' ? ucwords(mb_strtolower($clean)) : null;
    }
}
