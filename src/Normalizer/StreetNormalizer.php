<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Cleans whitespace, splits a trailing house number off the street ("Hauptstr. 12a")
 * and expands common German abbreviations ("Hauptstr." → "Hauptstraße").
 */
#[AsTaggedItem(priority: 80)]
final class StreetNormalizer implements ComponentNormalizerInterface
{
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

    private readonly WhitespaceNormalizer $whitespace;

    public function __construct(?WhitespaceNormalizer $whitespace = null)
    {
        $this->whitespace = $whitespace ?? WhitespaceNormalizer::createDefault();
    }

    public function normalize(Address $address): Address
    {
        $street = $this->whitespace->normalize($address->street, AddressField::Street);
        if (null === $street) {
            return $address->withStreet(null);
        }

        $houseNumber = $address->houseNumber;
        if (null === $this->whitespace->normalize($houseNumber, AddressField::HouseNumber) && 1 === preg_match(self::TRAILING_HOUSE_NUMBER, $street, $m)) {
            $street = $m['street'];
            $houseNumber = $m['number'];
        }

        foreach (self::ABBREVIATIONS as $pattern => $replacement) {
            $street = (string) preg_replace($pattern, $replacement, $street);
        }

        return $address->withStreet($street)->withHouseNumber($houseNumber);
    }
}
