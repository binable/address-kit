<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Street name (without house number), e.g. "Hauptstraße", "Rue de l'Église", "Straße des 17. Juni".
 *
 * Composition (stops at the first failing rule):
 * NotBlank (if required) → Type(string) → Length → Regex(allowed characters) → Regex(at least one letter)
 *
 * Combine with special-case constraints such as {@see StreetWithoutHouseNumber}, {@see NoPostBox}
 * or {@see NoPlaceholderValue} where needed.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Street extends Assert\Compound
{
    use AddressComponentTrait;

    /**
     * Unicode letters, combining marks, digits, whitespace and common punctuation in street names.
     */
    public const DEFAULT_PATTERN = '/^[\p{L}\p{M}\d\s.,\'’´`\-\/&()]+$/u';

    /**
     * @param int<0, max> $minLength
     * @param int<1, max> $maxLength
     */
    #[HasNamedArguments]
    public function __construct(
        public bool $required = true,
        public int $minLength = 2,
        public int $maxLength = 100,
        public string $pattern = self::DEFAULT_PATTERN,
        public string $message = 'This value is not a valid street name.',
        public string $noLetterMessage = 'The street name must contain at least one letter.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    protected function getConstraints(array $options): array
    {
        return $this->sequence($this->required, [
            new Assert\Type('string'),
            new Assert\Length(min: $this->minLength, max: $this->maxLength, normalizer: 'trim'),
            new Assert\Regex(pattern: $this->pattern, message: $this->message, normalizer: 'trim'),
            new Assert\Regex(pattern: '/\p{L}/u', message: $this->noLetterMessage),
        ]);
    }
}
