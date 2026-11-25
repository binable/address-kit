<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Special case: country specific ZIP code format, using the patterns of barbieswimcrew/zip-code-validator.
 *
 * Country resolution:
 * - {@see $country} (fixed ISO code, e.g. "DE") takes precedence over {@see $countryPath}.
 * - {@see $countryPath} defaults to "country": the property "country" (or getCountry()) of the validated object.
 *   Any symfony/property-access path is possible, e.g. "address.country".
 * - If the path is not readable, the country is empty or there is no object (standalone validation),
 *   the check is skipped.
 * - Unknown countries are skipped unless {@see $strict} is enabled.
 *
 * Used automatically by {@see ZipCode}.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ZipCodeForCountry extends Constraint
{
    public const INVALID_FORMAT_ERROR = '8d4b6f1a-3e2c-4b7d-a9f8-2e1c0d3b4a51';

    protected const ERROR_NAMES = [
        self::INVALID_FORMAT_ERROR => 'INVALID_FORMAT_ERROR',
    ];

    #[HasNamedArguments]
    public function __construct(
        public ?string $country = null,
        public ?string $countryPath = 'country',
        public bool $strict = false,
        public bool $caseSensitive = false,
        public string $message = 'This value is not a valid ZIP code.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        if (null === $country && null === $countryPath) {
            throw new MissingOptionsException(\sprintf('Either the option "country" or "countryPath" must be given for constraint "%s".', self::class), ['country', 'countryPath']);
        }

        parent::__construct(null, $groups, $payload);
    }
}
