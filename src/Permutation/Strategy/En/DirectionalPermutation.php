<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\En;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the directionals in front of and behind the name:
 * "West Sunset Blvd" / "W Sunset Blvd" / "Sunset Blvd", "2nd Ave Northeast" /
 * "2nd Ave NE" / "2nd Ave". Gazetteers that keep the directional in a column
 * of its own store the street without it.
 */
#[AsTaggedItem(priority: 20)]
final class DirectionalPermutation extends AbstractEnglishPermutation
{
    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        $parts = $this->parse($address->street);
        if (null === $parts['pre'] && null === $parts['post']) {
            return;
        }

        foreach ($this->getSpellings($parts['pre']) as $pre) {
            foreach ($this->getSpellings($parts['post']) as $post) {
                $street = $this->build(['pre' => $pre, 'post' => $post] + $parts);
                if ($street !== $address->street) {
                    yield $address->withStreet($street);
                }
            }
        }
    }

    /**
     * The directional as written, in full, abbreviated and left out - or
     * nothing but null for a street that has none in that place.
     *
     * @return list<?string>
     */
    private function getSpellings(?string $directional): array
    {
        if (null === $directional) {
            return [null];
        }

        return array_values(array_unique([$directional, ...($this->findSpelling($directional, self::DIRECTIONALS) ?? []), null], \SORT_REGULAR));
    }
}
