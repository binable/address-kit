<?php

declare(strict_types=1);

namespace Binable\AddressKit\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Special case: reject post office boxes and parcel lockers ("Postfach", "PO Box", "Packstation", "Postfiliale"),
 * e.g. for deliveries that need a physical address.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class NoPostBox extends Constraint
{
    public const POST_BOX_ERROR = 'c4e8a2b6-7d9f-4a1c-8e3b-5f6d7a8b9c01';
    public const PARCEL_LOCKER_ERROR = 'c4e8a2b6-7d9f-4a1c-8e3b-5f6d7a8b9c02';

    protected const ERROR_NAMES = [
        self::POST_BOX_ERROR => 'POST_BOX_ERROR',
        self::PARCEL_LOCKER_ERROR => 'PARCEL_LOCKER_ERROR',
    ];

    public const POST_BOX_PATTERN = '/\b(?:post\s?fach|p\.?\s?o\.?\s?box|post\s?office\s?box|bo[iî]te\s?postale|casella\s?postale|apartado(?:\s?de\s?correos)?)\b/iu';

    public const PARCEL_LOCKER_PATTERN = '/\b(?:pack\s?station|post\s?station|post\s?filiale|paket\s?shop|paket\s?box|parcel\s?(?:locker|shop))\b/iu';

    #[HasNamedArguments]
    public function __construct(
        public bool $allowParcelLockers = false,
        public string $postBoxMessage = 'Post office boxes are not allowed.',
        public string $parcelLockerMessage = 'Parcel lockers and post offices are not allowed.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
