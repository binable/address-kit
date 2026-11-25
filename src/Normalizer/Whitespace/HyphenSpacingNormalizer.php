<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Whitespace;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Removes spaces around hyphens between words or numbers:
 * "Max - Planck - Str." → "Max-Planck-Str.", "12 - 14" → "12-14", "D - 10115" → "D-10115".
 *
 * A hyphen with a space only behind it is kept as is, as it is usually a suspended hyphen ("Ein- und Ausgang").
 */
#[AsTaggedItem(priority: 200)]
final class HyphenSpacingNormalizer implements WhitespaceNormalizerInterface
{
    private const PATTERN = '/(?<=[\p{L}\p{N}.])\s+-\s*(?=[\p{L}\p{N}])/u';

    public function normalize(string $value, ?AddressField $field = null): string
    {
        return (string) preg_replace(self::PATTERN, '-', $value);
    }
}
