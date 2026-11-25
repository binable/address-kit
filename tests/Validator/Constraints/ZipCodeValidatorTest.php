<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Validator\Constraints\ZipCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Uses a real validator, as the country specific check is delegated to barbieswimcrew/zip-code-validator.
 */
final class ZipCodeValidatorTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    #[DataProvider('countryProvider')]
    public function testFixedCountry(string $country, string $zip, bool $valid): void
    {
        $violations = $this->validator->validate($zip, new ZipCode(country: $country));

        self::assertCount($valid ? 0 : 1, $violations, (string) $violations);
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

    public function testViolationCarriesOwnCode(): void
    {
        $violations = $this->validator->validate('ABC', new ZipCode(country: 'DE'));

        self::assertCount(1, $violations);
        self::assertSame(ZipCode::INVALID_FORMAT_ERROR, $violations[0]->getCode());
        self::assertSame('DE', $violations[0]->getParameters()['{{ country }}']);
    }

    public function testGenericCheckWithoutCountry(): void
    {
        self::assertCount(0, $this->validator->validate('12345', new ZipCode()));
        self::assertCount(1, $this->validator->validate('#', new ZipCode()));
    }

    public function testUnknownCountryIsIgnoredWhenNotStrict(): void
    {
        self::assertCount(0, $this->validator->validate('whatever', new ZipCode(country: 'QQ')));
    }

    public function testUnknownCountryThrowsWhenStrict(): void
    {
        $this->expectException(ConstraintDefinitionException::class);

        $this->validator->validate('12345', new ZipCode(country: 'QQ', strict: true));
    }

    public function testCountryAndCountryPathAreExclusive(): void
    {
        $this->expectException(ConstraintDefinitionException::class);

        new ZipCode(country: 'DE', countryPath: 'country');
    }

    public function testCountryPathOnAddress(): void
    {
        $valid = new Address('Hauptstraße', '1', '10115', 'Berlin', 'DE');
        $invalid = new Address('Hauptstraße', '1', '10115', 'Wien', 'AT');

        self::assertCount(0, $this->validator->validate($valid));

        $violations = $this->validator->validate($invalid);
        self::assertCount(1, $violations);
        self::assertSame('zipCode', $violations[0]->getPropertyPath());
    }
}
