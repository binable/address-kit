<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\City;
use Binable\AddressKit\Validator\Constraints\CityValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CityValidator>
 */
final class CityValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): CityValidator
    {
        return new CityValidator();
    }

    #[DataProvider('validProvider')]
    public function testValid(string $value): void
    {
        $this->validator->validate($value, new City());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<array{string}>
     */
    public static function validProvider(): iterable
    {
        yield ['München'];
        yield ['Frankfurt am Main'];
        yield ['Halle (Saale)'];
        yield ['Saint-Étienne'];
        yield ["L'Aquila"];
        yield ['St. Gallen'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalid(string $value): void
    {
        $this->validator->validate($value, new City());

        $this->buildViolation('This value is not a valid city name.')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setCode(City::INVALID_CHARACTERS_ERROR)
            ->assertRaised();
    }

    /**
     * @return iterable<array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield ['Berlin 10'];
        yield ['-Berlin'];
        yield ['Köln!'];
    }

    public function testDigitsCanBeAllowed(): void
    {
        $this->validator->validate('Berlin 10', new City(allowDigits: true));

        $this->assertNoViolation();
    }

    public function testTooShort(): void
    {
        $this->validator->validate('X', new City());

        $this->buildViolation('The city name is too short. It should have {{ limit }} characters or more.')
            ->setParameter('{{ value }}', '"X"')
            ->setParameter('{{ limit }}', '2')
            ->setCode(City::TOO_SHORT_ERROR)
            ->assertRaised();
    }
}
