<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Cleans whitespace and fixes fully upper- or lower-cased city names ("MÜNCHEN" → "München").
 */
#[AsTaggedItem(priority: 50)]
final class CityNormalizer implements ComponentNormalizerInterface
{
    /**
     * Words kept lower case when title-casing (e.g. "Frankfurt am Main").
     */
    private const LOWER_CASE_WORDS = ['am', 'an', 'der', 'die', 'das', 'im', 'in', 'ob', 'bei', 'vor', 'auf', 'de', 'la', 'le', 'sur', 'en'];

    private readonly WhitespaceNormalizer $whitespace;

    public function __construct(?WhitespaceNormalizer $whitespace = null)
    {
        $this->whitespace = $whitespace ?? WhitespaceNormalizer::createDefault();
    }

    public function normalize(Address $address): Address
    {
        $city = $this->whitespace->normalize($address->city, AddressField::City);
        if (null === $city) {
            return $address->withCity(null);
        }

        if ($city === mb_strtoupper($city) || $city === mb_strtolower($city)) {
            $city = $this->titleCase($city);
        }

        return $address->withCity($city);
    }

    private function titleCase(string $city): string
    {
        $city = mb_convert_case($city, \MB_CASE_TITLE);

        return (string) preg_replace_callback(
            '/(?<=\s)(\p{L}+)(?=\s)/u',
            static fn (array $m): string => \in_array(mb_strtolower($m[1]), self::LOWER_CASE_WORDS, true) ? mb_strtolower($m[1]) : $m[1],
            $city,
        );
    }
}
