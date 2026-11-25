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

All constraints live in `Binable\AddressKit\Validator\Constraints`.

### Main constraints (compositions with strong defaults)

One `Compound` constraint per address component, built from Symfony's own constraints
(`NotBlank`, `Type`, `Length`, `Regex`, `Country`, `Choice`). The rules run sequentially and stop at the
first failure, so the user gets exactly one meaningful message per field. Violations carry the codes of
the Symfony constraints (e.g. `Length::TOO_SHORT_ERROR`).

| Constraint    | Composition                                                                                 | Required by default | Options                                                         |
|---------------|---------------------------------------------------------------------------------------------|---------------------|-----------------------------------------------------------------|
| `Street`      | NotBlank → Type(string) → Length(2–100) → Regex(allowed chars) → Regex(at least one letter) | yes                 | `minLength`, `maxLength`, `pattern`, messages                   |
| `HouseNumber` | NotBlank → Type(string\|int) → Length(≤15) → Regex(`12`, `12a`, `12-14`, `12/1`, `7½`)      | **no**              | `maxLength`, `pattern`, `message`                               |
| `ZipCode`     | NotBlank → Type(string\|int) → Length(2–10) → Regex(generic) → `ZipCodeForCountry`          | yes                 | `country`, `countryPath` (default `country`), `strict`, `caseSensitive`, lengths |
| `City`        | NotBlank → Type(string) → Length(2–100) → Regex(starts with letter, no digits)              | yes                 | `minLength`, `maxLength`, `allowDigits`, `pattern`, `message`   |
| `Country`     | NotBlank → Type(string) → Symfony `Country` (alpha-2/alpha-3) → Choice(allow-list)*         | yes                 | `alpha3`, `allowedCountries`, messages                          |

\* only when the corresponding option is set.

`ZipCode` reads the country for the country specific check from the property `country` of the validated
object by default, so `#[ZipCode]` works without parameters on typical address classes. A fixed
`country: 'DE'` takes precedence; `countryPath: 'address.country'` reads another path;
`countryPath: null` disables the country check. If the path is not readable, or the value is validated
without an object, only the generic check applies.

Every main constraint has a `required` option. With `required: false`, blank values (`null`, `''`,
whitespace) are skipped and the remaining rules only apply to filled-in values.

### Special-case constraints

Opt-in constraints for specific requirements, to combine with the main constraints:

| Constraint                 | Purpose                                                                                     | Options                                     |
|----------------------------|---------------------------------------------------------------------------------------------|---------------------------------------------|
| `ZipCodeForCountry`        | Country specific ZIP format via barbieswimcrew/zip-code-validator (used by `ZipCode`)       | `country`, `countryPath` (default `country`), `strict`, `caseSensitive` |
| `StreetWithoutHouseNumber` | Street must not contain a house number (`Hauptstraße 12`), for a separate house number field | `pattern`, `message`                        |
| `NoPostBox`                | Rejects `Postfach`, `PO Box`, … and parcel lockers (`Packstation`, `Postfiliale`, …)         | `allowParcelLockers`, messages              |
| `NoPlaceholderValue`       | Rejects dummy input (`test`, `asdf`, `n/a`, `-`, `xxx`, `0000`, …)                           | `placeholders`, `extraPlaceholders`, `rejectRepeatedCharacters` |

`OptionalSequentially` is the building block behind `required: false`: like Symfony's `Sequentially`,
but it skips blank values.

```php
use Binable\AddressKit\Validator\Constraints as AddressAssert;

final class CheckoutAddress
{
    #[AddressAssert\Street]
    #[AddressAssert\StreetWithoutHouseNumber]
    #[AddressAssert\NoPostBox]
    #[AddressAssert\NoPlaceholderValue]
    public ?string $street = null;

    #[AddressAssert\HouseNumber(required: true)]
    public ?string $houseNumber = null;

    #[AddressAssert\ZipCode]
    public ?string $zip = null;

    #[AddressAssert\City]
    #[AddressAssert\NoPlaceholderValue]
    public ?string $city = null;

    #[AddressAssert\Country(allowedCountries: ['DE', 'AT', 'CH'])]
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
