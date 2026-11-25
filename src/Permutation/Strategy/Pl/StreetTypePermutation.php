<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Pl;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the leading generic term, including the two that no other
 * language needs: none at all and one that was not there before.
 *
 * "ul. Długa" / "ulica Długa" / "ul Długa" / "Długa", and the other way round
 * "Długa" / "ul. Długa". Polish registers are split right down the middle on
 * whether the "ul." belongs to the street name.
 *
 * The term stays lower case, the spelling Polish uses. A capitalised "Ul." is
 * not emitted: it would double the whole tree, and Polish registers compare
 * the name case insensitively anyway.
 */
#[AsTaggedItem(priority: 10)]
final class StreetTypePermutation extends AbstractPolishPermutation
{
    /**
     * The term that is put in front of a street that carries none. Only
     * "ulica" - it is the default by a wide margin, and guessing "aleja" or
     * "osiedle" for a name that never had one produces nothing but dead
     * lookups.
     *
     * @var list<string>
     */
    private const IMPLIED_TYPE = ['ul.', 'ulica', 'ul'];

    public function permute(Address $address): iterable
    {
        $street = $address->street;
        if (null === $street || '' === trim($street)) {
            return;
        }

        foreach ($this->getVariations($street) as $variation) {
            if ($variation !== $street) {
                yield $address->withStreet($variation);
            }
        }
    }

    /**
     * @return iterable<string>
     */
    private function getVariations(string $street): iterable
    {
        $split = $this->splitType($street);
        if (null === $split) {
            foreach (self::IMPLIED_TYPE as $type) {
                yield $type.' '.$street;
            }

            return;
        }

        [$type, $name] = $split;

        // The name on its own - a register that keeps the term in a column of
        // its own stores exactly this.
        yield $name;

        $found = mb_strtolower($type);

        foreach (self::TYPES as $spellings) {
            if (!\in_array($found, $spellings, true)) {
                continue;
            }

            foreach ($spellings as $spelling) {
                yield $spelling.' '.$name;
            }
        }
    }
}
