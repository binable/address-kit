<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Unifies dashes to the ASCII hyphen-minus "-": hyphen (U+2010), non-breaking hyphen (U+2011),
 * figure dash (U+2012), en dash "–" (U+2013), em dash "—" (U+2014), horizontal bar (U+2015),
 * hyphen bullet (U+2043), minus sign (U+2212), small and fullwidth hyphen-minus (U+FE58, U+FE63, U+FF0D).
 */
#[AsTaggedItem(priority: 200)]
final class DashNormalizer implements StringNormalizerInterface
{
    private const PATTERN = '/[\x{2010}-\x{2015}\x{2043}\x{2212}\x{FE58}\x{FE63}\x{FF0D}]/u';

    public function normalize(string $value, ?AddressField $field = null): string
    {
        return (string) preg_replace(self::PATTERN, '-', $value);
    }
}
