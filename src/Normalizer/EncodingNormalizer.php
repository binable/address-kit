<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer;

use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Model\AddressField;
use Binable\AddressKit\Normalizer\Encoding\ApostropheNormalizer;
use Binable\AddressKit\Normalizer\Encoding\ControlCharacterNormalizer;
use Binable\AddressKit\Normalizer\Encoding\DashNormalizer;
use Binable\AddressKit\Normalizer\Encoding\FullwidthNormalizer;
use Binable\AddressKit\Normalizer\Encoding\InvisibleCharacterNormalizer;
use Binable\AddressKit\Normalizer\Encoding\MojibakeNormalizer;
use Binable\AddressKit\Normalizer\Encoding\StringNormalizerInterface;
use Binable\AddressKit\Normalizer\Encoding\UnicodeNfcNormalizer;
use Binable\AddressKit\Normalizer\Encoding\Utf8Normalizer;
use Binable\AddressKit\Normalizer\Encoding\WhitespaceNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Cleans the encoding of all address fields by running the string normalizers tagged
 * "address_kit.encoding_normalizer" in priority order. Built-in steps:
 *
 *  900 {@see Utf8Normalizer}               invalid UTF-8 (Windows-1252 / ISO-8859-1) → UTF-8
 *  800 {@see MojibakeNormalizer}           "Ã¼" → "ü", "Ã©" → "é"
 *  700 {@see UnicodeNfcNormalizer}         "u" + U+0308 → "ü" (NFC)
 *  600 {@see InvisibleCharacterNormalizer} remove zero-width space, BOM, soft hyphen, ZWJ, direction marks, …
 *  500 {@see ControlCharacterNormalizer}   line breaks, tabs, control characters → space
 *  400 {@see FullwidthNormalizer}          "１２３" → "123" (NFKC only for the ZIP code)
 *  300 {@see WhitespaceNormalizer}         no-break / narrow / ideographic space … → " ", collapse, trim
 *  200 {@see DashNormalizer}               – — − ‐ → "-"
 *  100 {@see ApostropheNormalizer}         ’ ‘ ´ ` → "'"
 *
 * Runs before all other address normalizers, as they rely on clean input. Values that are empty
 * after cleaning become null.
 */
#[AsTaggedItem(priority: 200)]
final readonly class EncodingNormalizer implements ComponentNormalizerInterface
{
    /**
     * @param iterable<StringNormalizerInterface> $normalizers
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

        return $value;
    }

    /**
     * Instance with all built-in steps, for usage without the Symfony container.
     */
    public static function createDefault(): self
    {
        return new self([
            new Utf8Normalizer(),
            new MojibakeNormalizer(),
            new UnicodeNfcNormalizer(),
            new InvisibleCharacterNormalizer(),
            new ControlCharacterNormalizer(),
            new FullwidthNormalizer(),
            new WhitespaceNormalizer(),
            new DashNormalizer(),
            new ApostropheNormalizer(),
        ]);
    }
}
