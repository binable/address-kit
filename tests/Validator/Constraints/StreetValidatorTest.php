<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\Street;
use Binable\AddressKit\Validator\Constraints\StreetValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<StreetValidator>
 */
final class StreetValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): StreetValidator
    {
        return new StreetValidator();
    }

    #[DataProvider('validProvider')]
    public function testValid(string $value): void
    {
        $this->validator->validate($value, new Street());

        $this->assertNoViolation();
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
        yield ['Am Markt'];
        yield ['C 4'];
        yield ['Ulica Świętokrzyska'];
    }

    public function testInvalidCharacters(): void
    {
        $this->validator->validate('Hauptstraße <script>', new Street());

        $this->buildViolation('This value is not a valid street name.')
            ->setParameter('{{ value }}', '"Hauptstraße <script>"')
            ->setCode(Street::INVALID_CHARACTERS_ERROR)
            ->assertRaised();
    }

    public function testNoLetter(): void
    {
        $this->validator->validate('12345', new Street());

        $this->buildViolation('The street name must contain at least one letter.')
            ->setParameter('{{ value }}', '"12345"')
            ->setCode(Street::NO_LETTER_ERROR)
            ->assertRaised();
    }

    public function testTooShort(): void
    {
        $this->validator->validate('A', new Street());

        $this->buildViolation('The street name is too short. It should have {{ limit }} characters or more.')
            ->setParameter('{{ value }}', '"A"')
            ->setParameter('{{ limit }}', '2')
            ->setCode(Street::TOO_SHORT_ERROR)
            ->assertRaised();
    }

    public function testTooLong(): void
    {
        $value = str_repeat('a', 11);
        $this->validator->validate($value, new Street(maxLength: 10));

        $this->buildViolation('The street name is too long. It should have {{ limit }} characters or less.')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setParameter('{{ limit }}', '10')
            ->setCode(Street::TOO_LONG_ERROR)
            ->assertRaised();
    }
}
