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
    protected function normalizeCase(?string $value, string $mode = 'title'): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/\p{Cf}+/u', '', $value);
        $clean = trim($clean ?? '');

        if ($clean === '') {
            return null;
        }

        if ($mode === 'upper') {
            return mb_strtoupper($clean);
        }

        return ucwords(mb_strtolower($clean));
    }

    protected function toTitleCase(?string $value): ?string
    {
        return $this->normalizeCase($value, 'title');
    }

    protected function toUpperCase(?string $value): ?string
    {
        return $this->normalizeCase($value, 'upper');
    }
}
