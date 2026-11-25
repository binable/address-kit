<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Unifies apostrophes to the ASCII apostrophe "'", relevant for names like "O'Connell Street" or
 * "Rue d'Alésia": left/right single quotation marks ‘ ’ (U+2018/U+2019), single low-9 and high-reversed-9
 * quotation marks (U+201A/U+201B), acute accent ´ (U+00B4), grave accent ` (U+0060), modifier letter
 * apostrophe/prime (U+02BC/U+02B9), prime ′ (U+2032) and fullwidth apostrophe (U+FF07).
 */
#[AsTaggedItem(priority: 100)]
final class ApostropheNormalizer implements EncodingNormalizerInterface
{
    private const PATTERN = '/[\x{0060}\x{00B4}\x{02B9}\x{02BC}\x{2018}-\x{201B}\x{2032}\x{FF07}]/u';

    public function normalize(string $value, ?AddressField $field = null): string
    {
        return (string) preg_replace(self::PATTERN, "'", $value);
    }
}
