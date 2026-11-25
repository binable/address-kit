<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Validates a ZIP / postal code.
 *
 * Country-specific patterns are provided by barbieswimcrew/zip-code-validator. The country is either
 * fixed via {@see $country} or read from the validated object via {@see $countryPath}
 * (symfony/property-access path, e.g. "country" or "address.country").
 * Without a resolvable country only a generic plausibility check is applied.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ZipCode extends Constraint
{
    public const INVALID_FORMAT_ERROR = '8d4b6f1a-3e2c-4b7d-a9f8-2e1c0d3b4a51';

    protected const ERROR_NAMES = [
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
    ];

    /**
     * Generic fallback when no country is known: 2–10 alphanumerics, optionally separated by space or dash.
     */
    public const GENERIC_PATTERN = '/^[A-Za-z0-9](?:[A-Za-z0-9 \-]{0,8})[A-Za-z0-9]$/';

    #[HasNamedArguments]
    public function __construct(
        public ?string $country = null,
        public ?string $countryPath = null,
        public bool $strict = false,
        public bool $caseSensitive = false,
        public string $message = 'This value is not a valid ZIP code.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        if (null !== $country && null !== $countryPath) {
            throw new ConstraintDefinitionException(\sprintf('The options "country" and "countryPath" of constraint "%s" are mutually exclusive.', self::class));
        }

        parent::__construct(null, $groups, $payload);
    }
}
