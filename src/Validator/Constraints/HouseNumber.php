<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * House number such as "12", "12a", "12 A", "12-14", "12/1" or "7½".
 *
 * Composition (stops at the first failing rule):
 * NotBlank (if required) → Type(string|int) → Length → Regex
 *
 * Not required by default, as not every address has a house number.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class HouseNumber extends Assert\Compound
{
    use AddressComponentTrait;

    /**
     * Number, optional letter suffix, optional range/fraction ("12", "12a", "12-14", "12/1", "12 a-c").
     */
    public const DEFAULT_PATTERN = '/^\d{1,5}\s?(?:[a-zA-Z]|½)?(?:\s?[-\/]\s?(?:\d{1,5}\s?)?[a-zA-Z]?)?$/u';

    /**
     * @param int<1, max> $maxLength
     */
    #[HasNamedArguments]
    public function __construct(
        public bool $required = false,
        public int $maxLength = 15,
        public string $pattern = self::DEFAULT_PATTERN,
        public string $message = 'This value is not a valid house number.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    protected function getConstraints(array $options): array
    {
        return $this->sequence($this->required, [
            new Assert\Type(['string', 'int']),
            new Assert\Length(max: $this->maxLength, normalizer: 'trim'),
            new Assert\Regex(pattern: $this->pattern, message: $this->message, normalizer: 'trim'),
        ]);
    }
}
