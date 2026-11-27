<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\En;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the street suffix: "Sunset Boulevard" / "Sunset Blvd" /
 * "Sunset", "Maple Crescent" / "Maple Cres" / "Maple Cr" / "Maple". The bare
 * name is what a gazetteer stores that keeps the suffix in a column of its own.
 *
 * US addresses get the USPS abbreviations, all others the Commonwealth ones,
 * see {@see AbstractEnglishPermutation}.
 *
 * A street that leads with its type ("Avenue H") only swaps the spelling - the
 * type is all that tells "Avenue H" from "H Street".
 */
#[AsTaggedItem(priority: 10)]
final class StreetSuffixPermutation extends AbstractEnglishPermutation
{
    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        $parts = $this->parse($address, $address->street);
        $suffixes = $this->getSuffixes($address);
        $upperCase = $this->isUpperCase($address->street);

        $spellings = null === $parts['suffix'] ? null : $this->findSpellings($parts['suffix'], $suffixes, $upperCase);
        if (null !== $spellings) {
            foreach ([...$spellings, null] as $suffix) {
                yield from $this->yieldStreet($address, $this->build(['suffix' => $suffix] + $parts));
            }

            return;
        }

        $words = $parts['name'];
        $leading = \count($words) >= 2 ? $this->findSpellings($words[0], $suffixes, $upperCase) : null;
        if (null === $leading) {
            return;
        }

        foreach ($leading as $type) {
            $words[0] = $type;
            yield from $this->yieldStreet($address, $this->build(['name' => $words] + $parts));
        }
    }

    /**
     * @return iterable<Address>
     */
    private function yieldStreet(Address $address, string $street): iterable
    {
        if ('' !== $street && $street !== $address->street) {
            yield $address->withStreet($street);
        }
    }
}
