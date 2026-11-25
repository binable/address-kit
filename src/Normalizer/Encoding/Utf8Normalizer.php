<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Ensures valid UTF-8: input that is not valid UTF-8 is treated as Windows-1252 / ISO-8859-1
 * (the usual suspects of legacy CSV exports) and converted.
 *
 * Runs first, as all following steps require valid UTF-8.
 */
#[AsTaggedItem(priority: 900)]
final class Utf8Normalizer implements EncodingNormalizerInterface
{
    public function normalize(string $value, ?AddressField $field = null): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }
}
