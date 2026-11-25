<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Upper-cases the ZIP code, cleans whitespace and strips a leading country prefix ("D-12345", "DE-12345").
 */
#[AsTaggedItem(priority: 60)]
final class ZipCodeNormalizer implements ComponentNormalizerInterface
{
    private readonly WhitespaceNormalizer $whitespace;

    public function __construct(?WhitespaceNormalizer $whitespace = null)
    {
        $this->whitespace = $whitespace ?? WhitespaceNormalizer::createDefault();
    }

    public function normalize(Address $address): Address
    {
        $zip = $this->whitespace->normalize($address->zipCode, AddressField::ZipCode);
        if (null === $zip) {
            return $address->withZipCode(null);
        }

        $zip = mb_strtoupper($zip);

        if (null !== $address->country && 1 === preg_match('/^(?:'.preg_quote($address->country, '/').'|'.preg_quote($address->country[0], '/').')\s?-\s?(\d.*)$/', $zip, $m)) {
            $zip = $m[1];
        }

        return $address->withZipCode($zip);
    }
}
