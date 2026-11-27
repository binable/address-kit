<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Normalizer\Country\Alpha2Normalizer;
use Binable\AddressKit\Normalizer\Country\VehicleRegistrationCodeNormalizer;
use Binable\AddressKit\Normalizer\ZipCode\CedexZipCodeNormalizer;
use Binable\AddressKit\Normalizer\ZipCode\CountryZipCodeNormalizerInterface;
use Binable\AddressKit\Normalizer\ZipCode\LeadingZeroZipCodeNormalizer;
use Binable\AddressKit\Normalizer\ZipCode\PrefixedNumericZipCodeNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ZipCodeValidator\Constraints\ZipCode as BarbieswimcrewZipCode;

/**
 * Upper-cases the ZIP code and strips a leading country prefix ("D-12345", "DE-12345").
 *
 * With a country, the prefix is the country code or its first letter ("L-1111" for LU). Afterwards the
 * country specific steps tagged "address_kit.zip_code_normalizer" run in priority order. Built-in steps:
 *
 *  300 {@see CedexZipCodeNormalizer}           FR: "75008 CEDEX 08" → "75008"
 *  200 {@see PrefixedNumericZipCodeNormalizer} LU, LV, LT, MC, SM: "LV 1050" → "1050"
 *  100 {@see LeadingZeroZipCodeNormalizer}     AU, ZA: "800" → "0800"
 *
 * Without a country, the prefix is resolved as ISO alpha-2 or vehicle registration code ("LV-1050" → LV,
 * "L-1111" → LU, "D-10115" → DE) and taken as the country - but only if the rest is a valid ZIP code of
 * that country. Otherwise the ZIP code is kept as it is.
 */
#[AsTaggedItem(priority: 60)]
final class ZipCodeNormalizer implements ComponentNormalizerInterface
{
    /**
     * @var iterable<CountryZipCodeNormalizerInterface>
     */
    private iterable $normalizers;

    private ?ValidatorInterface $validator = null;

    /**
     * @param iterable<CountryZipCodeNormalizerInterface>|null $normalizers null for the built-in steps
     */
    public function __construct(?iterable $normalizers = null)
    {
        $this->normalizers = $normalizers ?? self::defaultNormalizers();
    }

    public function normalize(Address $address): Address
    {
        $zip = $address->zipCode;
        if (null === $zip) {
            return $address->withZipCode(null);
        }

        $zip = mb_strtoupper($zip);

        if (null === $address->country) {
            return $this->inferCountry($address->withZipCode($zip));
        }

        if (1 === preg_match('/^(?:'.preg_quote($address->country, '/').'|'.preg_quote($address->country[0], '/').')\s?-\s?(\d.*)$/', $zip, $m)) {
            $zip = $m[1];
        }

        return $address->withZipCode($this->normalizeForCountry($zip, $address->country));
    }

    /**
     * @return list<CountryZipCodeNormalizerInterface>
     */
    public static function defaultNormalizers(): array
    {
        return [
            new CedexZipCodeNormalizer(),
            new PrefixedNumericZipCodeNormalizer(),
            new LeadingZeroZipCodeNormalizer(),
        ];
    }

    private function normalizeForCountry(string $zip, string $country): string
    {
        foreach ($this->normalizers as $normalizer) {
            $zip = $normalizer->normalize($zip, $country);
        }

        return $zip;
    }

    private function inferCountry(Address $address): Address
    {
        if (1 !== preg_match('/^([A-Z]{1,3})\s?-\s?(\d.*)$/', (string) $address->zipCode, $m)) {
            return $address;
        }

        $country = (new Alpha2Normalizer())->toAlpha2($m[1]) ?? (new VehicleRegistrationCodeNormalizer())->toAlpha2($m[1]);
        if (null === $country) {
            return $address;
        }

        $zip = $this->normalizeForCountry($m[2], $country);
        if (!$this->isValidZipCode($zip, $country)) {
            return $address;
        }

        return $address->withZipCode($zip)->withCountry($country);
    }

    private function isValidZipCode(string $zip, string $country): bool
    {
        $this->validator ??= Validation::createValidator();

        try {
            return 0 === \count($this->validator->validate($zip, new BarbieswimcrewZipCode(iso: $country, strict: true)));
        } catch (ConstraintDefinitionException) {
            // no ZIP format known for the country - too uncertain to take the prefix as country
            return false;
        }
    }
}
