<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Validates a city / locality name, e.g. "München", "Frankfurt am Main", "Saint-Étienne", "Halle (Saale)".
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class City extends Constraint
{
    public const INVALID_CHARACTERS_ERROR = '3f8e2a1b-7c4d-4e9f-8a6b-5c1d0e2f3a81';
    public const TOO_SHORT_ERROR = '3f8e2a1b-7c4d-4e9f-8a6b-5c1d0e2f3a82';
    public const TOO_LONG_ERROR = '3f8e2a1b-7c4d-4e9f-8a6b-5c1d0e2f3a83';

    protected const ERROR_NAMES = [
        self::INVALID_CHARACTERS_ERROR => 'INVALID_CHARACTERS_ERROR',
        self::TOO_SHORT_ERROR => 'TOO_SHORT_ERROR',
        self::TOO_LONG_ERROR => 'TOO_LONG_ERROR',
    ];

    /**
     * Must start with a letter; allows letters, combining marks, whitespace and common punctuation.
     */
    public const DEFAULT_PATTERN = '/^\p{L}[\p{L}\p{M}\s.,\'’´`\-\/()]*$/u';

    /**
     * Same as DEFAULT_PATTERN but also allowing digits (e.g. "Berlin 10").
     */
    public const DIGITS_PATTERN = '/^\p{L}[\p{L}\p{M}\d\s.,\'’´`\-\/()]*$/u';

    #[HasNamedArguments]
    public function __construct(
        public int $minLength = 2,
        public int $maxLength = 100,
        public bool $allowDigits = false,
        public ?string $pattern = null,
        public string $message = 'This value is not a valid city name.',
        public string $minLengthMessage = 'The city name is too short. It should have {{ limit }} characters or more.',
        public string $maxLengthMessage = 'The city name is too long. It should have {{ limit }} characters or less.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        $this->pattern ??= $allowDigits ? self::DIGITS_PATTERN : self::DEFAULT_PATTERN;

        parent::__construct(null, $groups, $payload);
    }
}
