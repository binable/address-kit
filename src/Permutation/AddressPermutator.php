<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\Strategy\HouseNumberPermutation;
use Binable\AddressKit\Permutation\Strategy\StreetSuffixPermutation;
use Binable\AddressKit\Permutation\Strategy\UmlautPermutation;

/**
 * Combines all strategies: every strategy is applied to every variant produced so far,
 * so variants of different strategies are combined. The original address is always first.
 */
final readonly class AddressPermutator
{
    /**
     * @param iterable<PermutationStrategyInterface> $strategies
     */
    public function __construct(
        private iterable $strategies,
        private int $maxPermutations = 50,
    ) {
    }

    /**
     * @return list<Address>
     */
    public function permute(Address $address): array
    {
        $variants = [self::key($address) => $address];

        foreach ($this->strategies as $strategy) {
            foreach ($variants as $variant) {
                foreach ($strategy->permute($variant) as $permutation) {
                    $variants[self::key($permutation)] ??= $permutation;

                    if (\count($variants) >= $this->maxPermutations) {
                        return array_values($variants);
                    }
                }
            }
        }

        return array_values($variants);
    }

    /**
     * Instance with all built-in strategies, for usage without the Symfony container.
     */
    public static function createDefault(int $maxPermutations = 50): self
    {
        return new self([
            new StreetSuffixPermutation(),
            new HouseNumberPermutation(),
            new UmlautPermutation(),
        ], $maxPermutations);
    }

    private static function key(Address $address): string
    {
        return implode("\0", [$address->street, $address->houseNumber, $address->zipCode, $address->city, $address->country]);
    }
}
