<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Unicode normalization form C: "u" + combining diaeresis (U+0308) becomes the single character "ü".
 * Without it, comparisons and duplicate detection fail.
 */
#[AsTaggedItem(priority: 700)]
final class UnicodeNfcNormalizer implements StringNormalizerInterface
{
    public function normalize(string $value, ?AddressField $field = null): string
    {
        $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);

        return \is_string($normalized) ? $normalized : $value;
    }
}
