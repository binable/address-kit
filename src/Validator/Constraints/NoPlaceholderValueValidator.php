<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class NoPlaceholderValueValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoPlaceholderValue) {
            throw new UnexpectedTypeException($constraint, NoPlaceholderValue::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_scalar($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }

        $normalized = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $value)));
        $placeholders = array_map(mb_strtolower(...), [...$constraint->placeholders, ...$constraint->extraPlaceholders]);

        $isPlaceholder = \in_array($normalized, $placeholders, true)
            // the same character three or more times: "xxx", "aaaa", "0000"
            || ($constraint->rejectRepeatedCharacters && 1 === preg_match('/^(.)\1{2,}$/u', $normalized));

        if ($isPlaceholder) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(NoPlaceholderValue::PLACEHOLDER_ERROR)
                ->addViolation();
        }
    }
}
