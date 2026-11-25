<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\City;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Validator\Constraints as Assert;

final class CityTest extends ConstraintTestCase
{
    #[TestWith(['München'])]
    #[TestWith(['Frankfurt am Main'])]
    #[TestWith(['Halle (Saale)'])]
    #[TestWith(['Saint-Étienne'])]
    #[TestWith(["L'Aquila"])]
    #[TestWith(['St. Gallen'])]
    public function testValid(string $value): void
    {
        $this->assertValid($value, new City());
    }

    #[TestWith([null, Assert\NotBlank::IS_BLANK_ERROR])]
    #[TestWith(['X', Assert\Length::TOO_SHORT_ERROR])]
    #[TestWith(['Berlin 10', Assert\Regex::REGEX_FAILED_ERROR])]
    #[TestWith(['-Berlin', Assert\Regex::REGEX_FAILED_ERROR])]
    #[TestWith(['Köln!', Assert\Regex::REGEX_FAILED_ERROR])]
    public function testInvalid(mixed $value, string $code): void
    {
        $this->assertViolation($code, $value, new City());
    }

    public function testDigitsCanBeAllowed(): void
    {
        $this->assertValid('Berlin 10', new City(allowDigits: true));
    }
}
