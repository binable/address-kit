<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\AddressField;

/**
 * One cleaning step working on a single-line string value. Base of the steps run by the
 * {@see EncodingNormalizer} ({@see Encoding\EncodingNormalizerInterface}) and the
 * {@see WhitespaceNormalizer} ({@see Whitespace\WhitespaceNormalizerInterface}).
 */
interface StringNormalizerInterface
{
    /**
     * @param AddressField|null $field the address field the value belongs to, null if unknown
     */
    public function normalize(string $value, ?AddressField $field = null): string;
}
