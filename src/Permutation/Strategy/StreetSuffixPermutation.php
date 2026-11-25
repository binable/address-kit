<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\PermutationStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * "Hauptstraße" ⇄ "Hauptstr." ⇄ "Haupt Straße" ⇄ "Haupt-Straße".
 */
#[AsTaggedItem(priority: 100)]
final class StreetSuffixPermutation implements PermutationStrategyInterface
{
    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        if (1 !== preg_match('/^(?<base>.*?\p{L})[\s-]?(?:straße|strasse|str\.?)$/iu', $address->street, $m)) {
            return;
        }

        $base = $m['base'];
        foreach ([$base.'straße', $base.'str.', $base.' Straße', $base.'-Straße'] as $street) {
            if ($street !== $address->street) {
                yield $address->withStreet($street);
            }
        }
    }
}
