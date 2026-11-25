<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\En;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the street suffix: "Sunset Boulevard" / "Sunset Blvd" /
 * "Sunset". The bare name is what a gazetteer stores that keeps the suffix in
 * a column of its own.
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

        $parts = $this->parse($address->street);

        $spelling = null === $parts['suffix'] ? null : $this->findSpelling($parts['suffix'], self::SUFFIXES);
        if (null !== $spelling) {
            foreach ([...$spelling, null] as $suffix) {
                yield from $this->yieldStreet($address, $this->build(['suffix' => $suffix] + $parts));
            }

            return;
        }

        $words = $parts['name'];
        $leading = \count($words) >= 2 ? $this->findSpelling($words[0], self::SUFFIXES) : null;
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
