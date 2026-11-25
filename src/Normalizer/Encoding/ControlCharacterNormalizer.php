<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Address fields are single-line: line breaks (\r, \n, U+2028, U+2029, U+0085), tabs and all other
 * control characters (U+0000–U+001F, U+007F–U+009F) are replaced by a space.
 * Repeated spaces are collapsed afterwards by the {@see WhitespaceNormalizer}.
 */
#[AsTaggedItem(priority: 500)]
final class ControlCharacterNormalizer implements StringNormalizerInterface
{
    private const PATTERN = '/[\p{Cc}\x{2028}\x{2029}]+/u';

    public function normalize(string $value, ?AddressField $field = null): string
    {
        return (string) preg_replace(self::PATTERN, ' ', $value);
    }
}
