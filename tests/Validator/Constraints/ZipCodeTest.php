<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Validator\Constraints\ZipCode;
use Binable\AddressKit\Validator\Constraints\ZipCodeForCountry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

final class ZipCodeTest extends ConstraintTestCase
{
    #[TestWith(['12345'])]
    #[TestWith([12345])]
    #[TestWith(['SW1A 1AA'])]
    #[TestWith(['1012-AB'])]
    public function testGenericValid(string|int $value): void
    {
        $this->assertValid($value, new ZipCode());
    }

    #[TestWith([null, Assert\NotBlank::IS_BLANK_ERROR])]
    #[TestWith(['1', Assert\Length::TOO_SHORT_ERROR])]
    #[TestWith(['12345678901', Assert\Length::TOO_LONG_ERROR])]
    #[TestWith(['#123', Assert\Regex::REGEX_FAILED_ERROR])]
    #[TestWith(['-123', Assert\Regex::REGEX_FAILED_ERROR])]
    public function testGenericInvalid(mixed $value, string $code): void
    {
        $this->assertViolation($code, $value, new ZipCode());
    }

    #[DataProvider('countryProvider')]
    public function testFixedCountry(string $country, string $zip, bool $valid): void
    {
        $codes = $this->codes($zip, new ZipCode(country: $country));

        self::assertSame($valid ? [] : [ZipCodeForCountry::INVALID_FORMAT_ERROR], $codes);
    }

    /**
     * @return iterable<array{string, string, bool}>
     */
    public static function countryProvider(): iterable
    {
        yield ['DE', '10115', true];
        yield ['DE', '1011', false];
        yield ['de', '80331', true];
        yield ['AT', '1010', true];
        yield ['AT', '10101', false];
        yield ['NL', '1012 AB', true];
        yield ['NL', '1012 ab', true];
        yield ['GB', 'SW1A 1AA', true];
        yield ['GB', '12345', false];
    }

    public function testCountryParameter(): void
    {
        $violations = $this->validator->validate('1234', new ZipCode(country: 'DE'));

        self::assertSame('DE', $violations[0]->getParameters()['{{ country }}']);
    }

    public function testUnknownCountry(): void
    {
        $this->assertValid('ABC12', new ZipCode(country: 'QQ'));

        $this->expectException(ConstraintDefinitionException::class);
        $this->validator->validate('12345', new ZipCode(country: 'QQ', strict: true));
    }

    public function testCountryPathDefaultsToCountry(): void
    {
        self::assertSame('country', (new ZipCode())->countryPath);
        self::assertSame('country', (new ZipCodeForCountry())->countryPath);
    }

    public function testFixedCountryTakesPrecedenceOverCountryPath(): void
    {
        $object = new class {
            #[ZipCode(country: 'AT')]
            public string $zip = '10115';
            public string $country = 'DE';
        };

        self::assertSame([ZipCodeForCountry::INVALID_FORMAT_ERROR], $this->objectCodes($object));
    }

    public function testCustomCountryPath(): void
    {
        $object = new class {
            #[ZipCode(countryPath: 'land')]
            public string $zip = '10115';
            public string $land = 'AT';
        };

        self::assertSame([ZipCodeForCountry::INVALID_FORMAT_ERROR], $this->objectCodes($object));
    }

    public function testObjectWithoutCountryUsesGenericCheckOnly(): void
    {
        $object = new class {
            #[ZipCode]
            public string $zip = 'ABC12';
        };

        self::assertSame([], $this->objectCodes($object));
    }

    public function testCountryCheckCanBeDisabled(): void
    {
        $object = new class {
            #[ZipCode(countryPath: null)]
            public string $zip = '10115';
            public string $country = 'AT';
        };

        self::assertSame([], $this->objectCodes($object));
    }

    public function testZipCodeForCountryNeedsCountry(): void
    {
        $this->expectException(MissingOptionsException::class);

        new ZipCodeForCountry(countryPath: null);
    }

    /**
     * @return list<string|null>
     */
    private function objectCodes(object $object): array
    {
        $codes = [];
        foreach ($this->validator->validate($object) as $violation) {
            $codes[] = $violation->getCode();
        }

        return $codes;
    }

    public function testCountryPathOnAddress(): void
    {
        self::assertCount(0, $this->validator->validate(new Address('Hauptstraße', '1', '10115', 'Berlin', 'DE')));

        $violations = $this->validator->validate(new Address('Hauptstraße', '1', '10115', 'Wien', 'AT'));
        self::assertCount(1, $violations);
        self::assertSame('zipCode', $violations[0]->getPropertyPath());
        self::assertSame(ZipCodeForCountry::INVALID_FORMAT_ERROR, $violations[0]->getCode());
    }
}
