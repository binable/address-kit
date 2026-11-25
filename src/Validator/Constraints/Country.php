<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ISO 3166-1 country code (alpha-2 by default, alpha-3 optional), optionally restricted to an allow-list.
 *
 * Composition (stops at the first failing rule):
 * NotBlank (if required) → Type(string) → Symfony Country (symfony/intl) → Choice(allowedCountries, if set)
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Country extends Assert\Compound
{
    use AddressComponentTrait;

    /**
     * @param list<string> $allowedCountries Upper-case codes in the same format as {@see $alpha3}. Empty = all countries.
     */
    #[HasNamedArguments]
    public function __construct(
        public bool $required = true,
        public bool $alpha3 = false,
        public array $allowedCountries = [],
        public string $message = 'This value is not a valid country.',
        public string $notAllowedMessage = 'The country {{ value }} is not supported.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    protected function getConstraints(array $options): array
    {
        return $this->sequence($this->required, [
            new Assert\Type('string'),
            new Assert\Country(message: $this->message, alpha3: $this->alpha3),
            ...([] !== $this->allowedCountries
                ? [new Assert\Choice(choices: $this->allowedCountries, message: $this->notAllowedMessage)]
                : []),
        ]);
    }
}
