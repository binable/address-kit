<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * City / locality name, e.g. "München", "Frankfurt am Main", "Saint-Étienne", "Halle (Saale)".
 *
 * Composition (stops at the first failing rule):
 * NotBlank (if required) → Type(string) → Length → Regex
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class City extends Assert\Compound
{
    use AddressComponentTrait;

    /**
     * Must start with a letter; allows letters, combining marks, whitespace and common punctuation.
     */
    public const DEFAULT_PATTERN = '/^\p{L}[\p{L}\p{M}\s.,\'’´`\-\/()]*$/u';

    /**
     * Same as DEFAULT_PATTERN but also allowing digits (e.g. "Berlin 10").
     */
    public const DIGITS_PATTERN = '/^\p{L}[\p{L}\p{M}\d\s.,\'’´`\-\/()]*$/u';

    public string $pattern;

    /**
     * @param int<0, max> $minLength
     * @param int<1, max> $maxLength
     */
    #[HasNamedArguments]
    public function __construct(
        public bool $required = true,
        public int $minLength = 2,
        public int $maxLength = 100,
        public bool $allowDigits = false,
        ?string $pattern = null,
        public string $message = 'This value is not a valid city name.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        $this->pattern = $pattern ?? ($allowDigits ? self::DIGITS_PATTERN : self::DEFAULT_PATTERN);

        parent::__construct(null, $groups, $payload);
    }

    protected function getConstraints(array $options): array
    {
        return $this->sequence($this->required, [
            new Assert\Type('string'),
            new Assert\Length(min: $this->minLength, max: $this->maxLength, normalizer: 'trim'),
            new Assert\Regex(pattern: $this->pattern, message: $this->message, normalizer: 'trim'),
        ]);
    }
}
