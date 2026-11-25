<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Matching;

use Binable\AddressKit\Matching\HouseNumberRange;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class HouseNumberRangeTest extends TestCase
{
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
    public function testContains(string|int $houseNumber, string $expression): void
    {
        self::assertTrue((new HouseNumberRange())->contains($houseNumber, $expression));
    }

    #[TestWith(['6', '5'])]
    #[TestWith(['51', '1-50'])]
    #[TestWith(['123', '10-12'])]
    #[TestWith(['3', '4+'])]
    #[TestWith(['4', '3-'])]
    #[TestWith(['15', '1-10, 20-30'])]
    #[TestWith(['a', '3-'])]
    #[TestWith(['', '1+'])]
    #[TestWith(['5', 'abc'])]
    public function testNotContains(string $houseNumber, string $expression): void
    {
        self::assertFalse((new HouseNumberRange())->contains($houseNumber, $expression));
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
        self::assertSame($valid, (new HouseNumberRange())->isValidExpression($expression));
    }
}
