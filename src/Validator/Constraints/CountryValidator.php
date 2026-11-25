<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class CountryValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Country) {
            throw new UnexpectedTypeException($constraint, Country::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;
        $exists = $constraint->alpha3 ? Countries::alpha3CodeExists($value) : Countries::exists($value);

        if (!$exists) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Country::NO_SUCH_COUNTRY_ERROR)
                ->addViolation();

            return;
        }

        if ([] !== $constraint->allowedCountries && !\in_array($value, $constraint->allowedCountries, true)) {
            $this->context->buildViolation($constraint->notAllowedMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Country::NOT_ALLOWED_ERROR)
                ->addViolation();
        }
    }
}
