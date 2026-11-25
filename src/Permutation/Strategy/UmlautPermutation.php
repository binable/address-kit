<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\PermutationStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Transliterates German umlauts and ß in street and city ("Müllerstraße" → "Muellerstrasse").
 */
#[AsTaggedItem(priority: 80)]
final class UmlautPermutation implements PermutationStrategyInterface
{
    private const MAP = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ẞ' => 'SS',
    ];

    public function permute(Address $address): iterable
    {
        $street = null !== $address->street ? strtr($address->street, self::MAP) : null;
        $city = null !== $address->city ? strtr($address->city, self::MAP) : null;

        if ($street === $address->street && $city === $address->city) {
            return;
        }

        yield $address->withStreet($street)->withCity($city);
    }
}
