<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class OptionalSequentiallyValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof OptionalSequentially) {
            throw new UnexpectedTypeException($constraint, OptionalSequentially::class);
        }

        if (null === $value || (\is_string($value) && '' === trim($value))) {
            return;
        }

        $validator = $this->context->getValidator()->inContext($this->context);
        $originalCount = $validator->getViolations()->count();

        foreach ($constraint->constraints as $nested) {
            if ($originalCount !== $validator->validate($value, $nested)->getViolations()->count()) {
                break;
            }
        }
    }
}
