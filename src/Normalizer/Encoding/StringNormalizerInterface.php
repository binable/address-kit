<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Binable\AddressKit\Normalizer\EncodingNormalizer;

/**
 * One cleaning step of the {@see EncodingNormalizer}. Works on a single-line string value.
 *
 * Implementations are autoconfigured with the "address_kit.encoding_normalizer" tag and executed
 * in priority order (highest first, see #[AsTaggedItem]).
 */
interface StringNormalizerInterface
{
    /**
     * @param AddressField|null $field the address field the value belongs to, null if unknown
     */
    public function normalize(string $value, ?AddressField $field = null): string;
}
