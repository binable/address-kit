<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * "12 A" → "12a", "12 - 14" → "12-14", "12 / 1" → "12/1".
 */
#[AsTaggedItem(priority: 70)]
final class HouseNumberNormalizer implements ComponentNormalizerInterface
{
    private readonly WhitespaceNormalizer $whitespace;

    public function __construct(?WhitespaceNormalizer $whitespace = null)
    {
        $this->whitespace = $whitespace ?? WhitespaceNormalizer::createDefault();
    }

    public function normalize(Address $address): Address
    {
        $number = $this->whitespace->normalize($address->houseNumber, AddressField::HouseNumber);
        if (null === $number) {
            return $address->withHouseNumber(null);
        }

        $number = (string) preg_replace('/\s*([-\/])\s*/', '$1', $number);
        $number = (string) preg_replace('/(\d)\s+([a-zA-Z])\b/', '$1$2', $number);

        return $address->withHouseNumber(mb_strtolower($number));
    }
}
