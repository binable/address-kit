<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Normalizer\Country\Alpha2Normalizer;
use Binable\AddressKit\Normalizer\Country\Alpha3Normalizer;
use Binable\AddressKit\Normalizer\Country\CountryNameNormalizer;
use Binable\AddressKit\Normalizer\Country\VehicleRegistrationCodeNormalizer;
use Binable\AddressKit\Normalizer\CountryNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Countries;

final class CountryNormalizerTest extends TestCase
{
    #[DataProvider('alpha2Provider')]
    public function testAlpha2(string $input, ?string $expected): void
    {
        self::assertSame($expected, (new Alpha2Normalizer())->toAlpha2($input));
    }

    /**
     * @return iterable<array{string, ?string}>
     */
    public static function alpha2Provider(): iterable
    {
        yield ['DE', 'DE'];
        yield ['de', 'DE'];
        yield ['At', 'AT'];
        yield ['XX', null];
        yield ['DEU', null];
        yield ['D', null];
    }

    #[DataProvider('alpha3Provider')]
    public function testAlpha3(string $input, ?string $expected): void
    {
        self::assertSame($expected, (new Alpha3Normalizer())->toAlpha2($input));
    }

    /**
     * @return iterable<array{string, ?string}>
     */
    public static function alpha3Provider(): iterable
    {
        yield ['DEU', 'DE'];
        yield ['aut', 'AT'];
        yield ['CHE', 'CH'];
        yield ['USA', 'US'];
        yield ['XXX', null];
        yield ['DE', null];
        yield ['Deutschland', null];
    }

    #[DataProvider('vehicleProvider')]
    public function testVehicleRegistrationCode(string $input, ?string $expected): void
    {
        self::assertSame($expected, (new VehicleRegistrationCodeNormalizer())->toAlpha2($input));
    }

    /**
     * @return iterable<array{string, ?string}>
     */
    public static function vehicleProvider(): iterable
    {
        yield ['D', 'DE'];
        yield ['d', 'DE'];
        yield ['D.', 'DE'];
        yield ['A', 'AT'];
        yield ['CH', null]; // valid ISO alpha-2, left to the Alpha2Normalizer
        yield ['FL', 'LI'];
        yield ['I', 'IT'];
        yield ['E', 'ES'];
        yield ['GBZ', 'GI'];
        yield ['CDN', 'CA'];
        yield ['U.S.A.', 'US'];
        yield ['ES', null]; // ISO Spain, not the sign of El Salvador
        yield ['RU', null]; // ISO Russia, not the sign of Burundi
        yield ['X', null];
        yield ['Deutschland', null];
    }

    public function testVehicleRegistrationCodesMapToValidCountries(): void
    {
        foreach (VehicleRegistrationCodeNormalizer::CODES as $sign => $code) {
            self::assertTrue(Countries::exists($code), \sprintf('%s => %s', $sign, $code));
        }
    }

    public function testVehicleRegistrationCodeOverride(): void
    {
        self::assertSame('XK', (new VehicleRegistrationCodeNormalizer(['KOS' => 'XK']))->toAlpha2('KOS'));
    }

    #[DataProvider('nameProvider')]
    public function testCountryName(string $input, ?string $expected): void
    {
        self::assertSame($expected, (new CountryNameNormalizer())->toAlpha2($input));
    }

    /**
     * @return iterable<array{string, ?string}>
     */
    public static function nameProvider(): iterable
    {
        yield ['Deutschland', 'DE'];
        yield ['Germany', 'DE'];
        yield ['ÖSTERREICH', 'AT'];
        yield ['Osterreich', 'AT'];
        yield ['schweiz', 'CH'];
        yield ['Côte d’Ivoire', 'CI'];
        yield ['Bosnia & Herzegovina', 'BA'];
        yield ['Holland', 'NL'];
        yield ['Großbritannien', 'GB'];
        yield ['Atlantis', null];
        yield ['.', null];
    }

    public function testCountryNameLocalesAndAliases(): void
    {
        $normalizer = new CountryNameNormalizer(['fr'], ['Absurdistan' => 'de']);

        self::assertSame('DE', $normalizer->toAlpha2('Allemagne'));
        self::assertSame('DE', $normalizer->toAlpha2('absurdistan'));
        self::assertNull($normalizer->toAlpha2('Deutschland'));
    }

    #[DataProvider('chainProvider')]
    public function testChain(?string $input, ?string $expected): void
    {
        self::assertSame($expected, (new CountryNormalizer('DE'))->normalize(new Address(country: $input))->country);
    }

    /**
     * @return iterable<array{?string, ?string}>
     */
    public static function chainProvider(): iterable
    {
        yield ['de', 'DE'];
        yield ['DEU', 'DE'];
        yield ['D', 'DE'];
        yield ['FL', 'LI'];
        yield [' Deutschland ,', 'DE'];
        yield ['ES', 'ES']; // ISO alpha-2 wins over the sign of El Salvador
        yield ['ru', 'RU']; // ISO alpha-2 wins over the sign of Burundi
        yield ['CH', 'CH'];
        yield ['ROU', 'RO']; // ISO alpha-3 wins over the sign of Uruguay
        yield ['Atlantis', 'Atlantis'];
        yield [null, 'DE'];
    }

    public function testCustomSteps(): void
    {
        $normalizer = new CountryNormalizer(normalizers: [new Alpha3Normalizer()]);

        self::assertSame('AT', $normalizer->toAlpha2('AUT'));
        self::assertNull($normalizer->toAlpha2('A'));
        self::assertNull($normalizer->toAlpha2('Österreich'));
        self::assertNull($normalizer->toAlpha2('at'));
    }
}
