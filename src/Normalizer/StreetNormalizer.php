<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Splits a trailing house number off the street ("Hauptstr. 12a"), removes trailing separators
 * ("Hauptstraße," → "Hauptstraße") and expands abbreviations depending on the country:
 *
 *  DE, AT      "Hauptstr." → "Hauptstraße", "Str. des 17. Juni" → "Straße des 17. Juni", "Bahnhof Pl." → "Bahnhof Platz"
 *  CH, LI      "Bahnhofstr." → "Bahnhofstrasse" (no ß), "Bahnhof Pl." → "Bahnhof Platz"
 *  IT, SM      "Str. della Croce" → "Strada della Croce"
 *
 * Other countries (and addresses without country) keep their abbreviations: "Pl." is "Place" in FR / BE / LU
 * and "plac" in PL.
 *
 * The house number is only split off when none is given (null) and the country writes it behind the street
 * name. In other countries street names may end with a number ("State Highway 42", "Avenue du 8 Mai 1945").
 */
#[AsTaggedItem(priority: 80)]
final readonly class StreetNormalizer implements ComponentNormalizerInterface
{
    /**
     * Countries writing the house number behind the street name.
     */
    public const HOUSE_NUMBER_AFTER_STREET = [
        'AT', 'BA', 'BE', 'BG', 'CH', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'GR', 'HR', 'HU', 'IS', 'IT', 'LI',
        'LT', 'LV', 'ME', 'MK', 'NL', 'NO', 'PL', 'PT', 'RO', 'RS', 'SE', 'SI', 'SK', 'SM', 'VA',
    ];

    /**
     * regex => replacement
     */
    private const GERMAN_ABBREVIATIONS = [
        '/(?<=\p{L})str\.?$/iu' => 'straße',
        '/^(Str)\.?(?=\s)/u' => 'Straße',
        '/\bStr\.?$/u' => 'Straße',
        '/\bPl\.$/u' => 'Platz',
    ];

    private const SWISS_ABBREVIATIONS = [
        '/(?<=\p{L})str\.?$/iu' => 'strasse',
        '/^(Str)\.?(?=\s)/u' => 'Strasse',
        '/\bStr\.?$/u' => 'Strasse',
        '/\bPl\.$/u' => 'Platz',
    ];

    private const ITALIAN_ABBREVIATIONS = [
        '/^Str\.\s*(?=\p{L})/u' => 'Strada ',
    ];

    /**
     * Abbreviations expanded per country: country => [regex => replacement].
     */
    public const ABBREVIATIONS = [
        'DE' => self::GERMAN_ABBREVIATIONS,
        'AT' => self::GERMAN_ABBREVIATIONS,
        'CH' => self::SWISS_ABBREVIATIONS,
        'LI' => self::SWISS_ABBREVIATIONS,
        'IT' => self::ITALIAN_ABBREVIATIONS,
        'SM' => self::ITALIAN_ABBREVIATIONS,
    ];

    private const TRAILING_HOUSE_NUMBER = '/^(?<street>.*\p{L}.*?)\s+(?<number>\d{1,5}\s?[a-zA-Z]?(?:\s?[-\/]\s?\d{0,5}\s?[a-zA-Z]?)?)$/u';

    /**
     * @param list<string>                         $houseNumberAfterStreet countries writing the house number behind the street
     * @param array<string, array<string, string>> $abbreviations          country => [regex => replacement]
     */
    public function __construct(
        private array $houseNumberAfterStreet = self::HOUSE_NUMBER_AFTER_STREET,
        private array $abbreviations = self::ABBREVIATIONS,
    ) {
    }

    public function normalize(Address $address): Address
    {
        $street = $address->street;
        if (null === $street) {
            return $address->withStreet(null);
        }

        $country = $address->country;
        $houseNumber = $address->houseNumber;
        if (null === $houseNumber
            && null !== $country
            && \in_array($country, $this->houseNumberAfterStreet, true)
            && 1 === preg_match(self::TRAILING_HOUSE_NUMBER, $street, $m)
        ) {
            $street = $m['street'];
            $houseNumber = $m['number'];
        }

        $street = rtrim($street, ' ,;');

        foreach ($this->abbreviations[$country ?? ''] ?? [] as $pattern => $replacement) {
            $street = (string) preg_replace($pattern, $replacement, $street);
        }

        return $address->withStreet('' === $street ? null : $street)->withHouseNumber($houseNumber);
    }
}
