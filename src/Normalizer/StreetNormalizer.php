<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Cleans whitespace, splits a trailing house number off the street ("Hauptstr. 12a")
 * and expands common German abbreviations ("Hauptstr." → "Hauptstraße").
 */
#[AsTaggedItem(priority: 80)]
final class StreetNormalizer implements ComponentNormalizerInterface
{
    use WhitespaceTrait;

    /**
     * @var array<string, string> regex => replacement
     */
    private const ABBREVIATIONS = [
        '/(?<=\p{L})str\.?$/iu' => 'straße',
        '/^(Str)\.?(?=\s)/u' => 'Straße',
        '/\bStr\.?$/u' => 'Straße',
        '/\bPl\.$/u' => 'Platz',
    ];

    private const TRAILING_HOUSE_NUMBER = '/^(?<street>.*\p{L}.*?)\s+(?<number>\d{1,5}\s?[a-zA-Z]?(?:\s?[-\/]\s?\d{0,5}\s?[a-zA-Z]?)?)$/u';

    public function normalize(Address $address): Address
    {
        $street = $this->cleanWhitespace($address->street);
        if (null === $street) {
            return $address->withStreet(null);
        }

        $houseNumber = $address->houseNumber;
        if (null === $this->cleanWhitespace($houseNumber) && 1 === preg_match(self::TRAILING_HOUSE_NUMBER, $street, $m)) {
            $street = $m['street'];
            $houseNumber = $m['number'];
        }

        foreach (self::ABBREVIATIONS as $pattern => $replacement) {
            $street = (string) preg_replace($pattern, $replacement, $street);
        }

        return $address->withStreet($street)->withHouseNumber($houseNumber);
    }
}
