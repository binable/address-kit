<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class CityValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof City) {
            throw new UnexpectedTypeException($constraint, City::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = trim((string) $value);
        $length = mb_strlen($value);

        if ($length < $constraint->minLength) {
            $this->context->buildViolation($constraint->minLengthMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ limit }}', (string) $constraint->minLength)
                ->setCode(City::TOO_SHORT_ERROR)
                ->addViolation();

            return;
        }

        if ($length > $constraint->maxLength) {
            $this->context->buildViolation($constraint->maxLengthMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ limit }}', (string) $constraint->maxLength)
                ->setCode(City::TOO_LONG_ERROR)
                ->addViolation();

            return;
        }

        if (1 !== preg_match((string) $constraint->pattern, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(City::INVALID_CHARACTERS_ERROR)
                ->addViolation();
        }
    }
}
