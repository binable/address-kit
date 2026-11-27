<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Binable\AddressKit\Model\HouseNumber;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks whether a house number falls within a range expression.
 *
 * The house number may contain a suffix letter ("6a"), a flat ("12/3") or a
 * range ("5-7") - only the first number is used for comparison, see
 * {@see HouseNumber::parse()}. A house number without any digit is never contained.
 *
 * Supported expression formats (whitespace around the operators is ignored):
 *   "5"           — exact match
 *   "1-50"        — inclusive range
 *   "4+"          — 4 and above (open upper bound)
 *   "3-"          — 3 and below (open lower bound)
 *   "1-10, 20-30" — multiple comma-separated blocks (any match wins)
 */
final class HouseNumberInRangeValidator extends ConstraintValidator
{
    private const BLOCK_PATTERN = '/^(?:(?<min>\d+)\s*\+|(?<max>\d+)\s*-|(?<from>\d+)\s*-\s*(?<to>\d+)|(?<exact>\d+))$/';

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

        if ($this->contains($value, $constraint->range)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setParameter('{{ range }}', $constraint->range)
            ->setCode(HouseNumberInRange::NOT_IN_RANGE_ERROR)
            ->addViolation();
    }

    /**
     * True when every comma separated block of the expression has one of the supported formats.
     */
    public static function isValidExpression(string $expression): bool
    {
        foreach (explode(',', $expression) as $block) {
            if (1 !== preg_match(self::BLOCK_PATTERN, trim($block))) {
                return false;
            }
        }

        return true;
    }

    private function contains(string|int $houseNumber, string $expression): bool
    {
        $parsed = HouseNumber::parse($houseNumber);
        if (null === $parsed) {
            return false;
        }

        $number = $parsed->number;

        foreach (explode(',', $expression) as $block) {
            if ($this->matchesBlock($number, trim($block))) {
                return true;
            }
        }

        return false;
    }

    private function matchesBlock(int $number, string $block): bool
    {
        if (1 !== preg_match(self::BLOCK_PATTERN, $block, $m, \PREG_UNMATCHED_AS_NULL)) {
            return false;
        }

        return match (true) {
            null !== $m['min'] => $number >= (int) $m['min'],
            null !== $m['max'] => $number <= (int) $m['max'],
            null !== $m['from'] && null !== $m['to'] => $number >= (int) $m['from'] && $number <= (int) $m['to'],
            default => $number === (int) $m['exact'],
        };
    }
}
