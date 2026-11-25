# binable/address-kit

Symfony bundle for postal addresses: **validation**, **normalization** and **permutation**.

- ZIP code patterns come from [barbieswimcrew/zip-code-validator](https://github.com/barbieswimcrew/zip-code-validator)
- Country data comes from `symfony/intl`
- Symfony 7.4 LTS and 8.x, PHP ≥ 8.2

## Installation

```bash
composer require binable/address-kit
```

```php
// config/bundles.php
return [
    // ...
    Binable\AddressKit\AddressKitBundle::class => ['all' => true],
];
```

## Configuration

```yaml
# config/packages/address_kit.yaml
address_kit:
    default_country: DE     # used by the CountryNormalizer when an address has no country
    max_permutations: 50    # upper bound of variants returned by the AddressPermutator
```

## Validator

One constraint per address component, in `Binable\AddressKit\Validator\Constraints`:

| Constraint    | Checks                                                                                  | Options                                                     |
|---------------|-----------------------------------------------------------------------------------------|-------------------------------------------------------------|
| `Street`      | Unicode letters, digits, common punctuation, at least one letter, length                | `minLength`, `maxLength`, `pattern`, messages               |
| `HouseNumber` | `12`, `12a`, `12 A`, `12-14`, `12/1`, `7½`, length                                      | `pattern`, `maxLength`, messages                            |
| `ZipCode`     | Country specific pattern (barbieswimcrew), generic check if the country is unknown      | `country` **or** `countryPath`, `strict`, `caseSensitive`   |
| `City`        | Starts with a letter, letters and punctuation, optionally digits, length                | `minLength`, `maxLength`, `allowDigits`, `pattern`, messages |
| `Country`     | ISO 3166-1 alpha-2 (or alpha-3) via `symfony/intl`, optional allow-list                 | `alpha3`, `allowedCountries`, messages                      |

As usual in Symfony, `null` and `''` are valid — combine with `NotBlank` where required.

```php
use Binable\AddressKit\Validator\Constraints as AddressAssert;
use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutAddress
{
    #[Assert\NotBlank, AddressAssert\Street]
    public ?string $street = null;

    #[AddressAssert\HouseNumber]
    public ?string $houseNumber = null;

    #[Assert\NotBlank, AddressAssert\ZipCode(countryPath: 'country')]
    public ?string $zip = null;

    #[Assert\NotBlank, AddressAssert\City]
    public ?string $city = null;

    #[Assert\NotBlank, AddressAssert\Country(allowedCountries: ['DE', 'AT', 'CH'])]
    public ?string $country = null;
}
```

The bundled `Binable\AddressKit\Model\Address` value object is already annotated with these constraints.

## Normalizer

`AddressNormalizer` runs all services tagged `address_kit.normalizer` (autoconfigured for
`ComponentNormalizerInterface`, ordered by `#[AsTaggedItem(priority: …)]`):

| Normalizer             | Example                                                         |
|------------------------|-----------------------------------------------------------------|
| `CountryNormalizer`    | `Deutschland`, `deu`, `de` → `DE`; empty → `default_country`     |
| `StreetNormalizer`     | `Hauptstr. 12 A` → street `Hauptstraße`, house number `12 A`    |
| `HouseNumberNormalizer`| `12 A` → `12a`, `12 - 14` → `12-14`                             |
| `ZipCodeNormalizer`    | `d-10115` → `10115`, `sw1a 1aa` → `SW1A 1AA`                    |
| `CityNormalizer`       | `FRANKFURT AM MAIN` → `Frankfurt am Main`                       |

```php
$normalized = $addressNormalizer->normalize(new Address('Hauptstr. 12 A', null, 'D-10115', 'BERLIN', 'Germany'));
// Address('Hauptstraße', '12a', '10115', 'Berlin', 'DE')
```

## Permutation

`AddressPermutator` combines all services tagged `address_kit.permutation_strategy`
(autoconfigured for `PermutationStrategyInterface`) and returns unique variants of an address,
e.g. for fuzzy matching or search. The original address is always the first entry.

| Strategy                  | Example                                                  |
|---------------------------|----------------------------------------------------------|
| `StreetSuffixPermutation` | `Hauptstraße` ⇄ `Hauptstr.` ⇄ `Haupt Straße` ⇄ `Haupt-Straße` |
| `HouseNumberPermutation`  | `12a` ⇄ `12 a` ⇄ `12A` ⇄ `12 A`                           |
| `UmlautPermutation`       | `Müllerstraße` → `Muellerstrasse`                        |

Both `AddressNormalizer::createDefault()` and `AddressPermutator::createDefault()` work without the container.

## Development

```bash
composer test
composer phpstan
```
