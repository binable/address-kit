<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\De;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of a trailing "Straße": glued, separated, abbreviated and -
 * for multi part names - hyphenated.
 *
 * "Hauptstraße" -> "Hauptstrasse", "Hauptstr.", "Hauptstr", "Haupt Straße", …
 * "Albert-Einstein-Str." -> "Albert-Einstein-Straße", "Albert-Einstein Straße",
 * "Albert-Einsteinstraße", …
 * "Weberstasse" -> "Weberstraße", …
 * "Junkersstraße." -> "Junkersstraße", … (stray full stop behind the full term)
 * "Markkleeberger" -> "Markkleeberger Straße", … (term left out behind a
 * place adjective)
 * "Kurt-Schumacher" -> "Kurt-Schumacher-Straße", … (term left out behind a
 * hyphenated chain of names)
 */
#[AsTaggedItem(priority: 10)]
final class StrassePermutation extends AbstractGermanPermutation
{
    /**
     * @var list<string>
     */
    private const GLUED = ['straße', 'strasse', 'str.', 'str'];

    /**
     * @var list<string>
     */
    private const SEPARATED = ['Straße', 'Strasse', 'Str.'];

    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street) {
            return;
        }

        // The misspellings "-strase" and "-stasse" are common enough in typed
        // input to be read as the generic term as well, and so are a doubled
        // dot ("Cyriakstr..") and an abbreviation that lost its "s"
        // ("Brillertr."); only correct spellings are generated from the stem.
        if (1 === preg_match('/^(.+?)[ -]?(?:(?:stra(?:ss|ß|s)e|stasse)\.*|str\.+|str|(?<=[a-zäöü])tr\.+)$/iu', $street, $matches)) {
            yield from $this->getTermCombinations($address, $matches[1], self::GLUED, self::SEPARATED);

            return;
        }

        // A single word built from a place name ("Markkleeberger",
        // "Leipziger") is the street "… Straße" with its term left out.
        // Only separated spellings exist for those adjectives.
        if (1 === preg_match('/^\p{Lu}\p{Ll}{3,}er$/u', $street)) {
            foreach (self::SEPARATED as $term) {
                yield $address->withStreet($street.' '.$term);
            }

            return;
        }

        // A hyphenated chain of names ("Kurt-Schumacher", "Konrad-Adenauer")
        // is the street "…-Straße" with its term left out: a person's name is
        // never a street name on its own.
        if ($this->isTermlessNameChain($street)) {
            yield from $this->getTermCombinations($address, $street, self::GLUED, self::SEPARATED);
        }
    }

    /**
     * True for "Kurt-Schumacher" and "Kurt-schumacher" as typed, false for a
     * name that already carries another generic term ("Hans-Sachs-Weg",
     * "Kurt-Schumacher-Ring") and for a phrase ("Unter-den-Linden").
     */
    private function isTermlessNameChain(string $street): bool
    {
        if (1 !== preg_match('/^\p{Lu}\p{Ll}+(?:-\p{L}\p{Ll}+)+$/u', $street)) {
            return false;
        }

        $parts = explode('-', mb_strtolower($street));
        foreach ($parts as $part) {
            if (\in_array($part, self::PHRASE_WORDS, true)) {
                return false;
            }
        }

        $last = (string) end($parts);
        foreach (array_keys(GrundwortPermutation::TERMS) as $term) {
            if (str_ends_with($last, $term)) {
                return false;
            }
        }

        return true;
    }
}
