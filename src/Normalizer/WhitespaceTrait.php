<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

trait WhitespaceTrait
{
    /**
     * Trims, collapses all (unicode) whitespace to single spaces and maps '' to null.
     */
    private function cleanWhitespace(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim((string) preg_replace('/[\s\x{00A0}\x{200B}]+/u', ' ', $value));

        return '' === $value ? null : $value;
    }
}
