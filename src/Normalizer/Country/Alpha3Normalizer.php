<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Country;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Converts an ISO 3166-1 alpha-3 code to alpha-2: "DEU" → "DE", "aut" → "AT", "CHE" → "CH".
 */
#[AsTaggedItem(priority: 300)]
final class Alpha3Normalizer implements CountryCodeNormalizerInterface
{
    public function toAlpha2(string $country): ?string
    {
        $upper = strtoupper($country);

        if (1 !== preg_match('/^[A-Z]{3}$/', $upper) || !Countries::alpha3CodeExists($upper)) {
            return null;
        }

        try {
            return Countries::getAlpha2Code($upper);
        } catch (MissingResourceException) {
            return null;
        }
    }
}
