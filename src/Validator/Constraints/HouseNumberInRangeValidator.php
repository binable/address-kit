<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Binable\AddressKit\Matching\HouseNumberRange;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class HouseNumberInRangeValidator extends ConstraintValidator
{
    private readonly HouseNumberRange $houseNumberRange;

    public function __construct(?HouseNumberRange $houseNumberRange = null)
    {
        $this->houseNumberRange = $houseNumberRange ?? new HouseNumberRange();
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof HouseNumberInRange) {
            throw new UnexpectedTypeException($constraint, HouseNumberInRange::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) && !\is_int($value)) {
            throw new UnexpectedValueException($value, 'string|int');
        }

        if ($this->houseNumberRange->contains($value, $constraint->range)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setParameter('{{ range }}', $constraint->range)
            ->setCode(HouseNumberInRange::NOT_IN_RANGE_ERROR)
            ->addViolation();
    }
}
