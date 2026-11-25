<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use Binable\AddressKit\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints as Assert;

final class CountryTest extends ConstraintTestCase
{
    public function testValid(): void
    {
        $this->assertValid('DE', new Country());
        $this->assertValid('AUT', new Country(alpha3: true));
        $this->assertValid('CH', new Country(allowedCountries: ['DE', 'AT', 'CH']));
    }

    public function testInvalid(): void
    {
        $this->assertViolation(Assert\NotBlank::IS_BLANK_ERROR, null, new Country());
        $this->assertViolation(Assert\Country::NO_SUCH_COUNTRY_ERROR, 'XX', new Country());
        $this->assertViolation(Assert\Country::NO_SUCH_COUNTRY_ERROR, 'de', new Country());
        $this->assertViolation(Assert\Country::NO_SUCH_COUNTRY_ERROR, 'DE', new Country(alpha3: true));
    }

    public function testNotAllowed(): void
    {
        $violations = $this->validator->validate('FR', new Country(allowedCountries: ['DE', 'AT', 'CH']));

        self::assertCount(1, $violations);
        self::assertSame(Assert\Choice::NO_SUCH_CHOICE_ERROR, $violations[0]->getCode());
        self::assertSame('The country "FR" is not supported.', $violations[0]->getMessage());
    }
}
