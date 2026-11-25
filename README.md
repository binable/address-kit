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
    street_languages:       # override the street name languages of a country (see Permutation → Languages)
        BE: [fr, nl, de]
```

`default_country` is also the fallback country of the `KnownStreet` constraint.

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
| `HouseNumberInRange`       | House number within a range expression: `5`, `1-50`, `4+`, `3-`, `1-10, 20-30`               | `range`, `message`                          |
| `KnownStreet`              | Street must be known – fuzzy: permutations count, a unique one-letter typo gets a suggestion | `streets`, `allowTypos`, `country`, `zipCodePath`, `cityPath`, `countryPath`, messages |

`OptionalSequentially` is the building block behind `required: false`: like Symfony's `Sequentially`,
but it skips blank values.

#### HouseNumberInRange

Only the first number of the house number counts: `12a`, `12/3` and `12-14` are compared as `12`; a house
number without digits is never in range. An invalid range throws a `ConstraintDefinitionException`.
The check itself is available as service `Matching\HouseNumberRange` (`contains('12a', '1-50, 60+')`).

#### KnownStreet

The street is valid when it, or one of its [permutations](#permutation) (`Hauptstr.`, `Muellerweg`, …),
matches a known street case-insensitively. Otherwise, if exactly one known street is a single letter
away (`Paul-Gerhard-Allee` → `Paul-Gerhardt-Allee`, only for names of 8+ characters, see
`Matching\StreetTypo`), the violation `TYPO_ERROR` carries it as `{{ suggestion }}`; with
`allowTypos: true` it is accepted. Anything else is an `UNKNOWN_STREET_ERROR`.

Known streets come from the `streets` option or from your implementation of
`Register\StreetRegisterInterface`, which gets an `Address` built from the validated object
(`zipCodePath`, `cityPath`, `countryPath`) and returns the streets of that area – an empty result skips
the check. The country (fixed `country` option › object › `default_country`) selects the language
specific permutations.

```yaml
services:
    Binable\AddressKit\Register\StreetRegisterInterface: '@App\Address\StreetRegister'
```

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
| `EncodingNormalizer`   | Encoding cleanup of all fields, see below (runs first)          |
| `CountryNormalizer`    | `Deutschland`, `deu`, `de` → `DE`; empty → `default_country`     |
| `StreetNormalizer`     | `Hauptstr. 12 A` → street `Hauptstraße`, house number `12 A`    |
| `HouseNumberNormalizer`| `12 A` → `12a`, `12 - 14` → `12-14`                             |
| `ZipCodeNormalizer`    | `d-10115` → `10115`, `sw1a 1aa` → `SW1A 1AA`                    |
| `CityNormalizer`       | `FRANKFURT AM MAIN` → `Frankfurt am Main`                       |

```php
$normalized = $addressNormalizer->normalize(new Address('Hauptstr. 12 A', null, 'D-10115', 'BERLIN', 'Germany'));
// Address('Hauptstraße', '12a', '10115', 'Berlin', 'DE')
```

### Encoding

`EncodingNormalizer` cleans the encoding of every address field. It runs all string normalizers tagged
`address_kit.encoding_normalizer` (autoconfigured for `Encoding\EncodingNormalizerInterface`, ordered by
priority). Each step gets the value and the `AddressField` it belongs to. Values that are empty after
cleaning become `null`.

| Priority | Step                           | Example                                                                                        |
|----------|--------------------------------|------------------------------------------------------------------------------------------------|
| 900      | `Utf8Normalizer`               | invalid UTF-8 (Windows-1252 / ISO-8859-1 input) → UTF-8                                         |
| 800      | `MojibakeNormalizer`           | `Ã¼` → `ü`, `Ã©` → `é`, `â€™` → `’` (also double encoded and mixed strings)                     |
| 700      | `UnicodeNfcNormalizer`         | `u` + U+0308 → `ü` (NFC)                                                                       |
| 600      | `InvisibleCharacterNormalizer` | removes U+200B–U+200F (ZWSP, ZWNJ, ZWJ, direction marks), U+FEFF (BOM), U+00AD (soft hyphen), bidi controls |
| 500      | `ControlCharacterNormalizer`   | line breaks, tabs and control characters → space (fields are single-line)                     |
| 400      | `FullwidthNormalizer`          | `１２３` → `123`; full NFKC only for the ZIP code (configurable), otherwise digits only         |
| 300      | `SpaceNormalizer`              | U+00A0, U+202F, U+2009, U+3000, tabs … → space; collapse and trim                              |
| 200      | `DashNormalizer`               | `–` `—` `−` `‐` → `-`                                                                          |
| 100      | `ApostropheNormalizer`         | `’` `‘` `´` `` ` `` → `'` (`O'Connell Street`, `Rue d'Alésia`)                                 |

