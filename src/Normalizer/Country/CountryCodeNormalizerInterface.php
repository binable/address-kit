<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Country;

use Binable\AddressKit\Normalizer\CountryNormalizer;

/**
 * One resolution step of the {@see CountryNormalizer}: converts a country value of a specific format
 * (ISO alpha-3, vehicle registration code, country name, …) to an ISO 3166-1 alpha-2 code.
 *
 * Implementations are autoconfigured with the "address_kit.country_normalizer" tag and executed in
 * priority order (highest first, see #[AsTaggedItem]). The first step returning a code wins.
 */
interface CountryCodeNormalizerInterface
{
    /**
     * @param string $country whitespace-cleaned, non-empty country value
     *
     * @return string|null upper-case ISO 3166-1 alpha-2 code, null if the value is not recognized by this step
     */
    public function toAlpha2(string $country): ?string;
}
