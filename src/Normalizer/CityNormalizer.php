<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Fixes fully upper- or lower-cased city names: "MÜNCHEN" → "München", "FRANKFURT AM MAIN" → "Frankfurt am Main",
 * "SAINT-GERMAIN-EN-LAYE" → "Saint-Germain-en-Laye", "'S-HEERENBERG" → "'s-Heerenberg",
 * "VILLENEUVE-D'ASCQ" → "Villeneuve-d'Ascq". Mixed-case input is kept as it is.
 */
#[AsTaggedItem(priority: 50)]
final class CityNormalizer implements ComponentNormalizerInterface
{
    /**
     * Words kept lower case when title-casing, after a space or hyphen and before a space or hyphen
     * (e.g. "Frankfurt am Main", "Witry-lès-Reims", "Castelnuovo del Garda").
     */
    private const LOWER_CASE_WORDS = [
        // de
        'am', 'an', 'der', 'die', 'das', 'im', 'in', 'ob', 'bei', 'vor', 'auf',
        // fr
        'de', 'la', 'le', 'les', 'lès', 'lez', 'sur', 'sous', 'en', 'du', 'des', 'aux', 'et',
        // it
        'del', 'della', 'delle', 'dei', 'di', 'sul', 'sulla',
    ];

    /**
     * Elided articles / prepositions in front of an apostrophe ("d'Ascq", "L'Aquila", "Sant'Angelo", "O'Fallon"),
     * the word behind the apostrophe is capitalized.
     */
    private const ELISIONS = ['d', 'l', 'o', 'dell', 'sant', 'sull', 'nell', 'all'];

    public function normalize(Address $address): Address
    {
        $city = $address->city;
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

        // "Sant'angelo" → "Sant'Angelo", "Villeneuve-D'ascq" → "Villeneuve-D'Ascq"
        $city = (string) preg_replace_callback(
            '/(?<![\p{L}\'])('.implode('|', self::ELISIONS).")'(\p{L})/iu",
            static fn (array $m): string => $m[1]."'".mb_strtoupper($m[2]),
            $city,
        );

        // "Villeneuve-D'Ascq" → "Villeneuve-d'Ascq", but "L'Aquila" at the start stays
        $city = (string) preg_replace_callback(
            "/(?<=[\\s\\-])(d|l)'/iu",
            static fn (array $m): string => mb_strtolower($m[1])."'",
            $city,
        );

        // Dutch "'S-Heerenberg" → "'s-Heerenberg", "'T Harde" → "'t Harde"
        $city = (string) preg_replace_callback(
            "/(?<![\\p{L}])'([ST])(?=[\\s\\-])/u",
            static fn (array $m): string => "'".mb_strtolower($m[1]),
            $city,
        );

        return (string) preg_replace_callback(
            '/(?<=[\s\-])(\p{L}+)(?=[\s\-])/u',
            static fn (array $m): string => \in_array(mb_strtolower($m[1]), self::LOWER_CASE_WORDS, true) ? mb_strtolower($m[1]) : $m[1],
            $city,
        );
    }
}
