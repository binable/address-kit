<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Validates a street name (without house number), e.g. "Hauptstraße", "Rue de l'Église", "Straße des 17. Juni".
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Street extends Constraint
{
    public const INVALID_CHARACTERS_ERROR = '9b1e4d7a-2c3f-4a8b-b6e5-0f1d2c3b4a71';
    public const NO_LETTER_ERROR = '9b1e4d7a-2c3f-4a8b-b6e5-0f1d2c3b4a72';
    public const TOO_SHORT_ERROR = '9b1e4d7a-2c3f-4a8b-b6e5-0f1d2c3b4a73';
    public const TOO_LONG_ERROR = '9b1e4d7a-2c3f-4a8b-b6e5-0f1d2c3b4a74';

    protected const ERROR_NAMES = [
        self::INVALID_CHARACTERS_ERROR => 'INVALID_CHARACTERS_ERROR',
        self::NO_LETTER_ERROR => 'NO_LETTER_ERROR',
        self::TOO_SHORT_ERROR => 'TOO_SHORT_ERROR',
        self::TOO_LONG_ERROR => 'TOO_LONG_ERROR',
    ];

    /**
     * Unicode letters, combining marks, digits, whitespace and common punctuation in street names.
     */
    public const DEFAULT_PATTERN = '/^[\p{L}\p{M}\d\s.,\'’´`\-\/&()]+$/u';

    #[HasNamedArguments]
    public function __construct(
        public int $minLength = 2,
        public int $maxLength = 100,
        public string $pattern = self::DEFAULT_PATTERN,
        public string $message = 'This value is not a valid street name.',
        public string $noLetterMessage = 'The street name must contain at least one letter.',
        public string $minLengthMessage = 'The street name is too short. It should have {{ limit }} characters or more.',
        public string $maxLengthMessage = 'The street name is too long. It should have {{ limit }} characters or less.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
