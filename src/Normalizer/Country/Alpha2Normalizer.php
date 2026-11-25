<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Country;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Intl\Countries;

/**
 * Upper-cases a valid ISO 3166-1 alpha-2 code: "de" → "DE", "At" → "AT".
 *
 * Runs last: the other steps leave valid alpha-2 codes untouched, so the ISO meaning wins
 * (e.g. "ES" is Spain, not the vehicle registration code of El Salvador).
 */
#[AsTaggedItem(priority: -100)]
final class Alpha2Normalizer implements CountryCodeNormalizerInterface
{
    public function toAlpha2(string $country): ?string
    {
        $upper = strtoupper($country);

        if (1 !== preg_match('/^[A-Z]{2}$/', $upper) || !Countries::exists($upper)) {
            return null;
        }

        return $upper;
    }
}
