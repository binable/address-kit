<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Fr;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the leading "type de voie": "Avenue de la Gare" /
 * "Av. de la Gare" / "Av de la Gare" / "Ave de la Gare", "Bd Voltaire" /
 * "Boulevard Voltaire", "Rd-Pt des Vignes" / "Rond-Point des Vignes".
 *
 * French counterpart of the German Straße/Grundwort permutations, only much
 * simpler: the type de voie always stands in front, separated by a space, so
 * there is no glued and no hyphenated spelling to generate.
 */
#[AsTaggedItem(priority: 10)]
final class StreetTypePermutation extends AbstractFrenchPermutation
{
    public function permute(Address $address): iterable
    {
        foreach ($this->getTypeSpellings($address->street) as $street) {
            yield $address->withStreet($street);
        }
    }
}
