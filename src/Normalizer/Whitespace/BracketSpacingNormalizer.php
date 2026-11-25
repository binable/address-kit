<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Whitespace;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * No space inside of round and square brackets: "( 2. OG )" → "(2. OG)".
 */
#[AsTaggedItem(priority: 300)]
final class BracketSpacingNormalizer implements WhitespaceNormalizerInterface
{
    public function normalize(string $value, ?AddressField $field = null): string
    {
        $value = (string) preg_replace('/([(\[])\s+/u', '$1', $value);

        return (string) preg_replace('/\s+([)\]])/u', '$1', $value);
    }
}
