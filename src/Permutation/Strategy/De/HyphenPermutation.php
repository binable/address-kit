<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\De;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Hyphen <-> space in multi part street names, the most common difference
 * between two registers: "Albert-Einstein-Straße" / "Albert Einstein Straße",
 * "Dr.-Karl-Renner-Platz" / "Dr. Karl Renner Platz".
 *
 * Runs before {@see StrassePermutation} and {@see GrundwortPermutation} so
 * both spellings of the name part are fed into the generic term permutations.
 * The separator directly in front of the generic term is therefore not handled
 * here.
 */
#[AsTaggedItem(priority: 50)]
final class HyphenPermutation extends AbstractGermanPermutation
{
    /**
     * Leading parts of place names that are written either apart or together
     * ("Klein-Kollenburg" / "Kleinkollenburg").
     *
     * @var list<string>
     */
    private const PLACE_PREFIXES = ['Klein', 'Groß', 'Gross', 'Alt', 'Neu', 'Ober', 'Unter', 'Nieder', 'Hohen', 'Mittel'];

    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street) {
            return;
        }

        if (str_contains($street, '-')) {
            yield $address->withStreet(preg_replace('/ {2,}/', ' ', str_replace('-', ' ', $street)) ?? $street);
        }

        // Street names derived from a place carry the place's prefix either
        // hyphenated or glued: "Klein-Kollenburg-Straße" / "Kleinkollenburgstr.",
        // "Groß Gaglower Weg" / "Großgaglower Weg". Only these prefixes are
        // glued; a chain of person names ("Albert-Einstein") never is.
        if (1 === preg_match('/^('.implode('|', self::PLACE_PREFIXES).')[- ](\p{Lu})(.*)$/u', $street, $prefixed)) {
            yield $address->withStreet($prefixed[1].mb_strtolower($prefixed[2]).$prefixed[3]);
        }

        // A name chain typed without any separator keeps its capitals:
        // "TheoNeubauerStr" -> "Theo-Neubauer-Str", which the generic term
        // permutations then complete. German street names never carry a
        // capital inside a word, so the split is safe.
        if (!str_contains($street, ' ') && !str_contains($street, '-') && 1 === preg_match('/\p{Ll}\p{Lu}/u', $street)) {
            yield $address->withStreet(preg_replace('/(\p{Ll})(\p{Lu})/u', '$1-$2', $street) ?? $street);
        }

        // Two words are almost always name plus generic term ("Kissinger
        // Straße", "Alter Markt") and get their hyphen from the generic term
        // permutations; a real name chain has at least name, name and term.
        if (str_contains($street, ' ') && $this->isProperNameChain($street, 3)) {
            yield $address->withStreet(preg_replace('/ +/', '-', $street) ?? $street);
        }
    }
}
