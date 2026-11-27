<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Normalizer\AddressNormalizer;
use Binable\AddressKit\Normalizer\CityNormalizer;
use Binable\AddressKit\Normalizer\CountryNormalizer;
use Binable\AddressKit\Normalizer\HouseNumberNormalizer;
use Binable\AddressKit\Normalizer\StreetNormalizer;
use Binable\AddressKit\Normalizer\ZipCodeNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AddressNormalizerTest extends TestCase
{
    public function testFullAddress(): void
    {
        $normalized = AddressNormalizer::createDefault()->normalize(
            new Address('  Hauptstr.  12 A ', null, ' d-10115 ', 'BERLIN', 'Deutschland'),
        );

        self::assertTrue((new Address('Hauptstraße', '12a', '10115', 'Berlin', 'DE'))->equals($normalized), var_export($normalized, true));
    }

    public function testDefaultCountry(): void
    {
        $normalized = AddressNormalizer::createDefault('AT')->normalize(new Address(city: 'Wien'));

        self::assertSame('AT', $normalized->country);
        self::assertSame('AT', AddressNormalizer::createDefault('AT')->normalize(new Address(country: ' , '))->country);
    }

    public function testWithoutDefaultCountry(): void
    {
        $normalized = AddressNormalizer::createDefault(null)->normalize(new Address('Hauptstr. 12', null, null, 'BERLIN', null));

        self::assertNull($normalized->country);
        self::assertSame('Hauptstr. 12', $normalized->street);
        self::assertNull($normalized->houseNumber);
        self::assertSame('Berlin', $normalized->city);
    }

    #[DataProvider('countryProvider')]
    public function testCountry(string $input, string $expected): void
    {
        self::assertSame($expected, (new CountryNormalizer())->normalize(new Address(country: $input))->country);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function countryProvider(): iterable
    {
        yield ['de', 'DE'];
        yield ['DEU', 'DE'];
        yield ['Germany', 'DE'];
        yield ['Österreich', 'AT'];
        yield ['schweiz', 'CH'];
        yield ['Atlantis', 'Atlantis'];
    }

    #[DataProvider('streetProvider')]
    public function testStreet(string $input, string $street, ?string $houseNumber, ?string $country = 'DE'): void
    {
        $normalized = (new StreetNormalizer())->normalize(new Address(street: $input, country: $country));

        self::assertSame($street, $normalized->street);
        self::assertSame($houseNumber, $normalized->houseNumber);
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: ?string, 3?: ?string}>
     */
    public static function streetProvider(): iterable
    {
        yield ['Hauptstr.', 'Hauptstraße', null];
        yield ['Berliner Str. 5', 'Berliner Straße', '5'];
        yield ['Str. des 17. Juni', 'Straße des 17. Juni', null];
        yield ['Marktpl. 1-3', 'Marktpl.', '1-3'];
        yield ['Bahnhof Pl.', 'Bahnhof Platz', null];
        yield ['Am Markt', 'Am Markt', null];
        yield ['Hauptstraße, 12', 'Hauptstraße', '12'];
        yield ['Hauptstraße ,;', 'Hauptstraße', null];
        yield ['Hauptstr. 1', 'Hauptstraße', '1', 'AT'];
        // CH / LI: no ß
        yield ['Bahnhofstr.', 'Bahnhofstrasse', null, 'CH'];
        yield ['Bahnhofstr. 3', 'Bahnhofstrasse', '3', 'LI'];
        // IT / SM: Str. is Strada
        yield ['Str. della Croce', 'Strada della Croce', null, 'IT'];
        yield ['Str. di Paderna 4', 'Strada di Paderna', '4', 'SM'];
        // Pl. is Place / plac
        yield ['Pl. de la Gare', 'Pl. de la Gare', null, 'FR'];
        yield ['pl. Grunwaldzki 5', 'pl. Grunwaldzki', '5', 'PL'];
        // number at the end belongs to the street name
        yield ['State Highway 42', 'State Highway 42', null, 'US'];
        yield ['Avenue du 8 Mai 1945', 'Avenue du 8 Mai 1945', null, 'FR'];
        yield ['Hauptstr. 12', 'Hauptstr. 12', null, null];
    }

    public function testStreetKeepsGivenHouseNumber(): void
    {
        $normalized = (new StreetNormalizer())->normalize(new Address(street: 'Hauptstr. 12', houseNumber: '', country: 'DE'));

        self::assertSame('Hauptstr. 12', $normalized->street);
        self::assertSame('', $normalized->houseNumber);
    }

    #[DataProvider('houseNumberProvider')]
    public function testHouseNumber(?string $input, ?string $country, ?string $expected): void
    {
        self::assertSame($expected, (new HouseNumberNormalizer())->normalize(new Address(houseNumber: $input, country: $country))->houseNumber);
    }

    /**
     * @return iterable<array{?string, ?string, ?string}>
     */
    public static function houseNumberProvider(): iterable
    {
        yield [null, 'DE', null];
        yield ['12 A', 'DE', '12a'];
        yield ['12A', 'AT', '12a'];
        yield ['12 b', 'CH', '12b'];
        yield ['12 - 14', 'DE', '12-14'];
        yield ['12 / 1', 'LI', '12/1'];
        // upper case suffix
        yield ['74G', 'GB', '74G'];
        yield ['310A', 'US', '310A'];
        yield ['1B', 'NL', '1B'];
        yield ['30 / A', 'IT', '30/A'];
        // SE: letter behind a space
        yield ['18 A', 'SE', '18 A'];
        // house names and grid addresses
        yield ['Ivy Cottage', 'GB', 'Ivy Cottage'];
        yield ['Y Wenallt', 'GB', 'Y Wenallt'];
        yield ['N8342', 'US', 'N8342'];
        yield ['Haus Sonnenschein', 'DE', 'Haus Sonnenschein'];
        yield ['12 A', null, '12 A'];
    }

    public function testZipCode(): void
    {
        $normalizer = new ZipCodeNormalizer();

        self::assertSame('10115', $normalizer->normalize(new Address(zipCode: 'DE-10115', country: 'DE'))->zipCode);
        self::assertSame('SW1A 1AA', $normalizer->normalize(new Address(zipCode: 'sw1a 1aa', country: 'GB'))->zipCode);
        self::assertSame('1050', $normalizer->normalize(new Address(zipCode: 'LV-1050', country: 'LV'))->zipCode);
        self::assertSame('1111', $normalizer->normalize(new Address(zipCode: 'L-1111', country: 'LU'))->zipCode);
        // a differing prefix never overrides a given country
        self::assertSame('D-12345', $normalizer->normalize(new Address(zipCode: 'D-12345', country: 'AT'))->zipCode);
    }

    #[DataProvider('zipCodePerCountryProvider')]
    public function testZipCodePerCountry(string $input, string $country, string $expected): void
    {
        self::assertSame($expected, (new ZipCodeNormalizer())->normalize(new Address(zipCode: $input, country: $country))->zipCode);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function zipCodePerCountryProvider(): iterable
    {
        // prefixed numeric
        yield 'LU vehicle prefix with space' => ['L 1111', 'LU', '1111'];
        yield 'LU iso prefix without separator' => ['lu1111', 'LU', '1111'];
        yield 'LV without separator' => ['LV1050', 'LV', '1050'];
        yield 'LV with space' => ['LV 1050', 'LV', '1050'];
        yield 'LV with hyphen' => ['LV-1050', 'LV', '1050'];
        yield 'LT spaced digits' => ['LT-01 100', 'LT', '01100'];
        yield 'LT hyphen inside' => ['01-100', 'LT', '01100'];
        yield 'MC with prefix' => ['MC 98000', 'MC', '98000'];
        yield 'MC spaced' => ['98 000', 'MC', '98000'];
        yield 'SM vehicle prefix' => ['RSM-47890', 'SM', '47890'];
        yield 'SM iso prefix' => ['SM 47890', 'SM', '47890'];
        yield 'LV wrong length kept' => ['LV 105', 'LV', 'LV 105'];
        yield 'MC wrong range kept' => ['MC 12345', 'MC', 'MC 12345'];
        // leading zero
        yield 'AU lost leading zero' => ['800', 'AU', '0800'];
        yield 'ZA lost leading zero' => ['2', 'ZA', '2'];
        yield 'ZA three digits' => ['181', 'ZA', '0181'];
        yield 'AU complete' => ['2000', 'AU', '2000'];
        yield 'leading zero only for AU/ZA' => ['800', 'CH', '800'];
        // CEDEX
        yield 'FR cedex with number' => ['75008 cedex 08', 'FR', '75008'];
        yield 'FR cedex without number' => ['92100 CEDEX', 'FR', '92100'];
        yield 'FR cedex glued' => ['69002CEDEX 2', 'FR', '69002'];
        yield 'FR spaced' => ['75 008', 'FR', '75008'];
        yield 'FR corsica' => ['2a004 cedex', 'FR', '2A004'];
        yield 'MC cedex' => ['98000 CEDEX', 'MC', '98000'];
        yield 'RE cedex' => ['97400 CEDEX 9', 'RE', '97400'];
        yield 'FR prefix and cedex' => ['F-75008 CEDEX 08', 'FR', '75008'];
        yield 'FR other value kept' => ['CEDEX', 'FR', 'CEDEX'];
        // other countries keep spaces and hyphens
        yield 'PL keeps hyphen' => ['00-950', 'PL', '00-950'];
        yield 'PT keeps hyphen' => ['1000-001', 'PT', '1000-001'];
        yield 'NL keeps space' => ['1234 ab', 'NL', '1234 AB'];
    }

    public function testZipCodeCountryStepsAreReplaceable(): void
    {
        $normalizer = new ZipCodeNormalizer([]);

        self::assertSame('LV 1050', $normalizer->normalize(new Address(zipCode: 'lv 1050', country: 'LV'))->zipCode);
    }

    #[DataProvider('zipCodeCountryProvider')]
    public function testZipCodePrefixSetsMissingCountry(string $input, string $zipCode, ?string $country): void
    {
        $address = (new ZipCodeNormalizer())->normalize(new Address(zipCode: $input));

        self::assertSame($zipCode, $address->zipCode);
        self::assertSame($country, $address->country);
    }

    /**
     * @return iterable<string, array{string, string, ?string}>
     */
    public static function zipCodeCountryProvider(): iterable
    {
        yield 'iso alpha-2' => ['LV-1050', '1050', 'LV'];
        yield 'vehicle code' => ['L-1111', '1111', 'LU'];
        yield 'vehicle code lower case' => ['d-10115', '10115', 'DE'];
        yield 'spaces around hyphen' => ['A - 1010', '1010', 'AT'];
        yield 'iso wins over vehicle code' => ['ES-28001', '28001', 'ES'];
        yield 'rest does not fit the country' => ['D-1234', 'D-1234', null];
        yield 'unknown prefix' => ['XX-1234', 'XX-1234', null];
        yield 'no hyphen' => ['SW1A 1AA', 'SW1A 1AA', null];
        yield 'plain zip code' => ['10115', '10115', null];
        yield 'country steps apply after inference' => ['LT-01 100', '01100', 'LT'];
        yield 'vehicle code san marino' => ['RSM-47890', '47890', 'SM'];
    }

    #[DataProvider('cityProvider')]
    public function testCity(string $input, string $expected): void
    {
        self::assertSame($expected, (new CityNormalizer())->normalize(new Address(city: $input))->city);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function cityProvider(): iterable
    {
        yield ['FRANKFURT AM MAIN', 'Frankfurt am Main'];
        yield ['McAllen', 'McAllen'];
        yield ['münchen', 'München'];
        yield ['BAD NEUENAHR-AHRWEILER', 'Bad Neuenahr-Ahrweiler'];
        // fr
        yield ['SAINT-GERMAIN-EN-LAYE', 'Saint-Germain-en-Laye'];
        yield ['veules-les-roses', 'Veules-les-Roses'];
        yield ['WITRY-LÈS-REIMS', 'Witry-lès-Reims'];
        yield ['LES MUREAUX', 'Les Mureaux'];
        yield ["VILLENEUVE-D'ASCQ", "Villeneuve-d'Ascq"];
        // nl
        yield ["'S-HEERENBERG", "'s-Heerenberg"];
        yield ["'s-hertogenbosch", "'s-Hertogenbosch"];
        yield ["'T HARDE", "'t Harde"];
        // it
        yield ['CASTELNUOVO DEL GARDA', 'Castelnuovo del Garda'];
        yield ["L'AQUILA", "L'Aquila"];
        yield ["SANT'ANGELO DEI LOMBARDI", "Sant'Angelo dei Lombardi"];
        yield ['MONTECCHIO DI CROSARA', 'Montecchio di Crosara'];
        // mixed case input is kept
        yield ['Saint-Germain-En-Laye', 'Saint-Germain-En-Laye'];
    }
}
