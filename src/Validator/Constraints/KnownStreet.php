<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Special case: the street must be known - fuzzy, so the user is not punished for a spelling the
 * register writes differently.
 *
 * The street counts as known when it, or one of its permutations ("Hauptstr." for "Hauptstraße",
 * "Muellerweg" for "Müllerweg", …, see {@see \Binable\AddressKit\Permutation\AddressPermutator}),
 * matches a known street case-insensitively. Otherwise a known street that is exactly one letter
 * away and the only such street (names of 8+ characters only) is offered as
 * suggestion ({@see TYPO_ERROR}, parameter "{{ suggestion }}"), or accepted with {@see $allowTypos}.
 *
 * Known streets come from {@see $streets} or from the
 * {@see \Binable\AddressKit\Validator\StreetRegisterInterface} service, which gets an address built
 * from the validated object (properties {@see $zipCodePath}, {@see $cityPath}, {@see $countryPath},
 * or the object itself if it is an {@see \Binable\AddressKit\Model\Address}). If the register knows
 * no streets for the address, the check is skipped.
 *
 * The country decides which language specific permutations are used: {@see $country} (fixed) takes
 * precedence over the object's country; without both, the bundle's "default_country" is used.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class KnownStreet extends Constraint
{
    public const UNKNOWN_STREET_ERROR = '7c1e9a3d-2b4f-4d6e-8a1c-3e5f7a9b1c21';
    public const TYPO_ERROR = '7c1e9a3d-2b4f-4d6e-8a1c-3e5f7a9b1c22';

    protected const ERROR_NAMES = [
        self::UNKNOWN_STREET_ERROR => 'UNKNOWN_STREET_ERROR',
        self::TYPO_ERROR => 'TYPO_ERROR',
    ];

    /**
     * @param list<string>|null $streets fixed list of known streets; null asks the street register
     */
    #[HasNamedArguments]
    public function __construct(
        public ?array $streets = null,
        public bool $allowTypos = false,
        public ?string $country = null,
        public ?string $zipCodePath = 'zipCode',
        public ?string $cityPath = 'city',
        public ?string $countryPath = 'country',
        public string $message = 'The street {{ value }} is unknown.',
        public string $typoMessage = 'The street {{ value }} is unknown. Did you mean {{ suggestion }}?',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
