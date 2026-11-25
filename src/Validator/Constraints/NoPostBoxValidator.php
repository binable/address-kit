<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class NoPostBoxValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoPostBox) {
            throw new UnexpectedTypeException($constraint, NoPostBox::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        if (1 === preg_match(NoPostBox::POST_BOX_PATTERN, $value)) {
            $this->context->buildViolation($constraint->postBoxMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(NoPostBox::POST_BOX_ERROR)
                ->addViolation();

            return;
        }

        if (!$constraint->allowParcelLockers && 1 === preg_match(NoPostBox::PARCEL_LOCKER_PATTERN, $value)) {
            $this->context->buildViolation($constraint->parcelLockerMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(NoPostBox::PARCEL_LOCKER_ERROR)
                ->addViolation();
        }
    }
}
