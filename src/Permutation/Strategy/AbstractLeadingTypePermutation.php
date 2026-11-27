<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy;

/**
 * Base class for the languages that put the generic term in front of the name
 * instead of gluing it to the end: "Avenue de la Gare", "Via Guglielmo
 * Marconi", "ul. Tadeusza Kościuszki".
 *
 * The German and Dutch strategies work the other way round ("Hauptstraße",
 * "Kerkstraat") and therefore do not build on this class.
 *
 * Every subclass declares the spellings of its own generic terms in
 * {@see static::TYPES}, taken from the public dictionary of its language
 * ({@see \Binable\AddressKit\Language\Fr\StreetTypes}, …); the shared part
 * is telling the term from the name and finding the first word the name is
 * actually built from.
 */
abstract class AbstractLeadingTypePermutation extends AbstractPermutation
{
    /**
     * Canonical term => every spelling of it, the full one first, then the
     * abbreviations the registers use.
     *
     * @var array<string, list<string>>
     */
    protected const TYPES = [];

    /**
     * Splits "Avenue du Général de Gaulle" into "Avenue" and
     * "du Général de Gaulle". Returns null when the street does not start with
     * a known generic term - the name part cannot be told from the term then,
     * and every permutation building on it would be guessing.
     *
     * @return array{string, string}|null
     */
    protected function splitType(?string $street): ?array
    {
        if (null === $street || 1 !== preg_match('/^('.$this->getTypePattern().') +(\S.*)$/iu', $street, $matches)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * Alternation of every known spelling, longest first so "Grande Rue" wins
     * over "Rue", "V.le" over "V." and "Rd-Pt" over "Rd".
     */
    protected function getTypePattern(): string
    {
        return self::alternation(array_merge(...array_values(static::TYPES)));
    }

    /**
     * Every spelling of the type de voie / tipo di via the street starts with,
     * followed by the name ("Av. de la Gare" -> "Avenue de la Gare", "Ave de la Gare", …).
     *
     * @return iterable<string>
     */
    protected function getTypeSpellings(?string $street): iterable
    {
        $split = $this->splitType($street);
        if (null === $split) {
            return;
        }

        [$type, $name] = $split;
        $found = mb_strtolower($type);

        foreach (static::TYPES as $spellings) {
            if (!\in_array($found, $spellings, true)) {
                continue;
            }

            foreach ($spellings as $spelling) {
                $variation = $this->capitalise($spelling).' '.$name;
                if ($variation !== $street) {
                    yield $variation;
                }
            }
        }
    }

    /**
     * Drops the given name in front of a surname and shortens it to its
     * initial: "Via Guglielmo Marconi" -> "Via Marconi", "Via G. Marconi".
     * A name that is already an initial is only dropped.
     *
     * Both are register conventions, not spelling variants, so guessing is not
     * an option: the permutation only fires when the first word of the name is
     * a known given name of that language. Without the list "Via Monte Bianco"
     * would turn into "Via Bianco" and produce nothing but dead lookups.
     *
     * @param array{string, string}|null $split        the street split into its generic term and its
     *                                                 name, {@see splitType()}. A language whose term
     *                                                 may be missing passes an empty term instead of null.
     * @param list<string>               $givenNames   lowercase, in the form street names use them
     *                                                 (Polish names stand in the genitive: "tadeusza",
     *                                                 not "tadeusz")
     * @param list<string>               $notAnInitial lowercase single letters with a dot that look like
     *                                                 an initial but are not - the Italian "S." of
     *                                                 "Via S. Marco" is a saint, and dropping it loses the name
     *
     * @return iterable<string>
     */
    protected function getGivenNameVariations(?array $split, array $givenNames, array $notAnInitial = []): iterable
    {
        if (null === $split) {
            return;
        }

        [$type, $name] = $split;
        $words = preg_split('/ +/', trim($name)) ?: [];

        // A surname has to be left over, otherwise "Via Giuseppe" would lose
        // its whole name part.
        if (\count($words) < 2) {
            return;
        }

        $first = $words[0];
        $rest = implode(' ', \array_slice($words, 1));
        $isInitial = 1 === preg_match('/^\p{Lu}\.$/u', $first)
            && !\in_array(mb_strtolower($first), $notAnInitial, true);

        if (!$isInitial && !\in_array(mb_strtolower($first), $givenNames, true)) {
            return;
        }

        yield trim($type.' '.$rest);

        if (!$isInitial) {
            yield trim($type.' '.mb_strtoupper(mb_substr($first, 0, 1)).'. '.$rest);
        }
    }
}
