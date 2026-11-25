<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class HouseNumberValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof HouseNumber) {
            throw new UnexpectedTypeException($constraint, HouseNumber::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) && !\is_int($value)) {
            throw new UnexpectedValueException($value, 'string|int');
        }

        $value = trim((string) $value);

        if (mb_strlen($value) > $constraint->maxLength) {
            $this->context->buildViolation($constraint->maxLengthMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ limit }}', (string) $constraint->maxLength)
                ->setCode(HouseNumber::TOO_LONG_ERROR)
                ->addViolation();

            return;
        }

        if (1 !== preg_match($constraint->pattern, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(HouseNumber::INVALID_FORMAT_ERROR)
                ->addViolation();
        }
    }
}
