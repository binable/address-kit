<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class StreetValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Street) {
            throw new UnexpectedTypeException($constraint, Street::class);
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
                ->setCode(Street::TOO_SHORT_ERROR)
                ->addViolation();

            return;
        }

        if ($length > $constraint->maxLength) {
            $this->context->buildViolation($constraint->maxLengthMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ limit }}', (string) $constraint->maxLength)
                ->setCode(Street::TOO_LONG_ERROR)
                ->addViolation();

            return;
        }

        if (1 !== preg_match($constraint->pattern, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Street::INVALID_CHARACTERS_ERROR)
                ->addViolation();

            return;
        }

        if (1 !== preg_match('/\p{L}/u', $value)) {
            $this->context->buildViolation($constraint->noLetterMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Street::NO_LETTER_ERROR)
                ->addViolation();
        }
    }
}
