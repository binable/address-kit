<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\ZipCode;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Numeric ZIP codes that are often written with a country prefix: removes the prefix (with hyphen, space
 * or nothing as separator) and all spaces and hyphens - "LV 1050", "LV1050" → "1050", "LT-01 100" → "01100",
 * "RSM 47890" → "47890".
 *
 * The result is only taken if it fits the country's format, otherwise the value is kept as it is.
 */
#[AsTaggedItem(priority: 200)]
final class PrefixedNumericZipCodeNormalizer implements CountryZipCodeNormalizerInterface
{
    /**
     * ISO 3166-1 alpha-2 => accepted prefixes (longest first) and ZIP code pattern.
     */
    public const FORMATS = [
        'LU' => ['prefixes' => ['LU', 'L'], 'pattern' => '\d{4}'],
        'LV' => ['prefixes' => ['LV'], 'pattern' => '\d{4}'],
        'LT' => ['prefixes' => ['LT'], 'pattern' => '\d{5}'],
        'MC' => ['prefixes' => ['MC'], 'pattern' => '980\d{2}'],
        'SM' => ['prefixes' => ['RSM', 'SM'], 'pattern' => '4789\d'],
    ];

    public function normalize(string $zipCode, string $country): string
    {
        $format = self::FORMATS[$country] ?? null;
        if (null === $format) {
            return $zipCode;
        }

        $prefixes = implode('|', array_map(static fn (string $p): string => preg_quote($p, '/'), $format['prefixes']));
        $candidate = preg_replace('/^(?:'.$prefixes.')[\s-]*(?=\d)/', '', $zipCode) ?? $zipCode;
        $candidate = preg_replace('/[\s-]+/', '', $candidate) ?? $candidate;

        return 1 === preg_match('/^'.$format['pattern'].'$/', $candidate) ? $candidate : $zipCode;
    }
}
