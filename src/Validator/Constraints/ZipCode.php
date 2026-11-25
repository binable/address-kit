<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ZIP / postal code.
 *
 * Composition (stops at the first failing rule):
 * NotBlank (if required) → Type(string|int) → Length → Regex(generic format)
 * → {@see ZipCodeForCountry} (unless both "country" and "countryPath" are null)
 *
 * Country resolution for the country specific check:
 * - "country" (fixed ISO code, e.g. "DE") takes precedence, "countryPath" is ignored then.
 * - "countryPath" defaults to "country", i.e. the property "country" (or getCountry()) of the validated object.
 *   So `#[ZipCode]` works out of the box on objects like {@see \Binable\AddressKit\Model\Address}.
 *   Use another symfony/property-access path (e.g. "address.country") if needed.
 * - If the path is not readable, the object has no country or the value is validated standalone
 *   (no object), only the generic check applies.
 * - Set "countryPath: null" to disable the country specific check entirely.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ZipCode extends Assert\Compound
{
    use AddressComponentTrait;

    /**
     * Generic format: alphanumerics, optionally separated by space or dash, no leading/trailing separator.
     */
    public const GENERIC_PATTERN = '/^[A-Za-z0-9](?:[A-Za-z0-9 \-]*[A-Za-z0-9])?$/';

    /**
     * @param int<0, max> $minLength
     * @param int<1, max> $maxLength
     */
    #[HasNamedArguments]
    public function __construct(
        public bool $required = true,
        public ?string $country = null,
        public ?string $countryPath = 'country',
        public bool $strict = false,
        public bool $caseSensitive = false,
        public int $minLength = 2,
        public int $maxLength = 10,
        public string $message = 'This value is not a valid ZIP code.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    protected function getConstraints(array $options): array
    {
        $constraints = [
            new Assert\Type(['string', 'int']),
            new Assert\Length(min: $this->minLength, max: $this->maxLength, normalizer: 'trim'),
            new Assert\Regex(pattern: self::GENERIC_PATTERN, message: $this->message, normalizer: 'trim'),
        ];

        if (null !== $this->country || null !== $this->countryPath) {
            $constraints[] = new ZipCodeForCountry(
                country: $this->country,
                countryPath: null === $this->country ? $this->countryPath : null,
                strict: $this->strict,
                caseSensitive: $this->caseSensitive,
                message: $this->message,
            );
        }

        return $this->sequence($this->required, $constraints);
    }
}
