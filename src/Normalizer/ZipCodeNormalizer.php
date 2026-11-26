<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Normalizer\Country\Alpha2Normalizer;
use Binable\AddressKit\Normalizer\Country\VehicleRegistrationCodeNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ZipCodeValidator\Constraints\ZipCode as BarbieswimcrewZipCode;

/**
 * Upper-cases the ZIP code and strips a leading country prefix ("D-12345", "DE-12345").
 *
 * With a country, the prefix is the country code or its first letter ("L-1111" for LU).
 *
 * Without a country, the prefix is resolved as ISO alpha-2 or vehicle registration code ("LV-1050" → LV,
 * "L-1111" → LU, "D-10115" → DE) and taken as the country - but only if the rest is a valid ZIP code of
 * that country. Otherwise the ZIP code is kept as it is.
 */
#[AsTaggedItem(priority: 60)]
final class ZipCodeNormalizer implements ComponentNormalizerInterface
{
    private ?ValidatorInterface $validator = null;

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

        return $address->withZipCode($zip);
    }

    private function inferCountry(Address $address): Address
    {
        if (1 !== preg_match('/^([A-Z]{1,3})\s?-\s?(\d.*)$/', (string) $address->zipCode, $m)) {
            return $address;
        }

        $country = (new Alpha2Normalizer())->toAlpha2($m[1]) ?? (new VehicleRegistrationCodeNormalizer())->toAlpha2($m[1]);
        if (null === $country || !$this->isValidZipCode($m[2], $country)) {
            return $address;
        }

        return $address->withZipCode($m[2])->withCountry($country);
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
