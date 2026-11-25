<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\Country;
use Binable\AddressKit\Validator\Constraints\CountryValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CountryValidator>
 */
final class CountryValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): CountryValidator
    {
        return new CountryValidator();
    }

    public function testValidAlpha2(): void
    {
        $this->validator->validate('DE', new Country());

        $this->assertNoViolation();
    }

    public function testValidAlpha3(): void
    {
        $this->validator->validate('AUT', new Country(alpha3: true));

        $this->assertNoViolation();
    }

    public function testUnknownCountry(): void
    {
        $this->validator->validate('XX', new Country());

        $this->buildViolation('This value is not a valid country.')
            ->setParameter('{{ value }}', '"XX"')
            ->setCode(Country::NO_SUCH_COUNTRY_ERROR)
            ->assertRaised();
    }

    public function testLowerCaseIsInvalid(): void
    {
        $this->validator->validate('de', new Country());

        $this->buildViolation('This value is not a valid country.')
            ->setParameter('{{ value }}', '"de"')
            ->setCode(Country::NO_SUCH_COUNTRY_ERROR)
            ->assertRaised();
    }

    public function testNotAllowedCountry(): void
    {
        $this->validator->validate('FR', new Country(allowedCountries: ['DE', 'AT', 'CH']));

        $this->buildViolation('The country {{ value }} is not supported.')
            ->setParameter('{{ value }}', '"FR"')
            ->setCode(Country::NOT_ALLOWED_ERROR)
            ->assertRaised();
    }
}
