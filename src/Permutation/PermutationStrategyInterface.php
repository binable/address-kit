<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation;

use Binable\AddressKit\Model\Address;

/**
 * Produces alternative spellings of an address (e.g. for fuzzy matching or search).
 * Implementations are autoconfigured with the "address_kit.permutation_strategy" tag.
 */
interface PermutationStrategyInterface
{
    /**
     * Returns variants of the given address. The given address itself does not need to be included.
     *
     * @return iterable<Address>
     */
    public function permute(Address $address): iterable;
}
