<?php

declare(strict_types=1);

namespace Binable\AddressKit\Tests\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Runs constraints through a real validator, so compound constraints and delegations are covered end-to-end.
 */
abstract class ConstraintTestCase extends TestCase
{
    protected ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    /**
     * @return list<string|null> error codes of all violations
     */
    protected function codes(mixed $value, Constraint $constraint): array
    {
        $codes = [];
        foreach ($this->validator->validate($value, $constraint) as $violation) {
            $codes[] = $violation->getCode();
        }

        return $codes;
    }

    protected function assertValid(mixed $value, Constraint $constraint): void
    {
        self::assertSame([], $this->codes($value, $constraint), \sprintf('Expected %s to be valid.', var_export($value, true)));
    }

    protected function assertViolation(string $expectedCode, mixed $value, Constraint $constraint): void
    {
        self::assertSame([$expectedCode], $this->codes($value, $constraint), \sprintf('Unexpected violations for %s.', var_export($value, true)));
    }
}
