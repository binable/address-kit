<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\ZipCode;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Restores the leading zero of numeric ZIP codes that lost it, typically by a spreadsheet import:
 * "800" → "0800" for AU (Darwin) and ZA (Pretoria region).
 *
 * Only a single missing digit is added, values of other lengths are kept as they are.
 */
#[AsTaggedItem(priority: 100)]
final class LeadingZeroZipCodeNormalizer implements CountryZipCodeNormalizerInterface
{
    /**
     * ISO 3166-1 alpha-2 => length of the ZIP code.
     */
    public const LENGTHS = [
        'AU' => 4,
        'ZA' => 4,
    ];

    public function normalize(string $zipCode, string $country): string
    {
        $length = self::LENGTHS[$country] ?? null;
        if (null === $length || 1 !== preg_match('/^\d{'.($length - 1).'}$/', $zipCode)) {
            return $zipCode;
        }

        return '0'.$zipCode;
    }
}
