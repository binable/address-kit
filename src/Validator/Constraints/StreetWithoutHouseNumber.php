<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Special case: the street must not contain a house number ("Hauptstraße 12"),
 * for forms with a separate house number field.
 *
 * Numbers that are part of the name stay valid ("Straße des 17. Juni", "Am 2. Weg").
 * Note: Mannheim style addresses ("C 4") are rejected as well; don't use this constraint for them.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class StreetWithoutHouseNumber extends Constraint
{
    public const CONTAINS_HOUSE_NUMBER_ERROR = 'b2f7c1d4-5e6a-4b8c-9d0e-1f2a3b4c5d61';

    protected const ERROR_NAMES = [
        self::CONTAINS_HOUSE_NUMBER_ERROR => 'CONTAINS_HOUSE_NUMBER_ERROR',
    ];

    /**
     * A house number at the start ("12 Main Street") or at the end ("Hauptstraße 12a") of the street.
     */
    public const DEFAULT_PATTERN = '/(?:^\d{1,5}\s?[a-zA-Z]?\s+\p{L}|\s\d{1,5}\s?[a-zA-Z]?(?:\s?[-\/]\s?\d{0,5}\s?[a-zA-Z]?)?$)/u';

    #[HasNamedArguments]
    public function __construct(
        public string $pattern = self::DEFAULT_PATTERN,
        public string $message = 'Please enter the house number in the separate field.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