```php
$encodingNormalizer->normalizeString("Oâ€™Connell\u{00A0}Street\u{200B}"); // "O'Connell Street"
$encodingNormalizer->normalizeString('１００－０００１', AddressField::ZipCode); // "100-0001"
```

Add your own step by implementing `Encoding\EncodingNormalizerInterface` and setting the position with
`#[AsTaggedItem(priority: …)]`.

### Whitespace and punctuation

`WhitespaceNormalizer` cleans spaces and punctuation of a single value. It is used by the component
normalizers (country, street, house number, ZIP code, city) for their field and runs all string normalizers
tagged `address_kit.whitespace_normalizer` (autoconfigured for `Whitespace\WhitespaceNormalizerInterface`,
ordered by priority). Values that are empty after cleaning become `null`.

| Priority | Step                           | Example                                                                     |
|----------|--------------------------------|-----------------------------------------------------------------------------|
| 500      | `CollapseWhitespaceNormalizer` | `  Am   Markt ` → `Am Markt`                                                 |
| 400      | `PunctuationSpacingNormalizer` | no space before `,` `.` `;` `)`, one after `,` `;`: `Gütersloh ,Kreis` → `Gütersloh, Kreis` |
| 300      | `BracketSpacingNormalizer`     | `( 2. OG )` → `(2. OG)`                                                     |
| 200      | `HyphenSpacingNormalizer`      | `Max - Planck - Str.` → `Max-Planck-Str.`, `12 - 14` → `12-14`              |
| 100      | `EdgePunctuationNormalizer`    | `, Hauptstraße` → `Hauptstraße`, `Gütersloh,` → `Gütersloh`; keeps `Str.`, `a.M.` |

```php
$whitespaceNormalizer->normalize(' ,Max - Planck - Str . 1 ( 2. OG ) ,'); // "Max-Planck-Str. 1 (2. OG)"
```

Add your own step by implementing `Whitespace\WhitespaceNormalizerInterface`. Both step interfaces extend
`Normalizer\StringNormalizerInterface`.

## Permutation

`AddressPermutator` combines all services tagged `address_kit.permutation_strategy`
(autoconfigured for `PermutationStrategyInterface`) and returns unique variants of an address,
e.g. for fuzzy matching or search. The original address is always the first entry. Every strategy is
applied to every variant produced so far (ordered by `#[AsTaggedItem(priority: …)]`), until
`max_permutations` is reached.

### Languages

Language specific strategies implement `LocalizedPermutationStrategyInterface` and live in a namespace
named after their ISO 639-1 code (`Strategy\De`, `Strategy\Fr`, …). They only run for addresses whose
country uses that language for street names, according to the `Language\StreetLanguageMap`:

| Language | Countries (default)   |
|----------|-----------------------|
| `de`     | DE, AT, CH, LI        |
| `fr`     | FR, BE, CH, LU, MC    |
| `it`     | IT, CH, SM            |
| `nl`     | NL                    |
| `en`     | US (USPS abbreviations) |
| `pl`     | PL                    |
| `da`     | DK                    |

A German address only gets the `de` strategies, a Swiss one `de`, `fr` and `it`. Addresses without a
(known) country only get the language independent strategies. The defaults are conservative: Flanders and
East Belgium (BE: `nl`, `de`) and South Tyrol (IT: `de`) are not listed. Override the entry of a country
in the configuration (it replaces the default, `[]` disables the country):

```yaml
address_kit:
    street_languages:
        BE: [fr, nl, de]
        IT: [it, de]
```

`$permutator->permute($address, ['de'])` sets the languages explicitly.

### Strategies

