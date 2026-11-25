<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Special case: reject obvious dummy input ("test", "asdf", "xxx", "n/a", "-", "aaaa") in address fields.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class NoPlaceholderValue extends Constraint
{
    public const PLACEHOLDER_ERROR = 'd5f9b3c7-8e0a-4b2d-9f4c-6a7b8c9d0e11';

    protected const ERROR_NAMES = [
        self::PLACEHOLDER_ERROR => 'PLACEHOLDER_ERROR',
    ];

    /**
     * Compared case-insensitively after trimming.
     */
    public const DEFAULT_PLACEHOLDERS = [
        'test', 'testing', 'tester', 'dummy', 'example', 'beispiel', 'muster', 'mustermann', 'musterstraße', 'musterstadt',
        'asdf', 'asd', 'qwe', 'qwer', 'qwertz', 'qwerty', 'yxcv', 'abc', 'abcd', 'foo', 'bar', 'foobar', 'lorem ipsum',
        'n/a', 'na', 'none', 'null', 'nil', 'keine', 'kein', 'unknown', 'unbekannt', 'egal', '?', '-', '.', '...',
    ];

    /**
     * @param list<string> $placeholders      replaces the default list
     * @param list<string> $extraPlaceholders added to the (default) list
     */
    #[HasNamedArguments]
    public function __construct(
        public array $placeholders = self::DEFAULT_PLACEHOLDERS,
        public array $extraPlaceholders = [],
        public bool $rejectRepeatedCharacters = true,
        public string $message = 'This value looks like a placeholder.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
