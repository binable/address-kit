<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\ZipCode;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Cuts the CEDEX suffix (business mail) and joins the French ZIP code: "75008 CEDEX 08" → "75008",
 * "75 008" → "75008", "2A004 CEDEX" → "2A004".
 *
 * Applies to France, Monaco and the overseas territories using the French ZIP code system. Values that are
 * no French ZIP code (with optional CEDEX) are kept as they are.
 */
#[AsTaggedItem(priority: 300)]
final class CedexZipCodeNormalizer implements CountryZipCodeNormalizerInterface
{
    public const COUNTRIES = ['FR', 'MC', 'GP', 'MQ', 'GF', 'RE', 'YT', 'PM', 'BL', 'MF', 'WF', 'PF', 'NC'];

    public function normalize(string $zipCode, string $country): string
    {
        if (!\in_array($country, self::COUNTRIES, true)) {
            return $zipCode;
        }

        if (1 !== preg_match('/^(\d[\dAB])\s?(\d{3})(?:\s*CEDEX(?:\s*\d{1,2})?)?$/', $zipCode, $m)) {
            return $zipCode;
        }

        return $m[1].$m[2];
    }
}
