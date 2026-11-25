<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Validates an ISO 3166-1 country code (alpha-2 by default, alpha-3 optional) using symfony/intl,
 * optionally restricted to a list of allowed countries.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Country extends Constraint
{
    public const NO_SUCH_COUNTRY_ERROR = '5a2c7e9f-1b3d-4f6a-9c8e-7d0b1a2c3e91';
    public const NOT_ALLOWED_ERROR = '5a2c7e9f-1b3d-4f6a-9c8e-7d0b1a2c3e92';

    protected const ERROR_NAMES = [
        self::NO_SUCH_COUNTRY_ERROR => 'NO_SUCH_COUNTRY_ERROR',
        self::NOT_ALLOWED_ERROR => 'NOT_ALLOWED_ERROR',
    ];

    /**
     * @param list<string> $allowedCountries Upper-case codes in the same format as {@see $alpha3}. Empty = all countries.
     */
    #[HasNamedArguments]
    public function __construct(
        public bool $alpha3 = false,
        public array $allowedCountries = [],
        public string $message = 'This value is not a valid country.',
        public string $notAllowedMessage = 'The country {{ value }} is not supported.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
