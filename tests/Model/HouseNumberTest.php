<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Model;

use Binable\AddressKit\Model\HouseNumber;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class HouseNumberTest extends TestCase
{
    #[TestWith(['12', 12, null, null, null])]
    #[TestWith([12, 12, null, null, null])]
    #[TestWith([' 12 ', 12, null, null, null])]
    #[TestWith(['012', 12, null, null, null])]
    #[TestWith(['12a', 12, 'a', null, null])]
    #[TestWith(['12 a', 12, 'a', null, null])]
    #[TestWith(['12A', 12, 'A', null, null])]
    #[TestWith(['12/3', 12, '3', null, null])]
    #[TestWith(['30 / A', 30, 'A', null, null])]
    #[TestWith(['16 bis', 16, 'bis', null, null])]
    #[TestWith(['7½', 7, '½', null, null])]
    #[TestWith(['12-14', 12, null, 14, null])]
    #[TestWith(['12 - 14', 12, null, 14, null])]
    #[TestWith(['12-14a', 12, 'a', 14, null])]
    #[TestWith(['12-3', 12, '3', null, null])]
    #[TestWith(['12-12', 12, '12', null, null])]
    #[TestWith(['12a-14', 12, 'a-14', null, null])]
    #[TestWith(['N8342', 8342, null, null, 'N'])]
    #[TestWith(['Nr. 5', 5, null, null, 'Nr.'])]
    public function testParse(string|int $value, int $number, ?string $addition, ?int $rangeEnd, ?string $prefix): void
    {
        $houseNumber = HouseNumber::parse($value);

        self::assertNotNull($houseNumber);
        self::assertSame($number, $houseNumber->number);
        self::assertSame($addition, $houseNumber->addition);
        self::assertSame($rangeEnd, $houseNumber->rangeEnd);
        self::assertSame($prefix, $houseNumber->prefix);
        self::assertSame(null !== $rangeEnd, $houseNumber->isRange());
        self::assertSame(trim((string) $value), (string) $houseNumber);
    }

    #[TestWith([''])]
    #[TestWith(['  '])]
    #[TestWith(['Rose Cottage'])]
    public function testParseWithoutDigit(string $value): void
    {
        self::assertNull(HouseNumber::parse($value));
    }
}
