<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Pt;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the leading "tipo de via": "Av. da República" /
 * "Avenida da República" / "Av da República", "R. Augusta" / "Rua Augusta",
 * "Praça do Comércio" / "Pç. do Comércio" / "Praca do Comércio".
 */
#[AsTaggedItem(priority: 10)]
final class StreetTypePermutation extends AbstractPortuguesePermutation
{
    public function permute(Address $address): iterable
    {
        foreach ($this->getTypeSpellings($address->street) as $street) {
            yield $address->withStreet($street);
        }
    }
}
