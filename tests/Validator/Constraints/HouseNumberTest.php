<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\HouseNumber;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Validator\Constraints as Assert;

final class HouseNumberTest extends ConstraintTestCase
{
    #[TestWith(['1'])]
    #[TestWith([12])]
    #[TestWith(['12a'])]
    #[TestWith(['12 A'])]
    #[TestWith(['12-14'])]
    #[TestWith(['12 - 14'])]
    #[TestWith(['12/1'])]
    #[TestWith(['12a-c'])]
    #[TestWith(['7½'])]
    #[TestWith([null])]
    #[TestWith([''])]
    public function testValid(string|int|null $value): void
    {
        $this->assertValid($value, new HouseNumber());
    }

    #[TestWith(['abc', Assert\Regex::REGEX_FAILED_ERROR])]
    #[TestWith(['a12', Assert\Regex::REGEX_FAILED_ERROR])]
    #[TestWith(['12abc', Assert\Regex::REGEX_FAILED_ERROR])]
    #[TestWith(['123456', Assert\Regex::REGEX_FAILED_ERROR])]
    #[TestWith(['12345 a - 12345 b', Assert\Length::TOO_LONG_ERROR])]
    #[TestWith([1.5, Assert\Type::INVALID_TYPE_ERROR])]
    public function testInvalid(mixed $value, string $code): void
    {
        $this->assertViolation($code, $value, new HouseNumber());
    }

    #[TestWith(['12a'])]
    #[TestWith(['12-14'])]
    #[TestWith(['Rose Cottage'])]
    #[TestWith(['The Elms'])]
    #[TestWith(['16 bis'])]
    #[TestWith(['N8342'])]
    #[TestWith(['1:75'])]
    #[TestWith(['7½'])]
    public function testInternationalValid(string $value): void
    {
        $this->assertValid($value, new HouseNumber(international: true));
    }

    #[TestWith(['-12'])]
    #[TestWith(['12<b>'])]
    #[TestWith(['12?a=1'])]
    #[TestWith(['12;'])]
    public function testInternationalInvalid(string $value): void
    {
        $this->assertViolation(Assert\Regex::REGEX_FAILED_ERROR, $value, new HouseNumber(international: true));
    }

    public function testExplicitPatternWins(): void
    {
        $this->assertViolation(Assert\Regex::REGEX_FAILED_ERROR, '16 bis', new HouseNumber(international: true, pattern: HouseNumber::DEFAULT_PATTERN));
    }

    public function testRequired(): void
    {
        $this->assertViolation(Assert\NotBlank::IS_BLANK_ERROR, null, new HouseNumber(required: true));
    }
}
