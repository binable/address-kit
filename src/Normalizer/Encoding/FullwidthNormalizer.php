<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Encoding;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Converts fullwidth characters (common in Japanese and Chinese input), e.g. "１２３" → "123".
 *
 * NFKC is too aggressive for whole text fields (it would e.g. turn "½" into "1⁄2" or "²" into "2"), so:
 * - in the fields listed in {@see $nfkcFields} (default: ZIP code) the full NFKC normalization is applied,
 * - in all other fields (and for values without a known field) only fullwidth digits U+FF10–U+FF19 are converted.
 */
#[AsTaggedItem(priority: 400)]
final readonly class FullwidthNormalizer implements EncodingNormalizerInterface
{
    /**
     * @param list<AddressField> $nfkcFields
     */
    public function __construct(
        private array $nfkcFields = [AddressField::ZipCode],
    ) {
    }

    public function normalize(string $value, ?AddressField $field = null): string
    {
        if (null !== $field && \in_array($field, $this->nfkcFields, true)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_KC);

            return \is_string($normalized) ? $normalized : $value;
        }

        return (string) preg_replace_callback(
            '/[\x{FF10}-\x{FF19}]/u',
            static fn (array $m): string => (string) (mb_ord($m[0]) - 0xFF10),
            $value,
        );
    }
}
