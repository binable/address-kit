<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\AddressField;
use Binable\AddressKit\Normalizer\Whitespace\BracketSpacingNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\CollapseWhitespaceNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\EdgePunctuationNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\HyphenSpacingNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\PunctuationSpacingNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\WhitespaceNormalizerInterface;

/**
 * Cleans whitespace and punctuation of a single value by running the string normalizers tagged
 * "address_kit.whitespace_normalizer" in priority order. Built-in steps:
 *
 *  500 {@see CollapseWhitespaceNormalizer} trim, collapse repeated whitespace
 *  400 {@see PunctuationSpacingNormalizer} no space before , . ; ) — one space after , ;
 *  300 {@see BracketSpacingNormalizer}     "( 2. OG )" → "(2. OG)"
 *  200 {@see HyphenSpacingNormalizer}      "Max - Planck - Str." → "Max-Planck-Str.", "12 - 14" → "12-14"
 *  100 {@see EdgePunctuationNormalizer}    punctuation at start / end, keeps abbreviation dots ("Str.")
 *
 * Used by the component normalizers (street, house number, ZIP code, city, country) for their field.
 */
final readonly class WhitespaceNormalizer
{
    /**
     * @param iterable<WhitespaceNormalizerInterface> $normalizers
     */
    public function __construct(
        private iterable $normalizers,
    ) {
    }

    /**
     * Returns null for null and for values that are empty after cleaning.
     */
    public function normalize(?string $value, ?AddressField $field = null): ?string
    {
        if (null === $value) {
            return null;
        }

        foreach ($this->normalizers as $normalizer) {
            $value = $normalizer->normalize($value, $field);
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    /**
     * Instance with all built-in steps, for usage without the Symfony container.
     */
    public static function createDefault(): self
    {
        return new self([
            new CollapseWhitespaceNormalizer(),
            new PunctuationSpacingNormalizer(),
            new BracketSpacingNormalizer(),
            new HyphenSpacingNormalizer(),
            new EdgePunctuationNormalizer(),
        ]);
    }
}
