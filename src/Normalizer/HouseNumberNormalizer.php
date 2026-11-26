<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Removes spaces around "-" and "/" in all countries: "12 - 14" → "12-14", "30 / A" → "30/A".
 *
 * Only in countries writing the letter suffix lower case and attached (DE, AT, CH, LI, configurable)
 * the letter is attached and lower-cased: "12 A" → "12a". Other countries keep their spelling
 * ("74G" in GB, "Storgatan 18 A" in SE). House numbers not starting with a digit are never re-cased:
 * house names ("Ivy Cottage") and grid addresses ("N8342").
 */
#[AsTaggedItem(priority: 70)]
final readonly class HouseNumberNormalizer implements ComponentNormalizerInterface
{
    /**
     * Countries writing the letter suffix lower case and without space: "12a".
     */
    public const LOWER_CASE_SUFFIX_COUNTRIES = ['DE', 'AT', 'CH', 'LI'];

    /**
     * @param list<string> $lowerCaseSuffixCountries countries writing "12a" instead of "12A" / "12 A"
     */
    public function __construct(
        private array $lowerCaseSuffixCountries = self::LOWER_CASE_SUFFIX_COUNTRIES,
    ) {
    }

    public function normalize(Address $address): Address
    {
        $number = $address->houseNumber;
        if (null === $number) {
            return $address->withHouseNumber(null);
        }

        $number = (string) preg_replace('/\s*([-\/])\s*/', '$1', $number);

        if (null !== $address->country
            && \in_array($address->country, $this->lowerCaseSuffixCountries, true)
            && 1 === preg_match('/^\d/', $number)
        ) {
            $number = (string) preg_replace('/(\d)\s+([a-zA-Z])\b/', '$1$2', $number);
            $number = mb_strtolower($number);
        }

        return $address->withHouseNumber($number);
    }
}
