<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Whitespace;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Trims the value and collapses repeated (unicode) whitespace to a single space: "Am   Markt " → "Am Markt".
 */
#[AsTaggedItem(priority: 500)]
final class CollapseWhitespaceNormalizer implements WhitespaceNormalizerInterface
{
    public function normalize(string $value, ?AddressField $field = null): string
    {
        return trim((string) preg_replace('/[\s\p{Zs}\x{200B}]+/u', ' ', $value));
    }
}
