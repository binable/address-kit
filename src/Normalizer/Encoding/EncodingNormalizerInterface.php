<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Normalizer\EncodingNormalizer;
use Binable\AddressKit\Normalizer\StringNormalizerInterface;

/**
 * One cleaning step of the {@see EncodingNormalizer}.
 *
 * Implementations are autoconfigured with the "address_kit.encoding_normalizer" tag and executed
 * in priority order (highest first, see #[AsTaggedItem]).
 */
interface EncodingNormalizerInterface extends StringNormalizerInterface
{
}
