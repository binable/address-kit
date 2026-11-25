<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Validator\Constraints\KnownStreet;
use Binable\AddressKit\Validator\Constraints\KnownStreetValidator;
use Binable\AddressKit\Validator\StreetRegisterInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Validation;

final class KnownStreetTest extends ConstraintTestCase
{
    private const STREETS = ['Hauptstraße', 'Müllerweg', 'Paul-Gerhardt-Allee', 'Am Markt'];

    #[TestWith(['Hauptstraße'])]
    #[TestWith(['hauptstraße'])]
    #[TestWith(['Hauptstr.'])]
    #[TestWith(['Haupt Straße'])]
    #[TestWith(['Muellerweg'])]
    #[TestWith(['Markt'])]
    #[TestWith([null])]
    #[TestWith([''])]
    public function testKnown(?string $value): void
    {
        $this->assertValid($value, new KnownStreet(streets: self::STREETS, country: 'DE'));
    }

    public function testWithoutCountryOnlyLanguageIndependentPermutationsAreUsed(): void
    {
        $this->assertValid('hauptstraße 5', new KnownStreet(streets: self::STREETS));
        $this->assertViolation(KnownStreet::UNKNOWN_STREET_ERROR, 'Hauptstr.', new KnownStreet(streets: self::STREETS));
    }

    public function testTypo(): void
    {
        $violations = $this->validator->validate('Paul-Gerhard-Allee', new KnownStreet(streets: self::STREETS));

        self::assertCount(1, $violations);
        self::assertSame(KnownStreet::TYPO_ERROR, $violations->get(0)->getCode());
        self::assertSame('The street "Paul-Gerhard-Allee" is unknown. Did you mean "Paul-Gerhardt-Allee"?', (string) $violations->get(0)->getMessage());
    }

    #[TestWith(['Paul-Gerhard-Allee', 'Paul-Gerhardt-Allee'])]
    #[TestWith(['Bürgermeister-Spitta-Alle', 'Bürgermeister-Spitta-Allee'])]
    #[TestWith(['paul-gerhardt-alle', 'Paul-Gerhardt-Allee'])]
    #[TestWith(['Mullerstraße', 'Müllerstraße'], 'distance counts characters, not bytes')]
    #[TestWith(['Mülerstr.', 'Müllerstraße'], 'permutations count')]
    public function testTypoSuggestion(string $value, string $suggestion): void
    {
        $streets = ['Paul-Gerhardt-Allee', 'Bürgermeister-Spitta-Allee', 'Müllerstraße', 'Hof', 'Hohe'];
        $violations = $this->validator->validate($value, new KnownStreet(streets: $streets, country: 'DE'));

        self::assertCount(1, $violations);
        self::assertSame(KnownStreet::TYPO_ERROR, $violations->get(0)->getCode());
        self::assertSame('"'.$suggestion.'"', $violations->get(0)->getParameters()['{{ suggestion }}']);
    }

    /**
     * @param list<string> $streets
     */
    #[TestWith(['Hoh', ['Hof', 'Hohe']], 'too short')]
    #[TestWith(['Paul-Gerhrd-Alle', ['Paul-Gerhardt-Allee']], 'two letters off')]
    #[TestWith(['Lindenweg', ['Lindenwek', 'Lindenwef']], 'ambiguous')]
    public function testNoTypoSuggestion(string $value, array $streets): void
    {
        $this->assertViolation(KnownStreet::UNKNOWN_STREET_ERROR, $value, new KnownStreet(streets: $streets));
    }

    public function testTypoAllowed(): void
    {
        $this->assertValid('Paul-Gerhard-Allee', new KnownStreet(streets: self::STREETS, allowTypos: true));
    }

    #[TestWith(['Bahnhofstraße'])]
    #[TestWith(['Hauptstraßen-Allee'])]
    public function testUnknown(string $value): void
    {
        $this->assertViolation(KnownStreet::UNKNOWN_STREET_ERROR, $value, new KnownStreet(streets: self::STREETS));
    }

    public function testEmptyRegisterSkipsTheCheck(): void
    {
        $this->assertValid('Bahnhofstraße', new KnownStreet(streets: []));
    }

    public function testRegisterGetsAddressOfTheValidatedObject(): void
    {
        $register = new class implements StreetRegisterInterface {
            public ?Address $address = null;

            public function getStreets(Address $address): iterable
            {
                $this->address = $address;

                return '10115' === $address->zipCode ? ['Hauptstraße'] : [];
            }
        };

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory(new class($register) extends ConstraintValidatorFactory {
                public function __construct(private readonly StreetRegisterInterface $register)
                {
                    parent::__construct();
                }

                public function getInstance(Constraint $constraint): ConstraintValidatorInterface
                {
                    return $constraint instanceof KnownStreet ? new KnownStreetValidator($this->register) : parent::getInstance($constraint);
                }
            })
            ->getValidator();

        $form = new class {
            #[KnownStreet]
            public string $street = 'Hauptstrasse';
            public string $zipCode = '10115';
            public string $city = 'Berlin';
            public string $country = 'de';
        };

        self::assertCount(0, $validator->validate($form));
        self::assertNotNull($register->address);
        self::assertTrue((new Address('Hauptstrasse', null, '10115', 'Berlin', 'DE'))->equals($register->address));

        $form->street = 'Bahnhofstraße';
        self::assertCount(1, $validator->validate($form));

        $form->zipCode = '99999';
        self::assertCount(0, $validator->validate($form), 'unknown area');
    }

    public function testWithoutRegister(): void
    {
        $this->expectException(LogicException::class);

        $this->validator->validate('Hauptstraße', new KnownStreet());
    }
}
