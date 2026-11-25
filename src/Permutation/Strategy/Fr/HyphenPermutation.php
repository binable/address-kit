<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Fr;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Hyphen <-> space inside the dedicated name of a French street. La Poste and
 * the BAN write the whole name with hyphens ("Avenue du Général-de-Gaulle",
 * "Rue du 8-Mai-1945"), most municipal portals write it with spaces
 * ("Avenue du Général de Gaulle").
 *
 * Only the name is hyphenated, never the article and the particle that
 * introduce it: "Rue de la Paix" stays as it is.
 *
 * Runs before {@see StreetTypePermutation} and {@see TitlePermutation} so both
 * spellings of the name are fed into their permutations.
 */
#[AsTaggedItem(priority: 50)]
final class HyphenPermutation extends AbstractFrenchPermutation
{
    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street) {
            return;
        }

        if (str_contains($street, '-')) {
            yield $address->withStreet(preg_replace('/ {2,}/', ' ', str_replace('-', ' ', $street)) ?? $street);
        }

        $hyphenated = $this->hyphenateName($street);
        if (null !== $hyphenated && $hyphenated !== $street) {
            yield $address->withStreet($hyphenated);
        }
    }

    /**
     * "Avenue du Général de Gaulle" -> "Avenue du Général-de-Gaulle".
     *
     * Returns null when there is nothing to hyphenate: no type de voie in
     * front (the name cannot be told from the type then, and a German or Dutch
     * street of BE/CH would be mangled), or a name of a single word
     * ("Rue de la Paix").
     */
    private function hyphenateName(string $street): ?string
    {
        $split = $this->splitType($street);
        if (null === $split) {
            return null;
        }

        [$type, $rest] = $split;
        $words = preg_split('/ +/', $rest) ?: [];

        $start = null;
        foreach ($words as $index => $word) {
            if ($this->isNameWord($word)) {
                $start = $index;
                break;
            }
        }

        if (null === $start) {
            return null;
        }

        $name = \array_slice($words, $start);

        // A single word carries no hyphen, and a name may not end on a
        // particle - "Rue du Moulin de" is not a street.
        if (\count($name) < 2 || !$this->isNameWord((string) end($name))) {
            return null;
        }

        return rtrim($type.' '.implode(' ', \array_slice($words, 0, $start))).' '.implode('-', $name);
    }
}
