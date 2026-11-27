<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\ZipCode;

use Binable\AddressKit\Normalizer\ZipCodeNormalizer;

/**
 * One country specific step of the {@see ZipCodeNormalizer}: brings the ZIP code of the countries it knows
 * into their canonical form ("LV 1050" → "1050", "800" → "0800" for AU, "75008 CEDEX 08" → "75008").
 *
 * Implementations are autoconfigured with the "address_kit.zip_code_normalizer" tag and executed in
 * priority order (highest first, see #[AsTaggedItem]). Every step receives the result of the previous one.
 */
interface CountryZipCodeNormalizerInterface
{
    /**
     * @param string $zipCode upper-cased ZIP code, a "D-" style prefix of the country is already removed
     * @param string $country upper-case ISO 3166-1 alpha-2 code
     *
     * @return string the normalized ZIP code, unchanged if the country is not handled or the value does not fit
     */
    public function normalize(string $zipCode, string $country): string;
}
