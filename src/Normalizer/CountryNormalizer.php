<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Normalizer\Country\Alpha2Normalizer;
use Binable\AddressKit\Normalizer\Country\Alpha3Normalizer;
use Binable\AddressKit\Normalizer\Country\CountryCodeNormalizerInterface;
use Binable\AddressKit\Normalizer\Country\CountryNameNormalizer;
use Binable\AddressKit\Normalizer\Country\VehicleRegistrationCodeNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Converts the country to an upper-case ISO 3166-1 alpha-2 code.
 *
 The country steps tagged "address_kit.country_normalizer" run in priority order, the first match wins.
 * Built-in steps:
 *
 *  300 {@see Alpha3Normalizer}                  "DEU" → "DE"
 *  200 {@see VehicleRegistrationCodeNormalizer} "D" → "DE", "A" → "AT", "FL" → "LI"
 *  100 {@see CountryNameNormalizer}             "Deutschland", "Germany" → "DE"
 * -100 {@see Alpha2Normalizer}                  "de" → "DE"
 *
 * Unknown values are kept, an empty country becomes the default country.
 * Runs first, as other normalizers may depend on the country.
 */
#[AsTaggedItem(priority: 100)]
final readonly class CountryNormalizer implements ComponentNormalizerInterface
{
    /**
     * @var iterable<CountryCodeNormalizerInterface>
     */
    private iterable $normalizers;

    /**
     * @param iterable<CountryCodeNormalizerInterface>|null $normalizers null for the built-in steps
     */
    public function __construct(
        private ?string $defaultCountry = null,
        ?iterable $normalizers = null,
    ) {
        $this->normalizers = $normalizers ?? self::defaultNormalizers();
    }

    public function normalize(Address $address): Address
    {
        $country = $address->country;

        if (null === $country) {
            return $address->withCountry($this->defaultCountry);
        }

        return $address->withCountry($this->toAlpha2($country) ?? $country);
    }

    /**
     * Resolves a single value, null if it is not recognized.
     */
    public function toAlpha2(string $country): ?string
    {
        foreach ($this->normalizers as $normalizer) {
            $code = $normalizer->toAlpha2($country);
            if (null !== $code) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @return list<CountryCodeNormalizerInterface>
     */
    public static function defaultNormalizers(): array
    {
        return [
            new Alpha3Normalizer(),
            new VehicleRegistrationCodeNormalizer(),
            new CountryNameNormalizer(),
            new Alpha2Normalizer(),
        ];
    }
}
