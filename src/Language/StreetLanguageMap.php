<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language;

/**
 * Which languages the street names of a country are written in, as ISO 3166-1 alpha-2 country code
 * => ISO 639-1 language codes.
 *
 * Used by the {@see \Binable\AddressKit\Permutation\AddressPermutator} to run only the permutations
 * of the languages that matter for an address: a German address only gets the "de" permutations.
 *
 * The defaults are deliberately conservative and only list a language where its street names make
 * up a relevant share of the register:
 * - South Tyrol (IT) and East Belgium (BE) also use German street names and Flanders (BE) uses Dutch
 *   ones, but they are not listed. Add them via {@see __construct()} (bundle option
 *   "address_kit.street_languages") if your data needs them.
 * - English is limited to the US: the permutations follow the USPS abbreviations, and in GB, IE, NZ
 *   and CA "St" doubles as "Saint".
 */
final readonly class StreetLanguageMap
{
    /**
     * @var array<string, list<string>>
     */
    public const DEFAULT = [
        'AT' => ['de'],
        'BE' => ['fr'],
        'CH' => ['de', 'fr', 'it'],
        'DE' => ['de'],
        'DK' => ['da'],
        'FR' => ['fr'],
        'IT' => ['it'],
        'LI' => ['de'],
        'LU' => ['fr'],
        'MC' => ['fr'],
        'NL' => ['nl'],
        'PL' => ['pl'],
        'SM' => ['it'],
        'US' => ['en'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $map;

    /**
     * @param array<string, list<string>> $overrides country => languages; replaces the default entry of
     *                                               that country, an empty list disables it
     */
    public function __construct(array $overrides = [])
    {
        $map = self::DEFAULT;
        foreach ($overrides as $country => $languages) {
            $map[strtoupper($country)] = array_values(array_unique(array_map(strtolower(...), $languages)));
        }

        $this->map = $map;
    }

    /**
     * @return list<string> ISO 639-1 codes; empty for an unknown or missing country
     */
    public function getLanguages(?string $country): array
    {
        if (null === $country) {
            return [];
        }

        return $this->map[strtoupper(trim($country))] ?? [];
    }

    /**
     * @return list<string> ISO 3166-1 alpha-2 codes of all countries using the language for street names
     */
    public function getCountries(string $language): array
    {
        $language = strtolower($language);

        return array_keys(array_filter($this->map, static fn (array $languages): bool => \in_array($language, $languages, true)));
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->map;
    }
}
