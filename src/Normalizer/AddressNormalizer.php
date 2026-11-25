<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;

/**
 * Runs all registered component normalizers on an address.
 */
final readonly class AddressNormalizer
{
    /**
     * @param iterable<ComponentNormalizerInterface> $normalizers
     */
    public function __construct(
        private iterable $normalizers,
    ) {
    }

    public function normalize(Address $address): Address
    {
        foreach ($this->normalizers as $normalizer) {
            $address = $normalizer->normalize($address);
        }

        return $address;
    }

    /**
     * Instance with all built-in normalizers, for usage without the Symfony container.
     */
    public static function createDefault(string $defaultCountry = 'DE'): self
    {
        return new self([
            EncodingNormalizer::createDefault(),
            new CountryNormalizer($defaultCountry),
            new StreetNormalizer(),
            new HouseNumberNormalizer(),
            new ZipCodeNormalizer(),
            new CityNormalizer(),
        ]);
    }
}
