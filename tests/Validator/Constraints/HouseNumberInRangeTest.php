<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\HouseNumberInRange;
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
