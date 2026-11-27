<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\AddressField;
use Binable\AddressKit\Permutation\Strategy\De\UmlautPermutation;

/**
 * Comparison key for German street names (DE, AT, CH, LI): two spellings of the same street get the
 * same key, e.g. to match user input against the street list of a register or an API.
 *
 *   "Hauptstraße", "Hauptstrasse", "Hauptstr.", "HAUPTSTR" → "hauptstr."
 *   "Müllerweg", "Muellerweg"                              → "muellerweg"
 *   "Straße des 17. Juni", "Str. des 17. Juni"             → "str. des 17. juni"
 *   "Albert-Einstein-Straße"                               → "albert-einstein-str."
 *
 * The key is for comparing only, never for display - {@see StreetNormalizer} writes the street out for
 * that. Steps:
 *
 *  1. encoding and whitespace cleanup ({@see EncodingNormalizer}, {@see WhitespaceNormalizer}):
 *     non-breaking spaces, mojibake, repeated spaces, trailing separators
 *  2. lower case - before the transliteration, so "Ölweg" becomes "oelweg"
 *  3. ä/ö/ü/ß → ae/oe/ue/ss ({@see UmlautPermutation::TO_ASCII})
 *  4. the generic term "straße" in all spellings → "str.": glued or separated, abbreviated with or
 *     without dot, and the typos "strase", "stasse" and "str.." - but only as a whole word or word
 *     ending, "Straßenbahnweg" keeps its "strassen"
 *
 * Nothing else is unified: "Albert-Einstein-Str." and "Albert Einstein Str." stay different keys -
 * use the {@see \Binable\AddressKit\Permutation\AddressPermutator} for that.
 */
final class StreetKey
{
    public const GENERIC_TERM = 'str.';

    private const GENERIC_TERM_PATTERN = '/(?:strasse|strase|stasse|str\.*)(?=$|[\s\-\/,;)])/u';

    private static ?EncodingNormalizer $encoding = null;
    private static ?WhitespaceNormalizer $whitespace = null;

    public static function create(string $street): string
    {
        self::$encoding ??= EncodingNormalizer::createDefault();
        self::$whitespace ??= WhitespaceNormalizer::createDefault();

        $street = self::$encoding->normalizeString($street, AddressField::Street);
        $street = self::$whitespace->normalizeString($street, AddressField::Street);
        $street = strtr(mb_strtolower($street), UmlautPermutation::TO_ASCII);

        return (string) preg_replace(self::GENERIC_TERM_PATTERN, self::GENERIC_TERM, $street);
    }
}
