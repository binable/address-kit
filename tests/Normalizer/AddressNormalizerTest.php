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
