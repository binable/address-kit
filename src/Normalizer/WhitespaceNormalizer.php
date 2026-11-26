<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Model\AddressField;
use Binable\AddressKit\Normalizer\Whitespace\BracketSpacingNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\CollapseWhitespaceNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\EdgePunctuationNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\HyphenSpacingNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\PunctuationSpacingNormalizer;
use Binable\AddressKit\Normalizer\Whitespace\WhitespaceNormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Cleans whitespace and punctuation of all address fields by running the string normalizers tagged
 * "address_kit.whitespace_normalizer" in priority order. Built-in steps:
 *
 *  500 {@see CollapseWhitespaceNormalizer} trim, collapse repeated whitespace
 *  400 {@see PunctuationSpacingNormalizer} no space before , . ; ) — one space after , ; (not in the city)
 *  300 {@see BracketSpacingNormalizer}     "( 2. OG )" → "(2. OG)"
 *  200 {@see HyphenSpacingNormalizer}      "Max -Planck" → "Max-Planck", "12 - 14" → "12-14", keeps "Kamenz - Kamjenc"
 *  100 {@see EdgePunctuationNormalizer}    punctuation at start / end, keeps abbreviation dots ("Str.")
 *
 * Runs right after the {@see EncodingNormalizer} and before the component normalizers (country, street,
 * house number, ZIP code, city), so these get cleaned values. Values that are empty after cleaning become null.
 */
#[AsTaggedItem(priority: 150)]
final readonly class WhitespaceNormalizer implements ComponentNormalizerInterface
{
    /**
     * @param iterable<WhitespaceNormalizerInterface> $normalizers
     */
    public function __construct(
        private iterable $normalizers,
    ) {
    }

    public function normalize(Address $address): Address
    {
        foreach (AddressField::cases() as $field) {
            $value = $address->get($field);
            if (null !== $value) {
                $value = $this->normalizeString($value, $field);
                $address = $address->with($field, '' === $value ? null : $value);
            }
        }

        return $address;
    }

    /**
     * Cleans a single value, e.g. for usage outside of an {@see Address}.
     */
    public function normalizeString(string $value, ?AddressField $field = null): string
    {
        foreach ($this->normalizers as $normalizer) {
            $value = $normalizer->normalize($value, $field);
        }

        return trim($value);
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
