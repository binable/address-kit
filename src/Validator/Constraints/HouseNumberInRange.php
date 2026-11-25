<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Binable\AddressKit\Matching\HouseNumberRange;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Special case: the house number must lie within a range expression, e.g. the house numbers a
 * delivery area or a building covers.
 *
 * Expression formats ({@see HouseNumberRange}): "5", "1-50", "4+" (4 and above), "3-" (3 and below)
 * and comma separated combinations ("1-10, 20-30"). Only the first number of the house number counts:
 * "12a", "12/3" and "12-14" are all compared as 12.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class HouseNumberInRange extends Constraint
{
    public const NOT_IN_RANGE_ERROR = 'e2b7c4d1-5a3f-4e8b-9c6d-1f2a3b4c5d61';

    protected const ERROR_NAMES = [
        self::NOT_IN_RANGE_ERROR => 'NOT_IN_RANGE_ERROR',
    ];

    #[HasNamedArguments]
    public function __construct(
        public string $range,
        public string $message = 'The house number {{ value }} is not within {{ range }}.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        if (!(new HouseNumberRange())->isValidExpression($range)) {
            throw new ConstraintDefinitionException(\sprintf('The range "%s" of constraint "%s" is invalid. Use e.g. "5", "1-50", "4+", "3-" or "1-10, 20-30".', $range, self::class));
        }

        parent::__construct(null, $groups, $payload);
    }
}
