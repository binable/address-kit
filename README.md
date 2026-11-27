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
    default_country: DE     # used by the CountryNormalizer when an address has no country, ~ (null) to keep it empty
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
| `HouseNumber` | NotBlank → Type(string\|int) → Length(≤15) → Regex(`12`, `12a`, `12-14`, `12/1`, `7½`)      | **no**              | `maxLength`, `international`, `pattern`, `message`              |
| `ZipCode`     | NotBlank → Type(string\|int) → Length(2–10) → Regex(generic) → `ZipCodeForCountry`          | yes                 | `country`, `countryPath` (default `country`), `strict`, `caseSensitive`, lengths |
| `City`        | NotBlank → Type(string) → Length(2–100) → Regex(starts with letter, no digits)              | yes                 | `minLength`, `maxLength`, `allowDigits`, `international`, `pattern`, `message` |
| `Country`     | NotBlank → Type(string) → Symfony `Country` (alpha-2/alpha-3) → Choice(allow-list)*         | yes                 | `alpha3`, `allowedCountries`, messages                          |

\* only when the corresponding option is set.

The defaults of `HouseNumber` and `City` are deliberately strict, as they fit form input in most shops.
For international data `international: true` switches to the wider `INTERNATIONAL_PATTERN`:
house numbers then also take house names (`Rose Cottage`), `16 bis`, `N8342` and `1:75`, cities
`'s-Hertogenbosch`, `2e Exloërmond`, `Graz,03.Bez.:Geidorf` and `Dublin 6W`. An explicit `pattern` wins.

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
The number is taken from [`HouseNumber::parse()`](#house-number-parsing).

#### KnownStreet

The street is valid when it, or one of its [permutations](#permutation) (`Hauptstr.`, `Muellerweg`, …),
matches a known street case-insensitively. Otherwise, if exactly one known street is a single letter
away (`Paul-Gerhard-Allee` → `Paul-Gerhardt-Allee`, only for names of 8+ characters), the
violation `TYPO_ERROR` carries it as `{{ suggestion }}`; with `allowTypos: true` it is accepted. Anything else is an `UNKNOWN_STREET_ERROR`.

Known streets come from the `streets` option or from your implementation of
`Validator\StreetRegisterInterface`, which gets an `Address` built from the validated object
(`zipCodePath`, `cityPath`, `countryPath`) and returns the streets of that area – an empty result skips
the check. The country (fixed `country` option › object › `default_country`) selects the language
specific permutations.

```yaml
services:
    Binable\AddressKit\Validator\StreetRegisterInterface: '@App\Address\StreetRegister'
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
| `WhitespaceNormalizer` | Whitespace and punctuation cleanup of all fields, see below (runs second) |
| `CountryNormalizer`    | `Deutschland`, `deu`, `D`, `de` → `DE`; empty → `default_country` (see below) |
| `StreetNormalizer`     | `Hauptstr. 12 A` → street `Hauptstraße`, house number `12 A`; per country, see below |
| `HouseNumberNormalizer`| `12 - 14` → `12-14`; DE, AT, CH, LI: `12 A` → `12a`, other countries keep `74G`, `18 A` |
| `ZipCodeNormalizer`    | `d-10115` → `10115`, `sw1a 1aa` → `SW1A 1AA`; without country: `LV-1050` → `1050` + country `LV` (only if the rest fits the country's format) |
| `CityNormalizer`       | `FRANKFURT AM MAIN` → `Frankfurt am Main`, `SAINT-GERMAIN-EN-LAYE` → `Saint-Germain-en-Laye`, `'S-HEERENBERG` → `'s-Heerenberg` |

```php
$normalized = $addressNormalizer->normalize(new Address('Hauptstr. 12 A', null, 'D-10115', 'BERLIN', 'Germany'));
// Address('Hauptstraße', '12a', '10115', 'Berlin', 'DE')
```

Without a default country (`default_country: ~` or `AddressNormalizer::createDefault(null)`) addresses
without country stay without one; the country specific street and house number rules are skipped for them.

### Street and house number

`StreetNormalizer` removes trailing ` ,;` and works per country (lists configurable via constructor):

| Country     | Rule                                                                                  |
|-------------|---------------------------------------------------------------------------------------|
| DE, AT      | `Hauptstr.` → `Hauptstraße`, `Str. des 17. Juni` → `Straße des 17. Juni`, `Bahnhof Pl.` → `Bahnhof Platz` |
| CH, LI      | `Bahnhofstr.` → `Bahnhofstrasse` (no ß), `Bahnhof Pl.` → `Bahnhof Platz`              |
| IT, SM      | `Str. della Croce` → `Strada della Croce`                                             |
| others      | abbreviations are kept (`Pl.` is *Place* in FR / BE / LU, *plac* in PL)               |

A trailing house number is split off only when the house number is `null` and the country writes it behind
the street (`StreetNormalizer::HOUSE_NUMBER_AFTER_STREET`, e.g. DE, AT, CH, NL, IT, PL). In US, GB, FR …
street names may end with a number (`State Highway 42`, `Avenue du 8 Mai 1945`).

`HouseNumberNormalizer` removes spaces around `-` and `/` everywhere. Only in DE, AT, CH and LI
(`HouseNumberNormalizer::LOWER_CASE_SUFFIX_COUNTRIES`) the letter is attached and lower-cased (`12 A` → `12a`);
other countries keep their spelling (`74G`, `30/A`, `Storgatan 18 A`). House numbers not starting with a digit
(house names like `Ivy Cottage`, grid addresses like `N8342`) are never re-cased.

### Country

`CountryNormalizer` runs the country through the steps
tagged `address_kit.country_normalizer` (autoconfigured for `Country\CountryCodeNormalizerInterface`, ordered by
priority); the first step returning a code wins. Unknown values are kept as they are.

| Priority | Step                                | Example                                                                  |
|----------|-------------------------------------|--------------------------------------------------------------------------|
| 300      | `Alpha3Normalizer`                  | ISO alpha-3: `DEU` → `DE`, `aut` → `AT`                                   |
| 200      | `VehicleRegistrationCodeNormalizer` | international vehicle registration code: `D` → `DE`, `A` → `AT`, `FL` → `LI`, `U.S.A.` → `US` |
| 100      | `CountryNameNormalizer`             | name in `de` / `en` (configurable) plus common aliases: `Deutschland`, `Germany`, `Osterreich`, `Holland` |
| -100     | `Alpha2Normalizer`                  | ISO alpha-2, upper-cased: `de` → `DE`                                     |

Some vehicle registration codes are different ISO codes; there ISO wins (`ES` → Spain, not El Salvador;
`ROU` → Romania, not Uruguay): the vehicle step skips valid alpha-2 codes. Each step can be used on its own,
e.g. `(new VehicleRegistrationCodeNormalizer())->toAlpha2('FL')`.

```php
$countryNormalizer->toAlpha2('FL');          // "LI"
$countryNormalizer->toAlpha2('Niederlande'); // "NL"
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

`WhitespaceNormalizer` cleans spaces and punctuation of every address field. It runs right after the
`EncodingNormalizer` and before the component normalizers (country, street, house number, ZIP code, city),
so these already get cleaned values. It runs all string normalizers tagged `address_kit.whitespace_normalizer`
(autoconfigured for `Whitespace\WhitespaceNormalizerInterface`, ordered by priority). Values that are empty after cleaning become `null`.

| Priority | Step                           | Example                                                                     |
|----------|--------------------------------|-----------------------------------------------------------------------------|
| 500      | `CollapseWhitespaceNormalizer` | `  Am   Markt ` → `Am Markt`                                                 |
| 400      | `PunctuationSpacingNormalizer` | no space before `,` `.` `;` `)`, one after `,` `;`: `Gütersloh ,Kreis` → `Gütersloh, Kreis` (not in the city: `Graz,03.Bez.:Geidorf`) |
| 300      | `BracketSpacingNormalizer`     | `( 2. OG )` → `(2. OG)`                                                     |
| 200      | `HyphenSpacingNormalizer`      | `Max -Planck` → `Max-Planck`, `12 - 14` → `12-14`; keeps ` - ` between names (`Kamenz - Kamjenc`) |
| 100      | `EdgePunctuationNormalizer`    | `, Hauptstraße` → `Hauptstraße`, `Gütersloh,` → `Gütersloh`; keeps `Str.`, `a.M.` |

```php
$whitespaceNormalizer->normalizeString(' ,Max -Planck -Str . 1 ( 2. OG ) ,'); // "Max-Planck-Str. 1 (2. OG)"
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
| `en`     | US (USPS abbreviations), GB, IE, AU, NZ, ZA (Commonwealth types) |
| `pl`     | PL                    |
| `da`     | DK                    |
| `pt`     | PT                    |
| `ro`     | RO                    |
| `cs`     | CZ                    |
| `lt`     | LT                    |

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
| en       | `StreetSuffixPermutation`        | `Sunset Boulevard` ⇄ `Sunset Blvd` ⇄ `Sunset`, `Maple Cres` ⇄ `Maple Crescent` (outside the US) |
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
| pt       | `StreetTypePermutation`          | `Av. da República` ⇄ `Avenida da República`, `Praca` ⇄ `Praça` ⇄ `Pç.` |
| ro       | `StreetTypePermutation`          | `B-dul Unirii` ⇄ `Bulevardul Unirii` ⇄ `Bd. Unirii`, `Şoseaua` ⇄ `Șoseaua` |
| cs       | `StreetTypePermutation`          | `nám. Míru` ⇄ `náměstí Míru`, `Národní tř.` ⇄ `Národní třída`, `ul. Masarykova` → `Masarykova` |
| lt       | `StreetTypePermutation`          | `Vilniaus g.` ⇄ `Vilniaus gatvė` ⇄ `Vilniaus`                         |
| da       | `LetterPermutation`              | `Nørregade` ⇄ `Noerregade` ⇄ `Norregade`, `Aabenraa` ⇄ `Åbenrå`       |
| da       | `AccentPermutation`              | `Allé` ⇄ `Alle`                                                      |
| da       | `PrefixPermutation`              | `Gammel Kongevej` ⇄ `Gl. Kongevej`                                   |
| da       | `StreetTermPermutation`          | `Skovvej` ⇄ `Skovvejen`                                              |
| da       | `LinkingSPermutation`            | `Guldregnsvej` ⇄ `Guldregnvej`                                       |
| da       | `BygadePermutation`              | `Lemming Bygade` ⇄ `Lemmingbygade`                                   |

Add your own strategy by implementing `PermutationStrategyInterface` (language independent) or
`LocalizedPermutationStrategyInterface`.

### Street type dictionaries

The tables the strategies build on are public, so code outside the kit can strip, expand or compare
street types without keeping a copy of its own:

| Class                                  | Content                                                      |
|----------------------------------------|--------------------------------------------------------------|
| `Language\En\UspsAbbreviations`        | USPS suffixes and directionals, full => abbreviation         |
| `Language\En\Ordinals`                 | `2nd` => `Second`                                            |
| `Language\En\StreetTypes`              | GB, IE, AU, NZ, ZA: `close`, `crescent`, `mews`, `parade`, … |
| `Language\{Fr,It,Pl,Pt,Ro,Cs,Lt,Lv}\StreetTypes` | the generic terms of that language               |

Every `StreetTypes` class lists `TYPES` (canonical term => every spelling, full one first) and
`NAME_LIKE_TYPES` (terms that are just as often part of the name, like `Park`, `Clos` or `Ponte`;
the strategies leave them alone). The lookups are case insensitive and ignore a trailing full stop:

```php
use Binable\AddressKit\Language\Pt\StreetTypes;

StreetTypes::getCanonical('Av.');          // 'avenida'
StreetTypes::getSpellings('praca');        // ['praça', 'praca', 'pç.', …]
StreetTypes::isType('Quinta', true);       // true, name-like terms included
StreetTypes::getAllSpellings();            // every spelling, e.g. for a regex
```

Both `AddressNormalizer::createDefault()` and `AddressPermutator::createDefault()` work without the container.

## House number parsing

`Model\HouseNumber::parse()` splits a house number into number, addition and range end – the same
parsing `HouseNumberInRange` uses, available for your own code (e.g. matching against a register that
stores number and addition separately):

```php
use Binable\AddressKit\Model\HouseNumber;

$houseNumber = HouseNumber::parse('12-14a');
$houseNumber->number;    // 12
$houseNumber->rangeEnd;  // 14
$houseNumber->addition;  // 'a'
$houseNumber->isRange(); // true

HouseNumber::parse('12/3')->addition;  // '3'
HouseNumber::parse('16 bis')->addition; // 'bis'
HouseNumber::parse('N8342')->prefix;   // 'N'
HouseNumber::parse('Rose Cottage');    // null, no digit
```

Only the first group of digits is the number. A range end must be above the start (`12-3` is house 12,
addition `3`). One separator (`/`, `-`) and whitespace before the addition are dropped, its spelling
is kept (`12 A` → `A`).

## Development

```bash
composer test
composer phpstan
```
