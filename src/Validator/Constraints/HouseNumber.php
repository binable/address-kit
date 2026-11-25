<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Validates a house number such as "12", "12a", "12 A", "12-14", "12/1" or "7½".
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class HouseNumber extends Constraint
{
    public const INVALID_FORMAT_ERROR = '6c3a1f2e-0b6e-4f5e-9a8d-1d3c2b4a5e61';
    public const TOO_LONG_ERROR = '6c3a1f2e-0b6e-4f5e-9a8d-1d3c2b4a5e62';

    protected const ERROR_NAMES = [
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
        self::TOO_LONG_ERROR => 'TOO_LONG_ERROR',
    ];

    /**
     * Number, optional letter suffix, optional range/fraction ("12", "12a", "12-14", "12/1", "12 a-c").
     */
    public const DEFAULT_PATTERN = '/^\d{1,5}\s?(?:[a-zA-Z]|½)?(?:\s?[-\/]\s?(?:\d{1,5}\s?)?[a-zA-Z]?)?$/u';

    #[HasNamedArguments]
    public function __construct(
        public string $pattern = self::DEFAULT_PATTERN,
        public int $maxLength = 15,
        public string $message = 'This value is not a valid house number.',
        public string $maxLengthMessage = 'The house number is too long. It should have {{ limit }} characters or less.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
