<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Country;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Intl\Countries;

use function Symfony\Component\String\u;

/**
 * Converts a country name to ISO 3166-1 alpha-2: "Deutschland" → "DE", "Germany" → "DE",
 * "osterreich" → "AT", "Holland" → "NL". Case, diacritics and punctuation are ignored.
 *
 * Names come from symfony/intl for the configured locales, plus a list of common colloquial names.
 */
#[AsTaggedItem(priority: 100)]
final class CountryNameNormalizer implements CountryCodeNormalizerInterface
{
    /**
     * Colloquial names not provided by symfony/intl => alpha-2.
     */
    public const ALIASES = [
        'Holland' => 'NL',
        'England' => 'GB', 'Scotland' => 'GB', 'Schottland' => 'GB', 'Wales' => 'GB',
        'Great Britain' => 'GB', 'Großbritannien' => 'GB', 'Britain' => 'GB', 'UK' => 'GB',
        'United States of America' => 'US', 'Vereinigte Staaten von Amerika' => 'US', 'Amerika' => 'US', 'America' => 'US',
        'Russian Federation' => 'RU', 'Russische Föderation' => 'RU',
        'Czech Republic' => 'CZ', 'Tschechische Republik' => 'CZ',
        'Republic of Korea' => 'KR', 'South Korea' => 'KR', 'Südkorea' => 'KR',
        'North Korea' => 'KP', 'Nordkorea' => 'KP',
        'Ivory Coast' => 'CI', 'Elfenbeinküste' => 'CI',
        'Macedonia' => 'MK', 'Mazedonien' => 'MK',
        'Swaziland' => 'SZ', 'Burma' => 'MM', 'Birma' => 'MM',
        'Vatican' => 'VA', 'Vatikan' => 'VA', 'Vatikanstadt' => 'VA',
        'Turkey' => 'TR', 'Türkei' => 'TR',
    ];

    /**
     * @var array<string, string>|null folded name => alpha-2
     */
    private ?array $nameMap = null;

    /**
     * @param list<string>          $locales locales used to resolve country names
     * @param array<string, string> $aliases additional names => alpha-2
     */
    public function __construct(
        private readonly array $locales = ['de', 'en'],
        private readonly array $aliases = [],
    ) {
    }

    public function toAlpha2(string $country): ?string
    {
        $key = self::fold($country);

        return '' === $key ? null : $this->nameMap()[$key] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function nameMap(): array
    {
        if (null === $this->nameMap) {
            $map = [];
            foreach ($this->aliases as $name => $code) {
                $map[self::fold($name)] = strtoupper($code);
            }
            foreach ($this->locales as $locale) {
                foreach (Countries::getNames($locale) as $code => $name) {
                    $map[self::fold($name)] ??= $code;
                }
            }
            foreach (self::ALIASES as $name => $code) {
                $map[self::fold($name)] ??= $code;
            }
            $this->nameMap = $map;
        }

        return $this->nameMap;
    }

    /**
     * Lower-cased ASCII with punctuation removed and spaces collapsed: "Côte d’Ivoire" → "cote divoire".
     */
    private static function fold(string $name): string
    {
        $folded = u($name)->ascii()->lower()->toString();
        $folded = (string) preg_replace('/[^a-z0-9 ]+/', '', str_replace(['-', '&'], [' ', ' and '], $folded));

        return trim((string) preg_replace('/ +/', ' ', $folded));
    }
}