| Language | Strategy                         | Example                                                              |
|----------|----------------------------------|----------------------------------------------------------------------|
| –        | `HouseNumberInStreetPermutation` | `Denkstr. 19` → `Denkstr.` + house number `19`                        |
| –        | `CasePermutation`                | `HAUPTSTRASSE` → `Hauptstrasse`, `straße im moos` → `Straße im Moos` |
| –        | `HouseNumberPermutation`         | `12a` ⇄ `12 a` ⇄ `12A` ⇄ `12 A` (runs last)                          |
| de       | `UmlautPermutation`              | `Müllerweg` ⇄ `Muellerweg`, `Schlossstraße` → `Schloßstraße`          |
| de       | `HyphenPermutation`              | `Albert-Einstein-Straße` ⇄ `Albert Einstein Straße`                  |
| de       | `PrepositionPermutation`         | `Am Freiheitsplatz` → `Freiheitsplatz`, `Alte Mühle` → `An der Alten Mühle` |
| de       | `GrundwortPermutation`           | `Marktplatz` ⇄ `Markt Platz` ⇄ `Marktpl.`                            |
| de       | `StrassePermutation`             | `Hauptstraße` ⇄ `Hauptstr.` ⇄ `Haupt Straße`, `Leipziger` → `Leipziger Straße` |
| nl       | `StreetPermutation`              | `Burgemeester van der Vlugtstraat` ⇄ `Burg. v.d. Vlugtstr.`           |
| en       | `OrdinalPermutation`             | `2nd Ave` ⇄ `Second Ave`                                             |
| en       | `DirectionalPermutation`         | `W Sunset Blvd` ⇄ `West Sunset Blvd` ⇄ `Sunset Blvd`                 |
| en       | `StreetSuffixPermutation`        | `Sunset Boulevard` ⇄ `Sunset Blvd` ⇄ `Sunset`                        |
| fr       | `AccentPermutation`              | `Rue de l’Église` ⇄ `Rue de l'Eglise`                                |
| fr       | `HyphenPermutation`              | `Avenue du Général de Gaulle` ⇄ `Avenue du Général-de-Gaulle`        |
| fr       | `TitlePermutation`               | `Rue Saint-Étienne` ⇄ `Rue St-Étienne`                               |
| fr       | `StreetTypePermutation`          | `Av. de la Gare` ⇄ `Avenue de la Gare`                               |
| it       | `AccentPermutation`              | `VIA DELLA LIBERTA'` ⇄ `Via della Libertà`                           |
| it       | `GivenNamePermutation`           | `Via Guglielmo Marconi` → `Via G. Marconi`, `Via Marconi`            |
| it       | `TitlePermutation`               | `Piazza Sant'Agostino` ⇄ `Piazza S. Agostino`, `F.lli` ⇄ `Fratelli`  |
| it       | `StreetTypePermutation`          | `P.zza Garibaldi` ⇄ `Piazza Garibaldi`                               |
| pl       | `DiacriticPermutation`           | `Łódź` ⇄ `Lodz`, `Swietokrzyska` → `Świętokrzyska`                    |
| pl       | `GivenNamePermutation`           | `ul. Tadeusza Kościuszki` → `ul. T. Kościuszki`, `ul. Kościuszki`    |
| pl       | `TitlePermutation`               | `ul. Generała Andersa` ⇄ `ul. Gen. Andersa`                          |
| pl       | `StreetTypePermutation`          | `ul. Długa` ⇄ `ulica Długa` ⇄ `Długa`                                |
| da       | `LetterPermutation`              | `Nørregade` ⇄ `Noerregade` ⇄ `Norregade`, `Aabenraa` ⇄ `Åbenrå`       |
| da       | `AccentPermutation`              | `Allé` ⇄ `Alle`                                                      |
| da       | `PrefixPermutation`              | `Gammel Kongevej` ⇄ `Gl. Kongevej`                                   |
| da       | `StreetTermPermutation`          | `Skovvej` ⇄ `Skovvejen`                                              |

Add your own strategy by implementing `PermutationStrategyInterface` (language independent) or
`LocalizedPermutationStrategyInterface`.

Both `AddressNormalizer::createDefault()` and `AddressPermutator::createDefault()` work without the container.

## Development

```bash
composer test
composer phpstan
```
