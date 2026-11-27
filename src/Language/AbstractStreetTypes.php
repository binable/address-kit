<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language;

/**
 * Base class of the public street type dictionaries, one per language
 * ({@see Fr\StreetTypes}, {@see It\StreetTypes}, …).
 *
 * The permutation strategies of a language build on its dictionary, and code
 * outside the kit can use the same tables to strip, expand or compare street
 * types instead of keeping a copy of its own.
 *
 * Every dictionary lists its terms twice:
 *
 *  - {@see static::TYPES}: terms that are a generic term whenever they stand in
 *    their place ("Rue", "Avenue", "ulica"). The permutations generate every
 *    spelling of these and may drop them.
 *  - {@see static::NAME_LIKE_TYPES}: terms that are just as often the first or
 *    last word of the name itself ("Le Clos", "Ponte Vecchio", "Victoria Park").
 *    They are recognised by the lookups when asked for, but the permutations
 *    leave them alone - permuting them only produces dead lookups.
 *
 * Lookups are case insensitive and ignore a trailing full stop, so "Av", "av."
 * and "AV" are all found. Diacritics are not folded: every dictionary lists
 * the folded spelling the registers of that language use ("praca", "namesti")
 * as a spelling of its own.
 */
abstract class AbstractStreetTypes
{
    /**
     * Canonical term => every spelling of it, lowercase. The full spelling comes
     * first, then the abbreviations the registers use.
     *
     * @var array<string, list<string>>
     */
    public const TYPES = [];

    /**
     * Same format as {@see static::TYPES}, for the terms that are just as often
     * part of the name.
     *
     * @var array<string, list<string>>
     */
    public const NAME_LIKE_TYPES = [];

    final private function __construct()
    {
    }

    /**
     * The canonical term of a spelling ("av." -> "avenue"), or null when the
     * word is no known street type.
     */
    public static function getCanonical(string $word, bool $includeNameLike = false): ?string
    {
        $needle = self::fold($word);
        if ('' === $needle) {
            return null;
        }

        foreach (static::getTypes($includeNameLike) as $canonical => $spellings) {
            foreach ($spellings as $spelling) {
                if (self::fold($spelling) === $needle) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    /**
     * Every spelling of the term the word is a spelling of, full one first
     * ("av." -> ["avenue", "av.", "av", …]); empty when the word is no known
     * street type.
     *
     * @return list<string>
     */
    public static function getSpellings(string $word, bool $includeNameLike = false): array
    {
        $canonical = static::getCanonical($word, $includeNameLike);

        return null === $canonical ? [] : static::getTypes($includeNameLike)[$canonical];
    }

    public static function isType(string $word, bool $includeNameLike = false): bool
    {
        return null !== static::getCanonical($word, $includeNameLike);
    }

    /**
     * Every spelling of every term, e.g. to build a regex alternation or to
     * filter the street types out of a list of words.
     *
     * @return list<string>
     */
    public static function getAllSpellings(bool $includeNameLike = false): array
    {
        $types = static::getTypes($includeNameLike);

        return [] === $types ? [] : array_values(array_unique(array_merge(...array_values($types))));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function getTypes(bool $includeNameLike = false): array
    {
        return $includeNameLike ? static::TYPES + static::NAME_LIKE_TYPES : static::TYPES;
    }

    private static function fold(string $word): string
    {
        return rtrim(mb_strtolower(trim($word)), '.');
    }
}
