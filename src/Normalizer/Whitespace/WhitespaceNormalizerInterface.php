<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Whitespace;

use Binable\AddressKit\Normalizer\StringNormalizerInterface;
use Binable\AddressKit\Normalizer\WhitespaceNormalizer;

/**
 * One whitespace / punctuation step of the {@see WhitespaceNormalizer}.
 *
 * Implementations are autoconfigured with the "address_kit.whitespace_normalizer" tag and executed
 * in priority order (highest first, see #[AsTaggedItem]).
 */
interface WhitespaceNormalizerInterface extends StringNormalizerInterface
{
}
