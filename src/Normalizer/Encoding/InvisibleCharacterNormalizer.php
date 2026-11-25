<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Removes invisible characters: soft hyphen (U+00AD), Mongolian vowel separator (U+180E),
 * zero-width space / non-joiner / joiner (U+200B–U+200D), direction marks (U+200E/U+200F),
 * bidi embeddings and isolates (U+202A–U+202E, U+2066–U+2069), word joiner and invisible
 * operators (U+2060–U+2064) and the BOM / zero-width no-break space (U+FEFF).
 */
#[AsTaggedItem(priority: 600)]
final class InvisibleCharacterNormalizer implements StringNormalizerInterface
{
    private const PATTERN = '/[\x{00AD}\x{180E}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{2069}\x{FEFF}]/u';

    public function normalize(string $value, ?AddressField $field = null): string
    {
        return (string) preg_replace(self::PATTERN, '', $value);
    }
}
