<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\NoPlaceholderValue;
use Binable\AddressKit\Validator\Constraints\NoPostBox;
use Binable\AddressKit\Validator\Constraints\Street;
use Binable\AddressKit\Validator\Constraints\StreetWithoutHouseNumber;
use PHPUnit\Framework\Attributes\TestWith;

final class SpecialCaseConstraintsTest extends ConstraintTestCase
{
    #[TestWith(['Hauptstraße'])]
    #[TestWith(['Straße des 17. Juni'])]
    #[TestWith(['Am 2. Weg'])]
    #[TestWith(['Rue de la Paix'])]
    public function testStreetWithoutHouseNumberValid(string $value): void
    {
        $this->assertValid($value, new StreetWithoutHouseNumber());
    }

    #[TestWith(['Hauptstraße 12'])]
    #[TestWith(['Hauptstraße 12a'])]
    #[TestWith(['Hauptstraße 12 - 14'])]
    #[TestWith(['221B Baker Street'])]
    #[TestWith(['12 Main Street'])]
    public function testStreetWithoutHouseNumberInvalid(string $value): void
    {
        $this->assertViolation(StreetWithoutHouseNumber::CONTAINS_HOUSE_NUMBER_ERROR, $value, new StreetWithoutHouseNumber());
    }

    #[TestWith(['Postfach 1234', NoPostBox::POST_BOX_ERROR])]
    #[TestWith(['P.O. Box 55', NoPostBox::POST_BOX_ERROR])]
    #[TestWith(['PO Box 55', NoPostBox::POST_BOX_ERROR])]
    #[TestWith(['Packstation 123', NoPostBox::PARCEL_LOCKER_ERROR])]
    #[TestWith(['Postfiliale 512', NoPostBox::PARCEL_LOCKER_ERROR])]
    public function testNoPostBoxInvalid(string $value, string $code): void
    {
        $this->assertViolation($code, $value, new NoPostBox());
    }

    public function testNoPostBoxValid(): void
    {
        $this->assertValid('Poststraße', new NoPostBox());
        $this->assertValid('Postweg', new NoPostBox());
        $this->assertValid('Packstation 123', new NoPostBox(allowParcelLockers: true));
    }

    #[TestWith(['test'])]
    #[TestWith([' ASDF '])]
    #[TestWith(['xxx'])]
    #[TestWith(['0000'])]
    #[TestWith(['n/a'])]
    #[TestWith(['-'])]
    public function testPlaceholderInvalid(string $value): void
    {
        $this->assertViolation(NoPlaceholderValue::PLACEHOLDER_ERROR, $value, new NoPlaceholderValue());
    }

    public function testPlaceholderValid(): void
    {
        $this->assertValid('Berlin', new NoPlaceholderValue());
        $this->assertValid('Aa', new NoPlaceholderValue());
        $this->assertValid('xxx', new NoPlaceholderValue(rejectRepeatedCharacters: false));
        $this->assertViolation(NoPlaceholderValue::PLACEHOLDER_ERROR, 'Entenhausen', new NoPlaceholderValue(extraPlaceholders: ['entenhausen']));
    }

    public function testCombinationWithCompound(): void
    {
        $object = new class {
            #[Street]
            #[StreetWithoutHouseNumber]
            #[NoPostBox]
            #[NoPlaceholderValue]
            public string $street = 'Hauptstraße 12';
        };

        $violations = $this->validator->validate($object);
        self::assertCount(1, $violations);
        self::assertSame(StreetWithoutHouseNumber::CONTAINS_HOUSE_NUMBER_ERROR, $violations[0]->getCode());

        $object->street = 'Hauptstraße';
        self::assertCount(0, $this->validator->validate($object));
    }
}
