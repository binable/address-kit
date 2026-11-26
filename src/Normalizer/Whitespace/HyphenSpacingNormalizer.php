<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Whitespace;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Removes spaces around hyphens inside a name or between numbers:
 * "Max -Planck" → "Max-Planck", "12 - 14" → "12-14", "D - 10115" → "D-10115".
 *
 * A hyphen with spaces on both sides between words is kept, as it separates two names
 * ("Kamenz - Kamjenc", "Brnik - Aerodrom", "Località Poggio - Biana"). A hyphen with a space only
 * behind it is kept as well, as it is usually a suspended hyphen ("Ein- und Ausgang").
 */
#[AsTaggedItem(priority: 200)]
final class HyphenSpacingNormalizer implements WhitespaceNormalizerInterface
{
    /**
     * Space only in front of the hyphen: "Max -Planck".
     */
    private const LEADING_SPACE = '/(?<=[\p{L}\p{N}.])\s+-(?=[\p{L}\p{N}])/u';

    /**
     * Spaces on both sides with a number on at least one side: "12 - 14", "D - 10115".
     */
    private const NUMERIC = '/(?<=\p{N})\s+-\s*(?=[\p{L}\p{N}])|(?<=[\p{L}\p{N}.])\s+-\s*(?=\p{N})/u';

    public function normalize(string $value, ?AddressField $field = null): string
    {
        $value = (string) preg_replace(self::LEADING_SPACE, '-', $value);

        return (string) preg_replace(self::NUMERIC, '-', $value);
    }
}
