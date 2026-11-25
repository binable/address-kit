<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Country;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Intl\Countries;

/**
 * Converts an international vehicle registration code ("Autokennzeichen", distinguishing sign of the
 * Geneva / Vienna road traffic conventions) to ISO 3166-1 alpha-2: "D" → "DE", "A" → "AT", "FL" → "LI",
 * "I" → "IT", "GBZ" → "GI", "U.S.A." → "US". Dots and spaces are ignored.
 *
 * Some signs are also (different) ISO codes, there the ISO meaning wins: valid alpha-2 codes are skipped
 * and left to the {@see Alpha2Normalizer} ("ES" is Spain, not El Salvador; "RU" is Russia, not Burundi),
 * alpha-3 codes are resolved by the {@see Alpha3Normalizer} first ("ROU" is Romania, not Uruguay).
 */
#[AsTaggedItem(priority: 200)]
final class VehicleRegistrationCodeNormalizer implements CountryCodeNormalizerInterface
{
    /**
     * Distinguishing sign => ISO 3166-1 alpha-2.
     */
    public const CODES = [
        'A' => 'AT', 'AFG' => 'AF', 'AL' => 'AL', 'AM' => 'AM', 'AND' => 'AD', 'AUS' => 'AU', 'AZ' => 'AZ',
        'B' => 'BE', 'BD' => 'BD', 'BDS' => 'BB', 'BF' => 'BF', 'BG' => 'BG', 'BH' => 'BZ', 'BIH' => 'BA',
        'BOL' => 'BO', 'BR' => 'BR', 'BRN' => 'BH', 'BRU' => 'BN', 'BS' => 'BS', 'BW' => 'BW', 'BY' => 'BY',
        'C' => 'CU', 'CAM' => 'CM', 'CDN' => 'CA', 'CH' => 'CH', 'CI' => 'CI', 'CL' => 'LK', 'CO' => 'CO',
        'CR' => 'CR', 'CY' => 'CY', 'CZ' => 'CZ',
        'D' => 'DE', 'DK' => 'DK', 'DOM' => 'DO', 'DY' => 'BJ', 'DZ' => 'DZ',
        'E' => 'ES', 'EAK' => 'KE', 'EAT' => 'TZ', 'EAU' => 'UG', 'EC' => 'EC', 'ER' => 'ER', 'ES' => 'SV',
        'EST' => 'EE', 'ET' => 'EG', 'ETH' => 'ET',
        'F' => 'FR', 'FIN' => 'FI', 'FJI' => 'FJ', 'FL' => 'LI', 'FO' => 'FO',
        'G' => 'GA', 'GB' => 'GB', 'GBA' => 'GG', 'GBG' => 'GG', 'GBJ' => 'JE', 'GBM' => 'IM', 'GBZ' => 'GI',
        'GCA' => 'GT', 'GE' => 'GE', 'GH' => 'GH', 'GR' => 'GR', 'GUY' => 'GY',
        'H' => 'HU', 'HK' => 'HK', 'HN' => 'HN', 'HR' => 'HR',
        'I' => 'IT', 'IL' => 'IL', 'IND' => 'IN', 'IR' => 'IR', 'IRL' => 'IE', 'IRQ' => 'IQ', 'IS' => 'IS',
        'J' => 'JP', 'JA' => 'JM', 'JOR' => 'JO',
        'K' => 'KH', 'KS' => 'KG', 'KSA' => 'SA', 'KWT' => 'KW', 'KZ' => 'KZ',
        'L' => 'LU', 'LAO' => 'LA', 'LAR' => 'LY', 'LB' => 'LR', 'LS' => 'LS', 'LT' => 'LT', 'LV' => 'LV',
        'M' => 'MT', 'MA' => 'MA', 'MAL' => 'MY', 'MC' => 'MC', 'MD' => 'MD', 'MEX' => 'MX', 'MGL' => 'MN',
        'MK' => 'MK', 'MNE' => 'ME', 'MOC' => 'MZ', 'MS' => 'MU', 'MW' => 'MW', 'MYA' => 'MM',
        'N' => 'NO', 'NAM' => 'NA', 'NAU' => 'NR', 'NEP' => 'NP', 'NIC' => 'NI', 'NL' => 'NL', 'NMK' => 'MK',
        'NZ' => 'NZ',
        'OM' => 'OM',
        'P' => 'PT', 'PA' => 'PA', 'PE' => 'PE', 'PK' => 'PK', 'PL' => 'PL', 'PNG' => 'PG', 'PY' => 'PY',
        'Q' => 'QA',
        'RA' => 'AR', 'RB' => 'BW', 'RC' => 'TW', 'RCA' => 'CF', 'RCB' => 'CG', 'RCH' => 'CL', 'RDC' => 'CD',
        'RG' => 'GN', 'RH' => 'HT', 'RI' => 'ID', 'RIM' => 'MR', 'RL' => 'LB', 'RM' => 'MG',
        'RMM' => 'ML', 'RN' => 'NE', 'RO' => 'RO', 'ROK' => 'KR', 'ROU' => 'UY', 'RP' => 'PH', 'RSM' => 'SM',
        'RU' => 'BI', 'RUS' => 'RU', 'RWA' => 'RW',
        'S' => 'SE', 'SD' => 'SZ', 'SGP' => 'SG', 'SK' => 'SK', 'SLO' => 'SI', 'SME' => 'SR', 'SN' => 'SN',
        'SO' => 'SO', 'SRB' => 'RS', 'STP' => 'ST', 'SUD' => 'SD', 'SY' => 'SC', 'SYR' => 'SY',
        'T' => 'TH', 'TCH' => 'TD', 'TG' => 'TG', 'TJ' => 'TJ', 'TM' => 'TM', 'TN' => 'TN', 'TO' => 'TO',
        'TR' => 'TR', 'TT' => 'TT',
        'UA' => 'UA', 'UAE' => 'AE', 'USA' => 'US', 'UZ' => 'UZ',
        'V' => 'VA', 'VN' => 'VN',
        'WAG' => 'GM', 'WAL' => 'SL', 'WAN' => 'NG', 'WD' => 'DM', 'WG' => 'GD', 'WL' => 'LC', 'WS' => 'WS',
        'WV' => 'VC',
        'YAR' => 'YE', 'YV' => 'VE',
        'Z' => 'ZM', 'ZA' => 'ZA', 'ZW' => 'ZW',
    ];

    /**
     * @param array<string, string> $codes additional / overriding signs (upper-case sign => alpha-2)
     */
    public function __construct(
        private readonly array $codes = [],
    ) {
    }

    public function toAlpha2(string $country): ?string
    {
        $sign = strtoupper(str_replace(['.', ' '], '', $country));

        if (1 !== preg_match('/^[A-Z]{1,3}$/', $sign) || (2 === \strlen($sign) && Countries::exists($sign))) {
            return null;
        }

        return $this->codes[$sign] ?? self::CODES[$sign] ?? null;
    }
}
