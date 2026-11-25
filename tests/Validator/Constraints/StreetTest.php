<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\Street;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Validator\Constraints as Assert;

final class StreetTest extends ConstraintTestCase
{
    #[DataProvider('validProvider')]
    public function testValid(string $value): void
    {
        $this->assertValid($value, new Street());
    }

    /**
     * @return iterable<array{string}>
     */
    public static function validProvider(): iterable
    {
        yield ['Hauptstraße'];
        yield ['Berliner Str.'];
        yield ['Straße des 17. Juni'];
        yield ["Rue de l'Église"];
        yield ['C 4'];
        yield ['Ulica Świętokrzyska'];
    }

    #[TestWith([null, Assert\NotBlank::IS_BLANK_ERROR])]
    #[TestWith(['   ', Assert\NotBlank::IS_BLANK_ERROR])]
    #[TestWith([42, Assert\Type::INVALID_TYPE_ERROR])]
    #[TestWith(['A', Assert\Length::TOO_SHORT_ERROR])]
    #[TestWith(['Hauptstraße <script>', Assert\Regex::REGEX_FAILED_ERROR])]
    #[TestWith(['12345', Assert\Regex::REGEX_FAILED_ERROR])]
    public function testInvalidStopsAtFirstError(mixed $value, string $code): void
    {
        $this->assertViolation($code, $value, new Street());
    }

    public function testMessages(): void
    {
        $violations = $this->validator->validate('12345', new Street());
        self::assertSame('The street name must contain at least one letter.', $violations[0]->getMessage());

        $violations = $this->validator->validate('<>', new Street(message: 'Ungültige Straße.'));
        self::assertSame('Ungültige Straße.', $violations[0]->getMessage());
    }

    public function testOptional(): void
    {
        $this->assertValid(null, new Street(required: false));
        $this->assertValid('', new Street(required: false));
    }

    public function testCustomLength(): void
    {
        $this->assertViolation(Assert\Length::TOO_LONG_ERROR, str_repeat('a', 11), new Street(maxLength: 10));
    }
}
