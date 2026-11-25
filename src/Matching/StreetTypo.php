<?php

declare(strict_types=1);

namespace Binable\AddressKit\Matching;

/**
 * Finds the one register street a typed name misses by a single letter:
 * "Paul-Gerhard-Allee" for "Paul-Gerhardt-Allee", "Bürgermeister-Spitta-Alle"
 * for "…-Allee".
 *
 * Only a unique hit counts, and only for names of at least
 * {@see MIN_LENGTH} characters - short names lie one letter apart from each
 * other all the time ("Hof"/"Hohe"), long ones practically never.
 *
 * The distance is counted in characters, not bytes: "Mullerstraße" is one
 * letter away from "Müllerstraße".
 */
final class StreetTypo
{
    public const MIN_LENGTH = 8;

    /**
     * @param iterable<string> $tried     the spellings already asked for (e.g. the street and its permutations)
     * @param iterable<string> $known     the register's street names
     * @param int              $minLength spellings shorter than this are ignored
     *
     * @return string|null the register's spelling of the unique hit
     */
    public static function findUnique(iterable $tried, iterable $known, int $minLength = self::MIN_LENGTH): ?string
    {
        $register = [];
        foreach ($known as $candidate) {
            $register[$candidate] = mb_strtolower(trim($candidate));
        }

        $found = [];
        foreach ($tried as $street) {
            $needle = mb_strtolower(trim($street));
            if (mb_strlen($needle) < $minLength) {
                continue;
            }

            foreach ($register as $candidate => $lowered) {
                if (1 === self::distance($needle, $lowered)) {
                    $found[(string) $candidate] = true;
                }
            }
        }

        return 1 === \count($found) ? (string) array_key_first($found) : null;
    }

    /**
     * Levenshtein distance in characters. PHP's levenshtein() counts bytes, so every character is
     * mapped to a single byte first (fine as long as both strings together use at most 256 distinct
     * characters, which a street name always does).
     */
    public static function distance(string $a, string $b): int
    {
        $map = [];
        $encode = static function (string $value) use (&$map): string {
            $encoded = '';
            foreach (mb_str_split($value) as $character) {
                $map[$character] ??= \chr(\count($map) % 256);
                $encoded .= $map[$character];
            }

            return $encoded;
        };

        $encodedA = $encode($a);
        $encodedB = $encode($b);

        return \count($map) > 256 ? levenshtein($a, $b) : levenshtein($encodedA, $encodedB);
    }
}
