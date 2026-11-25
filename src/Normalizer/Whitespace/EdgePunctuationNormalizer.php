<?php

declare(strict_types=1);

namespace Binable\AddressKit\Normalizer\Whitespace;

use Binable\AddressKit\Model\AddressField;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Removes punctuation at the start and the end of a value: ", Hauptstraße" → "Hauptstraße",
 * "Gütersloh," → "Gütersloh", "Berlin." → "Berlin".
 *
 * A trailing dot is kept when it ends an abbreviation: "Hauptstr.", "Marktpl.", "St.", "Frankfurt a.M.".
 * An abbreviation is a last word of at most two letters, a word containing a dot, a word ending with
 * one of the configured suffixes or one of the configured words. Brackets, quotes and apostrophes
 * ("'s-Hertogenbosch") are never removed.
 */
#[AsTaggedItem(priority: 100)]
final readonly class EdgePunctuationNormalizer implements WhitespaceNormalizerInterface
{
    private const EDGE = '[\s,.;:!?\-_\/\\\\|*·•]';
    private const TRAILING_WITHOUT_DOT = '/(?:[\s,;:!?\-_\/\\\\|*·•])+$/u';

    /**
     * @param list<string> $abbreviationSuffixes lower-case endings of abbreviated words ("Hauptstr.")
     * @param list<string> $abbreviations        lower-case abbreviated words ("Bhf.")
     */
    public function __construct(
        private array $abbreviationSuffixes = ['str', 'pl'],
        private array $abbreviations = ['app', 'apt', 'bhf', 'hbf', 'geb', 'gem', 'whg', 'etg', 'ehem', 'bzw', 'ca', 'ggf', 'usw'],
    ) {
    }

    public function normalize(string $value, ?AddressField $field = null): string
    {
        $value = (string) preg_replace('/^'.self::EDGE.'+/u', '', $value);

        do {
            $previous = $value;
            $value = (string) preg_replace(self::TRAILING_WITHOUT_DOT, '', $value);
            if (str_ends_with($value, '.') && !$this->isAbbreviation(substr($value, 0, -1))) {
                $value = substr($value, 0, -1);
            }
        } while ($value !== $previous);

        return $value;
    }

    /**
     * Checks the last word of the value (without its trailing dot).
     */
    private function isAbbreviation(string $value): bool
    {
        if (1 !== preg_match('/(?:^|[\s\-(])([\p{L}.]+)$/u', $value, $m)) {
            return false;
        }

        $word = mb_strtolower($m[1]);
        if (str_contains($word, '.') || mb_strlen($word) <= 2 || \in_array($word, $this->abbreviations, true)) {
            return true;
        }

        foreach ($this->abbreviationSuffixes as $suffix) {
            if (str_ends_with($word, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
