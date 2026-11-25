<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\It;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * All spellings of the leading "tipo di via": "Piazza Garibaldi" /
 * "P.zza Garibaldi" / "P.za Garibaldi" / "Pza Garibaldi", "V.le Europa" /
 * "Viale Europa", "C.so Italia" / "Corso Italia".
 *
 * Italian counterpart of
 * {@see \Binable\AddressKit\Permutation\Strategy\Fr\StreetTypePermutation}: the
 * term always stands in front, separated by a space, so there is no glued and
 * no hyphenated spelling to generate.
 */
#[AsTaggedItem(priority: 10)]
final class StreetTypePermutation extends AbstractItalianPermutation
{
    public function permute(Address $address): iterable
    {
        foreach ($this->getTypeSpellings($address->street) as $street) {
            yield $address->withStreet($street);
        }
    }
}
