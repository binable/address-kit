<?php

declare(strict_types=1);

namespace Binable\AddressKit\Matching;

/**
 * Checks whether a house number falls within a range expression.
 *
 * The house number may contain a suffix letter ("6a"), a flat ("12/3") or a
 * range ("5-7") - only the first number is used for comparison. A house number
 * without any digit is never contained.
 *
 * Supported expression formats (whitespace around the operators is ignored):
 *   "5"           — exact match
 *   "1-50"        — inclusive range
 *   "4+"          — 4 and above (open upper bound)
 *   "3-"          — 3 and below (open lower bound)
 *   "1-10, 20-30" — multiple comma-separated blocks (any match wins)
 */
final class HouseNumberRange
{
    private const BLOCK_PATTERN = '/^(?:(?<min>\d+)\s*\+|(?<max>\d+)\s*-|(?<from>\d+)\s*-\s*(?<to>\d+)|(?<exact>\d+))$/';

    public function contains(string|int $houseNumber, string $expression): bool
    {
        // Only the first group of digits: "12/3" is house 12, flat 3, and
        // "5-7" starts at house 5 — gluing the digits would make 123 and 57.
        if (1 !== preg_match('/\d+/', (string) $houseNumber, $match)) {
            return false;
        }

        $number = (int) $match[0];

        foreach (explode(',', $expression) as $block) {
            if ($this->matchesBlock($number, trim($block))) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when every comma separated block of the expression has one of the supported formats.
     */
    public function isValidExpression(string $expression): bool
    {
        foreach (explode(',', $expression) as $block) {
            if (1 !== preg_match(self::BLOCK_PATTERN, trim($block))) {
                return false;
            }
        }

        return true;
    }

    private function matchesBlock(int $number, string $block): bool
    {
        if (1 !== preg_match(self::BLOCK_PATTERN, $block, $m, \PREG_UNMATCHED_AS_NULL)) {
            return false;
        }

        return match (true) {
            null !== $m['min'] => $number >= (int) $m['min'],
            null !== $m['max'] => $number <= (int) $m['max'],
            null !== $m['from'] && null !== $m['to'] => $number >= (int) $m['from'] && $number <= (int) $m['to'],
            default => $number === (int) $m['exact'],
        };
    }
}
