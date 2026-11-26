<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Whitespace;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * No space before "," "." ";" ")", exactly one space after "," and ";":
 * "Hauptstr . 1" → "Hauptstr. 1", "Gütersloh ,Kreis" → "Gütersloh, Kreis".
 * A comma between digits ("1,5") is left untouched.
 *
 * No space is added in city names, as official names are written without: "Graz,03.Bez.:Geidorf" (AT).
 */
#[AsTaggedItem(priority: 400)]
final class PunctuationSpacingNormalizer implements WhitespaceNormalizerInterface
{
    public function normalize(string $value, ?AddressField $field = null): string
    {
        $value = (string) preg_replace('/\s+(?=[,.;)])/u', '', $value);
        if (AddressField::City === $field) {
            return $value;
        }

        $value = (string) preg_replace('/;(?=[^\s;])/u', '; ', $value);

        return (string) preg_replace('/(?<!\d),(?=[^\s,])|,(?=[^\s,\d])/u', ', ', $value);
    }
}
