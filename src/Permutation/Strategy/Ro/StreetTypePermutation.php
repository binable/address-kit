<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\Ro;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the leading generic term: "Str. Mihai Eminescu" /
 * "Strada Mihai Eminescu", "B-dul Unirii" / "Bulevardul Unirii" / "Bd. Unirii",
 * "Şoseaua Kiseleff" / "Șoseaua Kiseleff" / "Sos. Kiseleff".
 */
#[AsTaggedItem(priority: 10)]
final class StreetTypePermutation extends AbstractRomanianPermutation
{
    public function permute(Address $address): iterable
    {
        foreach ($this->getTypeSpellings($address->street) as $street) {
            yield $address->withStreet($street);
        }
    }
}
