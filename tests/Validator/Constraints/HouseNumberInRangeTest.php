<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\HouseNumberInRange;
use Binable\AddressKit\Validator\Constraints\HouseNumberInRangeValidator;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

final class HouseNumberInRangeTest extends ConstraintTestCase
{
    #[TestWith(['12'])]
    #[TestWith([12])]
    #[TestWith(['12a'])]
    #[TestWith(['60'])]
    #[TestWith([null])]
    #[TestWith([''])]
    public function testValid(string|int|null $value): void
    {
        $this->assertValid($value, new HouseNumberInRange('1-50, 60+'));
    }

    #[TestWith(['51'])]
    #[TestWith(['0'])]
    #[TestWith(['abc'])]
    public function testInvalid(string $value): void
    {
        $this->assertViolation(HouseNumberInRange::NOT_IN_RANGE_ERROR, $value, new HouseNumberInRange('1-50, 60+'));
    }

    #[TestWith(['5', '5'])]
    #[TestWith([5, '5'])]
    #[TestWith(['6a', '1-50'])]
    #[TestWith(['1', '1-50'])]
    #[TestWith(['50', '1-50'])]
    #[TestWith(['12/3', '10-12'])]
    #[TestWith(['5-7', '5'])]
    #[TestWith(['4', '4+'])]
    #[TestWith(['400', '4+'])]
    #[TestWith(['3', '3-'])]
    #[TestWith(['1', '3-'])]
    #[TestWith(['25', '1-10, 20-30'])]
    #[TestWith(['25', ' 1 - 10 ,20 - 30 '])]
    public function testInRange(string|int $value, string $range): void
    {
        $this->assertValid($value, new HouseNumberInRange($range));
    }

    #[TestWith(['6', '5'])]
    #[TestWith(['51', '1-50'])]
    #[TestWith(['123', '10-12'])]
    #[TestWith(['3', '4+'])]
    #[TestWith(['4', '3-'])]
    #[TestWith(['15', '1-10, 20-30'])]
    #[TestWith(['a', '3-'])]
    public function testNotInRange(string $value, string $range): void
    {
        $this->assertViolation(HouseNumberInRange::NOT_IN_RANGE_ERROR, $value, new HouseNumberInRange($range));
    }

    #[TestWith(['5', true])]
    #[TestWith(['1-50, 60+, 3-', true])]
    #[TestWith(['1 - 50', true])]
    #[TestWith(['', false])]
    #[TestWith(['1-50,', false])]
    #[TestWith(['a-b', false])]
    #[TestWith(['1..5', false])]
    public function testIsValidExpression(string $expression, bool $valid): void
    {
        self::assertSame($valid, HouseNumberInRangeValidator::isValidExpression($expression));
    }

    public function testMessageParameters(): void
    {
        $violations = $this->validator->validate('51', new HouseNumberInRange(range: '1-50'));

        self::assertSame('The house number "51" is not within 1-50.', (string) $violations->get(0)->getMessage());
    }

    public function testInvalidExpression(): void
    {
        $this->expectException(ConstraintDefinitionException::class);

        new HouseNumberInRange('1 to 50');
    }
}
