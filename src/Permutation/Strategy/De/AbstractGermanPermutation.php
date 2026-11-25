<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\De;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\LocalizedPermutationStrategyInterface;
use Binable\AddressKit\Permutation\Strategy\AbstractPermutation;

/**
 * Base class for permutations that only make sense for German street names
 * ("Hauptstraße", "Albert-Einstein-Platz", "Schürhornweg").
 *
 * Which countries use German street names is decided by the
 * {@see \Binable\AddressKit\Language\StreetLanguageMap}.
 */
abstract class AbstractGermanPermutation extends AbstractPermutation implements LocalizedPermutationStrategyInterface
{
    /**
     * Lowercase words that mark a street name as a phrase ("Am Alten
     * Sportplatz", "Straße des Friedens", "Alte Poststraße") instead of a chain
     * of proper names. Phrases are never written with hyphens.
     *
     * Names built with a particle ("Von der Tann") are covered as well, so they
     * are only permuted from hyphen to space, not the other way round.
     *
     * @var list<string>
     */
    protected const PHRASE_WORDS = [
        'am', 'an', 'auf', 'aus', 'bei', 'beim', 'bis', 'das', 'dem', 'den', 'der', 'des', 'die', 'durch', 'für',
        'gegen', 'hinter', 'im', 'in', 'nach', 'neben', 'ob', 'über', 'um', 'unter', 'vom', 'von', 'vor', 'zu',
        'zum', 'zur', 'zwischen',
        // leading adjectives
        'alte', 'alten', 'alter', 'altes', 'breite', 'breiter', 'große', 'großen', 'großer', 'grosse', 'grossen',
        'grosser', 'hintere', 'hinterer', 'kleine', 'kleinen', 'kleiner', 'kurze', 'kurzer', 'lange', 'langen',
        'langer', 'mittlere', 'mittlerer', 'neue', 'neuen', 'neuer', 'neues', 'obere', 'oberen', 'oberer',
        'untere', 'unteren', 'unterer', 'vordere', 'vorderer',
    ];

    public function getLanguage(): string
    {
        return 'de';
    }

    /**
     * Emits every common way a compound street name can be written: the generic
     * term glued to the name part ("Hauptstraße"), separated by a space
     * ("Haupt Straße") and - for name parts that already consist of several
     * words - separated by a hyphen ("Albert-Einstein-Straße",
     * "Albert-Einstein-straße").
     *
     * @param string       $base      street name without its generic term
     * @param list<string> $glued     lowercase spellings appended without separator
     * @param list<string> $separated capitalised spellings appended after a separator
     *
     * @return iterable<Address>
     */
    protected function getTermCombinations(Address $address, string $base, array $glued, array $separated): iterable
    {
        $base = rtrim($base, " \t-");

        // Nothing sensible is left of the name part ("Am Weg" -> "Am").
        if (mb_strlen($base) < 3) {
            return;
        }

        foreach ($glued as $term) {
            yield $address->withStreet($base.$term);
        }

        foreach ($separated as $term) {
            yield $address->withStreet($base.' '.$term);
        }

        // Only chains of proper names ("Albert-Einstein", "Käthe Kollwitz") are
        // written with a hyphen in front of the generic term - "Am Sport-Platz"
        // and "Alte Post-Straße" do not exist.
        if (!str_contains($base, '-') && !$this->isProperNameChain($base)) {
            return;
        }

        foreach ($separated as $term) {
            yield $address->withStreet($base.'-'.$term);
        }

        foreach ($glued as $term) {
            yield $address->withStreet($base.'-'.$term);
        }
    }

    /**
     * True for "Albert Einstein Straße" and "Käthe Kollwitz", false for
     * "Am Anger", "Alte Post" and "Straße des Friedens".
     */
    protected function isProperNameChain(string $value, int $minWords = 2): bool
    {
        $words = explode(' ', trim($value));
        if (\count($words) < $minWords || \count($words) > 4) {
            return false;
        }

        foreach ($words as $word) {
            if (\in_array(mb_strtolower($word), static::PHRASE_WORDS, true)) {
                return false;
            }

            $first = mb_substr($word, 0, 1);
            if ('' === $first || mb_strtolower($first) === $first) {
                return false;
            }
        }

        return true;
    }
}
