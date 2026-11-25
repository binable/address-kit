<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\HouseNumber;
use Binable\AddressKit\Validator\Constraints\HouseNumberValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<HouseNumberValidator>
 */
final class HouseNumberValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): HouseNumberValidator
    {
        return new HouseNumberValidator();
    }

    public function testNullAndEmptyAreValid(): void
    {
        $this->validator->validate(null, new HouseNumber());
        $this->validator->validate('', new HouseNumber());

        $this->assertNoViolation();
    }

    #[DataProvider('validProvider')]
    public function testValid(string|int $value): void
    {
        $this->validator->validate($value, new HouseNumber());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<array{string|int}>
     */
    public static function validProvider(): iterable
    {
        yield ['1'];
        yield [12];
        yield ['12a'];
        yield ['12 A'];
        yield ['12-14'];
        yield ['12 - 14'];
        yield ['12/1'];
        yield ['12a-c'];
        yield ['7½'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalid(string $value): void
    {
        $this->validator->validate($value, new HouseNumber());

        $this->buildViolation('This value is not a valid house number.')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setCode(HouseNumber::INVALID_FORMAT_ERROR)
            ->assertRaised();
    }

    /**
     * @return iterable<array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield ['abc'];
        yield ['a12'];
        yield ['12abc'];
        yield ['123456'];
        yield ['12!'];
    }

    public function testTooLong(): void
    {
        $this->validator->validate('12345 a - 12345 b', new HouseNumber());

        $this->buildViolation('The house number is too long. It should have {{ limit }} characters or less.')
            ->setParameter('{{ value }}', '"12345 a - 12345 b"')
            ->setParameter('{{ limit }}', '15')
            ->setCode(HouseNumber::TOO_LONG_ERROR)
            ->assertRaised();
    }
}
