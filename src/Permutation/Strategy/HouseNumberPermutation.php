<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\PermutationStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * "12a" ⇄ "12 a" ⇄ "12A" ⇄ "12 A".
 *
 * Language independent. Runs last, so the house number split off the street by
 * {@see HouseNumberInStreetPermutation} is covered as well, and the street
 * variants are not crowded out by the house number variants when the limit is reached.
 */
#[AsTaggedItem(priority: 0)]
final class HouseNumberPermutation implements PermutationStrategyInterface
{
    public function permute(Address $address): iterable
    {
        if (null === $address->houseNumber || 1 !== preg_match('/^(?<number>\d+)\s?(?<suffix>[a-zA-Z])$/', $address->houseNumber, $m)) {
            return;
        }

        $lower = strtolower($m['suffix']);
        $upper = strtoupper($m['suffix']);

        foreach ([$m['number'].$lower, $m['number'].' '.$lower, $m['number'].$upper, $m['number'].' '.$upper] as $houseNumber) {
            if ($houseNumber !== $address->houseNumber) {
                yield $address->withHouseNumber($houseNumber);
            }
        }
    }
}
