<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Unifies all space variants to a normal space (U+0020): no-break space (U+00A0), narrow no-break
 * space (U+202F), thin/hair/em/en spaces (U+2000–U+200A), ideographic space (U+3000), tabs, …
 * (Unicode category Zs). Repeated spaces are collapsed and the value is trimmed.
 */
#[AsTaggedItem(priority: 300)]
final class SpaceNormalizer implements EncodingNormalizerInterface
{
    public function normalize(string $value, ?AddressField $field = null): string
    {
        return trim((string) preg_replace('/[\p{Zs}\t]+/u', ' ', $value));
    }
}
