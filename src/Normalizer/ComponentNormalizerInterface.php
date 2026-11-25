<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;

/**
 * Normalizes one aspect of an address. Implementations are autoconfigured with the
 * "address_kit.normalizer" tag and executed by {@see AddressNormalizer} in priority order.
 */
interface ComponentNormalizerInterface
{
    public function normalize(Address $address): Address;
}
