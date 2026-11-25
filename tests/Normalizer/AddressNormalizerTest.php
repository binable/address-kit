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
    public function testStreet(string $input, string $street, ?string $houseNumber): void
    {
        $normalized = (new StreetNormalizer())->normalize(new Address(street: $input));

        self::assertSame($street, $normalized->street);
        self::assertSame($houseNumber, $normalized->houseNumber);
    }

    /**
     * @return iterable<array{string, string, ?string}>
     */
    public static function streetProvider(): iterable
    {
        yield ['Hauptstr.', 'Hauptstraße', null];
        yield ['Berliner Str. 5', 'Berliner Straße', '5'];
        yield ['Str. des 17. Juni', 'Straße des 17. Juni', null];
        yield ['Marktpl. 1-3', 'Marktpl.', '1-3'];
        yield ['Am Markt', 'Am Markt', null];
    }

    public function testHouseNumber(): void
    {
        $normalizer = new HouseNumberNormalizer();

        self::assertSame('12a', $normalizer->normalize(new Address(houseNumber: '12 A'))->houseNumber);
        self::assertSame('12-14', $normalizer->normalize(new Address(houseNumber: '12 - 14'))->houseNumber);
        self::assertNull($normalizer->normalize(new Address())->houseNumber);
    }

    public function testZipCode(): void
    {
        $normalizer = new ZipCodeNormalizer();

        self::assertSame('10115', $normalizer->normalize(new Address(zipCode: 'DE-10115', country: 'DE'))->zipCode);
        self::assertSame('SW1A 1AA', $normalizer->normalize(new Address(zipCode: 'sw1a 1aa', country: 'GB'))->zipCode);
    }

    public function testCity(): void
    {
        $normalizer = new CityNormalizer();

        self::assertSame('Frankfurt am Main', $normalizer->normalize(new Address(city: 'FRANKFURT AM MAIN'))->city);
        self::assertSame('McAllen', $normalizer->normalize(new Address(city: 'McAllen'))->city);
    }
}
